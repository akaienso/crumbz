<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Front-end output: assets, the config object, and admin-supplied head snippets.
 * The banner and icon are built in JavaScript, so no markup is printed here.
 */
class Crumbz_Frontend {

    public static function init(): void {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
        add_action('wp_head', [__CLASS__, 'head_snippets'], 1);
        add_action('init', [__CLASS__, 'vary_headers']);
    }

    /**
     * The config object carries the Global Privacy Control header, so cached copies of the page
     * must be keyed on it.
     */
    public static function vary_headers(): void {
        if (!is_admin() && !headers_sent()) {
            header('Vary: Sec-GPC', false);
        }
    }

    public static function enqueue(): void {
        if (!Crumbz_Settings::get('enabled')) {
            return;
        }
        wp_enqueue_style('crumbz', CRUMBZ_URL . 'assets/css/frontend.css', [], CRUMBZ_VERSION);
        wp_enqueue_script('crumbz', CRUMBZ_URL . 'assets/js/frontend.js', [], CRUMBZ_VERSION, true);
        wp_add_inline_script('crumbz', 'window.crumbzConfig = ' . wp_json_encode(self::config()) . ';', 'before');
    }

    public static function config(): array {
        $s = Crumbz_Settings::all();

        $docs = [];
        foreach ((array) $s['documents'] as $id => $doc) {
            if (!is_array($doc) || !Crumbz_Settings::doc_is_active($doc)) {
                continue;
            }
            if (!empty($doc['page_id'])) {
                // Drafts and private pages would 404 for visitors, so only published pages are listed.
                $post = get_post((int) $doc['page_id']);
                if (!$post || $post->post_status !== 'publish') {
                    continue;
                }
                $url = (string) get_permalink($post);
            } else {
                $url = (string) $doc['url'];
            }
            if ($url === '') {
                continue;
            }
            $docs[] = [
                'id'      => (string) $id,
                'label'   => (string) $doc['label'],
                'url'     => (string) $url,
                'version' => (int) $doc['version'],
            ];
        }

        return [
            'cookie'         => Crumbz_Settings::COOKIE,
            'revision'       => Crumbz_Settings::revision(),
            'lifetime'       => (int) $s['cookie_lifetime_days'],
            'theme'          => (string) $s['theme'],
            'position'       => (string) $s['button_position'],
            'bannerPosition' => (string) $s['banner_position'],
            'jurisdictions'  => array_values((array) $s['jurisdictions']),
            'gpc'            => Crumbz_Consent::gpc(),
            'documents'      => $docs,
            'text'           => (array) $s['text'],
            'nonce'          => Crumbz_Scripts::nonce(),
        ];
    }

    /**
     * Admin-supplied head snippets. Each snippet is wrapped in an inert <template>, so nothing in it
     * runs or loads (scripts or images) until frontend.js moves it into the page after consent.
     */
    public static function head_snippets(): void {
        if (!Crumbz_Settings::get('enabled')) {
            return;
        }
        $snips = (array) Crumbz_Settings::get('snippets');
        foreach (Crumbz_Settings::CATEGORIES as $cat) {
            $html = trim((string) ($snips[$cat] ?? ''));
            if ($html === '') {
                continue;
            }
            echo "\n<!-- Crumbz snippet: " . esc_html($cat) . " -->\n";
            // Admin-entered HTML for users with unfiltered_html; output is intentionally not escaped.
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<template data-crumbz-category="' . esc_attr($cat) . '" data-crumbz-nonce="' . esc_attr(Crumbz_Scripts::nonce()) . '">' . $html . '</template>';
            echo "\n";
        }
    }
}
