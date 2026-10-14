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
 * Tests for the unit parser / normaliser.
 *
 * @package    local_rplkit
 * @category   test
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_rplkit\unit
 */
#[\PHPUnit\Framework\Attributes\CoversClass(unit::class)]
final class unit_test extends \basic_testcase {
    /**
     * Mixed string / array items are normalised and blanks dropped.
     */
    public function test_normalise(): void {
        $u = unit::normalise(require(__DIR__ . '/fixtures/unit.php'));
        $this->assertCount(7, $u['performance_criteria']);
        $this->assertCount(5, $u['knowledge_evidence']);
        $this->assertSame('KE3', $u['knowledge_evidence'][2]['ref']);
        $this->assertSame(
            ['ref' => 'PE1', 'text' => 'Contributed to the health and safety of self and others on at least two '
                . 'occasions'],
            $u['performance_evidence'][0]
        );
        $this->assertSame('PE2', $u['performance_evidence'][1]['ref']);
    }

    /**
     * Non-array components and scalar junk do not break normalisation.
     */
    public function test_normalise_junk(): void {
        $u = unit::normalise(['code' => ['x'], 'performance_criteria' => 'not a list', 'knowledge_evidence' => [[], null, 5]]);
        $this->assertSame('', $u['code']);
        $this->assertSame([], $u['performance_criteria']);
        $this->assertSame([['ref' => 'KE1', 'text' => '5']], $u['knowledge_evidence']);
    }

    /**
     * Automatic references never duplicate an explicit one.
     */
    public function test_unique_refs(): void {
        $u = unit::normalise(['knowledge_evidence' => ['First', ['ref' => 'KE1', 'text' => 'Second'], 'Third']]);
        $this->assertSame(['KE2', 'KE1', 'KE3'], array_column($u['knowledge_evidence'], 'ref'));
    }

    /**
     * Form text is parsed line by line in the documented formats.
     */
    public function test_from_form(): void {
        $u = unit::from_form([
            'code' => ' ABC123 ',
            'name' => 'Test unit',
            'elements' => "1 Work safely\r\n2. Follow procedures\nElement 3: Report\n\nNo number here",
            'performance_criteria' => "1.1 First criterion\n• 1.2. Second criterion\n- Uncoded criterion\n10.1 Tenth",
            'knowledge_evidence' => "KE7: Own reference\n* Bulleted point\nke 9 - lower case ref",
            'performance_evidence' => "PE1 Two occasions\nAnother requirement",
            'assessment_conditions' => 'Simulated environment',
        ]);
        $this->assertSame('ABC123', $u['code']);
        $this->assertSame([
            ['number' => 1, 'title' => 'Work safely'],
            ['number' => 2, 'title' => 'Follow procedures'],
            ['number' => 3, 'title' => 'Report'],
            ['number' => 4, 'title' => 'No number here'],
        ], $u['elements']);
        $this->assertSame([
            ['code' => '1.1', 'text' => 'First criterion'],
            ['code' => '1.2', 'text' => 'Second criterion'],
            ['code' => '', 'text' => 'Uncoded criterion'],
            ['code' => '10.1', 'text' => 'Tenth'],
        ], $u['performance_criteria']);
        $this->assertSame([
            ['ref' => 'KE7', 'text' => 'Own reference'],
            ['ref' => 'KE2', 'text' => 'Bulleted point'],
            ['ref' => 'KE9', 'text' => 'lower case ref'],
        ], $u['knowledge_evidence']);
        $this->assertSame('PE1', $u['performance_evidence'][0]['ref']);
        $this->assertSame('Simulated environment', $u['assessment_conditions']);
    }

    /**
     * to_form() output parses back to the same unit.
     */
    public function test_round_trip(): void {
        $u = unit::normalise(require(__DIR__ . '/fixtures/unit.php'));
        $this->assertEquals($u, unit::from_form(unit::to_form($u)));
        $ex = unit::from_form(unit::example_form_data());
        $this->assertCount(5, $ex['performance_criteria']);
    }

    /**
     * File prefixes are filesystem-safe.
     */
    public function test_file_prefix(): void {
        $this->assertSame('BSB_WHS-211', unit::file_prefix(['code' => 'BSB_WHS-211']));
        $this->assertSame('BSBWHS211x', unit::file_prefix(['code' => 'BSBWHS211/../x']));
        $this->assertSame('unit', unit::file_prefix(['code' => '../']));
    }
}
