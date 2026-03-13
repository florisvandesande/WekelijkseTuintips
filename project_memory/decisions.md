# Decisions

## DEC-001 - Use dependency-free YAML frontmatter parsing for MVP
- Date: 2026-03-09
- Status: accepted
- Related tasks: TASK-100, TASK-101, TASK-103
- Related bugs: None
- Context:
  The repository currently has no dependency management setup, and the app must remain simple for beginners and shared hosting.
- Decision:
  Implement a narrow, explicit YAML frontmatter parser in PHP for the required category schema instead of adding Composer dependencies.
- Alternatives considered:
  - Use a third-party YAML package via Composer
  - Depend on the optional PHP YAML extension
- Why this decision:
  It keeps setup minimal, avoids extension assumptions, and makes validator behavior predictable.
- Consequences:
  The parser intentionally supports a limited YAML subset and must be documented clearly.

## DEC-002 - Keep user-facing UI text Dutch while retaining locale-ready content fields
- Date: 2026-03-09
- Status: accepted
- Related tasks: TASK-100, TASK-102
- Related bugs: None
- Context:
  Project rules require Dutch user-facing UI, while content files include translation maps.
- Decision:
  Render the app UI in Dutch and treat translation maps as content model support/fallback data.
- Alternatives considered:
  - Fully switchable UI locales for end users
  - Dutch-only content model with no translation maps
- Why this decision:
  It follows repository constraints while preserving future multilingual extensibility.
- Consequences:
  Footer includes language status but active UI language remains Dutch in MVP.

## DEC-003 - Author the category pack as Dutch literal strings within the existing translation-capable schema
- Date: 2026-03-10
- Status: accepted
- Related tasks: TASK-105
- Related bugs: None
- Context:
  `CONTENT_PLAN.md` requires Dutch category metadata and Dutch narrative item text, while the validator still accepts either literal strings or translation maps.
- Decision:
  Write `category_title`, `title`, and `body` as Dutch strings in the Markdown files instead of adding translation maps for this content pass.
- Alternatives considered:
  - Add `nl` translation maps to every text field
  - Keep the older English-oriented sample identifiers and wording
- Why this decision:
  It matches the content plan directly, keeps files shorter and easier for beginners to edit, and still satisfies the validator with no warnings.
- Consequences:
  The app remains locale-ready at code level, but the shipped content pack is authored as Dutch-first literals.

## DEC-004 - Show one-off content in one representative week and space true recurring reminders
- Date: 2026-03-10
- Status: accepted
- Related tasks: TASK-106
- Related bugs: None
- Context:
  The first narrative content pass used broad adjacent week ranges, which makes one-off seasonal tasks appear too many weeks in a row.
- Decision:
  Collapse one-time tasks to a single best-fit week and keep multiple weeks only for work that is genuinely recurring, with spaced reminders instead of uninterrupted weekly repetition where possible.
- Alternatives considered:
  - Leave broad contiguous ranges as written
  - Collapse every item to exactly one week
- Why this decision:
  It keeps the weekly view more useful and less repetitive without losing reminders for truly ongoing care such as watering, harvesting, hygiene checks, or repeated inspections under seasonal pressure.
- Consequences:
  The content remains seasonal, but week-level presentation becomes more selective and easier to scan.

## DEC-005 - Cluster similar maintenance across categories into shared work weeks where timing allows
- Date: 2026-03-10
- Status: accepted
- Related tasks: TASK-107
- Related bugs: None
- Context:
  After reducing repeated week windows, similar jobs such as mulching or seasonal leaf handling were still spread across neighboring weeks in different categories.
- Decision:
  Align comparable work into shared work waves where the seasonal timing still makes sense, so users can batch work across categories in the same week.
- Alternatives considered:
  - Keep each category independently optimized without cross-category batching
  - Force every similar tag into exactly the same week regardless of seasonal fit
- Why this decision:
  It makes the weekly view more practical for real garden work while keeping the advice seasonally believable.
- Consequences:
  Some weeks intentionally carry stronger themes, such as mulch work, dry-care, or autumn leaf handling.

## DEC-006 - Build a Buienradar town link only for explicit user geolocation coordinates
- Date: 2026-03-11
- Status: accepted
- Related tasks: TASK-112, TASK-113
- Related bugs: None
- Context:
  The weather header currently links to generic Buienradar, while user feedback asked for a town-specific link like `/weer/Culemborg/NL/2757872`.
- Decision:
  Resolve Buienradar place metadata via Buienradar's geo endpoint only when coordinates are explicitly provided by browser geolocation (`/api/weather.php` path). Keep fallback/server-side weather on the generic Buienradar homepage.
- Alternatives considered:
  - Always build a place URL, including fallback coordinates
  - Keep generic Buienradar URL in all cases
- Why this decision:
  It gives a useful town-specific link for users who allow geolocation while avoiding misleading links for users on fallback coordinates or failed lookups.
- Consequences:
  Geolocation users get a location-specific Buienradar URL when lookup succeeds; all failures remain non-blocking and safely fall back to `https://www.buienradar.nl/`.

## DEC-007 - Use one source icon with generated standard derivatives and static manifest metadata
- Date: 2026-03-12
- Status: accepted
- Related tasks: TASK-114
- Related bugs: None
- Context:
  A new `1024x1024` app icon was added and needs support for iOS home screen, Chrome/Android install prompts, and standard browser favicon usage.
- Decision:
  Keep `assets/images/app-icon.png` as the source icon and generate standard derivative PNG sizes (`512`, `192`, `180`, `32`, `16`). Add static manifest metadata (`assets/site.webmanifest`) and `<head>` icon/theme tags in `partials/header.php`.
- Alternatives considered:
  - Use only a single icon file for all contexts
  - Add a dynamic PHP-generated manifest for per-config runtime values
- Why this decision:
  Standard icon sizes improve compatibility across install surfaces, while a static manifest keeps setup simple for beginners and shared hosting.
- Consequences:
  Install surfaces now have predictable icons and metadata; if branding changes later, generated icon files should be regenerated from the source icon.

## DEC-008 - Treat month intro lines as content loaded from markdown with PHP fallback
- Date: 2026-03-12
- Status: accepted
- Related tasks: TASK-115
- Related bugs: None
- Context:
  The short intro line below the week date range was hardcoded inside `app_season_intro()`, making text maintenance less content-friendly.
- Decision:
  Store month intro lines in `content/monthly-intros.md` frontmatter (`monthly_intros` + `fallback_intro`) and load them in `app_season_intro()`.
- Alternatives considered:
  - Keep hardcoded month texts in PHP
  - Store month intro strings in locale files
- Why this decision:
  This text is editorial content and should be maintainable in a content file without changing application logic.
- Consequences:
  Editors can update month intro text in one markdown file; PHP defaults remain as a safe fallback when the file is missing or invalid.

## DEC-009 - Render the current week directly at `/tuintips/` while keeping explicit week URLs
- Date: 2026-03-13
- Status: accepted
- Related tasks: TASK-116
- Related bugs: None
- Context:
  The app should be addable to the iPhone home screen using `/tuintips/`, but the base path previously redirected to the explicit current week URL and changed the visible URL immediately.
- Decision:
  Serve the current ISO week directly at `/tuintips/` with no redirect, keep `/tuintips/current` as a redirect alias to the explicit current week URL, and leave `/tuintips/{year}/week/{week}` unchanged for all weeks.
- Alternatives considered:
  - Keep redirecting `/tuintips/` to the explicit current week URL
  - Remove the explicit current week URL and use only `/tuintips/`
- Why this decision:
  It gives iOS home-screen installs a stable launch URL while preserving direct, shareable, week-specific URLs for browsing and linking.
- Consequences:
  The base path becomes a dynamic "current week" view, while the explicit current week URL remains available for direct navigation and sharing.

## DEC-010 - Prefer plain Dutch over jargon, rare compounds, and English loanwords in user-facing garden content
- Date: 2026-03-13
- Status: accepted
- Related tasks: TASK-117
- Related bugs: None
- Context:
  User feedback showed that parts of the Dutch content read as technically correct but mentally heavy, with odd compounds, English loanwords, and phrases that felt less natural for beginners.
- Decision:
  Rewrite user-facing Dutch content toward plainer wording, clearer sentence flow, and more common garden vocabulary, even when a rarer term might still be defensible or domain-correct.
- Alternatives considered:
  - Keep the existing content because most phrases were technically understandable
  - Only fix clear spelling mistakes and leave stylistic awkwardness untouched
- Why this decision:
  The app is beginner-focused, so readability and natural phrasing matter more than preserving writerly or specialist wording.
- Consequences:
  Content editing now favors familiar Dutch terms such as `bastkanker`, `groenblijvend`, `mulchlaag`, and direct sentence structure over obscure compounds or imported wording.

## DEC-011 - Use local OpenWeather day/night icon assets in the header
- Date: 2026-03-13
- Status: superseded
- Superseded by: DEC-012
- Related tasks: TASK-118
- Related bugs: BUG-002
- Context:
  The header weather UI used Unicode glyphs, which iOS can render as emoji. The rainy-state glyph also looked like an umbrella instead of a cloud with rain, and the user first requested the official OpenWeather icon style instead.
- Decision:
  Replace display glyphs with local OpenWeather `@2x` day/night PNG assets, keep them in the repository, and resolve them through `weather_icon_key` values in the active header weather UI.
- Alternatives considered:
  - Keep Unicode weather glyphs
  - Keep the earlier local monochrome SVG-mask approach
  - Hotlink OpenWeather assets instead of storing them locally
- Why this decision:
  Local OpenWeather assets avoid iOS emoji substitution, satisfy the original icon-style request, and keep the app independent from runtime third-party asset requests.
- Consequences:
  This decision was not carried forward on the current branch after the implementation direction changed to ErikFlowers SVG assets.

## DEC-012 - Use a small local ErikFlowers SVG subset for header weather icons
- Date: 2026-03-13
- Status: accepted
- Supersedes: DEC-011
- Related tasks: TASK-118
- Related bugs: BUG-002
- Context:
  The live repo state still rendered Unicode weather glyphs, and the user explicitly requested ErikFlowers icons hosted from the app's own server with simple light/dark color behavior.
- Decision:
  Store only the needed ErikFlowers SVG files in `assets/images/weather/`, expose `weather_icon_slug` values in weather payloads, and render the header icons with CSS masks so light mode gets dark icons and dark mode gets light icons.
- Alternatives considered:
  - Keep Unicode glyphs
  - Ship the full ErikFlowers CSS/font package
  - Use self-hosted OpenWeather PNG assets instead
- Why this decision:
  It matches the requested icon set, keeps runtime independent from third-party asset requests, and lets one local SVG set adapt cleanly to both color themes without separate light/dark files.
- Consequences:
  Header markup and the geolocation refresh now update icon classes instead of text glyphs, only a small vendored SVG subset needs to be maintained, and the asset folder should keep a short upstream source/license note.

## Decision status reference
- proposed
- accepted
- superseded
- rejected
