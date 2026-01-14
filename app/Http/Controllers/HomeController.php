<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\XmrPriceController;
use App\Models\User;
use App\Models\Orders;
use App\Models\Product;
use App\Models\ProductReviews;
use App\Models\Category;

class HomeController extends Controller
{
    /**
     * Calculate vendor level based on completed deals.
     * 
     * Level thresholds:
     * - Level 1: 0-9 deals
     * - Level 2: 10-24 deals
     * - Level 3: 25-49 deals
     * - Level 4: 50-99 deals
     * - Level 5: 100-199 deals
     * - Level 6+: Each additional 100 deals up to level 20
     * 
     * @param int $completedDeals Number of completed orders
     * @return int Vendor level (1-20)
     */
    public static function calculateVendorLevel(int $completedDeals): int
    {
        if ($completedDeals < 10) return 1;
        if ($completedDeals < 25) return 2;
        if ($completedDeals < 50) return 3;
        if ($completedDeals < 100) return 4;
        if ($completedDeals < 200) return 5;
        
        // Level 6+: each 100 deals adds 1 level, max 20
        $additionalLevels = floor(($completedDeals - 200) / 100);
        return min(20, 6 + $additionalLevels);
    }

    /**
     * Calculate vendor rating from all product reviews.
     * 
     * Since reviews use sentiment (positive/mixed/negative) instead of numeric ratings,
     * we convert: positive=5, mixed=3, negative=1
     * 
     * @param string $vendorId UUID of the vendor
     * @return float|null Average rating (1-5) or null if no reviews
     */
    public static function calculateVendorRating(string $vendorId): ?float
    {
        $productIds = Product::where('user_id', $vendorId)->pluck('id');
        
        if ($productIds->isEmpty()) {
            return null;
        }
        
        $reviews = ProductReviews::whereIn('product_id', $productIds)->get();
        
        if ($reviews->isEmpty()) {
            return null;
        }
        
        // Convert sentiment to numeric rating
        $totalRating = 0;
        foreach ($reviews as $review) {
            $totalRating += match($review->sentiment) {
                ProductReviews::SENTIMENT_POSITIVE => 5,
                ProductReviews::SENTIMENT_MIXED => 3,
                ProductReviews::SENTIMENT_NEGATIVE => 1,
                default => 3,
            };
        }
        
        return round($totalRating / $reviews->count(), 2);
    }

    /**
     * Get top vendors sorted by level and deals count.
     * 
     * @return \Illuminate\Support\Collection
     */
    private function getTopVendors(): \Illuminate\Support\Collection
    {
        // Get vendors with vendor role, excluding those in vacation mode
        $vendors = User::whereHas('roles', fn($q) => $q->where('name', 'vendor'))
            ->whereDoesntHave('vendorProfile', fn($q) => $q->where('vacation_mode', true))
            ->with('profile')
            ->get();
        
        // Calculate stats for each vendor
        $vendors = $vendors->map(function ($vendor) {
            // Count completed orders where this user is the vendor
            $vendor->deals_count = Orders::where('vendor_id', $vendor->id)
                ->where('status', Orders::STATUS_COMPLETED)
                ->count();
            
            $vendor->level = self::calculateVendorLevel($vendor->deals_count);
            $vendor->rating = self::calculateVendorRating($vendor->id);
            
            return $vendor;
        });
        
        // Sort by level descending, then by deals_count descending
        return $vendors->sortByDesc('deals_count')
            ->sortByDesc('level')
            ->take(5)
            ->values();
    }

    /**
     * Get recent completed purchases.
     * 
     * @return \Illuminate\Support\Collection
     */
    private function getRecentPurchases(): \Illuminate\Support\Collection
    {
        return Orders::where('status', Orders::STATUS_COMPLETED)
            ->with(['items.product'])
            ->orderBy('completed_at', 'desc')
            ->take(8)
            ->get()
            ->flatMap(fn($order) => $order->items->map(fn($item) => [
                'product_name' => $item->product_name,
                'price' => $item->price,
                'completed_at' => $order->completed_at,
                'product' => $item->product,
                'product_picture_url' => $item->product?->product_picture_url ?? asset('images/default-product-picture.png'),
                'product_slug' => $item->product?->slug,
            ]))
            ->take(8);
    }
    /**
     * Show the home page.
     *
     * @return \Illuminate\View\View
     */
    public function index(XmrPriceController $xmrPriceController)
    {
        // Get active popup (always show if available)
        $popup = \App\Models\Popup::getActive();
        
        // Get active advertisements
        $advertisements = \App\Models\Advertisement::getActiveAdvertisements();
        
        // Get current XMR price for conversion
        $xmrPrice = $xmrPriceController->getXmrPrice();
        
        // Organize advertisements by slot, skipping ads with deleted products or vendors in vacation mode
        $adSlots = [];
        foreach ($advertisements as $ad) {
            // Skip advertisements where product is soft-deleted
            if (!$ad->product || $ad->product->trashed()) {
                continue;
            }
            
            // Skip advertisements where vendor is in vacation mode
            if ($ad->product->user && $ad->product->user->vendorProfile && $ad->product->user->vendorProfile->vacation_mode) {
                continue;
            }
            
            // Get the formatted measurement unit
            $measurementUnits = \App\Models\Product::getMeasurementUnits();
            $formattedMeasurementUnit = $measurementUnits[$ad->product->measurement_unit] ?? $ad->product->measurement_unit;
            
            // Format product price in XMR
            $productXmrPrice = (is_numeric($xmrPrice) && $xmrPrice > 0) 
                ? $ad->product->price / $xmrPrice 
                : null;
            
            // Get formatted options with XMR price
            $formattedBulkOptions = $ad->product->getFormattedBulkOptions($xmrPrice);
            $formattedDeliveryOptions = $ad->product->getFormattedDeliveryOptions($xmrPrice);
            
            $adSlots[$ad->slot_number] = [
                'product' => $ad->product,
                'vendor' => $ad->product->user,
                'ends_at' => $ad->ends_at,
                'measurement_unit' => $formattedMeasurementUnit,
                'xmr_price' => $productXmrPrice,
                'bulk_options' => $formattedBulkOptions,
                'delivery_options' => $formattedDeliveryOptions
            ];
        }
        
        // Get featured products, excluding soft-deleted and vacation mode vendors
        $featuredProducts = \App\Models\FeaturedProduct::getAllFeaturedProducts();
        
        // Format featured products similar to advertisements
        $formattedFeaturedProducts = [];
        foreach ($featuredProducts as $featured) {
            // Skip featured products where product is soft-deleted
            if (!$featured->product || $featured->product->trashed()) {
                continue;
            }
            
            // Skip featured products where vendor is in vacation mode
            if ($featured->product->user && $featured->product->user->vendorProfile && $featured->product->user->vendorProfile->vacation_mode) {
                continue;
            }
            
            // Get the formatted measurement unit
            $measurementUnits = \App\Models\Product::getMeasurementUnits();
            $formattedMeasurementUnit = $measurementUnits[$featured->product->measurement_unit] ?? $featured->product->measurement_unit;
            
            // Format product price in XMR
            $productXmrPrice = (is_numeric($xmrPrice) && $xmrPrice > 0) 
                ? $featured->product->price / $xmrPrice 
                : null;
            
            // Get formatted options with XMR price
            $formattedBulkOptions = $featured->product->getFormattedBulkOptions($xmrPrice);
            $formattedDeliveryOptions = $featured->product->getFormattedDeliveryOptions($xmrPrice);
            
            $formattedFeaturedProducts[] = [
                'product' => $featured->product,
                'vendor' => $featured->product->user,
                'measurement_unit' => $formattedMeasurementUnit,
                'xmr_price' => $productXmrPrice,
                'bulk_options' => $formattedBulkOptions,
                'delivery_options' => $formattedDeliveryOptions
            ];
        }
        
        // Get top vendors with stats
        $topVendors = $this->getTopVendors();
        
        // Get recent purchases
        $recentPurchases = $this->getRecentPurchases();
        
        // Get main categories for search bar dropdown
        $categories = Category::mainCategories();
        
        return view('home', [
            'username' => Auth::user()->username,
            'popup' => $popup,
            'adSlots' => $adSlots,
            'featuredProducts' => $formattedFeaturedProducts,
            'topVendors' => $topVendors,
            'recentPurchases' => $recentPurchases,
            'categories' => $categories,
        ]);
    }
}
