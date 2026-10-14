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
 * Assembles the RPL kit: the three output files, their names, and the ZIP bundle.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class kit {
    /** @var string[] Output types, in kit order. */
    public const TYPES = ['quiz', 'smartform', 'benchmarks'];

    /**
     * File name for one output.
     *
     * @param array $unit Unit data.
     * @param string $type One of self::TYPES, or 'all' for the ZIP.
     * @return string
     */
    public static function filename(array $unit, string $type): string {
        $prefix = unit::file_prefix(unit::normalise($unit));
        switch ($type) {
            case 'quiz':
                return $prefix . '_1_theory_questions_moodlexml.xml';
            case 'smartform':
                return $prefix . '_2_observation_checklist_smartform.json';
            case 'benchmarks':
                return $prefix . '_3_skill_demonstration_benchmarks.json';
            case 'all':
                return $prefix . '_RPL_kit.zip';
        }
        throw new \coding_exception('Unknown RPL Kit output type: ' . $type);
    }

    /**
     * Content of one output.
     *
     * @param array $unit Unit data.
     * @param string $type One of self::TYPES.
     * @return string
     */
    public static function content(array $unit, string $type): string {
        switch ($type) {
            case 'quiz':
                return (new quiz_builder())->build_theory_quiz_xml($unit);
            case 'smartform':
                return (new smartform_builder())->build_observation_kit_json($unit);
            case 'benchmarks':
                return (new assignment_benchmarks_builder())->build_benchmarks_criteria_json($unit);
        }
        throw new \coding_exception('Unknown RPL Kit output type: ' . $type);
    }

    /**
     * Build the ZIP containing all three outputs plus a README, and return its path.
     *
     * @param array $unit Unit data.
     * @param string $dir Directory to write the ZIP into.
     * @return string Path of the ZIP file.
     */
    public static function zip(array $unit, string $dir): string {
        $files = [];
        foreach (self::TYPES as $type) {
            $files[self::filename($unit, $type)] = [self::content($unit, $type)];
        }
        $files['README.txt'] = [self::readme($unit)];
        $path = $dir . '/' . self::filename($unit, 'all');
        $packer = get_file_packer('application/zip');
        if (!$packer->archive_to_pathname($files, $path)) {
            throw new \moodle_exception('errorzip', 'local_rplkit');
        }
        return $path;
    }

    /**
     * Plain-text instructions included in the ZIP.
     *
     * @param array $unit Unit data.
     * @return string
     */
    public static function readme(array $unit): string {
        $unit = unit::normalise($unit);
        $lines = [
            'RPL Kit - ' . unit::label($unit),
            'Generated ' . userdate(time(), get_string('strftimedatetime', 'langconfig')),
            '',
            '1. ' . self::filename($unit, 'quiz'),
            '   Theory evidence: one essay question per Knowledge Evidence point, with a model-answer',
            '   placeholder (general feedback) and a marking guide (information for graders).',
            '   Import: open the course question bank, choose Import > "Moodle XML format", then add',
            '   the questions to a quiz. Complete each model answer before use.',
            '',
            '2. ' . self::filename($unit, 'smartform'),
            '   Practical, forms-based evidence: observation / third-party checklist with one row per',
            '   Performance Criterion, authenticity declaration and assessor decision.',
            '   Use: paste into the SmartForm app.',
            '',
            '3. ' . self::filename($unit, 'benchmarks'),
            '   Practical skill demonstration: Assignment Benchmarks criteria (one group per element,',
            '   one item per Performance Criterion, plus a Performance Evidence sufficiency group).',
            '   Use: requires the gradingform_benchmarks plugin. Easiest: RPL Kit > Add to an',
            '   assignment, which loads these criteria into a new or existing assignment for you.',
            '',
            'Review, contextualise and validate every tool before use. Confirm the unit version is',
            'current on training.gov.au. A score is never a competency decision - the Competent /',
            'Not Yet Competent judgement is made by a credentialled assessor.',
        ];
        return implode("\r\n", $lines) . "\r\n";
    }
}
