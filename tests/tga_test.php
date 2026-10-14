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
 * Tests for fetching units from training.gov.au through Assignment Benchmarks.
 *
 * @package    local_rplkit
 * @category   test
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_rplkit\tga
 */
#[\PHPUnit\Framework\Attributes\CoversClass(tga::class)]
final class tga_test extends \advanced_testcase {
    /**
     * A Benchmarks-format result (as returned by training_component::get_unit()).
     *
     * @return array
     */
    public static function benchmarks_result(): array {
        return [
            'code' => 'BSBWHS211',
            'title' => 'Contribute to the health and safety of self and others',
            'elements' => [
                ['code' => '1', 'name' => 'Work safely', 'performanceCriteria' => ['Follow procedures', 'Use PPE'],
                    'criteria' => [['code' => '1.1', 'text' => 'Follow procedures'], ['code' => '1.2', 'text' => 'Use PPE']]],
                // Older Benchmarks versions return criteria text only.
                ['code' => '2', 'name' => 'Report hazards', 'performanceCriteria' => ['Identify hazards', 'Report them']],
            ],
            'performanceEvidence' => ['Contributed on at least two occasions'],
            'knowledgeEvidence' => ['Duty holders', 'Hierarchy of control'],
            'assessmentConditions' => ['Workplace or simulated environment', 'Access to WHS legislation'],
        ];
    }

    /**
     * Restore the real source after each test.
     */
    protected function tearDown(): void {
        tga::set_source_for_testing(null);
        parent::tearDown();
    }

    /**
     * Benchmarks' result maps onto every RPL Kit unit component, keeping or rebuilding PC codes.
     */
    public function test_convert(): void {
        $unit = tga::convert(self::benchmarks_result());
        $this->assertSame('BSBWHS211', $unit['code']);
        $this->assertSame('Contribute to the health and safety of self and others', $unit['name']);
        $this->assertSame(
            [['number' => 1, 'title' => 'Work safely'], ['number' => 2, 'title' => 'Report hazards']],
            $unit['elements']
        );
        $this->assertSame(['1.1', '1.2', '2.1', '2.2'], array_column($unit['performance_criteria'], 'code'));
        $this->assertSame(['KE1', 'KE2'], array_column($unit['knowledge_evidence'], 'ref'));
        $this->assertSame('Contributed on at least two occasions', $unit['performance_evidence'][0]['text']);
        $this->assertSame("Workplace or simulated environment\nAccess to WHS legislation", $unit['assessment_conditions']);
        // And the converted unit drives all three outputs.
        $this->assertSame(4, count(array_merge(...array_map(
            fn($g) => array_values($g['items']),
            array_slice(array_values((new assignment_benchmarks_builder())->build_benchmarks_criteria($unit)['groups']), 0, 2)
        ))));
    }

    /**
     * fetch() validates the code, reports a miss, and converts a hit.
     */
    public function test_fetch(): void {
        $this->resetAfterTest();
        $calls = [];
        tga::set_source_for_testing(function (string $code) use (&$calls) {
            $calls[] = $code;
            return $code === 'BSBWHS211' ? self::benchmarks_result() : null;
        });
        $this->assertTrue(tga::is_available());

        $bad = tga::fetch('../x');
        $this->assertNull($bad['unit']);
        $this->assertSame([], $calls, 'Invalid codes must not reach training.gov.au.');

        $miss = tga::fetch('ABC999');
        $this->assertNull($miss['unit']);
        $this->assertStringContainsString('ABC999', $miss['error']);

        $hit = tga::fetch(' bsbwhs211 ');
        $this->assertSame('', $hit['error']);
        $this->assertCount(4, $hit['unit']['performance_criteria']);
        $this->assertSame(['ABC999', 'BSBWHS211'], $calls);
    }

    /**
     * The real client is used when Assignment Benchmarks is installed (served from its cache here).
     */
    public function test_fetch_through_benchmarks_cache(): void {
        $this->resetAfterTest();
        if (!class_exists('\\gradingform_benchmarks\\tga\\training_component') || !benchmarks::is_available()) {
            $this->markTestSkipped('Assignment Benchmarks with training.gov.au support is not installed.');
        }
        if (class_exists('\\gradingform_benchmarks\\unlock_verifier')) {
            \cache::make('core', 'config')->set(
                \gradingform_benchmarks\unlock_verifier::CACHE_KEY,
                ['unlocked' => true, 'expires' => time() + 3600]
            );
        }
        \cache::make('gradingform_benchmarks', 'tgaunits')->set('BSBWHS211', self::benchmarks_result());
        $result = tga::fetch('BSBWHS211');
        $this->assertSame('', $result['error']);
        $this->assertSame('Work safely', $result['unit']['elements'][0]['title']);
    }
}
