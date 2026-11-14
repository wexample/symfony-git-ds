<?php

declare(strict_types=1);

namespace Wexample\SymfonyGitDs\Tests\Unit\Helper;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Wexample\SymfonyGitDs\Helper\GitActivityCalendarHelper;

class GitActivityCalendarHelperTest extends TestCase
{
    /**
     * Every day from the first, its commits given in order.
     *
     * @param list<int> $commits
     *
     * @return array<string, int>
     */
    private function days(
        string $first,
        array $commits
    ): array {
        $days = [];
        $date = new DateTimeImmutable($first);

        foreach ($commits as $count) {
            $days[$date->format('Y-m-d')] = $count;
            $date = $date->modify('+1 day');
        }

        return $days;
    }

    public function testAWeekIsAColumnFromMonday(): void
    {
        // Two weeks from Monday the 5th of January 2026.
        $layout = GitActivityCalendarHelper::layout($this->days('2026-01-05', range(0, 13)), 'en');

        $this->assertCount(14, $layout['cells']);
        $this->assertSame(['x' => 30, 'y' => 16], array_intersect_key($layout['cells'][0], ['x' => 0, 'y' => 0]));
        // Sunday, the bottom of the first column.
        $this->assertSame(['x' => 30, 'y' => 94], array_intersect_key($layout['cells'][6], ['x' => 0, 'y' => 0]));
        // The next Monday opens the second.
        $this->assertSame(['x' => 43, 'y' => 16], array_intersect_key($layout['cells'][7], ['x' => 0, 'y' => 0]));
        $this->assertSame(56, $layout['width']);
        $this->assertSame(129, $layout['height']);
        $this->assertSame(13, $layout['cells'][13]['commits']);
    }

    public function testTheShadeIsAQuarterOfTheDaysWithCommits(): void
    {
        $layout = GitActivityCalendarHelper::layout($this->days('2026-01-05', range(0, 13)), 'en');

        // Thirteen days with commits, 1 to 13: bounds 4, 7 and 10.
        $this->assertSame(
            [0, 1, 1, 1, 1, 2, 2, 2, 3, 3, 3, 4, 4, 4],
            array_column($layout['cells'], 'level')
        );
        $this->assertSame([0, 1, 2, 3, 4], array_column($layout['legend'], 'level'));
    }

    public function testAYearWithoutCommitsIsAllOfTheLowestShade(): void
    {
        $layout = GitActivityCalendarHelper::layout($this->days('2026-01-05', array_fill(0, 7, 0)), 'en');

        $this->assertSame(array_fill(0, 7, 0), array_column($layout['cells'], 'level'));
    }

    public function testMonthsAndDaysAreNamed(): void
    {
        $layout = GitActivityCalendarHelper::layout($this->days('2026-01-05', range(0, 13)), 'en');

        $this->assertSame(['Jan', 'Mon', 'Wed', 'Fri'], array_column($layout['labels'], 'label'));
        $this->assertSame(['x' => 30, 'y' => 11, 'label' => 'Jan', 'anchor' => 'start'], $layout['labels'][0]);
        $this->assertSame([26, 52, 78], array_column(array_slice($layout['labels'], 1), 'y'));
    }

    public function testAMonthCutShortGivesWayToTheNext(): void
    {
        // Opens on Monday the 26th of January; February starts that same week.
        $layout = GitActivityCalendarHelper::layout($this->days('2026-01-26', array_fill(0, 21, 1)), 'en');

        $this->assertSame(['Feb', 'Mon', 'Wed', 'Fri'], array_column($layout['labels'], 'label'));
    }

    public function testStandingTheWeeksAreRows(): void
    {
        $layout = GitActivityCalendarHelper::layout($this->days('2026-01-05', range(0, 13)), 'en', standing: true);

        $this->assertSame(['x' => 38, 'y' => 29], array_intersect_key($layout['cells'][7], ['x' => 0, 'y' => 0]));
        $this->assertSame(155, $layout['width']);
        $this->assertSame(64, $layout['height']);
        // A letter a day, above the columns.
        $this->assertSame(['M', 'W', 'F'], array_column(array_slice($layout['labels'], 1), 'label'));
    }
}
