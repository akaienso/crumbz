<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Step-by-step policy wizard. It asks one question per screen, then creates
 * three DRAFT pages (cookie policy, privacy policy, terms). Nothing publishes.
 */
class Crumbz_Wizard {

    const ANSWERS = 'crumbz_wizard_answers';
    const PAGES   = 'crumbz_wizard_pages';

    public static function steps(): array {
        $yes_no = ['yes' => __('Yes', 'crumbz'), 'no' => __('No', 'crumbz')];
        return [
            'business_name' => [
                'question' => __('What is the legal name of the business or person who runs this site?', 'crumbz'),
                'type'     => 'text',
                'default'  => (string) get_bloginfo('name'),
            ],
            'contact_email' => [
                'question' => __('Which email address should visitors use for privacy questions?', 'crumbz'),
                'type'     => 'email',
                'default'  => (string) get_option('admin_email'),
            ],
            'business_type' => [
                'question' => __('What kind of site is this?', 'crumbz'),
                'type'     => 'select',
                'options'  => [
                    'blog'        => __('Blog or content site', 'crumbz'),
                    'shop'        => __('Online store', 'crumbz'),
                    'services'    => __('Services or booking', 'crumbz'),
                    'membership'  => __('Membership or community', 'crumbz'),
                    'nonprofit'   => __('Nonprofit or association', 'crumbz'),
                    'other'       => __('Something else', 'crumbz'),
                ],
                'default'  => 'other',
            ],
            'uses_analytics' => [
                'question' => __('Do you use analytics, such as Google Analytics, Site Kit, Jetpack Stats, or Matomo?', 'crumbz'),
                'type'     => 'yesno',
                'default'  => 'no',
            ],
            'uses_marketing' => [
                'question' => __('Do you run ads, retargeting, or social media tracking pixels?', 'crumbz'),
                'type'     => 'yesno',
                'default'  => 'no',
            ],
            'uses_newsletter' => [
                'question' => __('Do you collect email addresses for a newsletter or promotions?', 'crumbz'),
                'type'     => 'yesno',
                'default'  => 'no',
            ],
            'uses_forms' => [
                'question' => __('Do visitors send you information through contact, quote, or booking forms?', 'crumbz'),
                'type'     => 'yesno',
                'default'  => 'no',
            ],
            'has_accounts' => [
                'question' => __('Can visitors create an account or log in?', 'crumbz'),
                'type'     => 'yesno',
                'default'  => 'no',
            ],
            'takes_payments' => [
                'question' => __('Do you sell products or take payments on the site?', 'crumbz'),
                'type'     => 'yesno',
                'default'  => 'no',
            ],
            'jurisdictions' => [
                'question' => __('Which privacy laws should the drafts cover? Pick all that apply.', 'crumbz'),
                'type'     => 'multi',
                'options'  => [
                    'eu' => __('European Union (GDPR and ePrivacy)', 'crumbz'),
                    'ca' => __('California (CCPA and CPRA)', 'crumbz'),
                ],
                'default'  => [],
            ],
            'region' => [
                'question' => __('Which country, and which state or province, is the business based in?', 'crumbz'),
                'type'     => 'text',
                'default'  => '',
            ],
        ];
    }

    public static function answers(): array {
        $saved = get_option(self::ANSWERS, []);
        return is_array($saved) ? $saved : [];
    }

    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to use the Crumbz wizard.', 'crumbz'));
        }
        $steps = self::steps();
        $keys  = array_keys($steps);
        $step  = isset($_GET['step']) ? sanitize_key((string) wp_unslash($_GET['step'])) : '1';

        echo '<div class="wrap crumbz-admin crumbz-wizard">';
        echo '<h1>' . esc_html__('Policy wizard', 'crumbz') . '</h1>';
        echo '<p class="crumbz-note">' . esc_html__('The wizard creates draft pages only. Nothing is published. The drafts are a starting point and are not legal advice, so have a qualified lawyer review them before you publish.', 'crumbz') . '</p>';

        if ($step === 'summary') {
            self::render_summary($steps);
        } elseif ($step === 'done') {
            self::render_done();
        } else {
            $n = max(1, min(count($keys), absint($step)));
            self::render_step($n, $steps, $keys);
        }
        echo '</div>';
    }

    private static function render_step(int $n, array $steps, array $keys): void {
        $key     = $keys[$n - 1];
        $def     = $steps[$key];
        $answers = self::answers();
        $value   = array_key_exists($key, $answers) ? $answers[$key] : $def['default'];
        $total   = count($keys);

        echo '<p class="crumbz-progress">' . esc_html(sprintf(
            /* translators: 1: current question number, 2: total questions */
            __('Question %1$d of %2$d', 'crumbz'),
            $n,
            $total
        )) . '</p>';
        echo '<h2>' . esc_html($def['question']) . '</h2>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('crumbz_wizard');
        echo '<input type="hidden" name="action" value="crumbz_wizard_save">';
        echo '<input type="hidden" name="step" value="' . esc_attr((string) $n) . '">';

        switch ($def['type']) {
            case 'text':
            case 'email':
                echo '<p><input type="' . esc_attr($def['type']) . '" class="regular-text" name="answer" value="' . esc_attr((string) $value) . '"></p>';
                break;
            case 'select':
                echo '<p><select name="answer">';
                foreach ($def['options'] as $opt => $label) {
                    echo '<option value="' . esc_attr((string) $opt) . '"' . selected((string) $value, (string) $opt, false) . '>' . esc_html($label) . '</option>';
                }
                echo '</select></p>';
                break;
            case 'yesno':
                foreach (['yes' => __('Yes', 'crumbz'), 'no' => __('No', 'crumbz')] as $opt => $label) {
                    echo '<p><label><input type="radio" name="answer" value="' . esc_attr($opt) . '"' . checked((string) $value, $opt, false) . '> ' . esc_html($label) . '</label></p>';
                }
                break;
            case 'multi':
                foreach ($def['options'] as $opt => $label) {
                    $checked = in_array($opt, (array) $value, true);
                    echo '<p><label><input type="checkbox" name="answer[]" value="' . esc_attr((string) $opt) . '"' . checked($checked, true, false) . '> ' . esc_html($label) . '</label></p>';
                }
                break;
        }

        echo '<p class="crumbz-actions">';
        if ($n > 1) {
            echo '<button type="submit" class="button" name="intent" value="back">' . esc_html__('Back', 'crumbz') . '</button> ';
        }
        echo '<button type="submit" class="button button-primary" name="intent" value="next">' . esc_html__('Next', 'crumbz') . '</button>';
        echo '</p></form>';
    }

    private static function render_summary(array $steps): void {
        $answers = self::answers();
        echo '<h2>' . esc_html__('Check your answers', 'crumbz') . '</h2>';
        echo '<table class="widefat crumbz-summary"><tbody>';
        foreach ($steps as $key => $def) {
            $value = array_key_exists($key, $answers) ? $answers[$key] : $def['default'];
            if (is_array($value)) {
                $parts = [];
                foreach ($value as $v) {
                    $parts[] = $def['options'][$v] ?? $v;
                }
                $display = $parts ? implode(', ', $parts) : __('None', 'crumbz');
            } elseif (isset($def['options'][$value])) {
                $display = $def['options'][$value];
            } else {
                $display = $value === '' ? __('Not answered', 'crumbz') : (string) $value;
            }
            echo '<tr><th scope="row">' . esc_html($def['question']) . '</th><td>' . esc_html((string) $display) . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="crumbz-actions">';
        wp_nonce_field('crumbz_wizard');
        echo '<input type="hidden" name="action" value="crumbz_wizard_save">';
        echo '<input type="hidden" name="step" value="0">';
        echo '<button type="submit" class="button button-primary" name="intent" value="generate">' . esc_html__('Create draft pages', 'crumbz') . '</button>';
        echo '</form>';
    }

    private static function render_done(): void {
        $pages = get_option(self::PAGES, []);
        echo '<h2>' . esc_html__('Draft pages created', 'crumbz') . '</h2>';
        echo '<p>' . esc_html__('The three documents are set in Settings. Each draft is still a draft, so visitors cannot see it yet. Fill in every [bracketed] field, then publish.', 'crumbz') . '</p>';
        echo '<ul class="crumbz-list">';
        foreach ((array) $pages as $doc_id => $post_id) {
            $edit = get_edit_post_link((int) $post_id, 'raw');
            if (!$edit) {
                continue;
            }
            echo '<li><a href="' . esc_url($edit) . '">' . esc_html(get_the_title((int) $post_id)) . '</a></li>';
        }
        echo '</ul>';
        echo '<p><a class="button" href="' . esc_url(admin_url('admin.php?page=crumbz')) . '">' . esc_html__('Go to Crumbz settings', 'crumbz') . '</a></p>';
    }

    public static function handle(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to use the Crumbz wizard.', 'crumbz'));
        }
        check_admin_referer('crumbz_wizard');

        $intent = isset($_POST['intent']) ? sanitize_key((string) wp_unslash($_POST['intent'])) : 'next';
        $step   = isset($_POST['step']) ? absint($_POST['step']) : 0;

        if ($intent === 'generate') {
            self::generate();
            wp_safe_redirect(self::url('done'));
            exit;
        }

        $steps = self::steps();
        $keys  = array_keys($steps);
        $total = count($keys);

        if ($step >= 1 && $step <= $total) {
            $key     = $keys[$step - 1];
            $answers = self::answers();
            $answers[$key] = self::clean(wp_unslash($_POST['answer'] ?? null), $steps[$key]);
            update_option(self::ANSWERS, $answers, false);
        }

        if ($intent === 'back') {
            $next = max(1, $step - 1);
        } else {
            $next = $step + 1 > $total ? 'summary' : $step + 1;
        }
        wp_safe_redirect(self::url($next));
        exit;
    }

    private static function clean($raw, array $def) {
        switch ($def['type']) {
            case 'email':
                return sanitize_email((string) $raw);
            case 'select':
                $value = sanitize_key((string) $raw);
                return array_key_exists($value, $def['options']) ? $value : 'other';
            case 'yesno':
                return $raw === 'yes' ? 'yes' : 'no';
            case 'multi':
                $picked = array_map('sanitize_key', (array) $raw);
                return array_values(array_intersect(array_keys($def['options']), $picked));
            default:
                return sanitize_text_field((string) $raw);
        }
    }

    private static function url($step): string {
        return add_query_arg(['page' => 'crumbz-wizard', 'step' => (string) $step], admin_url('admin.php'));
    }

    /** Creates or refreshes the three draft pages and points the settings at them. */
    private static function generate(): void {
        $built    = Crumbz_Templates::build(self::answers(), Crumbz_Detect::scan(), (int) Crumbz_Settings::get('cookie_lifetime_days'));
        $pages    = get_option(self::PAGES, []);
        $pages    = is_array($pages) ? $pages : [];
        $settings = Crumbz_Settings::all();

        foreach ($built as $doc_id => $page) {
            $args = [
                'post_type'    => 'page',
                'post_status'  => 'draft',
                'post_title'   => $page['title'],
                'post_content' => $page['content'],
            ];
            // Always a new draft: an earlier draft may already have the owner's edits, so it is never overwritten.
            $id = wp_insert_post($args, true);
            if (is_wp_error($id) || !$id) {
                continue;
            }
            $pages[$doc_id] = (int) $id;
            if (isset($settings['documents'][$doc_id])) {
                $settings['documents'][$doc_id]['page_id'] = (int) $id;
            }
        }

        update_option(self::PAGES, $pages, false);
        update_option(Crumbz_Settings::OPTION, Crumbz_Settings::sanitize($settings));
    }
}
