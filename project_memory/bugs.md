# Bugs

## Open
- None

## Monitoring
- BUG-001: Header weather + week navigation caused horizontal pressure on iPhone 12 width.
  - Reported: 2026-03-11
  - Reproduction:
    - Open a week page on iPhone 12 portrait width (`390px`).
    - Header weather area (today + next 3 days) and single-row week navigation can feel too wide.
  - Suspected cause:
    - Header kept title and weather in one row on small screens.
    - Weather row showed a two-column layout with a 3-day list.
    - Week navigation links stayed in a single non-wrapping row.
  - Fix attempted:
    - In `assets/css/app.css` at `@media (max-width: 700px)`, hide `.header-weather-next`, stack header/weather rows, wrap week navigation, and allow content text wrapping.
    - Follow-up (same day): keep header in a horizontal row on small screens and right-align the weather block so weather stays top-right on phone.
  - Validation:
    - `php -l week.php`
    - `php -l partials/header.php`
    - `php scripts/validate_content.php` (`0 errors`, `0 warnings`)
  - Next diagnostic step:
    - Verify on a real iPhone 12 in portrait and landscape that weather remains top-right and no horizontal overflow remains, then move to resolved.

- BUG-002: iOS renders header weather symbols as emoji, and rainy conditions show an umbrella icon instead of a cloud with rain.
  - Reported: 2026-03-13
  - Reproduction:
    - Open any week page on iOS Safari or from the iPhone home screen.
    - Look at the weather block in the top-right header area.
    - Symbols may render as emoji-style characters, and rain may show an umbrella.
  - Suspected cause:
    - The header used Unicode glyphs as weather icons, which iOS can render with emoji presentation.
  - Fix attempted:
    - Replace glyph output with semantic `weather_icon_slug` values that resolve to a small local ErikFlowers SVG set under `assets/images/weather/`.
    - Render the icons via CSS masks so the same local SVG files can use one clear theme-aware color: dark in light mode and light in dark mode.
    - Increase the icon sizes in the header for better legibility while preserving the existing mobile rule that hides the next-day row.
    - Update the client-side geolocation refresh so it swaps icon classes and the Buienradar link instead of writing Unicode glyphs into the DOM.
  - Validation:
    - `php -l includes/weather.php`
    - `php -l partials/header.php`
    - `php -l api/weather.php`
    - `node --check assets/js/app.js`
    - CLI slug mapping check for weather codes `0`, `1`, `3`, `45`, `51`, `61`, `71`, `80`, and `95`, with output such as `wi-day-sunny`, `wi-rain`, and `wi-thunderstorm`
    - Local PHP dev-server + Playwright checks confirming the header uses local SVG masks, switches icon color between light and dark mode, keeps `390px` mobile width overflow-free, and updates icon classes/Buienradar URL during geolocation refresh
  - Next diagnostic step:
    - Verify the new icons on a real iPhone or iOS Simulator in both Safari and home-screen mode, then move to resolved.

## Resolved Pending Archive
- None
