# Crumbz design tokens

Every color used by the plugin, in hex, RGB, and OKLCH. The values live as CSS custom properties in `assets/css/frontend.css` and `assets/css/admin.css`. The plugin uses the system font stack, so no fonts are loaded from another server.

The look is "signage": ink text and borders on white, with one crumb-yellow accent. Yellow is the primary button and the settings icon. Ink fills the admin header and the secondary-action outline. Keep yellow for primary actions only.

## Light theme (default)

| Token | Hex | RGB | OKLCH |
|---|---|---|---|
| `--crumbz-bg` | #ffffff | rgb(255, 255, 255) | oklch(100.0% 0.000 89.9) |
| `--crumbz-fg` | #15171c | rgb(21, 23, 28) | oklch(20.5% 0.010 268.2) |
| `--crumbz-muted` | #545b68 | rgb(84, 91, 104) | oklch(47.0% 0.023 262.9) |
| `--crumbz-accent` | #ffc53d | rgb(255, 197, 61) | oklch(85.4% 0.157 84.1) |
| `--crumbz-accent-fg` | #15171c | rgb(21, 23, 28) | oklch(20.5% 0.010 268.2) |
| `--crumbz-accent-hover` | #f2b11e | rgb(242, 177, 30) | oklch(80.0% 0.160 80.8) |
| `--crumbz-border` | #7d8694 | rgb(125, 134, 148) | oklch(61.7% 0.024 259.2) |
| `--crumbz-surface` | #f4f5f7 | rgb(244, 245, 247) | oklch(97.0% 0.003 264.5) |
| `--crumbz-switch-off` | #7d8694 | rgb(125, 134, 148) | oklch(61.7% 0.024 259.2) |
| `--crumbz-focus` | #1a4fb0 | rgb(26, 79, 176) | oklch(45.4% 0.165 261.3) |

## Dark theme

Applies when the visitor or admin chooses Dark, or when the theme is Auto and the system is dark.

| Token | Hex | RGB | OKLCH |
|---|---|---|---|
| `--crumbz-bg` | #14171c | rgb(20, 23, 28) | oklch(20.4% 0.011 260.7) |
| `--crumbz-fg` | #eef1f5 | rgb(238, 241, 245) | oklch(95.7% 0.006 255.5) |
| `--crumbz-muted` | #a7b0bd | rgb(167, 176, 189) | oklch(75.4% 0.021 257.5) |
| `--crumbz-accent` | #ffc53d | rgb(255, 197, 61) | oklch(85.4% 0.157 84.1) |
| `--crumbz-accent-fg` | #15171c | rgb(21, 23, 28) | oklch(20.5% 0.010 268.2) |
| `--crumbz-accent-hover` | #ffd166 | rgb(255, 209, 102) | oklch(88.0% 0.135 86.1) |
| `--crumbz-border` | #737d8d | rgb(115, 125, 141) | oklch(58.7% 0.027 259.8) |
| `--crumbz-surface` | #1e232b | rgb(30, 35, 43) | oklch(25.5% 0.017 259.8) |
| `--crumbz-switch-off` | #737d8d | rgb(115, 125, 141) | oklch(58.7% 0.027 259.8) |
| `--crumbz-focus` | #ffd166 | rgb(255, 209, 102) | oklch(88.0% 0.135 86.1) |

## Admin screens

| Use | Hex | RGB |
|---|---|---|
| Header band | #15171c | rgb(21, 23, 28) |
| Header tagline | #a7aebb | rgb(167, 174, 187) |
| Primary button hover | #2b2f38 | rgb(43, 47, 56) |
| Good status | #1e7a4c | rgb(30, 122, 76) |
| Off status | #8a1c1c | rgb(138, 28, 28) |
| Note background | #fff9e6 | rgb(255, 249, 230) |

## Overlays (translucent)

| Token | Light | Dark |
|---|---|---|
| `--crumbz-backdrop` | rgba(21, 23, 28, 0.55) | rgba(0, 0, 0, 0.7) |
| `--crumbz-shadow` | rgba(21, 23, 28, 0.22) | rgba(0, 0, 0, 0.5) |

## Contrast (WCAG 2.x)

| Theme | Pair | Ratio |
|---|---|---|
| Light | Text #15171c on #ffffff | 17.93:1 |
| Light | Muted #545b68 on #ffffff | 6.83:1 |
| Light | Muted #545b68 on #f4f5f7 | 6.26:1 |
| Light | Ink #15171c on accent #ffc53d (primary button) | 11.36:1 |
| Light | Border #7d8694 on #ffffff | 3.68:1 (meets 3:1 non-text, WCAG 1.4.11) |
| Light | Switch track off #7d8694 on #ffffff | 3.68:1 (meets 3:1 non-text) |
| Light | Focus #1a4fb0 on #ffffff | 7.53:1 |
| Dark | Text #eef1f5 on #14171c | 15.86:1 |
| Dark | Muted #a7b0bd on #14171c | 8.20:1 |
| Dark | Ink #15171c on accent #ffc53d | 11.36:1 |
| Dark | Border #737d8d on #14171c | 4.32:1 (meets 3:1 non-text) |
| Dark | Focus #ffd166 on #14171c | 12.46:1 |
| Admin | Yellow #ffc53d on header #15171c | 11.36:1 |
| Admin | Tagline #a7aebb on header #15171c | 8.04:1 |
| Admin | Good status #1e7a4c on #ffffff | 5.33:1 |
| Admin | Off status #8a1c1c on #ffffff | 9.28:1 |

Computed with a small script (kept outside the plugin). Values are sRGB. OKLCH is computed from the sRGB hex with the Björn Ottosson OKLab matrices.
