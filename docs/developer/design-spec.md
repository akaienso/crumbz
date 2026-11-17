# Crumbz: design spec (v0.1)

Crumbz is a free, open-source (GPL-2.0-or-later) cookie consent plugin for any WordPress site. It works with any theme or page builder and does not depend on another plugin.

## Decisions made for this version

| Topic | Decision | Why |
|---|---|---|
| License | GPL-2.0-or-later | WordPress.org requires GPL-compatible code. |
| Text domain / prefix | `crumbz`, functions and options prefixed `crumbz_` | Avoids collisions with other plugins. |
| Consent wording in brief | Read as: clicking **Accept** = consent. Clicking **Reject**, clicking **×**, or doing nothing = refusal. | The brief's literal wording ("accept is the same as refusing") reads as a typo. Flagged for the maintainer to confirm. |
| Refusal default | Only `necessary` cookies and scripts run until a visitor makes a choice. | Consent-first: nothing non-essential runs before a choice. |
| Cookies the plugin sets | One first-party cookie, `crumbz_consent`, written only **after** a visitor chooses. It stores the choice and the policy revision. | A consent record is needed to remember a choice or a refusal. Set before any choice: zero cookies. |
| Icon position | Stored in the `crumbz_icon` cookie, lasting the same period as the consent record. Set only after the visitor accepts Preferences. If Preferences is refused, the icon returns to its default corner on each page. | The maintainer wants the icon to stay put across browser restarts. A position is a non-essential preference, so it must follow the same consent gate as other preferences. |
| Jurisdictions | Site manager declares them in settings. A Jurisdiction advisor page asks where the business is and who it serves, then suggests modes and explains why. | Admins often don't know which laws apply. The advisor reads only what the admin types or what the admin's own request reveals (see Location hints). |
| Location hints | The admin's own request only: the Cloudflare country header and the browser language. No outside lookup service. Visitor-level geo switching is not built. | Keeps visitor data out of the plugin. Visitors get the same rules as the admin chose. |
| Cookie lifetime | 180 days, configurable. | Common regulator guidance for stored consent. |
| Refusal | Any dismissal that is not an Accept click counts as refusal and is recorded: Reject all, the ×, and Escape. | Accept is the only consent. |
| Leaving without a choice | Nothing is recorded. No optional script runs, and the banner returns on the next page. | Writing a record on load would set a cookie before any choice. The effect for the visitor is the same as refusal. Confirmed by the maintainer on 2026-11-17. |
| Theme choice | Stored in the `crumbz_theme` cookie, set only after Preferences is accepted. If Preferences is refused, the choice lasts for the current page only. | A theme choice is a preference, so it follows the same consent gate as the icon position. Confirmed by the maintainer on 2026-11-17. |
| Border contrast | Light card border `#7d8694` (3:1 or better on white). Dark card border `#737d8d` (3:1 or better on the dark background). | Non-text contrast guideline (WCAG 1.4.11). Confirmed by the maintainer on 2026-11-17. |

## Consent rules

1. Before a choice: no non-essential script runs and no non-essential cookie is set.
2. Accept all: every category and every required document is accepted.
3. Reject all: only `necessary`. Documents are viewable but not accepted.
4. Customize: per-category toggles, and per-document acceptance checkboxes. Nothing is pre-ticked (EU mode).
5. The Reject button has the same size, weight, and position as Accept (EU mode).
6. Changing a document version, a category, or a jurisdiction bumps the policy revision. Every visitor is asked again.
7. The consent record cookie is scoped to `Path=/`, `SameSite=Lax`, and `Secure` on HTTPS.

## Jurisdiction modes

- **EU:** opt-in per category, equal Accept/Reject, no cookie wall (content is never blocked), withdraw in one click (reopen icon).
- **California:** notice at collection, a "Do Not Sell or Share My Personal Information" link in the panel, and the Global Privacy Control signal (`Sec-GPC: 1`) treated as an opt-out of marketing category scripts.
- Both can be on at once; the stricter rule wins for each.

## Script blocking

- Scripts enqueued through WordPress with a known vendor host are matched by a rule list (for example googletagmanager.com, connect.facebook.net). Matching scripts render as `type="text/plain" data-crumbz-category="<cat>"` until that category is allowed. Once allowed, the script re-creates itself so it runs.
- Custom snippets (head/footer) are set per category in the admin and render the same way.
- Limit: scripts printed directly by a theme or page builder, outside WordPress enqueueing, are not caught. The admin must move them into the Crumbz snippet fields.

## Plugin detection

Crumbz checks the active plugin list against a built-in map (WooCommerce, Jetpack, Site Kit, Meta for WooCommerce, Polylang, WPML, and others). It shows which active plugins are likely to set cookies, with a suggested category. The admin can accept or change each suggestion.

## Button and banner

- Sticky icon (cookie glyph, labeled "Cookie settings"), bottom-left by default. Six positions can be chosen in the admin.
- Drag to move it. A tap opens the settings dialog. A drag that moves less than 6 px counts as a tap. The position is kept in the `crumbz_icon` cookie once Preferences is accepted (see decisions table).
- Keyboard: Tab to focus, Enter opens, arrow keys nudge it 8 px.
- Hidden permanently when a visitor picks "Hide this icon" (stored in the consent record). Clearing cookies resets the flow.
- Banner: bottom bar, top bar, or centered panel (admin choice).
- Theme: Auto (follows `prefers-color-scheme`), Light, or Dark. A visitor toggle is in the dialog. Admin can set the default.

## Documents and wizard

- Documents: cookie policy, privacy policy, terms of use, and up to three custom documents. Each is a WordPress page or a URL, with a version number.
- Wizard: step-by-step questions (business type, analytics, ads, newsletter, forms, accounts, online sales, jurisdictions, contact details). It drafts three pages as **drafts** in WordPress. Nothing publishes automatically.
- Drafts are plain text with `[bracketed]` fields for the site owner to fill in. The admin screen says the drafts are not legal advice.

## Out of scope for v0.1

- Automatic cookie scanning.
- Consent receipts or logs (these would store IP addresses, so they are excluded).
- Multi-language UI strings (the strings are filterable for translation).
- IP-based geolocation.
- Blocking scripts printed outside WordPress enqueueing (see Limit above).
- WordPress.org submission (waits for the maintainer's go).

## Files

- `crumbz.php`: bootstrap, constants, hooks.
- `includes/class-crumbz-settings.php`: defaults, sanitize, policy revision.
- `includes/class-crumbz-consent.php`: server-side reads of the consent record, GPC check, category checks.
- `includes/class-crumbz-frontend.php`: enqueues, config, banner and button mount points, custom snippets.
- `includes/class-crumbz-scripts.php`: script rules and output rewriting.
- `includes/class-crumbz-detect.php`: detected-plugin map.
- `includes/class-crumbz-admin.php`: admin menu, settings screen, wizard screen.
- `includes/class-crumbz-wizard.php`: wizard steps and draft creation.
- `includes/class-crumbz-templates.php`: draft text for the three policy pages.
- `assets/js/frontend.js`, `assets/css/frontend.css`, `assets/css/admin.css`.
- `docs/DESIGN-TOKENS.md`: every color in hex, RGB, and OKLCH.
