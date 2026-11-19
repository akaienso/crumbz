# Setup and settings

## Install

1. Download `crumbz.zip` from the [latest release](https://github.com/akaienso/crumbz/releases/latest).
2. In WordPress, go to **Plugins > Add New > Upload Plugin**, choose the zip, and install it.
3. Activate **Crumbz**.

Requirements: WordPress 6.0 or newer, PHP 7.4 or newer.

## Settings

Go to **Crumbz > Settings**.

| Setting | What it does |
|---|---|
| Crumbz is on | Shows the banner and icon, and blocks optional scripts. Off means Crumbz does nothing on the site. |
| Automatic updates | Installs new Crumbz releases on WordPress's schedule. Turn it off to update by hand. |
| Banner position | Bottom, top bar, or a centered panel. |
| Banner shape | At the bottom: a floating card in the corner (default), or a full-width bar. |
| Settings icon position | Bottom left, bottom center, bottom right, top left, top center, or top right. Visitors can drag it anywhere. |
| Default theme | Auto (follows the visitor's system), light, or dark. |
| Cookie lifetime | How many days a visitor's choice is remembered. Default 180. |
| Jurisdictions | EU and California rules. See [jurisdictions and the advisor](jurisdictions-and-advisor.md). |
| Documents | Link your Cookie Policy, Privacy Policy, Terms, and up to three custom documents. Each has a version number. |
| Plugins that set cookies | Crumbz lists active plugins it knows set cookies, and suggests a category for each. Change a category, or ignore a plugin. |
| Third-party script rules | Domains whose scripts must wait for consent. One per line, as `domain | category`. |
| Category snippets | Tracking code that Crumbz prints only after its category is allowed. Requires an administrator with unfiltered HTML access. |
| Wording | The banner and dialog text. |

## Look

Under **Look**, choose how the banner, the dialog, and the settings icon are styled.

**Plugin styling** (the default) uses the colors and typefaces you pick:

- **Primary, secondary, and tertiary colors.** Primary is the Accept button and the settings icon. Secondary is Reject and Save. Tertiary is Customize and the links. Crumbz picks black or white text for the primary button, whichever reads better. A warning appears under a color that is too light to read on white.
- **Primary and secondary typeface.** Primary is for headings and buttons. Secondary is for body text. The choices are Inter (sans serif), Source Serif 4 (serif), Playfair Display (display serif), and JetBrains Mono (monospace). All four are included with Crumbz and load from your own site, not from another server. They are open-source fonts under the SIL Open Font License.

**My theme** uses class names your theme already defines. Enter a class for primary, secondary, and tertiary, and Crumbz adds it to the matching buttons. Your theme's CSS then sets the colors, fonts, and shapes. Crumbz keeps the banner's surface neutral in this mode.

**Icons.** Pick an icon for Accept, Reject, Customize, and the settings icon, or choose no icon. The icons are from the Lucide set (ISC license) and take the color of their button.

### Your typefaces

To use your own typeface, upload a WOFF2 or WOFF file of 2 MB or less under **Your typefaces**, and give it a name. It then appears in the typeface lists. Upload only fonts you have the license to use on your site.

Uploaded fonts are stored in your uploads folder. Deleting Crumbz from the Plugins screen removes them. The bundled fonts go with the plugin.

## Upgrading

After you update Crumbz, clear your full-page cache (the caching plugin's "Clear cache" button, or your host's or CDN's purge). Pages cached before the update do not have the new markers, so their optional scripts stay blocked until the cache is cleared. Visitors then get the new version of each page.

## Link your policy pages

1. Publish your Cookie Policy, Privacy Policy, and Terms pages in WordPress. Drafts are not shown to visitors.
2. In **Documents**, choose each page, or enter a URL.
3. Each page needs a version number. Raise it when the text changes in a way that matters to visitors. Every visitor is then asked to accept the new version.

Changing a document's version or its linked page, changing jurisdictions, or turning a document on or off, asks every visitor to choose again.

## Scripts from other plugins

Crumbz blocks scripts that WordPress loads through its own script system, when their URL matches a rule. For example, Google Tag Manager is matched by `googletagmanager.com`.

Some tracking code is printed straight into the page by a theme or page builder. Crumbz cannot see those. Move that code into **Category snippets**, or tell the site developer.
