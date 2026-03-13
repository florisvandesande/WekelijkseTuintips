# Floris' tuintips

Floris' tuintips is a beginner-friendly PHP web app that shows weekly garden tasks.

The app answers one question:

**"Wat moet ik deze week in de tuin doen?"**

It builds each week page from category markdown files in `content/categories/`.

## Features

- homepage shows the current ISO week without redirect, so `/tuintips/` stays stable for iOS home-screen use
- weekly pages with URL pattern `/tuintips/{year}/week/{week}`
- previous/current/next week navigation
- season calendar for the selected year
- priority block with the most urgent tips
- category sections (empty categories are hidden)
- weather block (today + next 3 days)
- Buienradar link
- install icon metadata for iOS home screen and Chrome/Android app install (manifest + touch icon + favicons)
- dark mode support (`prefers-color-scheme`)
- print-friendly layout
- content validator script for markdown files
- 20 Dutch category files with narrative-first recurring garden care content

## Requirements

- PHP 8.3+
- Apache with `mod_rewrite` (for pretty URLs)
- shared hosting compatible file access

No Node, React, or Python runtime is required for the web app.

## Project structure

```text
assets/
  css/app.css
  js/app.js
  images/app-icon.png
  images/icons/*.png
  site.webmanifest
api/
  weather.php
content/
  categories/*.md
  monthly-intros.md
includes/
partials/
data/
  cache/
  locales/
  logs/
scripts/
  validate_content.php
index.php
week.php
config.example.php
.htaccess
```

## Setup (beginner steps)

### 1. Copy the config file

Copy `config.example.php` to `config.php`.

```bash
cp config.example.php config.php
```

If you cannot use terminal commands, duplicate the file manually and rename it to `config.php`.

### 2. Fill in values in `config.php`

Open `config.php` and update at least:

- `app.base_url`
- `app.base_path`
- `weather.fallback_location`
- `logging.enabled`

### 3. Confirm `config.php` is ignored by Git

Run:

```bash
git status
```

`config.php` must **not** appear in changed files.

### 4. Never commit or share `config.php`

`config.php` may contain sensitive values. Keep it local/private.

## Routes

- `/tuintips/` -> shows the current ISO week without redirect
- `/tuintips/current` -> redirects to current ISO week URL
- `/tuintips/{year}/week/{week}` -> week page

Examples:

- `/tuintips/`
- `/tuintips/2026/week/10`
- `/tuintips/2027/week/1`

## Content model

Category content lives in:

```text
content/categories/
```

Each file is one category with YAML frontmatter.

The current content pack contains these 20 category files:

- `algemeen-onderhoud.md`
- `grotere-klussen.md`
- `bodem-en-mulch.md`
- `compost-en-kringloop.md`
- `regenwater-en-bewatering.md`
- `moestuin.md`
- `kruiden-en-theeplanten.md`
- `fruitbomen-en-kleinfruit.md`
- `potten-en-bakken.md`
- `borders-en-vaste-planten.md`
- `eenjarigen-en-bloembollen.md`
- `heesters.md`
- `hagen.md`
- `bomen.md`
- `klimplanten.md`
- `gazon.md`
- `vijver-en-water.md`
- `biodiversiteit-en-habitat.md`
- `terras-paden-en-erfafscheidingen.md`
- `winterbeeld-en-structuur.md`

Per category and per week, a file may produce `0..N` active items. Empty categories are hidden automatically in the app.

## Month intro content (below date range)

The short intro line under the week date range is content-driven and stored in:

```text
content/monthly-intros.md
```

Use the `monthly_intros` mapping (`1`..`12`) to edit text per month.
Use `fallback_intro` for a default message if a month is missing.

### Example category file

```md
---
category_key: fruitbomen_en_kleinfruit
category_title: "Fruitbomen en kleinfruit"
sort_order: 80
items:
  - id: fruit-appel-peer-wintersnoei
    title: "Snoei appel en peer op een droge vorstvrije dag"
    weeks: [2, 3, 4, 5, 6]
    start_year: 2026
    repeat_every_years: 1
    priority: high
    conditions: [vorstvrij]
    tags: [snoeien, structuur]
    garden_types: [eetbaar, biodiversiteit]
    body: |
      In deze rustfase is de takstructuur goed zichtbaar en herstellen pitfruitbomen meestal netjes van een doordachte wintersnoei. Haal kruisende takken, waterloten en sterk naar binnen groeiende scheuten weg en werk naar een open, leesbare kroon toe. Snoei niet tijdens harde vorst en laat geen rafelige snijvlakken achter die onnodig inwateren. Een rustige wintersnoei geeft meer licht, lucht en beter gevormd vruchthout.
---
```

### Content writing rules

- `category_key`, `category_title`, `title`, and `body` are authored in Dutch
- `body` should be one flowing paragraph with 3 to 5 full sentences
- content should describe recurring yearly maintenance, not one-time setup
- advice should stay biological or ecological where possible
- use the schema fields exactly as shown above
- keep item IDs unique across all category files

### Repeat logic

An item is active when:

1. selected week is in `weeks`
2. selected year is `>= start_year`
3. `(selected_year - start_year) % repeat_every_years === 0`

Defaults:

- `repeat_every_years` defaults to `1` when omitted
- `start_year` defaults to current year when omitted

## Add a new category

1. Create a new file in `content/categories/` (for example: `hagen.md`)
2. Copy the example structure
3. Use a unique `category_key`
4. Add items
5. Save

No PHP code change is needed for category discovery.

## Validate content before deployment

Use the built-in PHP validator:

```bash
php scripts/validate_content.php
```

### Useful validator options

Validate one file:

```bash
php scripts/validate_content.php --file=content/categories/fruitbomen-en-kleinfruit.md
```

Treat warnings as errors:

```bash
php scripts/validate_content.php --strict
```

JSON output:

```bash
php scripts/validate_content.php --format=json
```

### Validator output example

```text
ERROR   content/categories/fruitbomen-en-kleinfruit.md
        item: fruit-appel-peer-wintersnoei
        field: items[0].weeks[2]
        message: Invalid week number 54, expected integer 1..53.
```

Summary:

```text
Checked 20 files, 278 items.
0 errors, 0 warnings.
Validation passed.
```

The validator exits with a non-zero status when validation fails.

## Weather behavior

- Server always fetches weather for fallback coordinates from `config.php`
- Browser geolocation can optionally update weather to the user location
- If geolocation is denied, fallback weather stays visible
- Weather responses are cached in `data/cache/`

## Logging

- logs are written to `data/logs/app.log` when `logging.enabled` is `true`
- parser, route, and weather errors are logged with context

## Deploy on shared hosting

1. Upload project files to your web root or subfolder
2. Ensure `config.php` exists on the server
3. Ensure `data/cache/` and `data/logs/` are writable by PHP
4. Confirm `.htaccess` and `mod_rewrite` are active
5. Open `/tuintips/` and verify it stays on the base URL while showing the current week

## Notes about language

All user-facing text in the web app is Dutch. Code, logs, and project documentation stay in English.
