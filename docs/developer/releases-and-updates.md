# Releases and updates

## Branches

Every change goes through a pull request into `main`. Direct pushes to `main` are blocked.

Branch names follow `type/short-description`, using lowercase letters, digits, `.`, `_`, and `-`:

| Type | Use it for |
|---|---|
| `feat/` | A new feature |
| `fix/` | A bug fix |
| `docs/` | Documentation only |
| `refactor/` | Code change with no behavior change |
| `test/` | Tests |
| `chore/` | Maintenance |
| `ci/` | Workflows |
| `build/` | Build or packaging |
| `perf/` | Performance |
| `revert/` | Reverting a change |

Examples: `feat/icon-cookie`, `fix/banner-focus`, `docs/update-faq`. A name that does not match fails the `branch-name` check. The one other allowed name is `release-please--branches--main`, which release-please uses for its own release PR.

## Pull request titles and commits

Pull requests are **squash-merged**. The PR title becomes the commit message on `main`, so it must be a Conventional Commit:

- `feat: add Escape handling to the banner` (a new minor version, 0.x → 0.(x+1).0)
- `fix: keep the icon inside the window on resize` (a patch version)
- `feat!: ...` or a `BREAKING CHANGE:` footer (a major version)

The `pr-title` check enforces this.

## Automatic releases

1. A pull request is merged into `main`.
2. The `Release` workflow runs release-please. It opens or updates a **release PR** that bumps the version and writes `CHANGELOG.md`.
3. The `auto-merge-release-pr` job turns on auto-merge for the release PR. GitHub merges it once the required checks pass.
4. Merging the release PR creates the tag (for example `v0.2.0`) and the GitHub release, with the notes from the changelog.
5. The `build-zip` job builds `crumbz.zip` from the files not listed in `.distignore`, and attaches it to the release.

Versions are kept in one place. The version is on the `/* Version: ... */` line in `crumbz.php`, and in `define('CRUMBZ_VERSION', ...)`. Both carry the `x-release-please-version` marker, so release-please updates them together. The release-please manifest is `.release-please-manifest.json`.

### The release token

release-please needs a token whose pull requests trigger CI. GitHub does not run workflows for pull requests opened with the default `GITHUB_TOKEN`, so the release PR would never get its checks.

Set a fine-grained personal access token as the repository secret `RELEASE_PLEASE_TOKEN`:

- Repository access: `akaienso/crumbz` only.
- Permissions: Contents (read and write), Pull requests (read and write), Metadata (read).

Without the secret, the workflow falls back to `GITHUB_TOKEN`. Releases still happen, but the release PR runs no CI, so auto-merge waits until someone merges it by hand.

## Updates for sites

Crumbz checks GitHub for the latest release every 12 hours and adds it to WordPress's plugin update list. A site sees the update on the Plugins screen.

When **Automatic updates** is on (the default), WordPress installs the update on its own schedule. WordPress checks about twice a day through WP-Cron. Sites that turn off WP-Cron, or that use an external cron job, install updates when that job runs.

The updater:

- sends a generic User-Agent (`Crumbz-Updater`) and no site information
- accepts only packages from `https://github.com/akaienso/crumbz/releases/download/`
- compares versions with `version_compare`, so a release is installed only when it is newer
- backs off for an hour after a failed check, instead of retrying on every page load

Pushing updates to sites immediately is not possible without a server that sends them. Sites pick updates up on their own schedule.

## Changing the release process

Edit `.github/workflows/release.yml`, `release-please-config.json`, and `.distignore`. Do not add the version to any other file without a marker, or release-please will not update it.
