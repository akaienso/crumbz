<?php
// Runs when the plugin is deleted from the Plugins screen. Draft pages are kept on purpose.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('crumbz_settings');
delete_option('crumbz_policy_revision');
delete_option('crumbz_policy_fingerprint');
delete_option('crumbz_wizard_answers');
delete_option('crumbz_wizard_pages');

// Uploaded custom fonts go with the plugin. The bundled fonts ship in the plugin folder and are removed with it.
$crumbz_uploads = wp_upload_dir(null, false);
$crumbz_font_dir = rtrim((string) $crumbz_uploads['basedir'], '/') . '/crumbz-fonts';
if (is_dir($crumbz_font_dir)) {
    $crumbz_files = array_merge(glob($crumbz_font_dir . '/*') ?: [], glob($crumbz_font_dir . '/.[!.]*') ?: []);
    foreach ($crumbz_files as $crumbz_file) {
        if (is_file($crumbz_file)) {
            unlink($crumbz_file);
        }
    }
    @rmdir($crumbz_font_dir);
}
