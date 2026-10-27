<?php

namespace Wexample\SymfonyGitDs\Helper;

use DateTimeImmutable;
use DateTimeZone;
use IntlDateFormatter;

/**
 * Where each day of a year of commits stands in a contribution calendar: a
 * square a day, a week from Monday a column — or a row, turned for a narrow
 * place —, the months named along the weeks, Monday, Wednesday and Friday
 * across them. Its shade is one of five levels: none, then the quarters of
 * the days with commits.
 */
final class GitActivityCalendarHelper
{
    public const int CELL = 10;

    public const int GAP = 3;

    public const int LEVELS = 4;

    /** Room for the names along the side: the days lying, the months standing. */
    private const array SIDE = [false => 30, true => 38];

    /** Room for the names above: the months lying, the days standing. */
    private const int TOP = 16;

    /** Room for the legend, below. */
    private const int BOTTOM = 22;

    /** Room on the right of a standing calendar, for the end of its legend. */
    private const int STANDING_RIGHT = 26;

    /** A month named closer than that many weeks to the next one is left unnamed. */
    private const int MONTH_MIN_WEEKS = 3;

    /**
     * @param array<string, int> $days commits by `Y-m-d`, every day in order
     * @param bool $standing the weeks as rows rather than columns: the
     *                       calendar turned for a place narrower than tall
     *
     * @return array{
     *     width: int,
     *     height: int,
     *     cells: list<array{x: int, y: int, level: int, commits: int, date: string}>,
     *     labels: list<array{x: int, y: int, label: string, anchor: string}>,
     *     words: list<array{x: int, y: int, word: string, anchor: string}>,
     *     legend: list<array{x: int, y: int, level: int}>
     * }
     */
    public static function layout(array $days, string $locale, bool $standing = false): array
    {
        $step = self::CELL + self::GAP;
        $side = self::SIDE[$standing];
        // A week and a day of the week, to the place of their square.
        $place = static fn (int $week, int $weekday): array => $standing
            ? ['x' => $side + $weekday * $step, 'y' => self::TOP + $week * $step]
            : ['x' => $side + $week * $step, 'y' => self::TOP + $weekday * $step];
        $thresholds = self::thresholds($days);
        $monthName = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'UTC', null, 'LLL');
        // A letter a day where a column is all there is to write it in.
        $dayName = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'UTC', null, $standing ? 'EEEEE' : 'EEE');
        $fullDate = new IntlDateFormatter($locale, IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'UTC');
        $cells = [];
        $starts = [];
        $week = 0;

        foreach ($days as $day => $commits) {
            $date = new DateTimeImmutable($day, new DateTimeZone('UTC'));
            $weekday = (int) $date->format('N') - 1;

            if (0 === $weekday && [] !== $cells) {
                ++$week;
            }

            // A month is named at the week of its first day, the one the
            // calendar opens on at its first week.
            if ([] === $cells || 1 === (int) $date->format('j')) {
                $starts[] = ['week' => $week, 'label' => $monthName->format($date)];
            }

            $cells[] = $place($week, $weekday) + [
                'level' => self::level($commits, $thresholds),
                'commits' => $commits,
                'date' => $fullDate->format($date),
            ];
        }

        $labels = [];

        foreach ($starts as $index => $start) {
            $next = $starts[$index + 1]['week'] ?? PHP_INT_MAX;

            // Too close to the next one to be read: the month the calendar
            // opens on, cut short, gives way.
            if ($next - $start['week'] >= self::MONTH_MIN_WEEKS) {
                $square = $place($start['week'], 0);
                $labels[] = $standing
                    ? ['x' => $side - 6, 'y' => $square['y'] + self::CELL, 'label' => $start['label'], 'anchor' => 'end']
                    : ['x' => $square['x'], 'y' => 11, 'label' => $start['label'], 'anchor' => 'start'];
            }
        }

        $monday = new DateTimeImmutable('monday this week', new DateTimeZone('UTC'));

        foreach ([0, 2, 4] as $weekday) {
            $square = $place(0, $weekday);
            $label = $dayName->format($monday->modify('+'.$weekday.' days'));
            $labels[] = $standing
                ? ['x' => $square['x'] + self::CELL / 2, 'y' => 11, 'label' => $label, 'anchor' => 'middle']
                : ['x' => 0, 'y' => $square['y'] + self::CELL, 'label' => $label, 'anchor' => 'start'];
        }

        $weeks = $week + 1;
        $gridWidth = ($standing ? 7 : $weeks) * $step;
        $gridBottom = self::TOP + ($standing ? $weeks : 7) * $step;
        $width = $side + $gridWidth + ($standing ? self::STANDING_RIGHT : 0);
        $legendWidth = (self::LEVELS + 1) * $step;
        // Under the right end of a lying calendar, centred under a standing one.
        $legendX = $standing ? $side + intdiv($gridWidth - $legendWidth, 2) : $width - $legendWidth - 30;
        $legendY = $gridBottom + 6;

        // Worded by the component: `less` and `more` are keys of its catalogue.
        $words = [
            ['x' => $legendX - 6, 'y' => $legendY + self::CELL - 1, 'word' => 'less', 'anchor' => 'end'],
            ['x' => $legendX + $legendWidth + 3, 'y' => $legendY + self::CELL - 1, 'word' => 'more', 'anchor' => 'start'],
        ];

        return [
            'width' => $width,
            'height' => $gridBottom + self::BOTTOM,
            'cells' => $cells,
            'labels' => $labels,
            'words' => $words,
            'legend' => array_map(static fn (int $level): array => [
                'x' => $legendX + $level * $step,
                'y' => $legendY,
                'level' => $level,
            ], range(0, self::LEVELS)),
        ];
    }

    /**
     * The upper bound of each level but the last, from the days with
     * commits: a quarter of them under each.
     *
     * @param array<string, int> $days
     *
     * @return list<int>
     */
    private static function thresholds(array $days): array
    {
        $values = array_values(array_filter($days));
        sort($values);

        if ([] === $values) {
            return [];
        }

        return array_map(
            static fn (int $level): int => $values[(int) floor((count($values) - 1) * $level / self::LEVELS)],
            range(1, self::LEVELS - 1)
        );
    }

    /**
     * @param list<int> $thresholds
     */
    private static function level(int $commits, array $thresholds): int
    {
        if (0 === $commits) {
            return 0;
        }

        foreach ($thresholds as $index => $threshold) {
            if ($commits <= $threshold) {
                return $index + 1;
            }
        }

        return self::LEVELS;
    }
}
