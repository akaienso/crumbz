# Hooks and filters

Crumbz is early (0.x), and its extension points are small. Anything not listed here is internal and may change without notice.

## Filters

### `crumbz_latest_release`

Lets code supply the "latest release" without a network call. The updater uses this in tests.

- Argument: `null`.
- Return: `null` to use GitHub, or an array with the keys `version` (for example `"0.2.0"`), `package` (a URL that starts with `https://github.com/akaienso/crumbz/releases/download/`), `url`, and `changelog`.

```php
add_filter('crumbz_latest_release', static function () {
    return null; // use GitHub
});
```

Packages from any other host are rejected.

## Actions and hooks Crumbz uses

| Hook | Used by | Purpose |
|---|---|---|
| `wp_enqueue_scripts` | `Crumbz_Frontend::enqueue` | Loads the front-end assets. |
| `wp_head` (priority 1) | `Crumbz_Frontend::head_snippets` | Prints the category snippets, live or inert. |
| `script_loader_tag` | `Crumbz_Scripts::filter_tag` | Makes vendor scripts inert until allowed. |
| `pre_set_site_transient_update_plugins` | `Crumbz_Updater::check` | Adds Crumbz to WordPress's update list. |
| `plugins_api` | `Crumbz_Updater::plugin_info` | Supplies the "View details" popup. |
| `auto_update_plugin` | `Crumbz_Updater::auto_update` | Turns automatic updates on or off, per the site setting. |
| `admin_menu`, `admin_init`, `admin_enqueue_scripts` | `Crumbz_Admin` | The admin screens and styles. |
| `admin_post_crumbz_wizard_save` | `Crumbz_Wizard::handle` | Saves a wizard answer or creates the drafts. |
| `admin_post_crumbz_advisor_save` and `admin_post_crumbz_advisor_apply` | `Crumbz_Advisor` | Saves the location, and applies the suggested rules. |

## Reading consent from your own code

```php
if (Crumbz_Consent::allowed('statistics')) {
    // safe to print the analytics snippet
}
```

`Crumbz_Consent::allowed()` takes `preferences`, `statistics`, `marketing`, or `necessary`. It returns true for `necessary` always.

Only use this inside the plugin's own code path. The class is internal, and its name and signature may change before 1.0.
