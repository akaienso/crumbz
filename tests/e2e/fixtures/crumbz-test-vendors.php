<?php
// Test-only stand-in: enqueue two third-party scripts so Crumbz blocking can be checked.
add_action('wp_enqueue_scripts', static function () {
    wp_enqueue_script('test-gtag', 'https://www.googletagmanager.com/gtag/js?id=G-TESTONLY', [], null, true);
    wp_enqueue_script('test-fbpixel', 'https://connect.facebook.net/en_US/fbevents.js', [], null, true);
});
