<?php

namespace Wexample\SymfonyGitDs\Helper;

use DateTimeImmutable;
use DateTimeZone;
use IntlDateFormatter;

/**
 * Where each day of a year of commits stands in a contribution calendar: a
 * square a day, a column a week from Monday, the months named above their
 * first column, Monday, Wednesday and Friday named on the left. Its shade is
 * one of five levels: none, then the quarters of the days with commits.
 */
final class GitActivityCalendarHelper
{
    public const int CELL = 10;

    public const int GAP = 3;

    public const int LEVELS = 4;

    /** Room for the names of the days, on the left. */
    private const int LEFT = 30;

    /** Room for the names of the months, above. */
    private const int TOP = 16;

    /** Room for the legend, below. */
    private const int BOTTOM = 22;

    /** A month named closer than that many columns to the last one is left unnamed. */
    private const int MONTH_MIN_COLUMNS = 3;

    /**
     * @param array<string, int> $days commits by `Y-m-d`, every day in order
     *
     * @return array{
     *     width: int,
     *     height: int,
     *     cells: list<array{x: int, y: int, level: int, commits: int, date: string}>,
     *     months: list<array{x: int, label: string}>,
     *     weekdays: list<array{y: int, label: string}>,
     *     legend: list<array{x: int, level: int}>,
     *     legend_y: int,
     *     legend_less_x: int,
     *     legend_more_x: int
     * }
     */
    public static function layout(array $days, string $locale): array
    {
        $step = self::CELL + self::GAP;
        $thresholds = self::thresholds($days);
        $monthName = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'UTC', null, 'LLL');
        $dayName = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'UTC', null, 'EEE');
        $fullDate = new IntlDateFormatter($locale, IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'UTC');
        $cells = [];
        $starts = [];
        $column = 0;

        foreach ($days as $day => $commits) {
            $date = new DateTimeImmutable($day, new DateTimeZone('UTC'));
            $row = (int) $date->format('N') - 1;

            if (0 === $row && [] !== $cells) {
                ++$column;
            }

            // A month is named above the week of its first day, the one the
            // calendar opens on above its first week.
            if ([] === $cells || 1 === (int) $date->format('j')) {
                $starts[] = ['column' => $column, 'label' => $monthName->format($date)];
            }

            $cells[] = [
                'x' => self::LEFT + $column * $step,
                'y' => self::TOP + $row * $step,
                'level' => self::level($commits, $thresholds),
                'commits' => $commits,
                'date' => $fullDate->format($date),
            ];
        }

        $months = [];

        foreach ($starts as $index => $start) {
            $next = $starts[$index + 1]['column'] ?? PHP_INT_MAX;

            // Too close to the next one to be read: the month the calendar
            // opens on, cut short, gives way.
            if ($next - $start['column'] >= self::MONTH_MIN_COLUMNS) {
                $months[] = ['x' => self::LEFT + $start['column'] * $step, 'label' => $start['label']];
            }
        }

        $width = self::LEFT + ($column + 1) * $step;
        $gridBottom = self::TOP + 7 * $step;
        $legendWidth = (self::LEVELS + 1) * $step;
        $legendX = $width - $legendWidth - 30;
        $monday = new DateTimeImmutable('monday this week', new DateTimeZone('UTC'));

        return [
            'width' => $width,
            'height' => $gridBottom + self::BOTTOM,
            'cells' => $cells,
            'months' => $months,
            'weekdays' => array_map(static fn (int $row): array => [
                'y' => self::TOP + $row * $step + self::CELL,
                'label' => $dayName->format($monday->modify('+'.$row.' days')),
            ], [0, 2, 4]),
            'legend' => array_map(static fn (int $level): array => [
                'x' => $legendX + $level * $step,
                'level' => $level,
            ], range(0, self::LEVELS)),
            'legend_y' => $gridBottom + 6,
            'legend_less_x' => $legendX - 6,
            'legend_more_x' => $legendX + $legendWidth + 3,
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
