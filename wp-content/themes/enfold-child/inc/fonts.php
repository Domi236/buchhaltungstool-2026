<?php

// Register new icon as a theme icon
function avia_add_custom_icon($icons) {
    $icons['tiktok'] = array( 'font' =>'partycrowd', 'icon' => 'ue800');
    return $icons;
}
add_filter('avf_default_icons','avia_add_custom_icon', 10, 1);

// Add new icon as an option for social icons
function avia_add_custom_social_icon($icons) {
    $icons['TikTok'] = 'tiktok';
    return $icons;
}
add_filter('avf_social_icons_options','avia_add_custom_social_icon', 10, 1);