<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Feeds the latest GitHub release into WordPress's normal plugin update system.
 *
 * Sites see the update on the Plugins screen. When automatic updates are on for
 * Crumbz, WordPress installs it on its own schedule (usually twice a day, through
 * WP-Cron). Only packages hosted under this repository's release downloads are accepted.
 *
 * The request sends a generic User-Agent and no site information.
 */
class Crumbz_Updater {

    const REPO      = 'akaienso/crumbz';
    const ASSET     = 'crumbz.zip';
    const CACHE_KEY = 'crumbz_latest_release';
    const CACHE_TTL = 43200; // 12 hours
    const FAIL_TTL  = 3600;  // after a failed check, wait an hour before trying again

    public static function init(): void {
        add_filter('pre_set_site_transient_update_plugins', [__CLASS__, 'check']);
        add_filter('plugins_api', [__CLASS__, 'plugin_info'], 10, 3);
        add_filter('auto_update_plugin', [__CLASS__, 'auto_update'], 10, 2);
    }

    /**
     * Latest release as ['version', 'package', 'url', 'changelog'], or null.
     * The 'crumbz_latest_release' filter lets tests supply a release without network access.
     */
    public static function latest_release(): ?array {
        $filtered = apply_filters('crumbz_latest_release', null);
        if (is_array($filtered)) {
            return $filtered;
        }

        $cached = get_transient(self::CACHE_KEY);
        if ($cached !== false) {
            return is_array($cached) ? $cached : null;
        }

        $response = wp_remote_get('https://api.github.com/repos/' . self::REPO . '/releases/latest', [
            'timeout' => 10,
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'Crumbz-Updater',
            ],
        ]);

        $release = null;
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode((string) wp_remote_retrieve_body($response), true);
            $release = is_array($data) ? self::parse($data) : null;
        }

        set_transient(self::CACHE_KEY, $release ?? 'none', $release ? self::CACHE_TTL : self::FAIL_TTL);
        return $release;
    }

    /** Turns a GitHub release payload into the array used by this class. Returns null if it is unusable. */
    public static function parse(array $data): ?array {
        $version = ltrim((string) ($data['tag_name'] ?? ''), 'vV');
        if (!preg_match('/^\d+\.\d+\.\d+(-[0-9A-Za-z.-]+)?$/', $version)) {
            return null;
        }

        $package = '';
        foreach ((array) ($data['assets'] ?? []) as $asset) {
            if (is_array($asset) && (($asset['name'] ?? '') === self::ASSET)) {
                $package = (string) ($asset['browser_download_url'] ?? '');
            }
        }
        if (strpos($package, 'https://github.com/' . self::REPO . '/releases/download/') !== 0) {
            return null;
        }

        return [
            'version'   => $version,
            'package'   => $package,
            'url'       => (string) ($data['html_url'] ?? ''),
            'changelog' => (string) ($data['body'] ?? ''),
        ];
    }

    /** Adds Crumbz to the update list when the latest release is newer than the installed version. */
    public static function check($transient) {
        if (!is_object($transient) || empty($transient->checked)) {
            return $transient;
        }
        $release = self::latest_release();
        if ($release === null) {
            return $transient;
        }

        $file = CRUMBZ_BASENAME;
        $item = (object) [
            'id'           => 'github.com/' . self::REPO,
            'slug'         => 'crumbz',
            'plugin'       => $file,
            'new_version'  => $release['version'],
            'url'          => $release['url'],
            'package'      => $release['package'],
            'tested'       => '',
            'requires_php' => '7.4',
        ];

        if (version_compare($release['version'], CRUMBZ_VERSION, '>')) {
            $transient->response[$file] = $item;
        } else {
            $transient->no_update[$file] = $item;
        }
        return $transient;
    }

    /** Details for the "View version details" popup. */
    public static function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || !is_object($args) || empty($args->slug) || $args->slug !== 'crumbz') {
            return $result;
        }
        $release = self::latest_release();
        if ($release === null) {
            return $result;
        }
        return (object) [
            'name'          => 'Crumbz',
            'slug'          => 'crumbz',
            'version'       => $release['version'],
            'homepage'      => $release['url'],
            'download_link' => $release['package'],
            'requires_php'  => '7.4',
            'sections'      => [
                'changelog' => wp_kses_post(nl2br(esc_html($release['changelog']))),
            ],
        ];
    }

    /** Lets WordPress install Crumbz updates automatically, when the site setting is on. */
    public static function auto_update($update, $item) {
        if (is_object($item) && isset($item->plugin) && $item->plugin === CRUMBZ_BASENAME) {
            return (bool) Crumbz_Settings::get('auto_update');
        }
        return $update;
    }
}
