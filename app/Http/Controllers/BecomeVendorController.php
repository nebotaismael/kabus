<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NowPaymentsService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use App\Models\VendorPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Exceptions\NotReadableException;

class BecomeVendorController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $hasPgpVerified = false;
        $hasMoneroAddress = false;

        if ($user->pgpKey) {
            $hasPgpVerified = $user->pgpKey->verified;
        }

        // Check if the user has at least one Monero return address.
        $hasMoneroAddress = $user->returnAddresses()->exists();

        // Get the latest vendor payment
        $vendorPayment = VendorPayment::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        return view('become-vendor.index', compact('hasPgpVerified', 'hasMoneroAddress', 'vendorPayment'));
    }

    public function payment(Request $request, NowPaymentsService $nowPaymentsService)
    {
        $user = $request->user();

        // Determine verification statuses.
        $hasPgpVerified = false;
        $hasMoneroAddress = false;

        if ($user->pgpKey) {
            $hasPgpVerified = $user->pgpKey->verified;
        }

        $hasMoneroAddress = $user->returnAddresses()->exists();

        // Check if user has a processed application
        $existingPayment = VendorPayment::where('user_id', $user->id)
            ->whereNotNull('application_status')
            ->first();

        if ($existingPayment) {
            return redirect()->route('become.vendor')
                ->with('info', 'You already have a processed vendor application.');
        }

        // If the user is already a vendor, pass the verification variables.
        if ($user->isVendor()) {
            return view('become-vendor.payment', [
                'alreadyVendor'   => true,
                'hasPgpVerified'  => $hasPgpVerified,
                'hasMoneroAddress'=> $hasMoneroAddress
            ]);
        }

        try {
            $vendorPayment = $this->getCurrentVendorPayment($user, $nowPaymentsService);
            $qrCodeDataUri = $vendorPayment ? $this->generateQrCode(
                $vendorPayment->address, 
                null, 
                $vendorPayment->pay_currency ?? 'xmr',
                $nowPaymentsService
            ) : null;

            // Get supported currencies for display
            $supportedCurrencies = $nowPaymentsService->getSupportedCurrencies();
            $selectedCurrency = $vendorPayment->pay_currency ?? $nowPaymentsService->getDefaultCurrency();

            return view('become-vendor.payment', [
                'vendorPayment'       => $vendorPayment,
                'qrCodeDataUri'       => $qrCodeDataUri,
                'hasPgpVerified'      => $hasPgpVerified,
                'hasMoneroAddress'    => $hasMoneroAddress,
                'supportedCurrencies' => $supportedCurrencies,
                'selectedCurrency'    => $selectedCurrency,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in payment process: ' . $e->getMessage());
            return view('become-vendor.payment', [
                'error'               => 'An error occurred while setting up your payment. The payment service may be temporarily unavailable. Please try again in a few minutes.',
                'hasPgpVerified'      => $hasPgpVerified,
                'hasMoneroAddress'    => $hasMoneroAddress,
                'supportedCurrencies' => $nowPaymentsService->getSupportedCurrencies(),
                'selectedCurrency'    => $nowPaymentsService->getDefaultCurrency(),
            ]);
        }
    }

    private function getCurrentVendorPayment(User $user, NowPaymentsService $nowPaymentsService)
    {
        try {
            // Look for an existing non-expired vendor payment
            $vendorPayment = VendorPayment::where('user_id', $user->id)
                ->where('expires_at', '>', Carbon::now())
                ->orderBy('created_at', 'desc')
                ->first();

            if ($vendorPayment) {
                // Payment status is now updated via webhook, no need to check RPC
                return $vendorPayment;
            } else {
                return $this->createVendorPayment($user, $nowPaymentsService);
            }
        } catch (\Exception $e) {
            Log::error('Error getting current vendor payment: ' . $e->getMessage());
            throw $e;
        }
    }

    private function createVendorPayment(User $user, NowPaymentsService $nowPaymentsService, string $currency = 'xmr')
    {
        try {
            // Validate currency
            if (!$nowPaymentsService->isValidCurrency($currency)) {
                $currency = $nowPaymentsService->getDefaultCurrency();
            }
            
            // Get the required vendor payment amount in USD from config
            $requiredAmountUsd = config('marketplace.vendor_fee_usd', 250);
            
            // Pre-generate identifier for order_id before calling API
            // This avoids the issue of null order_id being rejected by NowPayments
            $identifier = \Str::random(30);
            
            // Create payment via NowPayments with USD pricing
            $paymentResult = $nowPaymentsService->createPayment(
                $requiredAmountUsd,
                'usd',  // Price in USD
                $currency,  // Pay in selected currency
                $identifier,  // Use pre-generated identifier as order_id
                'vendor_fee'
            );
            
            if (!$paymentResult) {
                throw new \Exception('Failed to create payment with NowPayments - payment service may be unavailable');
            }
            
            $vendorPayment = new VendorPayment([
                'identifier'    => $identifier,
                'address'       => $paymentResult['pay_address'],
                'np_payment_id' => $paymentResult['payment_id'],
                'pay_currency'  => $paymentResult['pay_currency'] ?? $currency,
                'user_id'       => $user->id,
                'expires_at'    => Carbon::now()->addMinutes((int) config('monero.address_expiration_time')),
            ]);
            $vendorPayment->save();

            Log::info("Created new vendor payment via NowPayments for user {$user->id} with currency {$currency}");
            return $vendorPayment;
        } catch (\Exception $e) {
            Log::error('Error creating NowPayments vendor payment: ' . $e->getMessage());
            throw new \Exception('Unable to create payment address. Please try again later.');
        }
    }

    public function showApplication()
    {
        // Check if user has a processed application
        $processedApplication = VendorPayment::where('user_id', auth()->id())
            ->whereNotNull('application_status')
            ->first();

        if ($processedApplication) {
            return redirect()->route('become.vendor')
                ->with('info', 'You already have a processed vendor application.');
        }

        $vendorPayment = VendorPayment::where('user_id', auth()->id())
            ->where('payment_completed', true)
            ->whereNull('application_status')
            ->first();

        if (!$vendorPayment) {
            return redirect()->route('become.vendor')
                ->with('error', 'You must complete the payment before submitting an application.');
        }

        return view('become-vendor.application', compact('vendorPayment'));
    }

    public function submitApplication(Request $request)
    {
        // Check if user has a processed application
        $processedApplication = VendorPayment::where('user_id', auth()->id())
            ->whereNotNull('application_status')
            ->first();

        if ($processedApplication) {
            return redirect()->route('become.vendor')
                ->with('info', 'You already have a processed vendor application.');
        }

        $vendorPayment = VendorPayment::where('user_id', auth()->id())
            ->where('payment_completed', true)
            ->whereNull('application_status')
            ->first();

        if (!$vendorPayment) {
            return redirect()->route('become.vendor')
                ->with('error', 'You must complete the payment before submitting an application.');
        }

        $request->validate([
            'application_text' => 'required|string|min:80|max:4000',
            'product_images' => [
                'required',
                'array',
                'min:1',
                'max:4'  // Maximum 4 images
            ],
            'product_images.*' => [
                'required',
                'file',
                'image',
                'max:800', // 800KB max size
                'mimes:jpeg,png,gif,webp'
            ]
        ]);

        try {
            $images = [];
            if ($request->hasFile('product_images')) {
                foreach ($request->file('product_images') as $image) {
                    try {
                        $images[] = $this->handleApplicationPictureUpload($image);
                    } catch (\Exception $e) {
                        // Clean up any images that were successfully uploaded
                        foreach ($images as $uploadedImage) {
                            Storage::disk('private')->delete('vendor_application_pictures/' . $uploadedImage);
                        }
                        Log::error('Failed to upload application image: ' . $e->getMessage());
                        return redirect()->back()
                            ->withInput()
                            ->with('error', 'Failed to upload images. Please try again.');
                    }
                }
            }

            try {
                $vendorPayment->update([
                    'application_text' => $request->application_text,
                    'application_images' => json_encode($images),
                    'application_status' => 'waiting',
                    'application_submitted_at' => now()
                ]);
            } catch (\Exception $e) {
                // Clean up uploaded images if the update fails
                foreach ($images as $image) {
                    Storage::disk('private')->delete('vendor_application_pictures/' . $image);
                }
                throw $e;
            }

            Log::info("Vendor application submitted for user {$vendorPayment->user_id}");

            return redirect()->route('become.vendor')
                ->with('success', 'Your application has been submitted successfully and is now under review.');

        } catch (\Exception $e) {
            Log::error('Error submitting vendor application: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while submitting your application. Please try again.');
        }
    }

    private function handleApplicationPictureUpload($file)
    {
        try {
            // Verify file type using finfo
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file->getPathname());

            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp'
            ];

            if (!in_array($mimeType, $allowedMimeTypes)) {
                throw new \Exception('Invalid file type. Allowed types are JPEG, PNG, GIF, and WebP.');
            }

            $extension = match($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg'
            };

            $filename = time() . '_' . \Str::uuid() . '.' . $extension;

            // Create a new ImageManager instance
            $manager = new ImageManager(new GdDriver());

            // Resize the image
            $image = $manager->read($file)
                ->resize(800, 800, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

            // Encode the image based on its MIME type
            $encodedImage = match($mimeType) {
                'image/png' => $image->encode(new PngEncoder()),
                'image/webp' => $image->encode(new WebpEncoder()),
                'image/gif' => $image->encode(new GifEncoder()),
                default => $image->encode(new JpegEncoder(80))
            };

            // Save the image to private storage in vendor_application_pictures directory
            if (!Storage::disk('private')->put('vendor_application_pictures/' . $filename, $encodedImage)) {
                throw new \Exception('Failed to save application picture to storage');
            }

            return $filename;
        } catch (NotReadableException $e) {
            Log::error('Image processing failed: ' . $e->getMessage());
            throw new \Exception('Failed to process uploaded image. Please try a different image.');
        } catch (\Exception $e) {
            Log::error('Application picture upload failed: ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    private function generateQrCode($address, $amount = null, $currency = 'xmr', ?NowPaymentsService $nowPaymentsService = null)
    {
        try {
            // Build payment URI based on currency
            if ($nowPaymentsService) {
                $data = $nowPaymentsService->buildPaymentUri($address, $amount, $currency);
            } else {
                // Fallback to plain address if service not available
                $data = $address;
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
     * Change the payment currency for vendor fee.
     */
    public function changeCurrency(Request $request, NowPaymentsService $nowPaymentsService)
    {
        $user = $request->user();
        
        // Check if user is already a vendor
        if ($user->isVendor()) {
            return redirect()->route('become.payment')
                ->with('info', 'You are already a vendor.');
        }

        // Check if user has a processed application
        $existingPayment = VendorPayment::where('user_id', $user->id)
            ->whereNotNull('application_status')
            ->first();

        if ($existingPayment) {
            return redirect()->route('become.vendor')
                ->with('info', 'You already have a processed vendor application.');
        }

        // Get the current unpaid vendor payment
        $vendorPayment = VendorPayment::where('user_id', $user->id)
            ->where('payment_completed', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$vendorPayment) {
            return redirect()->route('become.payment')
                ->with('error', 'No pending payment found.');
        }

        $currency = strtolower($request->input('currency', 'xmr'));
        
        // Validate currency
        if (!$nowPaymentsService->isValidCurrency($currency)) {
            Log::warning('Invalid currency selection attempted for vendor fee', ['currency' => $currency, 'user_id' => $user->id]);
            $currency = $nowPaymentsService->getDefaultCurrency();
        }

        try {
            // Get the required vendor payment amount in USD from config
            $requiredAmountUsd = config('marketplace.vendor_fee_usd', 250);
            
            // Create new payment with selected currency
            $paymentResult = $nowPaymentsService->createPayment(
                $requiredAmountUsd,
                'usd',
                $currency,
                $vendorPayment->identifier,
                'vendor_fee'
            );
            
            if ($paymentResult) {
                // Update vendor payment with new payment details
                $vendorPayment->address = $paymentResult['pay_address'];
                $vendorPayment->np_payment_id = $paymentResult['payment_id'];
                $vendorPayment->pay_currency = $paymentResult['pay_currency'] ?? $currency;
                $vendorPayment->expires_at = Carbon::now()->addMinutes((int) config('monero.address_expiration_time'));
                $vendorPayment->save();
                
                return redirect()->route('become.payment')
                    ->with('success', 'Payment currency changed to ' . strtoupper($currency) . '.');
            } else {
                return redirect()->route('become.payment')
                    ->with('error', 'Unable to change payment currency. Please try again.');
            }
        } catch (\Exception $e) {
            Log::error('Error changing vendor payment currency: ' . $e->getMessage());
            return redirect()->route('become.payment')
                ->with('error', 'An error occurred while changing the payment currency.');
        }
    }
}