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
