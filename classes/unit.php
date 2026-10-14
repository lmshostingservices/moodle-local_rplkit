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
 * Unit-of-competency data: parsing from the generator form and normalisation for the builders.
 *
 * Every builder runs its input through {@see self::normalise()}, so all three outputs accept the
 * same unit array and treat each component identically, whether an item is given as a plain
 * string or as an array with a code/ref and text.
 *
 * Canonical shape:
 *   'code' => string, 'name' => string,
 *   'elements' => [['number' => int, 'title' => string], ...],
 *   'performance_criteria' => [['code' => string, 'text' => string], ...],
 *   'knowledge_evidence' => [['ref' => string, 'text' => string], ...],
 *   'performance_evidence' => [['ref' => string, 'text' => string], ...],
 *   'assessment_conditions' => string.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unit {
    /**
     * Normalise a unit array into the canonical shape. Blank items are dropped.
     *
     * @param array $unit Raw unit data (strings or arrays per item).
     * @return array Canonical unit data.
     */
    public static function normalise(array $unit): array {
        $out = [
            'code' => self::clean($unit['code'] ?? ''),
            'name' => self::clean($unit['name'] ?? ''),
            'elements' => [],
            'performance_criteria' => [],
            'knowledge_evidence' => [],
            'performance_evidence' => [],
            'assessment_conditions' => trim((string) ($unit['assessment_conditions'] ?? '')),
        ];

        $n = 0;
        foreach (self::as_list($unit['elements'] ?? []) as $el) {
            $n++;
            if (is_array($el)) {
                $num = (int) ($el['number'] ?? $n);
                $title = self::clean($el['title'] ?? ($el['text'] ?? ''));
            } else {
                $num = $n;
                $title = self::clean($el);
            }
            if ($title !== '' && $num > 0) {
                $out['elements'][] = ['number' => $num, 'title' => $title];
            }
        }

        foreach (self::as_list($unit['performance_criteria'] ?? []) as $pc) {
            if (is_array($pc)) {
                $code = self::clean($pc['code'] ?? '');
                $text = self::clean($pc['text'] ?? '');
            } else {
                $code = '';
                $text = self::clean($pc);
            }
            if ($text !== '') {
                $out['performance_criteria'][] = ['code' => $code, 'text' => $text];
            }
        }

        foreach (['knowledge_evidence' => 'KE', 'performance_evidence' => 'PE'] as $key => $prefix) {
            $items = [];
            foreach (self::as_list($unit[$key] ?? []) as $item) {
                if (is_array($item)) {
                    $ref = self::clean($item['ref'] ?? '');
                    $text = self::clean($item['text'] ?? '');
                } else {
                    $ref = '';
                    $text = self::clean($item);
                }
                if ($text !== '') {
                    $items[] = ['ref' => $ref, 'text' => $text];
                }
            }
            // Items without a reference are numbered by position (KE3 = third point), skipping any
            // reference already used explicitly so references stay unique.
            $used = array_filter(array_column($items, 'ref'));
            foreach ($items as $i => $item) {
                if ($item['ref'] === '') {
                    $n = $i + 1;
                    while (in_array($prefix . $n, $used, true)) {
                        $n++;
                    }
                    $item['ref'] = $prefix . $n;
                    $used[] = $item['ref'];
                }
                $out[$key][] = $item;
            }
        }

        return $out;
    }

    /**
     * Build a canonical unit from the generator form's fields (one item per line).
     *
     * Accepted line formats:
     *   Elements:             "1 Work safely", "1. Work safely", "Element 1: Work safely"
     *   Performance Criteria: "1.1 Identify ...", "1.1. Identify ..." (lines without a code are kept)
     *   Knowledge / Performance Evidence: one point per line, optionally prefixed "KE3:" / "PE2 -";
     *                         leading bullets ("-", "*", "•") are removed.
     *
     * @param \stdClass|array $data Form data.
     * @return array Canonical unit data.
     */
    public static function from_form($data): array {
        $data = (array) $data;

        $elements = [];
        foreach (self::lines($data['elements'] ?? '') as $line) {
            if (preg_match('/^(?:element\s*)?(\d+)\s*[\.\):\-–—]?\s+(.+)$/iu', $line, $m)) {
                $elements[] = ['number' => (int) $m[1], 'title' => $m[2]];
            } else {
                $elements[] = ['number' => count($elements) + 1, 'title' => $line];
            }
        }

        $pcs = [];
        foreach (self::lines($data['performance_criteria'] ?? '') as $line) {
            if (preg_match('/^(\d+(?:\.\d+)+)\.?\s+(.+)$/u', $line, $m)) {
                $pcs[] = ['code' => $m[1], 'text' => $m[2]];
            } else {
                $pcs[] = ['code' => '', 'text' => $line];
            }
        }

        $evidence = [];
        foreach (['knowledge_evidence' => 'KE', 'performance_evidence' => 'PE'] as $key => $prefix) {
            $evidence[$key] = [];
            foreach (self::lines($data[$key] ?? '') as $line) {
                if (preg_match('/^(' . $prefix . '\s?\d+)\s*[:\.\-–—]?\s+(.+)$/iu', $line, $m)) {
                    $evidence[$key][] = ['ref' => strtoupper(str_replace(' ', '', $m[1])), 'text' => $m[2]];
                } else {
                    $evidence[$key][] = $line;
                }
            }
        }

        return self::normalise([
            'code' => $data['code'] ?? '',
            'name' => $data['name'] ?? '',
            'elements' => $elements,
            'performance_criteria' => $pcs,
            'knowledge_evidence' => $evidence['knowledge_evidence'],
            'performance_evidence' => $evidence['performance_evidence'],
            'assessment_conditions' => $data['assessment_conditions'] ?? '',
        ]);
    }

    /**
     * Convert a canonical unit back into form field values (for re-editing).
     *
     * @param array $unit Canonical unit data.
     * @return array Form field values.
     */
    public static function to_form(array $unit): array {
        $unit = self::normalise($unit);
        return [
            'code' => $unit['code'],
            'name' => $unit['name'],
            'elements' => implode("\n", array_map(fn($e) => $e['number'] . ' ' . $e['title'], $unit['elements'])),
            'performance_criteria' => implode("\n", array_map(
                fn($p) => trim($p['code'] . ' ' . $p['text']),
                $unit['performance_criteria']
            )),
            'knowledge_evidence' => implode("\n", array_map(
                fn($k) => $k['ref'] . ' ' . $k['text'],
                $unit['knowledge_evidence']
            )),
            'performance_evidence' => implode("\n", array_map(
                fn($k) => $k['ref'] . ' ' . $k['text'],
                $unit['performance_evidence']
            )),
            'assessment_conditions' => $unit['assessment_conditions'],
        ];
    }

    /**
     * A short label "CODE Name" for the unit.
     *
     * @param array $unit Canonical unit data.
     * @return string
     */
    public static function label(array $unit): string {
        return trim(($unit['code'] ?? '') . ' ' . ($unit['name'] ?? ''));
    }

    /**
     * A filesystem-safe prefix for downloaded files, based on the unit code.
     *
     * @param array $unit Canonical unit data.
     * @return string
     */
    public static function file_prefix(array $unit): string {
        $code = preg_replace('/[^A-Za-z0-9_\-]+/', '', (string) ($unit['code'] ?? ''));
        return $code !== '' ? $code : 'unit';
    }

    /**
     * Example data showing the expected input format.
     *
     * The content is illustrative only (shaped like a BSB unit) and is NOT an authoritative copy of
     * any unit; always use the current unit from training.gov.au.
     *
     * @return array Form field values.
     */
    public static function example_form_data(): array {
        return [
            'code' => 'BSBWHS211',
            'name' => 'Contribute to the health and safety of self and others',
            'elements' => "1 Work safely\n2 Implement workplace safety requirements\n3 Participate in WHS consultative processes",
            'performance_criteria' => "1.1 Identify and follow workplace procedures and work instructions for working safely\n"
                . "1.2 Identify and use personal protective equipment according to procedures\n"
                . "2.1 Identify designated persons for reporting queries and concerns about safety\n"
                . "2.2 Identify existing and potential hazards in the workplace and report them to designated persons\n"
                . "3.1 Contribute to workplace meetings, inspections or other consultative activities",
            'knowledge_evidence' => "Roles and responsibilities of duty holders under WHS legislation\n"
                . "Hazard identification and the hierarchy of risk control\n"
                . "Workplace emergency procedures",
            'performance_evidence' => "Contributed to the health and safety of self and others on at least two occasions",
            'assessment_conditions' => 'Skills must be demonstrated in a workplace or simulated environment.',
        ];
    }

    /**
     * Split text into trimmed, non-empty lines with leading bullet characters removed.
     *
     * @param mixed $text Raw textarea content.
     * @return string[]
     */
    private static function lines($text): array {
        $out = [];
        foreach (preg_split('/\R/u', (string) $text) as $line) {
            $line = trim(preg_replace('/^[\s\-\*•·▪◦]+/u', '', $line));
            if ($line !== '') {
                $out[] = $line;
            }
        }
        return $out;
    }

    /**
     * Collapse whitespace in a scalar value.
     *
     * @param mixed $value
     * @return string
     */
    private static function clean($value): string {
        if (!is_scalar($value)) {
            return '';
        }
        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    /**
     * Return the value as a list, or an empty list when it isn't an array.
     *
     * @param mixed $value
     * @return array
     */
    private static function as_list($value): array {
        return is_array($value) ? array_values($value) : [];
    }
}
