# Crumbz design tokens

Every color used by the plugin, in hex, RGB, and OKLCH. The values live as CSS custom properties in `assets/css/frontend.css` and `assets/css/admin.css`. The plugin uses the system font stack, so no fonts are loaded from another server.

## Light theme (default)

| Token | Hex | RGB | OKLCH |
|---|---|---|---|
| `--crumbz-bg` | #ffffff | rgb(255, 255, 255) | oklch(100.0% 0.000 0.0) |
| `--crumbz-fg` | #1b1f24 | rgb(27, 31, 36) | oklch(23.7% 0.011 254.1) |
| `--crumbz-muted` | #4f5a68 | rgb(79, 90, 104) | oklch(46.3% 0.027 254.5) |
| `--crumbz-accent` | #2450b8 | rgb(36, 80, 184) | oklch(46.6% 0.172 263.7) |
| `--crumbz-accent-fg` | #ffffff | rgb(255, 255, 255) | oklch(100.0% 0.000 0.0) |
| `--crumbz-border` | #7d8694 | rgb(125, 134, 148) | oklch(61.7% 0.024 259.2) |
| `--crumbz-surface` | #f3f5f8 | rgb(243, 245, 248) | oklch(96.9% 0.005 258.3) |
| `--crumbz-focus` | #1a4fb0 | rgb(26, 79, 176) | oklch(45.4% 0.165 261.3) |

## Dark theme

Applies when the visitor or admin chooses Dark, or when the theme is Auto and the system is dark.

| Token | Hex | RGB | OKLCH |
|---|---|---|---|
| `--crumbz-bg` | #14171c | rgb(20, 23, 28) | oklch(20.4% 0.011 260.7) |
| `--crumbz-fg` | #eef1f5 | rgb(238, 241, 245) | oklch(95.7% 0.006 255.5) |
| `--crumbz-muted` | #a7b0bd | rgb(167, 176, 189) | oklch(75.4% 0.021 257.5) |
| `--crumbz-accent` | #9cbaff | rgb(156, 186, 255) | oklch(79.3% 0.104 266.0) |
| `--crumbz-accent-fg` | #0c1426 | rgb(12, 20, 38) | oklch(19.4% 0.039 264.9) |
| `--crumbz-border` | #737d8d | rgb(115, 125, 141) | oklch(58.7% 0.027 259.8) |
| `--crumbz-surface` | #1e232b | rgb(30, 35, 43) | oklch(25.5% 0.017 259.8) |
| `--crumbz-focus` | #ffd166 | rgb(255, 209, 102) | oklch(88.0% 0.135 86.1) |

## Overlays (translucent)

| Token | Light | Dark |
|---|---|---|
| `--crumbz-backdrop` | rgba(20, 24, 31, 0.55) | rgba(0, 0, 0, 0.7) |
| `--crumbz-shadow` | rgba(20, 24, 31, 0.18) | rgba(0, 0, 0, 0.5) |

## Admin screens (WordPress admin palette)

| Use | Hex | RGB | OKLCH |
|---|---|---|---|
| Note border | #2271b1 | rgb(34, 113, 177) | oklch(53.4% 0.125 247.7) |
| Note background | #f0f6fc | rgb(240, 246, 252) | oklch(97.0% 0.010 247.9) |

## Contrast (WCAG 2.x)

| Theme | Pair | Ratio | Result |
|---|---|---|---|
| Light | Text #1b1f24 on #ffffff | 16.56:1 | AA text |
| Light | Muted #4f5a68 on #ffffff | 7.01:1 | AA text |
| Light | Muted #4f5a68 on #f3f5f8 | 6.42:1 | AA text |
| Light | Accent-fg #ffffff on accent #2450b8 | 7.21:1 | AA text |
| Light | Accent link #2450b8 on #ffffff | 7.21:1 | AA text |
| Light | Border #7d8694 on #ffffff | 3.68:1 | Meets 3:1 non-text (WCAG 1.4.11) |
| Dark | Text #eef1f5 on #14171c | 15.86:1 | AA text |
| Dark | Muted #a7b0bd on #14171c | 8.20:1 | AA text |
| Dark | Muted #a7b0bd on #1e232b | 7.21:1 | AA text |
| Dark | Accent-fg #0c1426 on accent #9cbaff | 9.51:1 | AA text |
| Dark | Accent link #9cbaff on #14171c | 9.31:1 | AA text |
| Dark | Border #737d8d on #14171c | 4.32:1 | Meets 3:1 non-text (WCAG 1.4.11) |
| Dark | Focus #ffd166 on #14171c | 12.46:1 | AA text |

The borders were darkened on 2026-11-17 to meet the 3:1 non-text guideline. The light border was 1.55:1 before.

Computed with a small script (kept outside the plugin). Values are sRGB. OKLCH is computed from the sRGB hex with the Björn Ottosson OKLab matrices.
