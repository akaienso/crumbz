# Crumbz

**Free, open-source cookie consent for any WordPress site.** Crumbz is consent-first: nothing optional runs, and no optional cookie is set, until a visitor clicks Accept. It works with any theme or page builder and does not need another plugin.

Status: released. Tested on WordPress 7.1 with Google Chrome. See the [latest release](https://github.com/akaienso/crumbz/releases/latest) and the [changelog](CHANGELOG.md).

## Features

- **Consent-first.** Accept is the only consent. Reject, the ×, and Escape are refusals. Leaving without a choice runs nothing optional.
- **A settings icon that stays put.** A round cookie icon, placed in a corner you choose. Visitors can drag it, and the spot is remembered once they accept Preferences.
- **Banner and dialog.** Accept all, Reject all, or Customize by category. Light, dark, and automatic themes.
- **Policy documents.** Link your Cookie Policy, Privacy Policy, Terms, and up to three custom documents. Raising a version number asks visitors to accept again.
- **EU and California modes.** Opt-in for the EU and EEA. Do Not Sell or Share, and Global Privacy Control, for California.
- **Jurisdiction advisor.** Answer a few questions about your business, and Crumbz suggests which rules apply.
- **Script blocking.** Scripts from known vendors (Google, Meta, Hotjar, TikTok, LinkedIn, X) wait for consent. Plugin detection suggests a category for each cookie-setting plugin you run.
- **Policy wizard.** Eleven questions create draft Cookie Policy, Privacy Policy, and Terms pages. Nothing publishes automatically.
- **Automatic updates.** New releases come from GitHub, and WordPress installs them on its schedule. You can turn this off.

## Install

Download `crumbz.zip` from the [latest release](https://github.com/akaienso/crumbz/releases/latest), then upload it in **Plugins > Add New > Upload Plugin**.

## Documentation

- **Site owners:** [user documentation](docs/user/README.md). Setup, the banner and icon, jurisdictions, the wizard, and an FAQ.
- **Developers:** [developer documentation](docs/developer/README.md). Architecture, the consent model, hooks, testing, and the release process.
- **Design:** [design spec](docs/developer/design-spec.md) and [design tokens](docs/developer/design-tokens.md).

## Development

Requirements: PHP 7.4 or newer for linting, Node.js 20 for the front-end syntax check.

```sh
find . -name '*.php' -print0 | xargs -0 -n1 php -l
node --check assets/js/frontend.js
```

Browser and admin tests run against a local WordPress site. See [tests/e2e/README.md](tests/e2e/README.md).

Contributions follow the rules in [CONTRIBUTING.md](CONTRIBUTING.md). Changes go through pull requests into `main`.

## Disclaimer

Crumbz is not legal advice. The jurisdiction advisor and the policy drafts are starting points. Have a qualified lawyer review your policies before you publish them.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
