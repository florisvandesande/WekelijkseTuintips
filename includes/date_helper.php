<?php

declare(strict_types=1);

/**
 * Return current time in configured application timezone.
 */
function app_now(): DateTimeImmutable
{
    $timezone = new DateTimeZone((string) app_array_get(app_config(), 'app.timezone', 'Europe/Amsterdam'));
    return new DateTimeImmutable('now', $timezone);
}

/**
 * Return the current ISO year and week.
 */
function app_current_iso_year_week(): array
{
    $now = app_now();

    return [
        'year' => (int) $now->format('o'),
        'week' => (int) $now->format('W'),
    ];
}

/**
 * Return 52 or 53 depending on ISO week count in the given year.
 */
function app_iso_weeks_in_year(int $year): int
{
    $timezone = new DateTimeZone((string) app_array_get(app_config(), 'app.timezone', 'Europe/Amsterdam'));
    $week_53 = (new DateTimeImmutable('now', $timezone))->setISODate($year, 53, 1);

    return ((int) $week_53->format('o') === $year && (int) $week_53->format('W') === 53) ? 53 : 52;
}

/**
 * Validate selected year against a safe range.
 */
function app_is_valid_year(int $year): bool
{
    return $year >= 2000 && $year <= 2100;
}

/**
 * Validate selected week within ISO constraints for the selected year.
 */
function app_is_valid_week_for_year(int $year, int $week): bool
{
    if ($week < 1) {
        return false;
    }

    return $week <= app_iso_weeks_in_year($year);
}

/**
 * Return ISO week context including date range and adjacent weeks.
 */
function app_week_context(int $year, int $week): array
{
    $timezone = new DateTimeZone((string) app_array_get(app_config(), 'app.timezone', 'Europe/Amsterdam'));

    $monday = (new DateTimeImmutable('now', $timezone))->setISODate($year, $week, 1);
    $sunday = $monday->modify('+6 days');
    $previous = $monday->modify('-1 week');
    $next = $monday->modify('+1 week');

    return [
        'year' => $year,
        'week' => $week,
        'start' => $monday,
        'end' => $sunday,
        'date_range_label' => sprintf(
            '%s t/m %s',
            app_format_date_dutch($monday, true),
            app_format_date_dutch($sunday, true)
        ),
        'previous' => [
            'year' => (int) $previous->format('o'),
            'week' => (int) $previous->format('W'),
        ],
        'next' => [
            'year' => (int) $next->format('o'),
            'week' => (int) $next->format('W'),
        ],
    ];
}

/**
 * Format date in Dutch, optionally without weekday.
 */
function app_format_date_dutch(DateTimeImmutable $date, bool $without_weekday = false): string
{
    $months = [
        1 => 'januari',
        2 => 'februari',
        3 => 'maart',
        4 => 'april',
        5 => 'mei',
        6 => 'juni',
        7 => 'juli',
        8 => 'augustus',
        9 => 'september',
        10 => 'oktober',
        11 => 'november',
        12 => 'december',
    ];

    $weekdays = [
        1 => 'maandag',
        2 => 'dinsdag',
        3 => 'woensdag',
        4 => 'donderdag',
        5 => 'vrijdag',
        6 => 'zaterdag',
        7 => 'zondag',
    ];

    $day = (int) $date->format('j');
    $month = $months[(int) $date->format('n')] ?? '';

    if ($without_weekday) {
        return sprintf('%d %s', $day, $month);
    }

    $weekday = $weekdays[(int) $date->format('N')] ?? '';

    return sprintf('%s %d %s', $weekday, $day, $month);
}

/**
 * Return weekday name in Dutch for the given date.
 */
function app_weekday_dutch(DateTimeImmutable $date): string
{
    $weekdays = [
        1 => 'maandag',
        2 => 'dinsdag',
        3 => 'woensdag',
        4 => 'donderdag',
        5 => 'vrijdag',
        6 => 'zaterdag',
        7 => 'zondag',
    ];

    return $weekdays[(int) $date->format('N')] ?? '';
}

/**
 * Build a season-based week list for the selected year.
 */
function app_season_calendar(int $year, int $selected_week): array
{
    $seasons = [
        'lente' => [],
        'zomer' => [],
        'herfst' => [],
        'winter' => [],
    ];

    $weeks_in_year = app_iso_weeks_in_year($year);

    for ($week = 1; $week <= $weeks_in_year; $week++) {
        $monday = app_now()->setISODate($year, $week, 1);
        $month = (int) $monday->format('n');

        if (in_array($month, [12, 1, 2], true)) {
            $season = 'winter';
        } elseif (in_array($month, [3, 4, 5], true)) {
            $season = 'lente';
        } elseif (in_array($month, [6, 7, 8], true)) {
            $season = 'zomer';
        } else {
            $season = 'herfst';
        }

        $seasons[$season][] = [
            'week' => $week,
            'is_active' => $week === $selected_week,
            'url' => app_week_url($year, $week),
        ];
    }

    return $seasons;
}

/**
 * Return a short month-aware intro sentence in Dutch.
 */
function app_season_intro(int $year, int $week): string
{
    $month = (int) app_now()->setISODate($year, $week, 1)->format('n');
    $intro_content = app_load_monthly_intro_content();

    return $intro_content['monthly_intros'][$month] ?? $intro_content['fallback_intro'];
}

/**
 * Load month intro text from content/monthly-intros.md.
 *
 * The intro text is content, so it is kept in markdown for easier maintenance.
 */
function app_load_monthly_intro_content(): array
{
    static $cached = null;

    if (is_array($cached)) {
        return $cached;
    }

    $defaults = app_default_monthly_intro_content();
    $file_path = app_path('content/monthly-intros.md');

    if (!is_readable($file_path)) {
        app_log('warning', 'Monthly intro content file is missing or not readable.', ['file' => $file_path]);
        $cached = $defaults;
        return $cached;
    }

    $raw_content = file_get_contents($file_path);

    if (!is_string($raw_content)) {
        app_log('warning', 'Monthly intro content file could not be read.', ['file' => $file_path]);
        $cached = $defaults;
        return $cached;
    }

    if (!preg_match('/^---\R(?P<frontmatter>.*?)\R---(?:\R|$)/s', $raw_content, $matches)) {
        app_log('warning', 'Monthly intro content file has no frontmatter block.', ['file' => $file_path]);
        $cached = $defaults;
        return $cached;
    }

    if (!class_exists(SimpleYamlParser::class)) {
        app_log('warning', 'SimpleYamlParser is not available for monthly intro parsing.');
        $cached = $defaults;
        return $cached;
    }

    $parser = new SimpleYamlParser();

    try {
        $parsed = $parser->parse((string) $matches['frontmatter']);
    } catch (SimpleYamlParseException $exception) {
        app_log('warning', 'Monthly intro content frontmatter is invalid YAML.', [
            'file' => $file_path,
            'line' => $exception->line_number,
            'message' => $exception->getMessage(),
        ]);
        $cached = $defaults;
        return $cached;
    }

    if (!is_array($parsed)) {
        app_log('warning', 'Monthly intro content frontmatter root must be a mapping.', ['file' => $file_path]);
        $cached = $defaults;
        return $cached;
    }

    $raw_monthly_intros = is_array($parsed['monthly_intros'] ?? null) ? (array) $parsed['monthly_intros'] : [];
    $monthly_intros = $defaults['monthly_intros'];

    foreach ($monthly_intros as $month => $default_text) {
        $candidate = trim((string) ($raw_monthly_intros[(string) $month] ?? ''));
        if ($candidate !== '') {
            $monthly_intros[$month] = $candidate;
        }
    }

    $fallback_intro = trim((string) ($parsed['fallback_intro'] ?? ''));

    $cached = [
        'monthly_intros' => $monthly_intros,
        'fallback_intro' => $fallback_intro !== '' ? $fallback_intro : $defaults['fallback_intro'],
    ];

    return $cached;
}

/**
 * Return built-in month intro defaults used when markdown content is unavailable.
 */
function app_default_monthly_intro_content(): array
{
    return [
        'monthly_intros' => [
            1 => 'Januari is rustig buitenwerk: snoei gericht en geef winterrust de ruimte.',
            2 => 'Februari is voorbereiden: maak bedden klaar en start met de eerste vroege klussen.',
            3 => 'Maart brengt startenergie: werk de bodem los en zet de eerste zaaiplanning in.',
            4 => 'April vraagt ritme: jonge groei beschermen, voeden en stap voor stap opbouwen.',
            5 => 'Mei is groeimaand: bijhouden, ondersteunen en op tijd water geven maakt het verschil.',
            6 => 'Juni draait om balans: oogst wat klaar is en houd nieuwe aanwas luchtig en sterk.',
            7 => 'Juli vraagt aandacht voor vocht: slim water geven en mulchen houdt de tuin in vorm.',
            8 => 'Augustus is doorwerken met beleid: oogsten, nazaaien en rustig blijven corrigeren.',
            9 => 'September is de overgang: opruimen wat klaar is en tegelijk ruimte maken voor najaarsteelt.',
            10 => 'Oktober is beschermen: bodem bedekken en planten klaarzetten voor kou en natte periodes.',
            11 => 'November is afronden: laatste onderhoud nu geeft een rustige start van de winter.',
            12 => 'December is onderhoud in kleine stappen: plannen, controleren en de tuin laten herstellen.',
        ],
        'fallback_intro' => 'Kies kleine, regelmatige tuinmomenten; consistentie werkt elk seizoen.',
    ];
}
