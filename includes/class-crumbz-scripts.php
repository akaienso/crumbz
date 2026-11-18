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
        // The original type (for example type="module") is kept in data-crumbz-type, so frontend.js can restore it.
        return (string) preg_replace_callback('/<script\b[^>]*>/i', static function ($m) use ($cat) {
            $tag  = $m[0];
            $orig = '';
            if (preg_match('/\stype\s*=\s*(["\']?)([^"\'\s>]*)\1/i', $tag, $t)) {
                $orig = $t[2];
                $tag  = (string) preg_replace('/\stype\s*=\s*(["\']?)[^"\'\s>]*\1/i', '', $tag, 1);
            }
            $extra = 'type="text/plain" data-crumbz-category="' . esc_attr($cat) . '"';
            if ($orig !== '') {
                $extra .= ' data-crumbz-type="' . esc_attr($orig) . '"';
            }
            return (string) preg_replace('/<script\b/i', '<script ' . $extra, $tag, 1);
        }, $html);
    }
}
