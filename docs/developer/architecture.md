# Architecture

Crumbz is a WordPress plugin with a PHP back end and a small vanilla JavaScript front end. It has no build step and no runtime dependencies.

## Files

| Path | What it does |
|---|---|
| `crumbz.php` | Plugin header, constants, class loading, and startup. |
| `includes/class-crumbz-settings.php` | Defaults, sanitizing, and the policy revision. Owns the `crumbz_settings` option. |
| `includes/class-crumbz-consent.php` | Reads the consent record cookie on the server and answers "is this category allowed?" |
| `includes/class-crumbz-scripts.php` | Filters enqueued script tags, and rewrites snippet tags, so vendor scripts stay inert until allowed. |
| `includes/class-crumbz-detect.php` | A built-in map of plugins known to set cookies, with suggested categories. |
| `includes/class-crumbz-frontend.php` | Enqueues the front-end assets, builds the config object, and prints head snippets. |
| `includes/class-crumbz-admin.php` | The Settings screen and the admin menu. |
| `includes/class-crumbz-advisor.php` | The Jurisdiction advisor: location form, recommendations, and apply. |
| `includes/class-crumbz-wizard.php` | The policy wizard: step questions, and creation of draft pages. |
| `includes/class-crumbz-templates.php` | Draft text for the Cookie Policy, Privacy Policy, and Terms pages. |
| `includes/class-crumbz-updater.php` | Reads the latest GitHub release and feeds WordPress's plugin update system. |
| `assets/js/frontend.js` | Builds the banner, the settings dialog, and the draggable icon. Writes the consent record and the preference cookies. |
| `assets/css/frontend.css` | Front-end styles, with the theme tokens. |
| `assets/css/admin.css` | Admin styles. The admin uses no inline scripts or styles. |
| `uninstall.php` | Deletes Crumbz options when the plugin is deleted. Draft pages are kept. |

## Request flow

1. On every front-end page, `Crumbz_Frontend::enqueue()` loads `frontend.js` and passes a config object through `wp_add_inline_script()`. The config holds the banner text, the documents, the jurisdictions, the revision, and whether the Global Privacy Control header was sent.
2. `Crumbz_Scripts::filter_tag()` checks each enqueued script's URL against the rules. A matching script is always printed as `type="text/plain" data-crumbz-category="..."`, whatever the visitor chose. Head snippets are always printed inside an inert `<template data-crumbz-category="...">`.
3. `frontend.js` runs on the page. With no valid consent record, it shows the banner. With a valid record, it shows the icon and restores the icon position, if Preferences is allowed.
4. When the visitor chooses, `frontend.js` writes the `crumbz_consent` cookie. It re-creates each allowed script, and moves each allowed snippet out of its template into the page.
5. Because the server output never depends on the cookie, a cached copy of a page is correct for every visitor. The only request header the output depends on is `Sec-GPC`, and `Vary: Sec-GPC` is sent for that reason.

## Settings storage

- `crumbz_settings`: one array, with all the settings. Saved through the WordPress Settings API.
- `crumbz_policy_revision`: an integer. It goes up whenever a consent-relevant setting changes, so visitors are asked again.
- `crumbz_policy_fingerprint`: a hash of the active documents and their versions, plus the jurisdictions. Used to detect a change.
- `crumbz_business_location`: the Jurisdiction advisor's answers.
- `crumbz_wizard_answers` and `crumbz_wizard_pages`: the wizard's answers and the IDs of the draft pages it created.

Crumbz stores no visitor data in the database. There are no consent logs and no IP addresses.

## Why the front end builds its own DOM

The banner and icon are created in JavaScript, not printed by PHP. That keeps the markup out of theme and page-builder output, avoids conflicts with caching plugins, and means no consent UI appears for visitors whose browser cannot run JavaScript. Without JavaScript, nothing is recorded and nothing optional runs.
