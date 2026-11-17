<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Draft text for the three policy pages. Fields the site owner must fill in
 * appear as [bracketed] text. The copy is written for visitors, not for the admin.
 */
class Crumbz_Templates {

    /**
     * @param array $a        Wizard answers.
     * @param array $detected Rows from Crumbz_Detect::scan().
     * @param int   $lifetime Cookie lifetime in days.
     * @return array<string, array{title: string, content: string}>
     */
    public static function build(array $a, array $detected, int $lifetime): array {
        $name   = trim((string) ($a['business_name'] ?? '')) ?: '[Business legal name]';
        $email  = trim((string) ($a['contact_email'] ?? '')) ?: '[contact email]';
        $region = trim((string) ($a['region'] ?? '')) ?: '[country and state or province]';
        $site   = (string) get_bloginfo('name');
        $url    = (string) home_url('/');
        $jur    = array_values((array) ($a['jurisdictions'] ?? []));
        $type   = (string) ($a['business_type'] ?? 'other');
        $yes    = static function (string $key) use ($a): bool {
            return (($a[$key] ?? 'no') === 'yes');
        };
        $eu = in_array('eu', $jur, true);
        $ca = in_array('ca', $jur, true);

        $tools = static function (string $cat) use ($detected): string {
            $labels = [];
            foreach ($detected as $row) {
                if (($row['category'] ?? '') === $cat) {
                    $labels[] = esc_html((string) $row['label']);
                }
            }
            return $labels ? implode(', ', $labels) : '';
        };

        $name_h   = esc_html($name);
        $email_h  = esc_html($email);
        $region_h = esc_html($region);
        $site_h   = esc_html($site);

        return [
            'cookie-policy' => [
                'title'   => __('Cookie Policy', 'crumbz'),
                'content' => self::cookie_policy($name_h, $email_h, $site_h, $lifetime, $yes, $tools, $type),
            ],
            'privacy-policy' => [
                'title'   => __('Privacy Policy', 'crumbz'),
                'content' => self::privacy_policy($name_h, $email_h, $site_h, $region_h, $yes, $eu, $ca, $tools),
            ],
            'terms' => [
                'title'   => __('Terms and Conditions', 'crumbz'),
                'content' => self::terms($name_h, $email_h, $site_h, $url, $region_h, $yes),
            ],
        ];
    }

    private static function p(string $html): string {
        return '<p>' . $html . '</p>';
    }

    private static function h(string $text): string {
        return '<h2>' . esc_html($text) . '</h2>';
    }

    private static function ul(array $items): string {
        return '<ul>' . implode('', array_map(static function ($i) {
            return '<li>' . $i . '</li>';
        }, $items)) . '</ul>';
    }

    private static function cookie_policy(string $name, string $email, string $site, int $lifetime, callable $yes, callable $tools, string $type): string {
        $c = [];
        $c[] = self::p(sprintf(
            /* translators: %s: business name */
            esc_html__('This Cookie Policy explains how %s uses cookies and similar tools on %s, and how you can change your choices.', 'crumbz'),
            $name,
            $site
        ));
        $c[] = self::h(__('What cookies are', 'crumbz'));
        $c[] = self::p(esc_html__('Cookies are small text files that a website saves on your device. Some cookies keep the site working. Others remember your choices, measure visits, or show ads.', 'crumbz'));

        $c[] = self::h(__('Cookies we use', 'crumbz'));
        $items = [];
        $items[] = '<strong>' . esc_html__('Necessary (always on):', 'crumbz') . '</strong> '
            . sprintf(esc_html__('the crumbz_consent cookie records your cookie choices. It lasts %d days.', 'crumbz'), $lifetime);
        if ($yes('has_accounts')) {
            $items[] = '<strong>' . esc_html__('Login:', 'crumbz') . '</strong> ' . esc_html__('cookies keep you signed in to your account.', 'crumbz');
        }
        if ($type === 'shop' || $yes('takes_payments')) {
            $items[] = '<strong>' . esc_html__('Shopping and checkout:', 'crumbz') . '</strong> ' . esc_html__('cookies keep your cart and checkout steps working.', 'crumbz');
        }
        $c[] = self::ul($items);

        $c[] = self::h(__('Optional cookies', 'crumbz'));
        $opt = [];
        $opt[] = '<strong>' . esc_html__('Preferences:', 'crumbz') . '</strong> ' . esc_html__('remember settings such as language or layout.', 'crumbz')
            . ($tools('preferences') !== '' ? ' ' . esc_html__('Tools on this site:', 'crumbz') . ' ' . $tools('preferences') . '.' : '');
        $opt[] = '<strong>' . esc_html__('Settings icon position:', 'crumbz') . '</strong> ' . esc_html__('the crumbz_icon cookie remembers where you placed the Cookie settings icon. It is set only if you accept Preferences, and it lasts for the same period as the consent record.', 'crumbz');
        $opt[] = '<strong>' . esc_html__('Theme:', 'crumbz') . '</strong> ' . esc_html__('the crumbz_theme cookie remembers whether you chose the light or dark display of the cookie banner. It is set only if you accept Preferences.', 'crumbz');
        $stat = $tools('statistics');
        $opt[] = '<strong>' . esc_html__('Statistics:', 'crumbz') . '</strong> ' . esc_html__('count visits and show how pages are used.', 'crumbz')
            . ' ' . esc_html__('Tools:', 'crumbz') . ' ' . ($stat !== '' ? $stat : '[name of your analytics tool]') . '.';
        $mkt = $tools('marketing');
        $opt[] = '<strong>' . esc_html__('Marketing:', 'crumbz') . '</strong> ' . esc_html__('used for advertising, retargeting, and social media.', 'crumbz')
            . ' ' . esc_html__('Tools:', 'crumbz') . ' ' . ($mkt !== '' ? $mkt : '[name of your advertising or social tool]') . '.';
        $c[] = self::ul($opt);
        $c[] = self::p(esc_html__('Optional cookies are set only after you accept the matching category.', 'crumbz'));

        $c[] = self::h(__('Your choices', 'crumbz'));
        $c[] = self::p(esc_html__('Use the Cookie settings icon on this site to accept, reject, or change your choices at any time. You can also clear cookies in your browser settings. Clearing cookies resets your choices here, and you will see the banner again.', 'crumbz'));

        $c[] = self::h(__('Changes to this policy', 'crumbz'));
        $c[] = self::p(esc_html__('We may update this policy. When we make a material change, we change the version number shown in the cookie settings, and we ask you to choose again.', 'crumbz'));

        $c[] = self::h(__('Contact', 'crumbz'));
        $c[] = self::p(esc_html__('Questions about this policy: ', 'crumbz') . '<a href="mailto:' . $email . '">' . $email . '</a>');

        return wp_kses_post(implode("\n", $c));
    }

    private static function privacy_policy(string $name, string $email, string $site, string $region, callable $yes, bool $eu, bool $ca, callable $tools): string {
        $c = [];
        $c[] = self::p(sprintf(
            /* translators: %s: business name */
            esc_html__('%s respects your privacy. This policy explains what information we collect on %s, why we collect it, and what choices you have.', 'crumbz'),
            $name,
            $site
        ));

        $c[] = self::h(__('Information we collect', 'crumbz'));
        $items = [];
        if ($yes('uses_forms')) {
            $items[] = esc_html__('Information you send through our forms, such as your name, email address, and message.', 'crumbz');
        }
        if ($yes('uses_newsletter')) {
            $items[] = esc_html__('Your email address, if you sign up for our newsletter or promotions.', 'crumbz');
        }
        if ($yes('has_accounts')) {
            $items[] = esc_html__('Account details, such as username, email address, and profile information.', 'crumbz');
        }
        if ($yes('takes_payments')) {
            $items[] = esc_html__('Order details. Payments are handled by [payment provider name], which receives the payment details you enter.', 'crumbz');
        }
        if ($yes('uses_analytics')) {
            $items[] = esc_html__('Usage information from analytics tools, if you allow statistics cookies.', 'crumbz');
        }
        if ($yes('uses_marketing')) {
            $items[] = esc_html__('Advertising and social media information, if you allow marketing cookies.', 'crumbz');
        }
        if (!$items) {
            $items[] = esc_html__('Basic technical information about your visit, such as your browser type and pages viewed.', 'crumbz');
        }
        $c[] = self::ul($items);

        $c[] = self::h(__('How we use information', 'crumbz'));
        $c[] = self::ul([
            esc_html__('To run and improve this site.', 'crumbz'),
            esc_html__('To answer your questions and respond to requests.', 'crumbz'),
            esc_html__('To send newsletters and promotions, only if you signed up for them.', 'crumbz'),
            esc_html__('To complete orders and provide support, where the site sells products or services.', 'crumbz'),
        ]);

        if ($eu) {
            $c[] = self::h(__('Legal basis (EU and UK visitors)', 'crumbz'));
            $c[] = self::p(esc_html__('We rely on your consent for optional cookies and marketing emails. We rely on performing a contract with you for orders, and on our legitimate interests for running and securing the site.', 'crumbz'));
        }

        $c[] = self::h(__('Sharing information', 'crumbz'));
        $c[] = self::p(esc_html__('We share information only with service providers that help us run this site, such as hosting, email, analytics, and payment providers. They may use it only to provide their service to us.', 'crumbz'));
        if ($ca) {
            $c[] = self::p(esc_html__('California: we [do not sell or share / sell or share] personal information for advertising. [Edit this sentence to match how your advertising tools work.]', 'crumbz'));
        }

        $c[] = self::h(__('How long we keep information', 'crumbz'));
        $c[] = self::p(esc_html__('We keep personal information for [retention period], or for as long as the law requires, whichever is longer.', 'crumbz'));

        $c[] = self::h(__('Your rights', 'crumbz'));
        $rights = [esc_html__('Ask for a copy of the information we hold about you.', 'crumbz'), esc_html__('Ask us to correct or delete it.', 'crumbz')];
        if ($eu) {
            $rights[] = esc_html__('Object to or restrict some processing, and ask for your information in a portable format.', 'crumbz');
            $rights[] = esc_html__('Withdraw consent at any time, using the Cookie settings icon.', 'crumbz');
            $rights[] = esc_html__('Complain to your local data protection authority.', 'crumbz');
        }
        if ($ca) {
            $rights[] = esc_html__('California residents: know what we collect, opt out of sale or sharing, and ask us to delete information. We do not discriminate against you for using these rights.', 'crumbz');
        }
        $c[] = self::ul($rights);
        $c[] = self::p(esc_html__('To use these rights, email ', 'crumbz') . '<a href="mailto:' . $email . '">' . $email . '</a>' . esc_html__('. We may ask you to confirm who you are before we respond.', 'crumbz'));

        $c[] = self::h(__('Children', 'crumbz'));
        $c[] = self::p(esc_html__('This site is not directed to children under [13, or 16 for EU visitors]. If you think a child has sent us personal information, contact us and we will delete it.', 'crumbz'));

        $c[] = self::h(__('Changes to this policy', 'crumbz'));
        $c[] = self::p(esc_html__('We may update this policy. The date of the latest version is shown below.', 'crumbz'));

        $c[] = self::h(__('Contact', 'crumbz'));
        $c[] = self::p($name . ' · <a href="mailto:' . $email . '">' . $email . '</a> · ' . $region);

        return wp_kses_post(implode("\n", $c));
    }

    private static function terms(string $name, string $email, string $site, string $url, string $region, callable $yes): string {
        $c = [];
        $c[] = self::p(sprintf(
            /* translators: 1: business name, 2: site name, 3: site address */
            esc_html__('These terms apply when you use %2$s at %3$s. %1$s provides the site. By using the site, you agree to these terms. If you do not agree, please do not use the site.', 'crumbz'),
            $name,
            $site,
            esc_html($url)
        ));

        $c[] = self::h(__('Using the site', 'crumbz'));
        $c[] = self::p(esc_html__('You may use the site for lawful purposes only. You may not interfere with how the site works, copy content for commercial use without permission, or attempt to access areas you are not allowed to use.', 'crumbz'));

        if ($yes('has_accounts')) {
            $c[] = self::h(__('Accounts', 'crumbz'));
            $c[] = self::p(esc_html__('You are responsible for keeping your login details safe and for activity on your account. Tell us as soon as you think someone else has used it.', 'crumbz'));
        }
        if ($yes('takes_payments')) {
            $c[] = self::h(__('Orders and payments', 'crumbz'));
            $c[] = self::p(esc_html__('Prices, product details, delivery, and refunds are set out at the time of purchase. [Link to your refund policy.] We may refuse or cancel an order where we have reasonable grounds to do so.', 'crumbz'));
        }

        $c[] = self::h(__('Content and intellectual property', 'crumbz'));
        $c[] = self::p(sprintf(
            /* translators: %s: business name */
            esc_html__('Text, images, logos, and other content on the site belong to %s or its licensors, unless we say otherwise.', 'crumbz'),
            $name
        ));

        $c[] = self::h(__('Links to other sites', 'crumbz'));
        $c[] = self::p(esc_html__('The site may link to other websites and tools. We do not control them and are not responsible for their content or practices.', 'crumbz'));

        $c[] = self::h(__('Disclaimers', 'crumbz'));
        $c[] = self::p(esc_html__('The site and its content are provided as they are. To the extent the law allows, we make no promises about accuracy, availability, or fitness for a particular purpose.', 'crumbz'));

        $c[] = self::h(__('Limitation of liability', 'crumbz'));
        $c[] = self::p(esc_html__('To the extent the law allows, we are not liable for indirect or consequential losses arising from your use of the site. [Insert any limit on total liability that applies to your business.]', 'crumbz'));

        $c[] = self::h(__('Governing law', 'crumbz'));
        $c[] = self::p(esc_html__('These terms are governed by the laws of [state or country]. The courts there have jurisdiction, unless the law where you live gives you other rights.', 'crumbz'));

        $c[] = self::h(__('Changes to these terms', 'crumbz'));
        $c[] = self::p(esc_html__('We may update these terms. When we do, we change the version number shown in the cookie settings, and we ask you to accept the new version.', 'crumbz'));

        $c[] = self::h(__('Contact', 'crumbz'));
        $c[] = self::p(esc_html__('Questions about these terms: ', 'crumbz') . '<a href="mailto:' . $email . '">' . $email . '</a>' . ($region !== '' ? ' · ' . $region : ''));

        return wp_kses_post(implode("\n", $c));
    }
}
