<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use App\Models\Cart;
use App\Services\NowPaymentsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\XmrPriceController;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;

class OrdersController extends Controller
{
    /**
     * Display a listing of the user's orders.
     */
    public function index()
    {
        $orders = Orders::getUserOrders(Auth::id());
        
        return view('orders.index', [
            'orders' => $orders
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show($uniqueUrl, NowPaymentsService $nowPaymentsService)
    {
        // Process any orders that need auto status changes
        Orders::processAllAutoStatusChanges();
        
        $order = Orders::findByUrl($uniqueUrl);
        
        if (!$order) {
            abort(404);
        }

        // Check if the user is either the buyer or the vendor
        if ($order->user_id !== Auth::id() && $order->vendor_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Determine if the current user is the buyer or vendor
        $isBuyer = $order->user_id === Auth::id();

        // For buyers with unpaid orders, check if a payment address exists
        // and handle payment processing
        $qrCode = null;
        if ($isBuyer && $order->status === Orders::STATUS_WAITING_PAYMENT) {
            // First check if the order has an expired payment
            if ($order->isExpired() && !empty($order->pay_address)) {
                // Handle expired payment (cancels the order)
                $order->handleExpiredPayment();
                
                // Refresh the order after cancellation
                $order->refresh();
                
                if ($order->status === Orders::STATUS_CANCELLED) {
                    return redirect()->route('orders.show', $order->unique_url)
                        ->with('info', 'This order has been automatically cancelled because the payment window has expired.');
                }
            }
            
            // Check if the order should be auto-cancelled (not sent within 96 hours)
            if ($order->shouldAutoCancelIfNotSent()) {
                $order->autoCancelIfNotSent();
                $order->refresh();
                
                if ($order->status === Orders::STATUS_CANCELLED) {
                    return redirect()->route('orders.show', $order->unique_url)
                        ->with('info', 'This order has been automatically cancelled because the vendor did not mark it as sent within 96 hours (4 days) after payment.');
                }
            }
            
            // Check if the order should be auto-completed (not marked completed within 192 hours after being sent)
            if ($order->shouldAutoCompleteIfNotConfirmed()) {
                $order->autoCompleteIfNotConfirmed();
                $order->refresh();
                
                if ($order->status === Orders::STATUS_COMPLETED) {
                    return redirect()->route('orders.show', $order->unique_url)
                        ->with('info', 'This order has been automatically marked as completed because it was not confirmed within 192 hours (8 days) after being marked as sent.');
                }
            }
            
            // Only create a NowPayments payment if none exists and the order isn't cancelled
            if (empty($order->np_payment_id) && $order->status === Orders::STATUS_WAITING_PAYMENT) {
                try {
                    // Get current XMR/USD rate
                    $xmrPriceController = new XmrPriceController();
                    $xmrRate = $xmrPriceController->getXmrPrice();
                    
                    if ($xmrRate === 'UNAVAILABLE') {
                        return redirect()->back()->with('error', 'Unable to get current cryptocurrency exchange rate. The payment service may be temporarily unavailable. Please try again in a few minutes.');
                    }
                    
                    // Calculate required XMR amount
                    $requiredXmrAmount = $order->calculateRequiredXmrAmount($xmrRate);
                    
                    // Update order with XMR details
                    $order->required_xmr_amount = $requiredXmrAmount;
                    $order->xmr_usd_rate = $xmrRate;
                    $order->save();
                    
                    // Create payment via NowPayments
                    $paymentResult = $nowPaymentsService->createPayment(
                        $order->total,
                        'usd',
                        'xmr',
                        $order->id,
                        'order'
                    );
                    
                    if ($paymentResult) {
                        // Store NowPayments details in order
                        $order->np_payment_id = $paymentResult['payment_id'];
                        $order->pay_address = $paymentResult['pay_address'];
                        $order->pay_amount = $paymentResult['pay_amount'];
                        $order->pay_currency = $paymentResult['pay_currency'] ?? 'xmr';
                        $order->expires_at = now()->addMinutes((int) config('monero.address_expiration_time', 1440));
                        $order->save();
                    } else {
                        return redirect()->back()->with('error', 'Unable to create payment address. The payment service may be temporarily unavailable. Please try again in a few minutes.');
                    }
                } catch (\Exception $e) {
                    Log::error('Error setting up payment: ' . $e->getMessage());
                    return redirect()->back()->with('error', 'An error occurred while setting up your payment. Please try again later. If the problem persists, please contact support.');
                }
            }
            
            // Refresh order data after potential updates
            $order->refresh();
            
            // Generate QR code if payment is not completed
            if (!$order->is_paid && $order->pay_address) {
                try {
                    $qrCode = $this->generateQrCode($order->pay_address, $order->pay_amount);
                } catch (\Exception $e) {
                    Log::error('Error generating QR code: ' . $e->getMessage());
                }
            }
        }

        // If the user is the buyer and the order is completed, prepare existing reviews for each order item.
        if ($isBuyer && $order->status === 'completed') {
            foreach ($order->items as $item) {
                $item->existingReview = \App\Models\ProductReviews::where('user_id', Auth::id())
                    ->where('order_item_id', $item->id)
                    ->first();
            }
        }
        
        // Get dispute if it exists
        $dispute = $order->dispute;
        
        // Calculate total number of items, accounting for bulk options
        $totalItems = 0;
        foreach($order->items as $item) {
            if($item->bulk_option && isset($item->bulk_option['amount'])) {
                $totalItems += $item->quantity * $item->bulk_option['amount'];
            } else {
                $totalItems += $item->quantity;
            }
        }
        
        return view('orders.show', [
            'order' => $order,
            'isBuyer' => $isBuyer,
            'dispute' => $dispute,
            'qrCode' => $qrCode,
            'totalItems' => $totalItems
        ]);
    }

    /**
     * Create a new order from the cart items.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $cartItems = Cart::where('user_id', $user->id)->with(['product', 'product.user'])->get();
            
            if ($cartItems->isEmpty()) {
                return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
            }
            
            // Get vendor ID from cart items
            $vendorId = $cartItems->first()->product->user_id;
            
            // Check if user can create a new order with this vendor (spam prevention)
            [$canCreate, $reason] = Orders::canCreateNewOrder($user->id, $vendorId);
            
            if (!$canCreate) {
                return redirect()->route('cart.checkout')->with('error', $reason);
            }
            
            // Calculate order totals
            $subtotal = Cart::getCartTotal($user);
            $commissionPercentage = config('marketplace.commission_percentage');
            $commission = ($subtotal * $commissionPercentage) / 100;
            $total = $subtotal + $commission;
            
            // Create the order
            $order = Orders::createFromCart($user, $cartItems, $subtotal, $commission, $total);
            
            // Clear the cart
            Cart::where('user_id', $user->id)->delete();
            
            return redirect()->route('orders.show', $order->unique_url)
                ->with('success', 'Order created successfully. Please complete the payment.');
                
        } catch (\Exception $e) {
            Log::error('Failed to create order: ' . $e->getMessage());
            return redirect()->route('cart.checkout')
                ->with('error', 'Failed to create order. Please try again.');
        }
    }

    /**
     * Generate a QR code for the given address with optional amount.
     * Creates a Monero URI format: monero:address?tx_amount=amount
     */
    private function generateQrCode($address, $amount = null)
    {
        try {
            // Build Monero URI with amount if provided
            $data = $address;
            if ($amount !== null && $amount > 0) {
                $data = "monero:{$address}?tx_amount={$amount}";
            }
            
            $result = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($data)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(300)
                ->margin(10)
                ->build();
            
            return $result->getDataUri();
        } catch (\Exception $e) {
            Log::error('Error generating QR code: ' . $e->getMessage());
            return null;
        }
    }


    /**
     * Mark the order as sent.
     */
    public function markAsSent($uniqueUrl)
    {
        $order = Orders::findByUrl($uniqueUrl);
        
        if (!$order) {
            abort(404);
        }

        // Verify ownership - only the vendor can mark as sent
        if ($order->vendor_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($order->markAsSent()) {
            return redirect()->route('vendor.sales.show', $order->unique_url)
                ->with('success', 'Product marked as sent. The buyer has been notified.');
        }

        return redirect()->route('vendor.sales.show', $order->unique_url)
            ->with('error', 'Unable to mark as sent at this time.');
    }

    /**
     * Mark the order as completed.
     */
    public function markAsCompleted($uniqueUrl)
    {
        $order = Orders::findByUrl($uniqueUrl);
        
        if (!$order) {
            abort(404);
        }

        // Verify ownership - only the buyer can mark as completed
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($order->markAsCompleted()) {
            return redirect()->route('orders.show', $order->unique_url)
                ->with('success', 'Order marked as completed and payment has been sent to the vendor. Thank you for your purchase.');
        }

        return redirect()->route('orders.show', $order->unique_url)
            ->with('error', 'Unable to mark as completed at this time.');
    }

    /**
     * Mark the order as cancelled.
     */
    public function markAsCancelled($uniqueUrl)
    {
        $order = Orders::findByUrl($uniqueUrl);
        
        if (!$order) {
            abort(404);
        }

        // Verify ownership - both buyer and vendor can cancel
        if ($order->user_id !== Auth::id() && $order->vendor_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Check if order is in a status that can be cancelled
        if ($order->status === Orders::STATUS_COMPLETED) {
            return redirect()->back()->with('error', 'Completed orders cannot be cancelled.');
        }

        if ($order->markAsCancelled()) {
            // Determine the redirect route based on whether the user is buyer or vendor
            $isBuyer = $order->user_id === Auth::id();
            $route = $isBuyer ? 'orders.show' : 'vendor.sales.show';
            
            return redirect()->route($route, $order->unique_url)
                ->with('success', 'Order has been cancelled successfully.');
        }

        return redirect()->back()->with('error', 'Unable to cancel the order at this time.');
    }

    /**
     * Submit a review for a product in a completed order.
     */
    public function submitReview(Request $request, $uniqueUrl, $orderItemId)
    {
        // Find the order
        $order = Orders::findByUrl($uniqueUrl);
        
        if (!$order) {
            abort(404);
        }

        // Verify ownership - only the buyer can submit reviews
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Verify order is completed
        if ($order->status !== Orders::STATUS_COMPLETED) {
            return redirect()->route('orders.show', $order->unique_url)
                ->with('error', 'You can only review products from completed orders.');
        }

        // Find the order item
        $orderItem = $order->items()->where('id', $orderItemId)->first();
        
        if (!$orderItem) {
            abort(404);
        }

        // Check if a review already exists for this item
        $existingReview = \App\Models\ProductReviews::where('user_id', Auth::id())
            ->where('order_item_id', $orderItem->id)
            ->first();
            
        if ($existingReview) {
            return redirect()->route('orders.show', $order->unique_url)
                ->with('error', 'You have already reviewed this product.');
        }

        // Validate the request
        $validated = $request->validate([
            'review_text' => 'required|string|min:8|max:800',
            'sentiment' => 'required|in:positive,mixed,negative',
        ]);

        // Create the review
        \App\Models\ProductReviews::create([
            'product_id' => $orderItem->product_id,
            'user_id' => Auth::id(),
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'review_text' => $validated['review_text'],
            'sentiment' => $validated['sentiment'],
        ]);

        return redirect()->route('orders.show', $order->unique_url)
            ->with('success', 'Your review has been submitted successfully.');
    }
}

