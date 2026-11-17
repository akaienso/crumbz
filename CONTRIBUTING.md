# Contributing to Crumbz

Thanks for helping. Crumbz is a consent plugin, so changes that touch consent get extra care.

## Workflow

1. Open an issue first if the change is large or changes behavior.
2. Create a branch with a semantic name: `type/short-description`. Allowed types: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`, `ci`, `build`, `perf`, `revert`. Examples: `feat/theme-cookie`, `fix/escape-key`.
3. Make the change. Run the checks in [docs/developer/testing.md](docs/developer/testing.md).
4. Open a pull request into `main`. Give it a Conventional Commit title, for example `fix: keep the icon inside the window on resize`.
5. Wait for CI to pass. Pull requests are squash-merged, and the title becomes the commit message.

`main` is protected. Direct pushes are not allowed, and every change needs a pull request.

## Commit and PR title types

| Type | Version effect (0.x) |
|---|---|
| `feat` | Minor bump |
| `fix` | Patch bump |
| `feat!` or a `BREAKING CHANGE:` footer | Major bump |
| `docs`, `refactor`, `test`, `chore`, `ci`, `build`, `perf`, `revert` | No release by themselves |

## Consent changes

Changes to consent behavior need a note in the PR description that says:

- which visitor action is consent, and which is refusal
- which cookies are set, when, and under which category
- what happens when a visitor refuses that category

The consent rules are in [docs/developer/consent-model.md](docs/developer/consent-model.md). Keep the code and that page in step.

## Code style

- PHP: WordPress coding conventions. Escape output, sanitize input, and check capabilities and nonces on every admin action.
- JavaScript: plain ES5-style code with no build step. Keep it readable without a toolchain.
- No inline scripts or styles in admin screens. Enqueue files instead.
- Colors come from the CSS custom properties. Add new colors to [docs/developer/design-tokens.md](docs/developer/design-tokens.md) with hex, RGB, OKLCH, and contrast ratios.
- Strings go through the WordPress translation functions, with the `crumbz` text domain.

## Reporting security issues

Do not open a public issue for a security problem. Use GitHub's private vulnerability reporting on the repository.

## License

By contributing, you agree that your contributions are licensed under GPL-2.0-or-later.
