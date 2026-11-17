<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Server-side view of the visitor's consent record.
 * The record is written by assets/js/frontend.js only after a visitor chooses.
 */
class Crumbz_Consent {

    /** Returns the valid consent record for the current policy revision, or null. */
    public static function record(): ?array {
        if (!isset($_COOKIE[Crumbz_Settings::COOKIE])) {
            return null;
        }
        $raw  = wp_unslash((string) $_COOKIE[Crumbz_Settings::COOKIE]);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = json_decode(rawurldecode($raw), true);
        }
        if (!is_array($data) || (($data['v'] ?? null) !== 1)) {
            return null;
        }
        if ((int) ($data['r'] ?? 0) !== Crumbz_Settings::revision()) {
            return null;
        }

        $cats = [];
        $posted = is_array($data['cats'] ?? null) ? $data['cats'] : [];
        foreach (Crumbz_Settings::CATEGORIES as $cat) {
            $cats[$cat] = !empty($posted[$cat]);
        }

        return [
            'cats'   => $cats,
            'docs'   => is_array($data['docs'] ?? null) ? $data['docs'] : [],
            'hidden' => !empty($data['hidden']),
        ];
    }

    /** True when the browser sends the Global Privacy Control signal. */
    public static function gpc(): bool {
        return isset($_SERVER['HTTP_SEC_GPC']) && trim((string) $_SERVER['HTTP_SEC_GPC']) === '1';
    }

    public static function in_california(): bool {
        return in_array('ca', (array) Crumbz_Settings::get('jurisdictions'), true);
    }

    /** Whether scripts and snippets in this category may run for the current visitor. */
    public static function allowed(string $category): bool {
        if ($category === 'necessary') {
            return true;
        }
        if (!in_array($category, Crumbz_Settings::CATEGORIES, true)) {
            return false;
        }
        if ($category === 'marketing' && self::gpc() && self::in_california()) {
            return false;
        }
        $rec = self::record();
        return $rec !== null && !empty($rec['cats'][$category]);
    }
}
