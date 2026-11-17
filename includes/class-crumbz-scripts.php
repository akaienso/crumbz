<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Keeps third-party scripts from running until their category is allowed.
 * A blocked script is printed as type="text/plain" with a data-crumbz-category
 * attribute. frontend.js restores it once consent is given.
 */
class Crumbz_Scripts {

    public static function init(): void {
        add_filter('script_loader_tag', [__CLASS__, 'filter_tag'], 10, 3);
    }

    /** Category for a script URL, from the domain rules, or null when no rule matches. */
    public static function category_for_src(string $src): ?string {
        $src = strtolower($src);
        foreach ((array) Crumbz_Settings::get('script_rules') as $rule) {
            if (!empty($rule['match']) && strpos($src, $rule['match']) !== false) {
                return (string) $rule['category'];
            }
        }
        return null;
    }

    public static function filter_tag($tag, $handle, $src) {
        if (!Crumbz_Settings::get('enabled') || $handle === 'crumbz' || empty($src)) {
            return $tag;
        }
        $cat = self::category_for_src((string) $src);
        if ($cat === null) {
            return $tag;
        }
        // Always inert on the server. frontend.js enables the script after the visitor's choice,
        // so the page HTML never depends on a cookie and can be cached safely.
        return self::block_html((string) $tag, $cat);
    }

    /** Rewrites every <script> opening tag in $html so it stays inert until allowed. */
    public static function block_html(string $html, string $cat): string {
        $html = (string) preg_replace('/(<script\b[^>]*?)\s+type\s*=\s*(["\']?)[^"\'\s>]*\2/i', '$1', $html);
        return (string) preg_replace(
            '/<script\b/i',
            '<script type="text/plain" data-crumbz-category="' . esc_attr($cat) . '"',
            $html
        );
    }
}
