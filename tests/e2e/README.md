# Crumbz local test suites

These suites drive Google Chrome against a WordPress site. Use a **throwaway local site only**. The admin suite changes settings and creates draft pages, and the browser suite changes the site's policy pages.

## One-time setup

1. Install WordPress with WP-CLI on a local database. Activate Crumbz. Set the site URL to `http://localhost:8080`.
2. Copy `fixtures/crumbz-test-vendors.php` into `wp-content/mu-plugins/`. It enqueues two third-party scripts the browser suite looks for.
3. Create three published pages: Cookie Policy, Privacy Policy, and Terms and Conditions. The browser suite links them as the documents, using page IDs 4, 5, and 6. Adjust the IDs in `test.mjs` if your IDs differ.
4. Create an administrator account for the tests. Keep the password out of shell history: pass it with an environment variable, not on the command line.
5. Install the dependencies:

   ```sh
   cd tests/e2e
   npm install
   ```

## Running

```sh
export CRUMBZ_WP_PATH=/path/to/wordpress
export CRUMBZ_BASE=http://localhost:8080          # optional, this is the default
export CRUMBZ_TEST_ADMIN_USER=testadmin           # optional, this is the default
export CRUMBZ_TEST_ADMIN_PASS='your-test-password'
export CHROME_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"   # optional
export WP_BIN=wp                                  # optional

npm test          # browser suite
npm run test:admin
```

Both suites print one line per check, then a total. A non-zero exit code means at least one check failed.

## Notes

- Requests to any other origin are blocked during the run, so an outside network cannot stall the tests.
- The browser suite changes the site's Crumbz settings, and restores the defaults it depends on at the start of each run.
- The suites were written against WordPress 7.1 and Google Chrome on macOS. Other setups may need small path changes.
