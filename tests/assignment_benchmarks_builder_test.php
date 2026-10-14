<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_rplkit;

/**
 * Tests for output #3 (Assignment Benchmarks criteria).
 *
 * @package    local_rplkit
 * @category   test
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_rplkit\assignment_benchmarks_builder
 */
#[\PHPUnit\Framework\Attributes\CoversClass(assignment_benchmarks_builder::class)]
final class assignment_benchmarks_builder_test extends \basic_testcase {
    /**
     * One group per element in numeric order, one item per PC, plus the PE group.
     */
    public function test_grouping(): void {
        $doc = json_decode((new assignment_benchmarks_builder())
            ->build_benchmarks_criteria_json(require(__DIR__ . '/fixtures/unit.php')), true);
        $groups = array_values($doc['groups']);
        $this->assertSame([
            'Element 1 — Work safely',
            'Element 2 — Implement workplace safety requirements',
            'Element 3 — Participate in WHS consultative processes',
            'Element 10 — Element ten criterion (sort-order test)',
            'Performance Evidence — sufficiency requirements',
        ], array_column($groups, 'description'));
        $this->assertSame([1, 2, 3, 4, 5], array_column($groups, 'sortorder'));
        $this->assertSame([3, 2, 1, 1, 2], array_map(fn($g) => count($g['items']), $groups));
        $this->assertSame(
            '1.1 Identify and follow workplace procedures for working safely',
            array_values($groups[0]['items'])[0]['definition']
        );

        $keys = array_keys($doc['groups']);
        foreach ($groups as $g) {
            $keys = array_merge($keys, array_keys($g['items']));
        }
        $this->assertSame($keys, array_unique($keys));
        foreach ($keys as $k) {
            $this->assertMatchesRegularExpression('/^NEWID\d+$/', $k);
        }
    }

    /**
     * Long definitions fit 255 characters and 255 bytes, including multibyte text.
     */
    public function test_truncation(): void {
        $long = str_repeat('Māori cultural safety ✓ ', 40);
        $doc = (new assignment_benchmarks_builder())->build_benchmarks_criteria([
            'code' => 'L', 'name' => 'Long',
            'performance_criteria' => [['code' => '1.1', 'text' => $long], 'Uncoded criterion'],
        ]);
        foreach ($doc['groups'] as $g) {
            foreach ($g['items'] as $item) {
                $this->assertLessThanOrEqual(255, strlen($item['definition']));
                $this->assertLessThanOrEqual(255, \core_text::strlen($item['definition']));
            }
        }
        $this->assertContains('Performance Criteria', array_column($doc['groups'], 'description'));
    }

    /**
     * No Performance Criteria gives a single placeholder group.
     */
    public function test_empty_unit(): void {
        $doc = (new assignment_benchmarks_builder())->build_benchmarks_criteria(['code' => 'X']);
        $this->assertCount(1, $doc['groups']);
        $this->assertSame(1, $doc['options']['enableitemremarks']);
    }
}
