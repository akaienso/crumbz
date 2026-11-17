<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Finds active plugins that are known to set cookies, and suggests a category for each.
 * Suggestions feed the settings screen, the wizard, and the cookie policy draft.
 */
class Crumbz_Detect {

    public static function known(): array {
        return [
            'woocommerce/woocommerce.php'                          => ['label' => 'WooCommerce', 'category' => 'necessary'],
            'wordfence/wordfence.php'                              => ['label' => 'Wordfence', 'category' => 'necessary'],
            'polylang/polylang.php'                                => ['label' => 'Polylang', 'category' => 'preferences'],
            'sitepress-multilingual-cms/sitepress.php'             => ['label' => 'WPML', 'category' => 'preferences'],
            'jetpack/jetpack.php'                                  => ['label' => 'Jetpack', 'category' => 'statistics'],
            'google-site-kit/google-site-kit.php'                  => ['label' => 'Site Kit by Google', 'category' => 'statistics'],
            'google-analytics-for-wordpress/googleanalytics.php'   => ['label' => 'MonsterInsights (Google Analytics)', 'category' => 'statistics'],
            'wp-piwik/wp-piwik.php'                                => ['label' => 'WP-Piwik (Matomo)', 'category' => 'statistics'],
            'facebook-for-woocommerce/facebook-for-woocommerce.php' => ['label' => 'Meta for WooCommerce', 'category' => 'marketing'],
            'pixelyoursite/pixelyoursite.php'                      => ['label' => 'PixelYourSite', 'category' => 'marketing'],
            'mailchimp-for-wp/mailchimp-for-wp.php'                => ['label' => 'Mailchimp for WordPress', 'category' => 'marketing'],
        ];
    }

    /** Active plugin files, including network-activated ones on multisite. */
    public static function active_files(): array {
        $active = (array) get_option('active_plugins', []);
        if (is_multisite()) {
            $active = array_merge($active, array_keys((array) get_site_option('active_sitewide_plugins', [])));
        }
        return array_values(array_unique($active));
    }

    /**
     * Rows for each known active plugin.
     * 'category' is the admin's override when set, otherwise the suggestion. '' means ignore.
     */
    public static function scan(): array {
        $overrides = (array) Crumbz_Settings::get('detected_overrides');
        $rows = [];
        foreach (self::active_files() as $file) {
            $known = self::known()[$file] ?? null;
            if ($known === null) {
                continue;
            }
            $rows[] = [
                'file'      => $file,
                'label'     => $known['label'],
                'suggested' => $known['category'],
                'category'  => array_key_exists($file, $overrides) ? (string) $overrides[$file] : $known['category'],
            ];
        }
        return $rows;
    }
}
