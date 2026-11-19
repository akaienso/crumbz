<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom web font uploads. Files live in uploads/crumbz-fonts and are removed on uninstall.
 * Only WOFF2 and WOFF files pass, checked by extension and by the file's signature bytes.
 */
class Crumbz_Fonts {

    const DIR_NAME   = 'crumbz-fonts';
    const MAX_BYTES  = 2097152; // 2 MB

    public static function init(): void {
        add_action('admin_post_crumbz_font_upload', [__CLASS__, 'handle_upload']);
        add_action('admin_post_crumbz_font_delete', [__CLASS__, 'handle_delete']);
    }

    /** Absolute folder path for uploads, without a trailing slash. */
    public static function dir(): string {
        $uploads = wp_upload_dir(null, false);
        return rtrim((string) $uploads['basedir'], '/') . '/' . self::DIR_NAME;
    }

    public static function url(string $file): string {
        $uploads = wp_upload_dir(null, false);
        return rtrim((string) $uploads['baseurl'], '/') . '/' . self::DIR_NAME . '/' . rawurlencode($file);
    }

    public static function handle_upload(): void {
        check_admin_referer('crumbz_font_upload');
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage Crumbz.', 'crumbz'));
        }

        $file = $_FILES['crumbz_font'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $name = isset($_POST['font_name']) ? sanitize_text_field(wp_unslash($_POST['font_name'])) : '';
        $error = self::validate($file);
        if ($error !== '') {
            self::back($error);
        }

        $ext  = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $name = $name !== '' ? $name : (string) pathinfo((string) $file['name'], PATHINFO_FILENAME);
        $slug = sanitize_title($name);
        if ($slug === '') {
            self::back(__('Give the font a name that has letters or numbers in it.', 'crumbz'));
        }

        $s = Crumbz_Settings::all();
        $fonts = Crumbz_Settings::custom_font_list($s);
        if (isset($fonts[$slug]) || in_array($slug, array_keys(Crumbz_Settings::bundled_fonts()), true)) {
            self::back(__('A font with that name already exists. Choose another name.', 'crumbz'));
        }
        if (count($fonts) >= 10) {
            self::back(__('You can upload up to 10 fonts. Remove one first.', 'crumbz'));
        }

        $dir = self::dir();
        if (!wp_mkdir_p($dir)) {
            self::back(__('The uploads folder is not writable.', 'crumbz'));
        }
        self::guard_folder($dir);

        $stored = $slug . '.' . $ext;
        if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $stored)) {
            self::back(__('The font could not be saved.', 'crumbz'));
        }

        $fonts[$slug] = ['slug' => $slug, 'name' => $name, 'file' => $stored, 'format' => $ext === 'woff2' ? 'woff2' : 'woff'];
        self::save_fonts($s, $fonts);
        self::back(sprintf(
            /* translators: %s: font name */
            __('Uploaded %s. Choose it under Typefaces.', 'crumbz'),
            $name
        ), 'updated');
    }

    public static function handle_delete(): void {
        check_admin_referer('crumbz_font_delete');
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage Crumbz.', 'crumbz'));
        }

        $slug = isset($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug'])) : '';
        $s = Crumbz_Settings::all();
        $fonts = Crumbz_Settings::custom_font_list($s);
        if (!isset($fonts[$slug])) {
            self::back(__('That font is no longer in the list.', 'crumbz'));
        }

        $path = self::dir() . '/' . basename((string) $fonts[$slug]['file']);
        if (is_file($path)) {
            unlink($path);
        }
        unset($fonts[$slug]);

        $fallback = 'sans';
        foreach (['primary', 'secondary'] as $role) {
            if (($s['fonts'][$role] ?? '') === 'custom:' . $slug) {
                $s['fonts'][$role] = $fallback;
            }
        }
        self::save_fonts($s, $fonts);
        self::back(__('Font removed.', 'crumbz'), 'updated');
    }

    /** Returns an error message, or an empty string when the file can be used. */
    private static function validate($file): string {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return __('Choose a WOFF2 or WOFF font file to upload.', 'crumbz');
        }
        if (!is_uploaded_file((string) $file['tmp_name'])) {
            return __('The upload was not valid.', 'crumbz');
        }
        if ((int) $file['size'] > self::MAX_BYTES) {
            return __('Fonts must be 2 MB or smaller.', 'crumbz');
        }
        $ext = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['woff2', 'woff'], true)) {
            return __('Only WOFF2 and WOFF fonts can be uploaded.', 'crumbz');
        }
        $head = (string) file_get_contents((string) $file['tmp_name'], false, null, 0, 4);
        $magic = $ext === 'woff2' ? 'wOF2' : 'wOFF';
        if ($head !== $magic) {
            return __('That file is not a valid font of the type its name says.', 'crumbz');
        }
        return '';
    }

    /** Keeps directory listings and direct PHP access out of the font folder. */
    private static function guard_folder(string $dir): void {
        if (!file_exists($dir . '/index.php')) {
            file_put_contents($dir . '/index.php', "<?php\n// Silence is golden.\n");
        }
        if (!file_exists($dir . '/.htaccess')) {
            file_put_contents($dir . '/.htaccess', "Options -Indexes\n<FilesMatch \"\\.php$\">\n  Require all denied\n</FilesMatch>\n");
        }
    }

    private static function save_fonts(array $s, array $fonts): void {
        $s['custom_fonts'] = array_values($fonts);
        update_option(Crumbz_Settings::OPTION, $s, false);
    }

    private static function back(string $message, string $type = 'error'): void {
        $url = add_query_arg(['page' => 'crumbz', 'crumbz_notice' => rawurlencode($message), 'crumbz_type' => $type], admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
    }
}
