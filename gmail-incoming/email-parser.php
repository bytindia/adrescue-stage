<?php
/**
 * Parse email body to extract ad creative fields: Headline, Primary text, Description.
 * Detects ad type: carousel, static, video.
 */

function parse_email_ad_content($bodyText) {
    $body = str_replace(["\r\n", "\r"], "\n", $bodyText);
    $result = [
        'type'       => 'static',
        'headline'   => '',
        'primary'    => '',
        'description'=> '',
        'has_content'=> false,
    ];

    $patterns = [
        'headline'    => '/Headline:\s*(.+?)(?=\n\s*(?:Primary|Description)|$)/is',
        'primary'     => '/Primary\s*text:\s*(.+?)(?=\n\s*(?:Headline|Description)|Description:|$)/is',
        'description' => '/Description:\s*(.+?)(?=\n\s*(?:Headline|Primary|Please note|Best)|Please note|Best,|$)/is',
    ];

    foreach ($patterns as $key => $pat) {
        if (preg_match($pat, $body, $m)) {
            $result[$key] = trim($m[1]);
        }
    }

    /* Detect ad type from email content */
    $bodyLower = strtolower($body);
    if (preg_match('/carousel|reach campaigns|keep the order of the images/i', $body)) {
        $result['type'] = 'carousel';
    } elseif (preg_match('/video|mp4|\.mov|attachment.*video/i', $body)) {
        $result['type'] = 'video';
    } else {
        $result['type'] = 'static';
    }

    $result['has_content'] = !empty($result['headline']) || !empty($result['primary']) || !empty($result['description']);

    return $result;
}
