# Consent model

This page lists the rules the code enforces. Change the code only if the rule changes too.

## Consent

- **Accept is the only consent.** Clicking Accept all, or Save choices with categories ticked, records consent for those categories and the documents ticked.
- **Refusal** is recorded when a visitor clicks Reject all, closes the banner with the ×, presses Escape while the banner is showing, or saves the settings dialog with nothing ticked.
- **Leaving without a choice** records nothing. No optional script runs, and the banner shows again on the next page. This was confirmed by the site owner on 2026-11-17. It is chosen over writing a record on load, which would set a cookie before any choice.
- Opening the settings dialog and closing it with the × counts as refusal only if no record exists yet. If a record exists, closing the dialog changes nothing.

## Cookies

| Cookie | Category | Set when | Contents | Lifetime |
|---|---|---|---|---|
| `crumbz_consent` | Necessary | A visitor makes any choice (accept or refuse). | JSON: `v` (format version, 1), `r` (policy revision), `cats` (the three categories, true or false), `docs` (accepted document versions), `hidden` (icon hidden), `ts` (time of choice). | Lifetime setting, default 180 days. |
| `crumbz_icon` | Preferences | A visitor drags the icon, and Preferences is accepted. | JSON with `fx` and `fy`: the icon position as fractions of the window size. | Same as the consent record. |
| `crumbz_theme` | Preferences | A visitor changes the theme, and Preferences is accepted. | The theme name: `auto`, `light`, or `dark`. | Same as the consent record. |

Changing Preferences to refused deletes `crumbz_icon` and `crumbz_theme` on the next choice.

When Preferences is refused, the theme choice and the icon position last only for the current page.

## Policy revision

The revision is part of every consent record. When the revision on the server no longer matches the record's `r`, the record is ignored and the banner shows again.

The revision goes up when the **active documents**, their **versions**, or the **jurisdictions** change. A document is active when it is linked to a published page or given a URL. Drafts never count as active, because they would 404 for visitors.

## Categories

- `necessary`: always allowed. Covers the site itself and the consent record.
- `preferences`: language, layout, the icon position, and the theme.
- `statistics`: analytics.
- `marketing`: ads, retargeting, and social media.

## Jurisdictions

- **EU mode**: no category is pre-ticked. Reject all has the same size and position as Accept all. Content is never blocked.
- **California mode**: the Do Not Sell or Share link is shown in the dialog. When the browser sends Global Privacy Control (`Sec-GPC: 1` on the server, `navigator.globalPrivacyControl` in JavaScript), the marketing category stays off even after Accept all.

Both modes can be on together. The stricter rule applies to each category.
