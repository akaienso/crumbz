# FAQ

## Does Crumbz set cookies before a visitor chooses?

No. Before a choice, Crumbz sets no cookies. The first cookie it sets is the consent record, `crumbz_consent`, and only after the visitor accepts or refuses.

## What does closing the banner do?

It counts as refusing. Optional cookies and scripts stay off, and the banner does not come back until the visitor clears cookies or the policy changes.

## What if a visitor leaves without choosing?

Nothing is saved. Optional scripts stay blocked, and the banner shows again on the next page.

## Will the icon stay where a visitor dragged it?

Yes, if the visitor has accepted Preferences. The position is kept in a cookie, `crumbz_icon`, and survives closing the browser. If they have not accepted Preferences, it returns to its default corner on each page.

## Why does the banner show again after I change a page?

Crumbz asks visitors again when the active documents, their version numbers, or the jurisdictions change. If you update a policy's text in a way that matters, raise its version number in the settings. Editing a draft or an unlinked page does not trigger it.

## Can I turn off automatic updates?

Yes. Turn off **Automatic updates** in **Crumbz > Settings**. You will still see new versions on the Plugins screen.

## Will Crumbz block the Google Analytics script my theme prints?

Only if the script is loaded through WordPress's script system and its URL matches a rule. If your theme prints the code directly into the page, add it to **Category snippets** instead.

## Does Crumbz track visitors?

No. Crumbz stores no visitor data in the database, sends no visitor information anywhere, and keeps no consent logs.

## Does Crumbz work with page builders?

It works with any theme, because the banner and icon are built in JavaScript. Page builders are not tested yet. If a page builder prints tracking code directly, see the question above.

## Where do I report a problem?

Open an issue at [github.com/akaienso/crumbz/issues](https://github.com/akaienso/crumbz/issues).
