# Project Overview

## Project name
Floris' tuintips

## Goal
Build a beginner-friendly PHP web app that answers: "What should I do in the garden this week?" The app should render weekly tips from category-based markdown files, show weather context, and run reliably on shared hosting.

## Problem this project solves
Garden tasks are seasonal and easy to forget. The project centralizes weekly tasks in one readable page, so users can quickly see what to do now without searching many sources.

## Scope
- [x] Core web application
- [ ] Optional iOS bridge
- [x] Localization-ready content model
- [ ] File uploads
- [x] Markdown-driven category content
- [x] Weekly navigation and season calendar
- [x] Weather header with fallback location

## Out of scope
- [x] Native Android app
- [x] Heavy framework migration
- [x] Runtime Python services
- [x] Admin panel in version 1

## Success criteria
- The feature works reliably for a beginner user.
- Setup and usage remain beginner-friendly.
- The code remains readable and maintainable.
- Documentation is updated when behavior changes.
- Adding a new category requires only adding one markdown file.

## Important constraints
- Production web runtime must remain PHP + browser technologies.
- Shared hosting compatibility is required.
- Do not rely on restricted shell functions.
- Configuration and secrets live in `config.php`.
- User-facing UI text must remain Dutch.

## Key architecture notes
- Server-rendered HTML first
- JavaScript only as progressive enhancement
- Content source: `content/categories/*.md` plus `content/monthly-intros.md` (YAML frontmatter)
- Localized strings loaded from `data/locales/`
- Configuration via `config.php`
- Weather fetched server-side with cache in `data/cache/`
- Header weather icons use a small self-hosted ErikFlowers SVG subset in `assets/images/weather/`

## Active repositories or deployment targets
- Repository: WekelijkseTuintips
- Primary branch: main
- Feature branch convention: feature/... or bugfix/...
- Hosting target: shared hosting (PHP 8.3 FPM/FastCGI, Apache)

## Notes
The MVP includes a PHP validator script for category markdown files with clear errors and warnings per file/item/field.
