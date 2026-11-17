=== Crumbz ===
Contributors: [WordPress.org username]
Tags: cookies, consent, gdpr, ccpa, privacy
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free, open-source cookie consent for any WordPress site. Consent-first, with a sticky settings icon, EU and California modes, and a policy wizard.

== Description ==

Crumbz blocks optional cookies and third-party scripts until a visitor chooses. It works with any theme or page builder, and it does not depend on other plugins.

* Nothing optional runs, and no optional cookie is set, until a visitor makes a choice.
* Accept all, Reject all, or Customize by category. Closing the banner counts as a refusal.
* A sticky settings icon that visitors can drag out of the way. Choose its corner in the settings.
* Visitors can review the documents they accepted, change their choices, or hide the icon. Clearing cookies resets everything.
* Jurisdictions: EU (opt-in, equal Accept and Reject, no content blocking) and California (Do Not Sell or Share link, and Global Privacy Control treated as a marketing opt-out).
* Blocks scripts from known vendors (Google Analytics and Tag Manager, Meta, Hotjar, TikTok, LinkedIn, X) until their category is allowed. You can add your own domains.
* Detects active plugins that set cookies (WooCommerce, Jetpack, Site Kit, and others) and suggests a category for each.
* Policy wizard: answer simple questions, and it creates draft Cookie Policy, Privacy Policy, and Terms and Conditions pages. Nothing publishes automatically.
* Free, with no paid tier. Licensed GPL-2.0-or-later.

== Installation ==

1. Upload the `crumbz` folder to `/wp-content/plugins/`, or install the zip from Plugins > Add New.
2. Activate Crumbz.
3. Open Crumbz > Settings. Choose the jurisdictions that apply, and link your policy pages.
4. Optional: open Crumbz > Policy wizard to create draft policy pages.

== Frequently Asked Questions ==

= Does Crumbz set a cookie before a visitor chooses? =

No. Before a choice, the only thing that runs is the banner. Crumbz sets one first-party cookie, crumbz_consent, after a visitor accepts or rejects. It records the choice and the policy revision, so the banner is not shown again.

= What does closing the banner do? =

It counts as a refusal. Optional cookies stay off, and the banner does not come back until the visitor clears cookies or you change the policy revision.

= Why does the banner ask again after I edit a page? =

Changing a document version, the jurisdictions, or the categories raises the policy revision. Every visitor is asked again. Raise a document's version number when its text changes in a way that matters to visitors.

= Does Crumbz block scripts that other plugins print directly? =

Scripts that WordPress enqueues with a known vendor URL are blocked. Scripts a theme or page builder prints outside WordPress enqueueing are not caught. Move those into the Category snippets fields in the settings.

== Changelog ==

= 0.1.0 =
* First release: consent gate, sticky draggable settings icon, banner and settings dialog, EU and California modes, Global Privacy Control, script blocking, plugin detection, and the policy wizard.
