<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin menu and the Settings screen. Styles come from assets/css/admin.css.
 * No inline scripts or styles.
 */
class Crumbz_Admin {

    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'register']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'assets']);
        add_action('admin_post_crumbz_wizard_save', ['Crumbz_Wizard', 'handle']);
        add_action('admin_post_crumbz_advisor_save', ['Crumbz_Advisor', 'handle_save']);
        add_action('admin_post_crumbz_advisor_apply', ['Crumbz_Advisor', 'handle_apply']);
    }

    public static function menu(): void {
        add_menu_page(
            __('Crumbz', 'crumbz'),
            __('Crumbz', 'crumbz'),
            'manage_options',
            'crumbz',
            [__CLASS__, 'render_settings'],
            'dashicons-shield-alt',
            81
        );
        add_submenu_page('crumbz', __('Settings', 'crumbz'), __('Settings', 'crumbz'), 'manage_options', 'crumbz', [__CLASS__, 'render_settings']);
        add_submenu_page('crumbz', __('Jurisdiction advisor', 'crumbz'), __('Jurisdiction advisor', 'crumbz'), 'manage_options', 'crumbz-advisor', ['Crumbz_Advisor', 'render_page']);
        add_submenu_page('crumbz', __('Policy wizard', 'crumbz'), __('Policy wizard', 'crumbz'), 'manage_options', 'crumbz-wizard', ['Crumbz_Wizard', 'render']);
    }

    public static function register(): void {
        register_setting('crumbz', Crumbz_Settings::OPTION, [
            'type'              => 'array',
            'sanitize_callback' => ['Crumbz_Settings', 'sanitize'],
            'default'           => [],
        ]);
    }

    public static function assets(string $hook): void {
        if (strpos($hook, 'crumbz') === false) {
            return;
        }
        wp_enqueue_style('crumbz-admin', CRUMBZ_URL . 'assets/css/admin.css', [], CRUMBZ_VERSION);
    }

    public static function render_settings(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage Crumbz.', 'crumbz'));
        }
        $s = Crumbz_Settings::all();
        $can_html = current_user_can('unfiltered_html');
        $detected = Crumbz_Detect::scan();
        $option = Crumbz_Settings::OPTION;

        echo '<div class="wrap crumbz-admin">';
        echo '<div class="crumbz-hero"><div class="crumbz-brand"><span class="crumbz-mark" aria-hidden="true">C</span><h1>' . esc_html__('Crumbz', 'crumbz') . '</h1><span class="crumbz-tagline">' . esc_html__('Consent for WordPress', 'crumbz') . '</span></div>';
        echo '<a class="crumbz-hero-action" href="' . esc_url(admin_url('admin.php?page=crumbz-wizard')) . '">' . esc_html__('Open the policy wizard', 'crumbz') . '</a></div>';
        echo '<p class="crumbz-lead">' . esc_html__('Consent-first cookie banner. Nothing optional runs, and no optional cookie is set, until a visitor makes a choice.', 'crumbz') . '</p>';
        echo '<div class="crumbz-tiles">';
        self::tile(__('Banner', 'crumbz'), !empty($s['enabled']) ? __('On', 'crumbz') : __('Off', 'crumbz'), !empty($s['enabled']) ? __('Blocking optional scripts', 'crumbz') : __('Visitors see no banner', 'crumbz'), !empty($s['enabled']));
        self::tile(__('Automatic updates', 'crumbz'), !empty($s['auto_update']) ? __('On', 'crumbz') : __('Off', 'crumbz'), __('Checks about twice a day', 'crumbz'), !empty($s['auto_update']));
        self::tile(__('Policy revision', 'crumbz'), (string) Crumbz_Settings::revision(), __('Visitors see the banner again on change', 'crumbz'), null);
        self::tile(__('Plugin version', 'crumbz'), CRUMBZ_VERSION, __('Installed release', 'crumbz'), null);
        echo '</div>';
        settings_errors();
        if (isset($_GET['crumbz_notice'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $ok = isset($_GET['crumbz_type']) && $_GET['crumbz_type'] === 'updated'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            echo '<div class="notice ' . ($ok ? 'notice-success' : 'notice-error') . ' is-dismissible"><p>' . esc_html(sanitize_text_field(wp_unslash((string) $_GET['crumbz_notice']))) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        echo '<form method="post" action="options.php">';
        settings_fields('crumbz');

        echo '<h2>' . esc_html__('General', 'crumbz') . '</h2><table class="form-table" role="presentation">';
        self::row(__('Crumbz is on', 'crumbz'), self::checkbox($option . '[enabled]', !empty($s['enabled']), __('Show the banner and icon, and block optional scripts', 'crumbz')));
        self::row(__('Automatic updates', 'crumbz'), self::checkbox($option . '[auto_update]', !empty($s['auto_update']), __('Install new Crumbz releases automatically', 'crumbz')), __('WordPress checks on its usual schedule, about twice a day. Turn this off to update by hand from the Plugins screen. Current version: ', 'crumbz') . CRUMBZ_VERSION);
        self::row(__('Banner position', 'crumbz'), self::select($option . '[banner_position]', [
            'bottom' => __('Bottom', 'crumbz'),
            'top'    => __('Top bar', 'crumbz'),
            'modal'  => __('Centered panel', 'crumbz'),
        ], $s['banner_position']));
        self::row(__('Settings icon position', 'crumbz'), self::select($option . '[button_position]', [
            'bottom-left'   => __('Bottom left', 'crumbz'),
            'bottom-center' => __('Bottom center', 'crumbz'),
            'bottom-right'  => __('Bottom right', 'crumbz'),
            'top-left'      => __('Top left', 'crumbz'),
            'top-center'    => __('Top center', 'crumbz'),
            'top-right'     => __('Top right', 'crumbz'),
        ], $s['button_position']), __('Visitors can drag the icon anywhere else on the page.', 'crumbz'));
        self::row(__('Default theme', 'crumbz'), self::select($option . '[theme]', [
            'auto'  => __('Follow the visitor’s system setting', 'crumbz'),
            'light' => __('Light', 'crumbz'),
            'dark'  => __('Dark', 'crumbz'),
        ], $s['theme']));
        self::row(__('Cookie lifetime (days)', 'crumbz'), '<input type="number" min="1" max="730" name="' . esc_attr($option . '[cookie_lifetime_days]') . '" value="' . esc_attr((string) $s['cookie_lifetime_days']) . '">');
        echo '</table>';

        echo '<h2>' . esc_html__('Look', 'crumbz') . '</h2>';
        echo '<p class="description">' . esc_html__('Choose how the banner, the dialog, and the settings icon look. Plugin styling uses the colors and typefaces below. My theme leaves the look to your theme’s CSS classes.', 'crumbz') . '</p>';
        echo '<table class="form-table" role="presentation">';
        self::row(__('Banner shape', 'crumbz'), self::select($option . '[banner_shape]', [
            'card' => __('Floating card', 'crumbz'),
            'bar'  => __('Full-width bar', 'crumbz'),
        ], $s['banner_shape']), __('The floating card sits in the corner. The bar spans the bottom of the page. Applies when the banner is at the bottom.', 'crumbz'));
        self::row(__('Styling', 'crumbz'), self::radio($option . '[style_mode]', [
            'plugin' => __('Plugin styling: use the colors and typefaces below', 'crumbz'),
            'theme'  => __('My theme: use the class names below, which my theme already styles', 'crumbz'),
        ], $s['style_mode']));
        $colors = (array) $s['colors'];
        self::row(__('Primary color', 'crumbz'), self::color($option . '[colors][primary]', $colors['primary']), __('Accept button and the settings icon. The text on it is chosen automatically for contrast.', 'crumbz'));
        self::row(__('Secondary color', 'crumbz'), self::color($option . '[colors][secondary]', $colors['secondary']) . self::contrast_note($colors['secondary'], '#ffffff'), __('Reject and Save buttons, and their outline.', 'crumbz'));
        self::row(__('Tertiary color', 'crumbz'), self::color($option . '[colors][tertiary]', $colors['tertiary']) . self::contrast_note($colors['tertiary'], '#ffffff'), __('Customize and the links in the banner.', 'crumbz'));
        self::row(__('Primary typeface', 'crumbz'), self::select($option . '[fonts][primary]', self::font_options($s), $s['fonts']['primary']), __('Headings and buttons.', 'crumbz'));
        self::row(__('Secondary typeface', 'crumbz'), self::select($option . '[fonts][secondary]', self::font_options($s), $s['fonts']['secondary']), __('Body text.', 'crumbz'));
        $classes = (array) $s['theme_classes'];
        self::row(__('Primary theme class', 'crumbz'), '<input type="text" class="regular-text code" name="' . esc_attr($option . '[theme_classes][primary]') . '" value="' . esc_attr($classes['primary']) . '">', __('Added to the Accept button and the settings icon. Separate several classes with spaces.', 'crumbz'));
        self::row(__('Secondary theme class', 'crumbz'), '<input type="text" class="regular-text code" name="' . esc_attr($option . '[theme_classes][secondary]') . '" value="' . esc_attr($classes['secondary']) . '">', __('Added to Reject and Save.', 'crumbz'));
        self::row(__('Tertiary theme class', 'crumbz'), '<input type="text" class="regular-text code" name="' . esc_attr($option . '[theme_classes][tertiary]') . '" value="' . esc_attr($classes['tertiary']) . '">', __('Added to Customize.', 'crumbz'));
        $icons = (array) $s['icons'];
        self::row(__('Accept icon', 'crumbz'), self::select($option . '[icons][accept]', Crumbz_Settings::icon_choices(), $icons['accept']));
        self::row(__('Reject icon', 'crumbz'), self::select($option . '[icons][reject]', Crumbz_Settings::icon_choices(), $icons['reject']));
        self::row(__('Customize icon', 'crumbz'), self::select($option . '[icons][customize]', Crumbz_Settings::icon_choices(), $icons['customize']));
        self::row(__('Settings icon', 'crumbz'), self::select($option . '[icons][settings]', Crumbz_Settings::icon_choices(), $icons['settings']));
        echo '</table>';

        echo '<h2>' . esc_html__('Jurisdictions', 'crumbz') . '</h2>';
        echo '<p class="description">' . esc_html__('Choose the rules this site follows. Changing this asks every visitor to choose again.', 'crumbz') . '</p>';
        echo '<table class="form-table" role="presentation">';
        $jur = (array) $s['jurisdictions'];
        self::row(__('European Union', 'crumbz'), '<label><input type="checkbox" name="' . esc_attr($option . '[jurisdictions][]') . '" value="eu"' . checked(in_array('eu', $jur, true), true, false) . '> ' . esc_html__('Opt-in for each optional category, with equal Accept and Reject buttons. Nothing is pre-ticked, and content is never blocked.', 'crumbz') . '</label>');
        self::row(__('California', 'crumbz'), '<label><input type="checkbox" name="' . esc_attr($option . '[jurisdictions][]') . '" value="ca"' . checked(in_array('ca', $jur, true), true, false) . '> ' . esc_html__('Shows the Do Not Sell or Share link, and treats the Global Privacy Control signal as an opt-out of marketing.', 'crumbz') . '</label>');
        echo '</table>';

        echo '<h2>' . esc_html__('Documents', 'crumbz') . '</h2>';
        echo '<p class="description">' . esc_html__('Pick a page or enter a URL. Raise the version number when a document changes, so visitors accept the new version.', 'crumbz') . '</p>';
        echo '<table class="widefat crumbz-docs"><thead><tr><th>' . esc_html__('Label', 'crumbz') . '</th><th>' . esc_html__('Page', 'crumbz') . '</th><th>' . esc_html__('Or URL', 'crumbz') . '</th><th>' . esc_html__('Version', 'crumbz') . '</th></tr></thead><tbody>';
        foreach ($s['documents'] as $id => $doc) {
            echo '<tr>';
            echo '<td><input type="text" class="regular-text" name="' . esc_attr(self::name('documents', $id, 'label')) . '" value="' . esc_attr($doc['label']) . '"></td>';
            echo '<td>' . wp_dropdown_pages([
                'name'             => self::name('documents', $id, 'page_id'),
                'selected'         => (int) $doc['page_id'],
                'show_option_none' => __('None', 'crumbz'),
                'option_none_value' => 0,
                'echo'             => 0,
            ]) . '</td>';
            echo '<td><input type="url" class="regular-text" name="' . esc_attr(self::name('documents', $id, 'url')) . '" value="' . esc_attr($doc['url']) . '"></td>';
            echo '<td><input type="number" min="1" class="small-text" name="' . esc_attr(self::name('documents', $id, 'version')) . '" value="' . esc_attr((string) $doc['version']) . '"></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        echo '<h2>' . esc_html__('Plugins that set cookies', 'crumbz') . '</h2>';
        if (empty($detected)) {
            echo '<p class="description">' . esc_html__('No known cookie-setting plugins are active.', 'crumbz') . '</p>';
        } else {
            echo '<p class="description">' . esc_html__('These active plugins are known to set cookies. Pick the category each one belongs to, or ignore it.', 'crumbz') . '</p>';
            echo '<table class="widefat"><thead><tr><th>' . esc_html__('Plugin', 'crumbz') . '</th><th>' . esc_html__('Suggested', 'crumbz') . '</th><th>' . esc_html__('Category', 'crumbz') . '</th></tr></thead><tbody>';
            $cat_options = [
                ''          => __('Ignore', 'crumbz'),
                'necessary'   => __('Necessary', 'crumbz'),
                'preferences' => __('Preferences', 'crumbz'),
                'statistics'  => __('Statistics', 'crumbz'),
                'marketing'   => __('Marketing', 'crumbz'),
            ];
            foreach ($detected as $row) {
                echo '<tr><td>' . esc_html($row['label']) . '</td><td>' . esc_html($cat_options[$row['suggested']] ?? $row['suggested']) . '</td><td>';
                echo self::select($option . '[detected][' . $row['file'] . ']', $cat_options, $row['category']);
                echo '</td></tr>';
            }
            echo '</tbody></table>';
        }

        echo '<h2>' . esc_html__('Third-party script rules', 'crumbz') . '</h2>';
        echo '<p class="description">' . esc_html__('One rule per line, in the form domain | category. Any script enqueued by WordPress whose URL contains the domain waits for that category.', 'crumbz') . '</p>';
        echo '<p><textarea class="large-text code" rows="8" name="' . esc_attr($option . '[script_rules_text]') . '">' . esc_textarea(Crumbz_Settings::rules_as_text((array) $s['script_rules'])) . '</textarea></p>';

        echo '<h2>' . esc_html__('Category snippets', 'crumbz') . '</h2>';
        if ($can_html) {
            echo '<p class="description">' . esc_html__('Paste a tracking tag here for a category. It prints in the page head and runs only after that category is allowed. Use this for scripts that the rules above do not catch, such as inline gtag code.', 'crumbz') . '</p>';
        } else {
            echo '<p class="description">' . esc_html__('Your account cannot add raw HTML. An administrator with unfiltered HTML access can edit these fields.', 'crumbz') . '</p>';
        }
        foreach (['preferences', 'statistics', 'marketing'] as $cat) {
            echo '<p><strong>' . esc_html(ucfirst($cat)) . '</strong><br>';
            echo '<textarea class="large-text code" rows="4" name="' . esc_attr($option . '[snippets][' . $cat . ']') . '"' . ($can_html ? '' : ' disabled') . '>' . esc_textarea((string) ($s['snippets'][$cat] ?? '')) . '</textarea></p>';
        }

        echo '<h2>' . esc_html__('Wording', 'crumbz') . '</h2><table class="form-table" role="presentation">';
        $wording = [
            'bannerTitle'   => __('Banner title', 'crumbz'),
            'bannerMessage' => __('Banner message', 'crumbz'),
            'accept'        => __('Accept button', 'crumbz'),
            'reject'        => __('Reject button', 'crumbz'),
            'customize'     => __('Customize button', 'crumbz'),
            'save'          => __('Save button', 'crumbz'),
            'hideIcon'      => __('Hide icon link', 'crumbz'),
            'doNotSell'     => __('Do Not Sell or Share link (California)', 'crumbz'),
            'iconLabel'     => __('Settings icon label', 'crumbz'),
        ];
        foreach ($wording as $key => $label) {
            $value = (string) ($s['text'][$key] ?? '');
            $input = $key === 'bannerMessage'
                ? '<textarea class="large-text" rows="3" name="' . esc_attr($option . '[text][' . $key . ']') . '">' . esc_textarea($value) . '</textarea>'
                : '<input type="text" class="regular-text" name="' . esc_attr($option . '[text][' . $key . ']') . '" value="' . esc_attr($value) . '">';
            self::row($label, $input);
        }
        echo '</table>';

        echo '<p class="description">' . esc_html(sprintf(
            /* translators: %d: policy revision number */
            __('Policy revision %d. Visitors are asked again each time this number goes up.', 'crumbz'),
            Crumbz_Settings::revision()
        )) . '</p>';

        submit_button(__('Save Crumbz settings', 'crumbz'));
        echo '</form>';
        self::render_fonts($s);
        echo '</div>';
    }

    private static function render_fonts(array $s): void {
        echo '<h2>' . esc_html__('Your typefaces', 'crumbz') . '</h2>';
        echo '<p class="description">' . esc_html__('Upload WOFF2 or WOFF files of 2 MB or less. Uploads are stored in your uploads folder and are deleted when Crumbz is deleted. Upload only fonts you are licensed to use on this site.', 'crumbz') . '</p>';

        $fonts = Crumbz_Settings::custom_font_list($s);
        if ($fonts) {
            echo '<table class="widefat striped"><thead><tr><th>' . esc_html__('Name', 'crumbz') . '</th><th>' . esc_html__('File', 'crumbz') . '</th><th><span class="screen-reader-text">' . esc_html__('Actions', 'crumbz') . '</span></th></tr></thead><tbody>';
            foreach ($fonts as $slug => $font) {
                echo '<tr><td>' . esc_html((string) $font['name']) . '</td><td><code>' . esc_html((string) $font['file']) . '</code></td><td>';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                echo '<input type="hidden" name="action" value="crumbz_font_delete">';
                echo '<input type="hidden" name="slug" value="' . esc_attr((string) $slug) . '">';
                wp_nonce_field('crumbz_font_delete');
                submit_button(__('Remove', 'crumbz'), 'secondary small', 'submit', false);
                echo '</form></td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p class="description">' . esc_html__('No uploaded typefaces yet.', 'crumbz') . '</p>';
        }

        echo '<form class="crumbz-upload" method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="crumbz_font_upload">';
        wp_nonce_field('crumbz_font_upload');
        echo '<label>' . esc_html__('Name', 'crumbz') . ' <input type="text" class="regular-text" name="font_name" maxlength="60"></label> ';
        echo '<input type="file" name="crumbz_font" accept=".woff2,.woff,font/woff2,font/woff"> ';
        submit_button(__('Upload typeface', 'crumbz'), 'secondary', 'submit', false);
        echo '</form>';
    }

    /** Typeface dropdown options: the bundled four, then any uploads. */
    private static function font_options(array $s): array {
        $options = [];
        foreach (Crumbz_Settings::bundled_fonts() as $id => $font) {
            $options[$id] = $font['label'];
        }
        foreach (Crumbz_Settings::custom_font_list($s) as $slug => $font) {
            $options['custom:' . $slug] = (string) $font['name'];
        }
        return $options;
    }

    private static function color(string $name, string $value): string {
        return '<input type="color" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
    }

    private static function radio(string $name, array $options, string $selected): string {
        $html = '<fieldset>';
        foreach ($options as $value => $label) {
            $html .= '<label class="crumbz-radio"><input type="radio" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '"' . checked((string) $selected, (string) $value, false) . '> ' . esc_html($label) . '</label><br>';
        }
        return $html . '</fieldset>';
    }

    /** A warning when text in this color would fall below the WCAG 4.5:1 minimum on the background. */
    private static function contrast_note(string $hex, string $background): string {
        $ratio = Crumbz_Settings::contrast($hex, $background);
        if ($ratio >= 4.5) {
            return '';
        }
        return '<p class="crumbz-warn">' . esc_html(sprintf(
            /* translators: %s: contrast ratio, such as 3.1 */
            __('Low contrast: %s:1 on white. Text needs at least 4.5:1.', 'crumbz'),
            number_format($ratio, 1)
        )) . '</p>';
    }

    private static function name(string ...$path): string {
        $name = Crumbz_Settings::OPTION;
        foreach ($path as $part) {
            $name .= '[' . $part . ']';
        }
        return $name;
    }

    private static function tile(string $label, string $value, string $note, ?bool $good): void {
        $class = $good === null ? '' : ($good ? ' is-good' : ' is-off');
        echo '<div class="crumbz-tile' . $class . '"><span class="crumbz-tile-label">' . esc_html($label) . '</span>';
        echo '<span class="crumbz-tile-value">' . esc_html($value) . '</span>';
        echo '<span class="crumbz-tile-note">' . esc_html($note) . '</span></div>';
    }

    private static function row(string $label, string $control, string $help = ''): void {
        echo '<tr><th scope="row">' . esc_html($label) . '</th><td>' . $control;
        if ($help !== '') {
            echo '<p class="description">' . esc_html($help) . '</p>';
        }
        echo '</td></tr>';
    }

    private static function checkbox(string $name, bool $checked, string $label): string {
        return '<label><input type="checkbox" name="' . esc_attr($name) . '" value="1"' . checked($checked, true, false) . '> ' . esc_html($label) . '</label>';
    }

    private static function select(string $name, array $options, string $selected): string {
        $html = '<select name="' . esc_attr($name) . '">';
        foreach ($options as $value => $label) {
            $html .= '<option value="' . esc_attr((string) $value) . '"' . selected((string) $selected, (string) $value, false) . '>' . esc_html($label) . '</option>';
        }
        return $html . '</select>';
    }
}
