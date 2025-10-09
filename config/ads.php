<?php
/**
 * Advertisement Configuration
 * 
 * Easy ad revenue setup for ConnectHub
 */

// Google AdSense Configuration
define('GOOGLE_ADSENSE_CLIENT', 'ca-pub-XXXXXXXXXX'); // Replace with your AdSense publisher ID
define('GOOGLE_ADSENSE_ENABLED', false); // Set to true when you have AdSense approval

// Ad Placements
define('ADS_DASHBOARD', true);
define('ADS_GROUPS_PAGE', true);
define('ADS_EVENTS_PAGE', true);
define('ADS_SIDEBAR', true);

// Alternative Ad Networks (if AdSense not approved yet)
define('MEDIA_NET_ENABLED', false);
define('PROPELLER_ADS_ENABLED', false);

// Affiliate Programs
define('AMAZON_AFFILIATE_TAG', 'your-tag-20'); // Amazon Associates
define('AFFILIATE_ENABLED', true);

/**
 * Get ad code based on placement
 */
function getAdCode($placement = 'default') {
    if (!GOOGLE_ADSENSE_ENABLED) {
        return getPlaceholderAd();
    }
    
    $client = GOOGLE_ADSENSE_CLIENT;
    
    // Different ad slots for different placements
    $adSlots = [
        'dashboard' => 'XXXXXXXXXX',
        'sidebar' => 'YYYYYYYYYY', 
        'header' => 'ZZZZZZZZZZ',
        'footer' => 'AAAAAAAAAA'
    ];
    
    $slot = $adSlots[$placement] ?? $adSlots['dashboard'];
    
    return "
    <script async src=\"https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$client}\" crossorigin=\"anonymous\"></script>
    <ins class=\"adsbygoogle\"
         style=\"display:block\"
         data-ad-client=\"{$client}\"
         data-ad-slot=\"{$slot}\"
         data-ad-format=\"auto\"
         data-full-width-responsive=\"true\"></ins>
    <script>
         (adsbygoogle = window.adsbygoogle || []).push({});
    </script>";
}

/**
 * Placeholder ad for testing/development
 */
function getPlaceholderAd() {
    return '
    <div class="text-center text-muted" style="min-height: 250px; display: flex; align-items: center; justify-content: center; flex-direction: column; border: 2px dashed #ccc; border-radius: 8px;">
        <i class="fas fa-ad fa-2x mb-2"></i>
        <div class="small">Advertisement Space</div>
        <div class="small">Ready for AdSense</div>
    </div>';
}

/**
 * Simple affiliate link generator
 */
function affiliateLink($url, $text = 'Check it out') {
    if (!AFFILIATE_ENABLED) {
        return "<a href=\"{$url}\" target=\"_blank\">{$text}</a>";
    }
    
    // Add affiliate tracking
    $affiliateUrl = $url . (strpos($url, '?') ? '&' : '?') . 'tag=' . AMAZON_AFFILIATE_TAG;
    return "<a href=\"{$affiliateUrl}\" target=\"_blank\" rel=\"nofollow\">{$text}</a>";
}
?>