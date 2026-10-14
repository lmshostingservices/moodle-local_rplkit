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
 * Tests for the kit assembler (file names and ZIP bundle).
 *
 * @package    local_rplkit
 * @category   test
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_rplkit\kit
 */
#[\PHPUnit\Framework\Attributes\CoversClass(kit::class)]
final class kit_test extends \advanced_testcase {
    /**
     * The ZIP contains the three outputs and a README, with content identical to the single files.
     */
    public function test_zip(): void {
        $this->resetAfterTest();
        $unit = require(__DIR__ . '/fixtures/unit.php');
        $path = kit::zip($unit, make_request_directory());
        $this->assertSame('BSBWHS211_RPL_kit.zip', basename($path));

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        sort($names);
        $this->assertSame([
            'BSBWHS211_1_theory_questions_moodlexml.xml',
            'BSBWHS211_2_observation_checklist_smartform.json',
            'BSBWHS211_3_skill_demonstration_benchmarks.json',
            'README.txt',
        ], $names);
        foreach (kit::TYPES as $type) {
            $this->assertSame(kit::content($unit, $type), $zip->getFromName(kit::filename($unit, $type)));
        }
        $zip->close();
    }

    /**
     * Unknown output types are rejected.
     */
    public function test_unknown_type(): void {
        $this->expectException(\coding_exception::class);
        kit::filename(['code' => 'X'], 'evil');
    }
}
