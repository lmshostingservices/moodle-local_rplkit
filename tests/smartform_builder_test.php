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
 * Tests for output #2 (SmartForm observation checklist).
 *
 * @package    local_rplkit
 * @category   test
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_rplkit\smartform_builder
 */
#[\PHPUnit\Framework\Attributes\CoversClass(smartform_builder::class)]
final class smartform_builder_test extends \basic_testcase {
    /**
     * Valid JSON, expected top-level keys, unique ids, one matrix row per non-blank PC.
     */
    public function test_structure(): void {
        $json = (new smartform_builder())->build_observation_kit_json(require(__DIR__ . '/fixtures/unit.php'));
        $doc = json_decode($json, true);
        $this->assertIsArray($doc);
        $this->assertSame(['title', 'description', 'confidence', 'suggestions', 'fields'], array_keys($doc));

        $ids = [];
        $matrix = null;
        foreach ($doc['fields'] as $f) {
            $ids[] = $f['id'];
            if ($f['type'] === 'matrix') {
                $matrix = $f;
                foreach ($f['matrixRows'] as $row) {
                    $ids[] = $row['id'];
                    foreach ($row['columns'] as $col) {
                        $ids[] = $col['id'];
                    }
                }
            }
        }
        $this->assertSame($ids, array_unique($ids));
        $this->assertNotNull($matrix);
        $this->assertCount(7, $matrix['matrixRows']);
        $this->assertSame('1.3 Identify and report hazards & risks in the work area', $matrix['matrixRows'][2]['label']);
    }

    /**
     * Labels and options are plain text; rich content is HTML-escaped.
     */
    public function test_text_encoding(): void {
        $doc = (new smartform_builder())->build_observation_kit(require(__DIR__ . '/fixtures/unit.php'));
        $labels = array_column($doc['fields'], 'label');
        $this->assertContains('Candidate & Assessment Details', $labels);
        foreach ($doc['fields'] as $f) {
            $this->assertStringNotContainsString('&amp;', $f['label']);
            foreach ($f['options'] ?? [] as $opt) {
                $this->assertStringNotContainsString('&amp;', $opt);
                $this->assertStringNotContainsString('&#039;', $opt);
            }
            if ($f['type'] === 'text-paragraph') {
                $this->assertStringNotContainsString('<incidents>', $f['content']);
            }
        }
        $conditions = array_values(array_filter($doc['fields'], fn($f) => $f['type'] === 'text-paragraph'
            && strpos($f['content'], 'simulated environment') !== false));
        $this->assertStringContainsString('&amp; procedures', $conditions[0]['content']);
    }

    /**
     * Performance Evidence given as strings or ref/text arrays renders as text (not "Array").
     */
    public function test_performance_evidence_forms(): void {
        $doc = (new smartform_builder())->build_observation_kit(require(__DIR__ . '/fixtures/unit.php'));
        $pe = array_values(array_filter($doc['fields'], fn($f) => strpos($f['id'], 'pe_') === 0))[0];
        $this->assertSame([
            'PE1 Contributed to the health and safety of self and others on at least two occasions',
            'PE2 Identified & reported at least one hazard',
        ], $pe['options']);
    }

    /**
     * A unit with no Performance Criteria produces a placeholder paragraph and no matrix.
     */
    public function test_empty_unit(): void {
        $doc = (new smartform_builder())->build_observation_kit(['code' => 'X', 'name' => 'Y']);
        $this->assertSame([], array_filter($doc['fields'], fn($f) => $f['type'] === 'matrix'));
        $this->assertNotEmpty(array_filter($doc['fields'], fn($f) => $f['type'] === 'text-paragraph'
            && strpos($f['content'], 'no Performance Criteria') !== false));
    }
}
