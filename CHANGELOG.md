# Changelog

All notable changes to Crumbz are recorded here. Release notes are generated from merged pull requests by release-please.

## [0.1.2](https://github.com/akaienso/crumbz/compare/v0.1.1...v0.1.2) (2026-11-18)


### Bug Fixes

* revive only plugin-printed blocked tags ([#4](https://github.com/akaienso/crumbz/issues/4)) ([3ab6922](https://github.com/akaienso/crumbz/commit/3ab692278b358753936ae01ca18375ea2b7b31e9))

## [0.1.1](https://github.com/akaienso/crumbz/compare/v0.1.0...v0.1.1) (2026-11-18)


### Bug Fixes

* keep page output independent of the consent cookie ([#1](https://github.com/akaienso/crumbz/issues/1)) ([7105403](https://github.com/akaienso/crumbz/commit/7105403a2d03348ebff6efb8a374a96c4256cc7c))

## 0.1.0 (2026-11-17)

Initial release.

* Consent gate: nothing optional runs or is stored until a visitor chooses. Accept is the only consent.
* Sticky, draggable settings icon. Its position is remembered in a cookie once Preferences is accepted.
* Banner, settings dialog, light and dark themes (theme choice remembered under the same rule).
* EU and California modes, Global Privacy Control, and a Jurisdiction advisor.
* Blocking of vendor scripts until their category is allowed, with plugin detection.
* Policy wizard that creates draft Cookie Policy, Privacy Policy, and Terms pages.
