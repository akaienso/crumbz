# Testing

Crumbz has three layers of checks. Automated CI runs the first layer on every pull request. The other two need a local WordPress install and Google Chrome.

## 1. Automated checks (CI)

Runs on every pull request to `main`, and on pushes to `main`. See `.github/workflows/ci.yml`.

- **branch-name**: the source branch must follow the semantic name rule (see [releases and updates](releases-and-updates.md)).
- **pr-title**: the PR title must be a Conventional Commit.
- **php-lint**: `php -l` on every PHP file, under PHP 7.4, 8.1, and 8.3.
- **js-check**: `node --check` on `assets/js/frontend.js`.

Run the PHP and JavaScript checks locally before you push:

```sh
find . -name '*.php' -print0 | xargs -0 -n1 php -l
node --check assets/js/frontend.js
```

## 2. Browser suite (local)

Runs the consent flows in real Chrome against a local WordPress site. It covers:

- first visit, Reject all, reopen, hide the icon, reset by clearing cookies
- Accept all, and the unblocking of scripts without a reload
- Customize with one category, and the remaining scripts staying blocked
- closing with × and Escape, and leaving without a choice
- dragging the icon, and whether its position survives, with and without Preferences
- the theme toggle, with and without Preferences
- California mode with Global Privacy Control
- a document version change, which asks visitors again
- the plugin switched off

Setup:

1. A WordPress site on `http://localhost:8080`, with Crumbz active.
2. Three published pages linked as the Cookie Policy, Privacy Policy, and Terms documents.
3. Two third-party scripts enqueued by a test-only mu-plugin: one from `googletagmanager.com` (statistics) and one from `connect.facebook.net` (marketing). The suite looks for them by the IDs `test-gtag-js` and `test-fbpixel-js`.
4. Node.js, `puppeteer-core`, and Google Chrome.

Run:

```sh
cd tests/e2e
npm install
CRUMBZ_WP_PATH=/path/to/wordpress node test.mjs
```

External requests are blocked by the suite. Only the local site loads, so a flaky network does not stall it.

## 3. Admin suite (local)

Runs the Settings screen, the Jurisdiction advisor, and the policy wizard as an administrator.

```sh
CRUMBZ_TEST_ADMIN_USER=admin CRUMBZ_TEST_ADMIN_PASS='...' node admin.mjs
```

Use a throwaway local site and password. The suite changes settings and creates draft pages.

## Known gaps

- No automated tests for the PHP classes yet. The browser and admin suites cover them end to end.
- Safari, Firefox, and page builders have not been tested. See the release notes for the status of each.
- The updater's live path against GitHub is tested by reading the public API. Installing a real newer release is a manual step.
