<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stores and validates Crumbz settings.
 *
 * Consent-relevant changes (document versions, active documents, jurisdictions)
 * bump the policy revision, so every visitor is asked again.
 */
class Crumbz_Settings {

    const OPTION      = 'crumbz_settings';
    const REVISION    = 'crumbz_policy_revision';
    const FINGERPRINT = 'crumbz_policy_fingerprint';
    const COOKIE      = 'crumbz_consent';

    const CATEGORIES = ['preferences', 'statistics', 'marketing'];

    public static function categories(): array {
        return array_merge(['necessary'], self::CATEGORIES);
    }

    public static function defaults(): array {
        return [
            'enabled'              => 1,
            'auto_update'          => 1,
            'banner_position'      => 'bottom',
            'banner_shape'         => 'card',
            'button_position'      => 'bottom-left',
            'theme'                => 'auto',
            'style_mode'           => 'plugin',
            'colors'               => ['primary' => '#ffc53d', 'secondary' => '#15171c', 'tertiary' => '#15171c'],
            'fonts'                => ['primary' => 'sans', 'secondary' => 'sans'],
            'custom_fonts'         => [],
            'theme_classes'        => ['primary' => '', 'secondary' => '', 'tertiary' => ''],
            'icons'                => ['accept' => 'check', 'reject' => 'x', 'customize' => 'sliders-horizontal', 'settings' => 'cookie'],
            'jurisdictions'        => [],
            'documents'            => self::default_documents(),
            'cookie_lifetime_days' => 180,
            'script_rules'         => self::default_script_rules(),
            'snippets'             => ['preferences' => '', 'statistics' => '', 'marketing' => ''],
            'detected_overrides'   => [],
            'text'                 => self::default_text(),
        ];
    }

    /** Bundled typefaces. Files are in assets/fonts and load from the plugin, never from another server. */
    public static function bundled_fonts(): array {
        return [
            'sans'    => ['label' => __('Sans serif (Inter)', 'crumbz'), 'family' => '"Crumbz Sans", system-ui, sans-serif'],
            'serif'   => ['label' => __('Serif (Source Serif 4)', 'crumbz'), 'family' => '"Crumbz Serif", Georgia, serif'],
            'display' => ['label' => __('Display serif (Playfair Display)', 'crumbz'), 'family' => '"Crumbz Display", Georgia, serif'],
            'mono'    => ['label' => __('Monospace (JetBrains Mono)', 'crumbz'), 'family' => '"Crumbz Mono", ui-monospace, monospace'],
        ];
    }

    /** Lucide icons (ISC). Names are the only values the icon settings accept. */
    public static function icon_choices(): array {
        return [
            'check'             => __('Check', 'crumbz'),
            'circle-check'      => __('Check in circle', 'crumbz'),
            'x'                 => __('Close', 'crumbz'),
            'circle-x'          => __('Close in circle', 'crumbz'),
            'ban'               => __('Ban', 'crumbz'),
            'sliders-horizontal' => __('Sliders', 'crumbz'),
            'settings'          => __('Gear', 'crumbz'),
            'cookie'            => __('Cookie', 'crumbz'),
            'shield-check'      => __('Shield', 'crumbz'),
            'lock'              => __('Lock', 'crumbz'),
            'none'              => __('No icon', 'crumbz'),
        ];
    }

    /** Uploaded fonts, keyed by their slug. */
    public static function custom_font_list(array $s): array {
        $out = [];
        foreach ((array) ($s['custom_fonts'] ?? []) as $font) {
            if (is_array($font) && !empty($font['slug'])) {
                $out[(string) $font['slug']] = $font;
            }
        }
        return $out;
    }

    /** Every typeface id the settings may point at: bundled ids plus uploaded fonts as custom:slug. */
    public static function font_ids(array $s): array {
        $ids = array_keys(self::bundled_fonts());
        foreach (array_keys(self::custom_font_list($s)) as $slug) {
            $ids[] = 'custom:' . $slug;
        }
        return $ids;
    }

    /** WCAG contrast ratio between two hex colors. */
    public static function contrast(string $a, string $b): float {
        $la = self::luminance($a);
        $lb = self::luminance($b);
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    public static function luminance(string $hex): float {
        $hex = ltrim($hex, '#');
        $rgb = [];
        foreach ([0, 2, 4] as $i) {
            $c = hexdec(substr($hex, $i, 2)) / 255;
            $rgb[] = $c <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
        }
        return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
    }

    /** Black or white text, whichever reads better on the given fill. */
    public static function readable_on(string $fill): string {
        return self::contrast($fill, '#ffffff') >= self::contrast($fill, '#15171c') ? '#ffffff' : '#15171c';
    }

    public static function default_documents(): array {
        return [
            'cookie-policy'  => ['label' => __('Cookie policy', 'crumbz'), 'page_id' => 0, 'url' => '', 'version' => 1],
            'privacy-policy' => ['label' => __('Privacy policy', 'crumbz'), 'page_id' => 0, 'url' => '', 'version' => 1],
            'terms'          => ['label' => __('Terms and conditions', 'crumbz'), 'page_id' => 0, 'url' => '', 'version' => 1],
            'custom-1'       => ['label' => __('Custom document 1', 'crumbz'), 'page_id' => 0, 'url' => '', 'version' => 1],
            'custom-2'       => ['label' => __('Custom document 2', 'crumbz'), 'page_id' => 0, 'url' => '', 'version' => 1],
            'custom-3'       => ['label' => __('Custom document 3', 'crumbz'), 'page_id' => 0, 'url' => '', 'version' => 1],
        ];
    }

    public static function default_script_rules(): array {
        return [
            ['match' => 'googletagmanager.com', 'category' => 'statistics'],
            ['match' => 'google-analytics.com', 'category' => 'statistics'],
            ['match' => 'static.hotjar.com', 'category' => 'statistics'],
            ['match' => 'connect.facebook.net', 'category' => 'marketing'],
            ['match' => 'analytics.tiktok.com', 'category' => 'marketing'],
            ['match' => 'snap.licdn.com', 'category' => 'marketing'],
            ['match' => 'platform.twitter.com', 'category' => 'marketing'],
        ];
    }

    public static function default_text(): array {
        return [
            'bannerTitle'       => __('Your choices about cookies', 'crumbz'),
            'bannerMessage'     => __('We use cookies and similar tools. Accept, reject, or customize by category. Optional tools do not run until you choose.', 'crumbz'),
            'accept'            => __('Accept all', 'crumbz'),
            'reject'            => __('Reject all', 'crumbz'),
            'customize'         => __('Customize', 'crumbz'),
            'save'              => __('Save choices', 'crumbz'),
            'hideIcon'          => __('Hide this icon', 'crumbz'),
            'doNotSell'         => __('Do Not Sell or Share My Personal Information', 'crumbz'),
            'iconLabel'         => __('Cookie settings', 'crumbz'),
            'settingsTitle'     => __('Cookie settings', 'crumbz'),
            'settingsIntro'     => __('Review the documents you have accepted and change your choices at any time.', 'crumbz'),
            'documentsHeading'  => __('Documents', 'crumbz'),
            'categoriesHeading' => __('Cookie categories', 'crumbz'),
            'docAccept'         => __('I have read and accept the %s', 'crumbz'),
            'read'              => __('Read', 'crumbz'),
            'necessary'         => __('Necessary', 'crumbz'),
            'necessaryDesc'     => __('Needed for the site to work and to remember this choice. Always on.', 'crumbz'),
            'preferences'       => __('Preferences', 'crumbz'),
            'preferencesDesc'   => __('Remember settings such as language or layout.', 'crumbz'),
            'statistics'        => __('Statistics', 'crumbz'),
            'statisticsDesc'    => __('Help us see how visitors use the site.', 'crumbz'),
            'marketing'         => __('Marketing', 'crumbz'),
            'marketingDesc'     => __('Used for ads, retargeting, and social media.', 'crumbz'),
            'gpcNote'           => __('Your browser sends a Global Privacy Control signal, so marketing is turned off.', 'crumbz'),
            'close'             => __('Close', 'crumbz'),
            'themeLabel'        => __('Theme', 'crumbz'),
            'themeAuto'         => __('Auto', 'crumbz'),
            'themeLight'        => __('Light', 'crumbz'),
            'themeDark'         => __('Dark', 'crumbz'),
        ];
    }

    /** Saved settings merged over defaults. */
    public static function all(): array {
        $saved = get_option(self::OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }
        return array_merge(self::defaults(), $saved);
    }

    public static function get(string $key) {
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : null;
    }

    public static function revision(): int {
        return max(1, (int) get_option(self::REVISION, 1));
    }

    public static function activate(): void {
        add_option(self::OPTION, self::defaults(), '', false);
        if (get_option(self::REVISION, null) === null) {
            add_option(self::REVISION, 1, '', false);
        }
        if (get_option(self::FINGERPRINT, null) === null) {
            add_option(self::FINGERPRINT, self::fingerprint(self::all()), '', false);
        }
    }

    public static function doc_is_active(array $doc): bool {
        return !empty($doc['page_id']) || !empty($doc['url']);
    }

    /** Hash of everything that should make visitors consent again. */
    public static function fingerprint(array $s): string {
        $docs = [];
        foreach ((array) ($s['documents'] ?? []) as $id => $doc) {
            if (is_array($doc) && self::doc_is_active($doc)) {
                $docs[(string) $id] = (int) ($doc['version'] ?? 1);
            }
        }
        ksort($docs);
        $jur = array_values((array) ($s['jurisdictions'] ?? []));
        sort($jur);
        return md5((string) wp_json_encode(['docs' => $docs, 'jurisdictions' => $jur]));
    }

    /**
     * Sanitizes settings. Accepts either posted form data (text, detected,
     * script_rules_text) or the internal stored format (detected_overrides,
     * script_rules), so the wizard can call it too.
     */
    public static function sanitize($input): array {
        $current = self::all();
        if (!current_user_can('manage_options')) {
            return $current;
        }

        $in = is_array($input) ? wp_unslash($input) : [];
        $d  = self::defaults();
        $out = [];

        $out['enabled']         = empty($in['enabled']) ? 0 : 1;
        $out['auto_update']     = empty($in['auto_update']) ? 0 : 1;
        $out['banner_position'] = self::pick($in, 'banner_position', ['bottom', 'top', 'modal'], $d['banner_position']);
        $out['button_position'] = self::pick($in, 'button_position', ['bottom-left', 'bottom-center', 'bottom-right', 'top-left', 'top-center', 'top-right'], $d['button_position']);
        $out['theme']           = self::pick($in, 'theme', ['auto', 'light', 'dark'], $d['theme']);
        $out['banner_shape']    = self::pick($in, 'banner_shape', ['card', 'bar'], $d['banner_shape']);
        $out['style_mode']      = self::pick($in, 'style_mode', ['plugin', 'theme'], $d['style_mode']);

        $out['custom_fonts'] = array_values(self::custom_font_list($current));
        $posted_colors = is_array($in['colors'] ?? null) ? $in['colors'] : [];
        $out['colors'] = [];
        foreach ($d['colors'] as $role => $default) {
            $hex = sanitize_hex_color((string) ($posted_colors[$role] ?? ''));
            $out['colors'][$role] = $hex ?: ($current['colors'][$role] ?? $default);
        }

        $posted_fonts = is_array($in['fonts'] ?? null) ? $in['fonts'] : [];
        $font_ids = self::font_ids($out);
        $out['fonts'] = [];
        foreach ($d['fonts'] as $role => $default) {
            $out['fonts'][$role] = in_array((string) ($posted_fonts[$role] ?? ''), $font_ids, true)
                ? (string) $posted_fonts[$role]
                : (string) ($current['fonts'][$role] ?? $default);
        }

        $posted_classes = is_array($in['theme_classes'] ?? null) ? $in['theme_classes'] : [];
        $out['theme_classes'] = [];
        foreach ($d['theme_classes'] as $role => $default) {
            $names = preg_split('/\s+/', trim((string) ($posted_classes[$role] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $out['theme_classes'][$role] = implode(' ', array_filter(array_map('sanitize_html_class', $names)));
        }

        $posted_icons = is_array($in['icons'] ?? null) ? $in['icons'] : [];
        $out['icons'] = [];
        foreach ($d['icons'] as $role => $default) {
            $out['icons'][$role] = self::pick($posted_icons, $role, array_keys(self::icon_choices()), (string) ($current['icons'][$role] ?? $default));
        }

        $jur = isset($in['jurisdictions']) ? (array) $in['jurisdictions'] : [];
        $out['jurisdictions'] = array_values(array_intersect(['eu', 'ca'], array_map('sanitize_key', $jur)));

        $posted_docs = is_array($in['documents'] ?? null) ? $in['documents'] : [];
        $out['documents'] = [];
        foreach ($d['documents'] as $id => $default) {
            $src = is_array($posted_docs[$id] ?? null) ? $posted_docs[$id] : [];
            $out['documents'][$id] = [
                'label'   => sanitize_text_field($src['label'] ?? ($current['documents'][$id]['label'] ?? $default['label'])),
                'page_id' => absint($src['page_id'] ?? ($current['documents'][$id]['page_id'] ?? 0)),
                'url'     => esc_url_raw($src['url'] ?? ($current['documents'][$id]['url'] ?? '')),
                'version' => max(1, absint($src['version'] ?? ($current['documents'][$id]['version'] ?? 1))),
            ];
        }

        $out['cookie_lifetime_days'] = min(730, max(1, absint($in['cookie_lifetime_days'] ?? $current['cookie_lifetime_days'])));

        if (isset($in['script_rules_text'])) {
            $out['script_rules'] = self::parse_rules((string) $in['script_rules_text']);
        } else {
            $out['script_rules'] = self::valid_rules(is_array($in['script_rules'] ?? null) ? $in['script_rules'] : $current['script_rules']);
        }

        $posted_snip = is_array($in['snippets'] ?? null) ? $in['snippets'] : [];
        $out['snippets'] = [];
        foreach (self::CATEGORIES as $cat) {
            $keep = (string) ($current['snippets'][$cat] ?? '');
            $out['snippets'][$cat] = (current_user_can('unfiltered_html') && isset($posted_snip[$cat]))
                ? (string) $posted_snip[$cat]
                : $keep;
        }

        $overrides_src = $in['detected'] ?? $in['detected_overrides'] ?? [];
        $out['detected_overrides'] = [];
        foreach ((array) $overrides_src as $file => $cat) {
            $cat = (string) $cat;
            if ($cat === '' || in_array($cat, self::CATEGORIES, true) || $cat === 'necessary') {
                $out['detected_overrides'][sanitize_text_field((string) $file)] = $cat;
            }
        }

        $posted_text = is_array($in['text'] ?? null) ? $in['text'] : [];
        $out['text'] = [];
        foreach ($d['text'] as $key => $default) {
            $val = array_key_exists($key, $posted_text)
                ? (string) $posted_text[$key]
                : (string) ($current['text'][$key] ?? $default);
            $out['text'][$key] = ($key === 'bannerMessage') ? sanitize_textarea_field($val) : sanitize_text_field($val);
        }

        $fp  = self::fingerprint($out);
        $old = get_option(self::FINGERPRINT, null);
        if ($old !== null && $old !== $fp) {
            update_option(self::REVISION, self::revision() + 1, false);
        }
        update_option(self::FINGERPRINT, $fp, false);

        return $out;
    }

    private static function pick(array $in, string $key, array $allowed, string $default): string {
        $value = isset($in[$key]) ? (string) $in[$key] : '';
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /** Parses "domain | category" lines. */
    public static function parse_rules(string $text): array {
        $rules = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) !== 2) {
                continue;
            }
            $match = strtolower((string) preg_replace('/[^a-z0-9.\-_]/i', '', $parts[0]));
            $cat   = sanitize_key($parts[1]);
            if ($match !== '' && in_array($cat, self::CATEGORIES, true)) {
                $rules[] = ['match' => $match, 'category' => $cat];
            }
        }
        return $rules;
    }

    public static function rules_as_text(array $rules): string {
        $lines = [];
        foreach ($rules as $rule) {
            $lines[] = $rule['match'] . ' | ' . $rule['category'];
        }
        return implode("\n", $lines);
    }

    private static function valid_rules(array $rules): array {
        $out = [];
        foreach ($rules as $rule) {
            if (is_array($rule) && !empty($rule['match']) && in_array($rule['category'] ?? '', self::CATEGORIES, true)) {
                $out[] = [
                    'match'    => strtolower((string) preg_replace('/[^a-z0-9.\-_]/i', '', (string) $rule['match'])),
                    'category' => (string) $rule['category'],
                ];
            }
        }
        return $out;
    }
}
