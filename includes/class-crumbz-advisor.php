<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Jurisdiction advisor. Turns the business location and audience into suggested
 * Crumbz modes (EU, California) plus notes about laws Crumbz does not yet have a mode for.
 * Suggestions only. This is a starting point, not a legal determination.
 *
 * Location hints come from the administrator's own request: the Cloudflare country
 * header and the browser language. No outside lookup service is called.
 */
class Crumbz_Advisor {

    const OPTION = 'crumbz_business_location';

    const EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT',
        'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
    ];

    const EEA_EXTRA = ['IS', 'LI', 'NO'];

    /** US states with comprehensive consumer privacy laws. Verify this list against current law before relying on it. */
    const US_STATES_COMPREHENSIVE = [
        'VA', 'CO', 'CT', 'UT', 'TX', 'OR', 'MT', 'IA', 'IN', 'TN', 'DE', 'NH', 'NJ', 'KY', 'MD', 'MN', 'NE', 'RI',
    ];

    public static function audiences(): array {
        return [
            'eu'              => __('Visitors or customers in the EU or EEA', 'crumbz'),
            'uk'              => __('Visitors or customers in the UK', 'crumbz'),
            'california'      => __('Visitors or customers in California', 'crumbz'),
            'other_us_states' => __('Visitors or customers in other US states', 'crumbz'),
            'canada'          => __('Visitors or customers in Canada', 'crumbz'),
            'brazil'          => __('Visitors or customers in Brazil', 'crumbz'),
            'australia'       => __('Visitors or customers in Australia', 'crumbz'),
        ];
    }

    public static function countries(): array {
        $names = [
            'US' => 'United States', 'CA' => 'Canada', 'GB' => 'United Kingdom', 'AU' => 'Australia',
            'BR' => 'Brazil', 'IS' => 'Iceland', 'LI' => 'Liechtenstein', 'NO' => 'Norway',
            'AT' => 'Austria', 'BE' => 'Belgium', 'BG' => 'Bulgaria', 'HR' => 'Croatia', 'CY' => 'Cyprus',
            'CZ' => 'Czechia', 'DK' => 'Denmark', 'EE' => 'Estonia', 'FI' => 'Finland', 'FR' => 'France',
            'DE' => 'Germany', 'GR' => 'Greece', 'HU' => 'Hungary', 'IE' => 'Ireland', 'IT' => 'Italy',
            'LV' => 'Latvia', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MT' => 'Malta', 'NL' => 'Netherlands',
            'PL' => 'Poland', 'PT' => 'Portugal', 'RO' => 'Romania', 'SK' => 'Slovakia', 'SI' => 'Slovenia',
            'ES' => 'Spain', 'SE' => 'Sweden',
        ];
        asort($names);
        return ['' => __('Choose a country', 'crumbz')] + $names + ['ZZ' => __('Another country', 'crumbz')];
    }

    public static function get(): array {
        $saved = get_option(self::OPTION, []);
        return array_merge(
            ['country' => '', 'region' => '', 'postal' => '', 'address' => '', 'audience' => []],
            is_array($saved) ? $saved : []
        );
    }

    public static function sanitize($input): array {
        $in = is_array($input) ? wp_unslash($input) : [];
        $country = strtoupper(preg_replace('/[^a-zA-Z]/', '', (string) ($in['country'] ?? '')));
        return [
            'country'  => strlen($country) === 2 ? $country : '',
            'region'   => sanitize_text_field((string) ($in['region'] ?? '')),
            'postal'   => sanitize_text_field(substr((string) ($in['postal'] ?? ''), 0, 20)),
            'address'  => sanitize_textarea_field((string) ($in['address'] ?? '')),
            'audience' => array_values(array_intersect(
                array_keys(self::audiences()),
                array_map('sanitize_key', (array) ($in['audience'] ?? []))
            )),
        ];
    }

    /**
     * Rows for the results table. 'mode' is 'eu', 'ca', or null (a note only, no mode yet).
     */
    public static function recommend(array $loc): array {
        $country = strtoupper((string) ($loc['country'] ?? ''));
        $region  = strtoupper(trim((string) ($loc['region'] ?? '')));
        $aud     = (array) ($loc['audience'] ?? []);
        $rows    = [];

        if (in_array($country, array_merge(self::EU_COUNTRIES, self::EEA_EXTRA), true) || in_array('eu', $aud, true)) {
            $rows[] = [
                'mode'   => 'eu',
                'title'  => __('EU and EEA', 'crumbz'),
                'laws'   => __('GDPR and the ePrivacy Directive, as each country applies it', 'crumbz'),
                'why'    => __('Non-essential cookies need opt-in consent before they are set.', 'crumbz'),
            ];
        }
        if ($country === 'GB' || in_array('uk', $aud, true)) {
            $rows[] = [
                'mode'   => 'eu',
                'title'  => __('United Kingdom', 'crumbz'),
                'laws'   => __('UK GDPR and PECR (Privacy and Electronic Communications Regulations)', 'crumbz'),
                'why'    => __('Non-essential cookies need consent. EU mode is the closest current setting.', 'crumbz'),
            ];
        }
        if (($country === 'US' && in_array($region, ['CA', 'CALIFORNIA'], true)) || in_array('california', $aud, true)) {
            $rows[] = [
                'mode'   => 'ca',
                'title'  => __('California', 'crumbz'),
                'laws'   => __('CCPA as amended by CPRA', 'crumbz'),
                'why'    => __('A notice at collection, a Do Not Sell or Share choice, and honoring the Global Privacy Control signal.', 'crumbz'),
            ];
        }
        if (($country === 'US' && in_array($region, self::US_STATES_COMPREHENSIVE, true)) || in_array('other_us_states', $aud, true)) {
            $rows[] = [
                'mode'   => null,
                'title'  => __('Other US states', 'crumbz'),
                'laws'   => __('State comprehensive privacy laws. Check which ones apply to your state.', 'crumbz'),
                'why'    => __('Several of these laws require honoring Global Privacy Control. California mode is the nearest current setting. Confirm with counsel.', 'crumbz'),
            ];
        }
        if ($country === 'CA' || in_array('canada', $aud, true)) {
            $rows[] = [
                'mode'   => null,
                'title'  => __('Canada', 'crumbz'),
                'laws'   => __('PIPEDA, plus provincial laws, and CASL for commercial email', 'crumbz'),
                'why'    => __('Crumbz has no Canada mode yet. EU mode is the strictest opt-in setting available.', 'crumbz'),
            ];
        }
        if ($country === 'BR' || in_array('brazil', $aud, true)) {
            $rows[] = [
                'mode'   => null,
                'title'  => __('Brazil', 'crumbz'),
                'laws'   => __('LGPD (Lei Geral de Proteção de Dados)', 'crumbz'),
                'why'    => __('Crumbz has no Brazil mode yet. EU mode is the strictest opt-in setting available.', 'crumbz'),
            ];
        }
        if ($country === 'AU' || in_array('australia', $aud, true)) {
            $rows[] = [
                'mode'   => null,
                'title'  => __('Australia', 'crumbz'),
                'laws'   => __('Privacy Act 1988 and the Australian Privacy Principles', 'crumbz'),
                'why'    => __('Crumbz has no Australia mode yet. Confirm what the Privacy Act requires for your cookies.', 'crumbz'),
            ];
        }
        if (!$rows) {
            $rows[] = [
                'mode'   => null,
                'title'  => __('No rule matched yet', 'crumbz'),
                'laws'   => '',
                'why'    => __('Choose a country and an audience above. Until then, no mode is suggested.', 'crumbz'),
            ];
        }
        return $rows;
    }

    /** Modes ('eu', 'ca') to apply, from the recommendations. */
    public static function recommended_modes(array $loc): array {
        $modes = [];
        foreach (self::recommend($loc) as $row) {
            if ($row['mode'] !== null) {
                $modes[$row['mode']] = true;
            }
        }
        return array_keys($modes);
    }

    /** Country from the Cloudflare header on this admin request, or ''. */
    public static function cloudflare_country(): string {
        $c = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? strtoupper((string) $_SERVER['HTTP_CF_IPCOUNTRY']) : '';
        return (preg_match('/^[A-Z]{2}$/', $c) && $c !== 'XX') ? $c : '';
    }

    /** Region code from the browser language, such as 'US' from en-US. A language hint, not a location. */
    public static function language_country(): string {
        $lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
        return preg_match('/\b[a-z]{2,3}-([A-Z]{2})\b/', $lang, $m) ? $m[1] : '';
    }

    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to use the jurisdiction advisor.', 'crumbz'));
        }
        $loc   = self::get();
        $rows  = self::recommend($loc);
        $modes = self::recommended_modes($loc);
        $current = (array) Crumbz_Settings::get('jurisdictions');
        $cf = self::cloudflare_country();
        $lang = self::language_country();
        $mode_names = ['eu' => __('EU mode', 'crumbz'), 'ca' => __('California mode', 'crumbz')];

        echo '<div class="wrap crumbz-admin">';
        echo '<h1>' . esc_html__('Jurisdiction advisor', 'crumbz') . '</h1>';
        echo '<p class="crumbz-lead">' . esc_html__('Answer a few questions, and Crumbz suggests which rules to turn on. You choose whether to apply them.', 'crumbz') . '</p>';
        echo '<p class="crumbz-note">' . esc_html__('This is a starting point, not legal advice. Laws change, and the list of state laws in particular needs checking. Confirm with a qualified lawyer.', 'crumbz') . '</p>';
        $notice = isset($_GET['notice']) ? sanitize_key((string) wp_unslash($_GET['notice'])) : '';
        if ($notice === 'saved') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Location and answers saved.', 'crumbz') . '</p></div>';
        } elseif ($notice === 'applied') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Suggested rules applied. Check them in Crumbz > Settings.', 'crumbz') . '</p></div>';
        }

        echo '<h2>' . esc_html__('1. Where is the business?', 'crumbz') . '</h2>';
        echo '<p class="description">' . esc_html__('Location is stored in your WordPress database only. Nothing is sent to Crumbz or to any outside service.', 'crumbz') . '</p>';
        if ($cf !== '' || $lang !== '') {
            echo '<div class="crumbz-hints"><strong>' . esc_html__('Suggestions from this request (you still choose):', 'crumbz') . '</strong><ul>';
            if ($cf !== '') {
                echo '<li>' . esc_html(sprintf(
                    /* translators: %s: two-letter country code */
                    __('Your connection reports country %s through Cloudflare.', 'crumbz'),
                    $cf
                )) . '</li>';
            }
            if ($lang !== '') {
                echo '<li>' . esc_html(sprintf(
                    /* translators: %s: two-letter country code */
                    __('Your browser language points to %s. This is a language setting, not a location.', 'crumbz'),
                    $lang
                )) . '</li>';
            }
            echo '</ul></div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('crumbz_advisor');
        echo '<input type="hidden" name="action" value="crumbz_advisor_save">';
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="crumbz-country">' . esc_html__('Country', 'crumbz') . '</label></th><td><select id="crumbz-country" name="country">';
        foreach (self::countries() as $code => $name) {
            echo '<option value="' . esc_attr((string) $code) . '"' . selected($loc['country'], (string) $code, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="crumbz-region">' . esc_html__('State, province, or region', 'crumbz') . '</label></th><td><input id="crumbz-region" type="text" class="regular-text" name="region" value="' . esc_attr($loc['region']) . '"><p class="description">' . esc_html__('For the US, use the two-letter code, such as CA or NY.', 'crumbz') . '</p></td></tr>';
        echo '<tr><th scope="row"><label for="crumbz-postal">' . esc_html__('Postal code', 'crumbz') . '</label></th><td><input id="crumbz-postal" type="text" class="regular-text" name="postal" value="' . esc_attr($loc['postal']) . '"></td></tr>';
        echo '<tr><th scope="row"><label for="crumbz-address">' . esc_html__('Business address (optional)', 'crumbz') . '</label></th><td><textarea id="crumbz-address" class="large-text" rows="3" name="address">' . esc_textarea($loc['address']) . '</textarea></td></tr>';
        echo '</table>';

        echo '<h2>' . esc_html__('2. Who do you serve?', 'crumbz') . '</h2>';
        echo '<p class="description">' . esc_html__('Pick every group that visits the site, buys from you, or signs up. If you are not sure, pick it. The advisor can only suggest what you tell it.', 'crumbz') . '</p>';
        foreach (self::audiences() as $key => $label) {
            echo '<p><label><input type="checkbox" name="audience[]" value="' . esc_attr($key) . '"' . checked(in_array($key, $loc['audience'], true), true, false) . '> ' . esc_html($label) . '</label></p>';
        }
        submit_button(__('Save location and answers', 'crumbz'), 'primary', 'submit', false);
        echo '</form>';

        echo '<h2>' . esc_html__('Suggested rules', 'crumbz') . '</h2>';
        echo '<table class="widefat crumbz-results"><thead><tr><th>' . esc_html__('Area', 'crumbz') . '</th><th>' . esc_html__('Laws', 'crumbz') . '</th><th>' . esc_html__('Why', 'crumbz') . '</th><th>' . esc_html__('Crumbz setting', 'crumbz') . '</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $setting = $row['mode'] !== null ? $mode_names[$row['mode']] : __('Note only', 'crumbz');
            echo '<tr><td>' . esc_html($row['title']) . '</td><td>' . esc_html($row['laws']) . '</td><td>' . esc_html($row['why']) . '</td><td>' . esc_html($setting) . '</td></tr>';
        }
        echo '</tbody></table>';

        $current_names = $current ? implode(', ', array_map(static function ($m) use ($mode_names) {
            return $mode_names[$m] ?? $m;
        }, $current)) : __('None', 'crumbz');
        echo '<p>' . esc_html__('Currently on:', 'crumbz') . ' <strong>' . esc_html($current_names) . '</strong></p>';

        if ($modes) {
            $apply_names = implode(' + ', array_map(static function ($m) use ($mode_names) {
                return $mode_names[$m];
            }, $modes));
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('crumbz_advisor');
            echo '<input type="hidden" name="action" value="crumbz_advisor_apply">';
            echo '<p>' . sprintf(
                /* translators: %s: mode names, e.g. "EU mode + California mode" */
                esc_html__('Apply the suggestion: %s. Visitors are asked again if this changes the rules.', 'crumbz'),
                esc_html($apply_names)
            ) . '</p>';
            submit_button(__('Apply suggested rules', 'crumbz'), 'secondary', 'crumbz-apply', false);
            echo '</form>';
        }
        echo '</div>';
    }

    public static function handle_save(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to use the jurisdiction advisor.', 'crumbz'));
        }
        check_admin_referer('crumbz_advisor');
        update_option(self::OPTION, self::sanitize($_POST), false);
        self::redirect('saved');
    }

    public static function handle_apply(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to use the jurisdiction advisor.', 'crumbz'));
        }
        check_admin_referer('crumbz_advisor');
        $modes = self::recommended_modes(self::get());
        $settings = Crumbz_Settings::all();
        $settings['jurisdictions'] = $modes;
        update_option(Crumbz_Settings::OPTION, Crumbz_Settings::sanitize($settings));
        self::redirect('applied');
    }

    private static function redirect(string $notice): void {
        wp_safe_redirect(add_query_arg(['page' => 'crumbz-advisor', 'notice' => $notice], admin_url('admin.php')));
        exit;
    }
}
