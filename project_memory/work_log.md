# Work Log

## 2026-03-13 (ErikFlowers local weather icons)
- Summary:
  Replaced the header weather glyphs with a self-hosted ErikFlowers SVG subset, switched the weather payload to `weather_icon_slug`, and doubled the icon sizes for clearer visibility while keeping the mobile overflow fix intact.
- Files touched:
  - Runtime/template: `includes/weather.php`, `partials/header.php`
  - Frontend: `assets/css/app.css`, `assets/js/app.js`
  - Assets/docs: `assets/images/weather/*.svg`, `assets/images/weather/README.md`, `README.md`
  - Project memory: `project_memory/*.md`
- Related tasks/decisions:
  - Tasks: TASK-118
  - Bugs: BUG-002
  - Decisions: DEC-012
- Checks run:
  - `php -l includes/weather.php`
  - `php -l partials/header.php`
  - `php -l api/weather.php`
  - `node --check assets/js/app.js`
  - CLI weather-code mapping check for `0`, `1`, `3`, `45`, `51`, `61`, `71`, `80`, and `95`
  - Local PHP dev-server + Playwright checks for light mode, dark mode, `390px` mobile width, and simulated geolocation refresh
- Result:
  - Weather data now carries `weather_icon_slug` values aligned with the local ErikFlowers SVG subset.
  - The header renders local SVG masks instead of Unicode glyphs, using dark icons in light mode and light icons in dark mode.
  - Header icon sizes were doubled and still passed the local no-overflow checks on desktop and mobile.
  - The client-side geolocation refresh updates icon classes and the Buienradar URL correctly.
- Next suggested step:
  - Do one final spot check on a real iPhone or iOS Simulator, then commit the bugfix branch if approved.

## 2026-03-13 (Dutch content language pass)
- Summary:
  Reviewed Dutch site content for awkward wording, uncommon compounds, English loanwords, and small grammar mistakes, then rewrote the affected copy into plainer and more natural Dutch.
- Files touched:
  - Content: `content/categories/*`, `content/monthly-intros.md`
  - Locale text: `data/locales/nl.php`
- Related tasks/decisions:
  - Tasks: TASK-117
  - Decisions: DEC-010
- Checks run:
  - `php scripts/validate_content.php`
  - `php -l data/locales/nl.php`
- Result:
  - Several awkward or unclear phrases were simplified, including rare compounds, English loanwords, and small grammar issues.
  - Content validation passed with `0 errors` and `0 warnings`.

## 2026-03-13 (Stable home route)
- Summary:
  Changed the app home route so `/tuintips/` renders the current week directly without redirect, while keeping `/tuintips/current` and explicit week URLs available.
- Files touched:
  - Routing/runtime: `index.php`, `includes/router.php`, `includes/functions.php`
  - Templates/install metadata: `partials/header.php`, `partials/week_navigation.php`, `assets/site.webmanifest`
  - Documentation: `README.md`
- Related tasks/decisions:
  - Tasks: TASK-116
  - Decisions: DEC-009
- Checks run:
  - `php -l index.php includes/router.php includes/functions.php partials/header.php partials/week_navigation.php week.php`
  - Local route checks for `/tuintips/`, `/tuintips/current`, and explicit week URLs via the PHP dev server
- Result:
  - `/tuintips/` now stays on the base path and shows the current ISO week with HTTP `200`.
  - `/tuintips/current` still redirects to the explicit current week URL.

## 2026-03-09 (MVP)
- Summary:
  Implemented the initial Floris' tuintips MVP with weekly gardening content, weather header, routing, validation, and beginner-friendly setup documentation.
- Files touched:
  - App runtime, includes, templates, assets, API, content, and docs
- Related tasks/decisions:
  - Tasks: TASK-100, TASK-101, TASK-102, TASK-103, TASK-104
  - Decisions: DEC-001, DEC-002
- Checks run:
  - `php -l` on all PHP files
  - `php scripts/validate_content.php`
- Result:
  - Syntax and validation checks passed, and the base weekly gardening experience was working end to end.
