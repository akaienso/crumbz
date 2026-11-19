<?php
/**
 * Plugin Name:       Crumbz
 * Description:       Free, open-source cookie consent for WordPress. Consent-first, with a sticky settings icon, EU and California modes, and a policy wizard.
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       crumbz
 */

/* Version: 0.2.0 */ // x-release-please-version

if (!defined('ABSPATH')) {
    exit;
}

define('CRUMBZ_VERSION', '0.2.0'); // x-release-please-version
define('CRUMBZ_DIR', plugin_dir_path(__FILE__));
define('CRUMBZ_URL', plugin_dir_url(__FILE__));
define('CRUMBZ_BASENAME', plugin_basename(__FILE__));

require_once CRUMBZ_DIR . 'includes/class-crumbz-settings.php';
require_once CRUMBZ_DIR . 'includes/class-crumbz-consent.php';
require_once CRUMBZ_DIR . 'includes/class-crumbz-detect.php';
require_once CRUMBZ_DIR . 'includes/class-crumbz-scripts.php';
require_once CRUMBZ_DIR . 'includes/class-crumbz-frontend.php';
require_once CRUMBZ_DIR . 'includes/class-crumbz-fonts.php';
require_once CRUMBZ_DIR . 'includes/class-crumbz-templates.php';
require_once CRUMBZ_DIR . 'includes/class-crumbz-advisor.php';
require_once CRUMBZ_DIR . 'includes/class-crumbz-wizard.php';
require_once CRUMBZ_DIR . 'includes/class-crumbz-updater.php';
require_once CRUMBZ_DIR . 'includes/class-crumbz-admin.php';

register_activation_hook(__FILE__, ['Crumbz_Settings', 'activate']);

add_action('plugins_loaded', static function () {
    Crumbz_Frontend::init();
    Crumbz_Fonts::init();
    Crumbz_Scripts::init();
    Crumbz_Updater::init();
    Crumbz_Admin::init();
});
