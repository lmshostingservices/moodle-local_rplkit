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

defined('MOODLE_INTERNAL') || die();

/**
 * Builds output #3 of the RPL Kit: the PRACTICAL SKILL DEMONSTRATION evidence tool — a
 * Moodle assignment graded with Assignment Benchmarks advanced-grading criteria
 * (gradingform_benchmarks schema: groups → items → scores) mapped to a unit of competency's
 * Performance Criteria and Performance Evidence.
 *
 * Compliance is baked in: every Performance Criterion maps to a named item in a logical
 * element group (validity + sufficiency), items carry a score so assessors can track which
 * criteria have been demonstrated, remarks fields are enabled for evidence notes, and the
 * generated description states that a score is not a competency decision.
 *
 * The JSON output can be:
 *   - Pasted directly into the gradingform_benchmarks editor via its import endpoint, or
 *   - Written programmatically to gradingbench_grp / gradingbench_items via the plugin's API.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_benchmarks_builder {

    /**
     * Build the Assignment Benchmarks grading criteria structure for a unit of competency.
     *
     * Returns the native gradingform_benchmarks form-data structure (groups → items → options)
     * that can be saved via gradingform_benchmarks_controller or encoded as JSON for export.
     *
     * @param array $unit Unit-of-competency components. Expected keys:
     *   'code'  (string)  national unit code, e.g. 'SITHFAB021'
     *   'name'  (string)  unit title
     *   'performance_criteria' (array) list of ['code' => '1.1', 'text' => '...']
     *   'performance_evidence' (array,  optional) list of strings or ['ref' => '...', 'text' => '...']
     *   'elements'            (array,  optional) list of ['number' => 1, 'title' => '...'] — when
     *                         present, element titles are used as group descriptions; when absent the
     *                         group description is synthesised from the element number and the first PC.
     * @return array Benchmarks criteria structure with 'groups', 'options', 'title', 'description'.
     */
    public function build_benchmarks_criteria(array $unit): array {
        $code = trim((string) ($unit['code'] ?? ''));
        $name = trim((string) ($unit['name'] ?? ''));
        $unitlabel = trim($code . ' ' . $name);

        $pcs        = is_array($unit['performance_criteria'] ?? null) ? $unit['performance_criteria'] : [];
        $pevidence  = is_array($unit['performance_evidence'] ?? null)  ? $unit['performance_evidence']  : [];
        $elements   = is_array($unit['elements'] ?? null)              ? $unit['elements']              : [];

        // Build a lookup: element number -> title (from the explicit elements list, if provided).
        $elementtitles = [];
        foreach ($elements as $el) {
            $num = (int) ($el['number'] ?? 0);
            if ($num > 0) {
                $elementtitles[$num] = trim((string) ($el['title'] ?? ''));
            }
        }

        // -----------------------------------------------------------------------
        // Step 1: Group Performance Criteria by element number.
        //
        // PC codes follow the Australian training-package convention: "1.1", "1.2",
        // "2.1" … The element number is the digit(s) before the first dot. When a PC
        // has no dotted code (plain text), all such criteria land in a single group.
        // -----------------------------------------------------------------------
        $byelement = [];   // [ elementno => [ pc, pc, ... ] ]
        $nocode    = [];   // PCs without a parseable element number.

        foreach ($pcs as $pc) {
            if (is_array($pc)) {
                $pccode = trim((string) ($pc['code'] ?? ''));
                $pctext = trim((string) ($pc['text'] ?? ''));
            } else {
                $pccode = '';
                $pctext = trim((string) $pc);
            }
            if ($pctext === '') {
                continue;
            }
            $elnum = self::element_number($pccode);
            if ($elnum !== null) {
                $byelement[$elnum][] = ['code' => $pccode, 'text' => $pctext];
            } else {
                $nocode[] = ['code' => $pccode, 'text' => $pctext];
            }
        }
        ksort($byelement);

        // -----------------------------------------------------------------------
        // Step 2: Build the gradingform_benchmarks groups/items array.
        //
        // Key format: 'NEWID{n}' — this is the format the plugin uses for unsaved
        // definitions in its checklist editor; it accepts the same keys when importing.
        // -----------------------------------------------------------------------
        $groups  = [];
        $newid   = 0;

        $make_newid = static function () use (&$newid): string {
            return 'NEWID' . (++$newid);
        };

        // One group per element.
        foreach ($byelement as $elnum => $elPcs) {
            $groupid = $make_newid();

            // Group description: use the explicit element title when available;
            // otherwise synthesise "Element N — <first PC abbreviated>".
            if (isset($elementtitles[$elnum]) && $elementtitles[$elnum] !== '') {
                $groupdesc = 'Element ' . $elnum . ' — ' . $elementtitles[$elnum];
            } else {
                $firstpc = self::shorten($elPcs[0]['text'], 60);
                $groupdesc = 'Element ' . $elnum . (($firstpc !== '') ? ' — ' . $firstpc : '');
            }

            $items = [];
            $sortorder = 1;
            foreach ($elPcs as $pc) {
                $itemid = $make_newid();
                $deflabel = ($pc['code'] !== '') ? $pc['code'] . ' ' . $pc['text'] : $pc['text'];
                $items[$itemid] = [
                    'definition' => self::truncdef($deflabel),
                    'score'      => 1,
                    'sortorder'  => $sortorder++,
                ];
            }

            $groups[$groupid] = [
                'description' => self::truncdef($groupdesc),
                'sortorder'   => count($groups) + 1,
                'items'       => $items,
            ];
        }

        // Ungrouped PCs (no element code) land in a catch-all group.
        if (!empty($nocode)) {
            $groupid = $make_newid();
            $items   = [];
            $sortorder = 1;
            foreach ($nocode as $pc) {
                $itemid = $make_newid();
                $deflabel = ($pc['code'] !== '') ? $pc['code'] . ' ' . $pc['text'] : $pc['text'];
                $items[$itemid] = [
                    'definition' => self::truncdef($deflabel),
                    'score'      => 1,
                    'sortorder'  => $sortorder++,
                ];
            }
            $groups[$groupid] = [
                'description' => 'Performance Criteria',
                'sortorder'   => count($groups) + 1,
                'items'       => $items,
            ];
        }

        // Fallback: no PCs provided — emit a single placeholder group so the
        // definition is still valid and the assessor knows what to fill in.
        if (empty($groups)) {
            $gid = $make_newid();
            $iid = $make_newid();
            $groups[$gid] = [
                'description' => 'Performance Criteria',
                'sortorder'   => 1,
                'items'       => [
                    $iid => [
                        'definition' => '[RPL Kit: fetch this unit from training.gov.au so an item '
                            . 'is generated for each Performance Criterion — every criterion must be '
                            . 'assessed for sufficiency.]',
                        'score'    => 1,
                        'sortorder' => 1,
                    ],
                ],
            ];
        }

        // -----------------------------------------------------------------------
        // Step 3: Performance Evidence group (frequency / volume requirements).
        //
        // Each PE point becomes a checklist item — the assessor ticks it once the
        // required number of demonstrations has been observed (sufficiency gate).
        // -----------------------------------------------------------------------
        if (!empty($pevidence)) {
            $pegroupid = $make_newid();
            $items     = [];
            $sortorder = 1;
            foreach ($pevidence as $pe) {
                if (is_array($pe)) {
                    $peref  = trim((string) ($pe['ref']  ?? ''));
                    $petext = trim((string) ($pe['text'] ?? ''));
                } else {
                    $peref  = '';
                    $petext = trim((string) $pe);
                }
                if ($petext === '') {
                    continue;
                }
                $itemid   = $make_newid();
                $deflabel = ($peref !== '') ? $peref . ' ' . $petext : $petext;
                $items[$itemid] = [
                    'definition' => self::truncdef($deflabel),
                    'score'      => 1,
                    'sortorder'  => $sortorder++,
                ];
            }
            if (!empty($items)) {
                $groups[$pegroupid] = [
                    'description' => 'Performance Evidence — sufficiency requirements',
                    'sortorder'   => count($groups) + 1,
                    'items'       => $items,
                ];
            }
        }

        // -----------------------------------------------------------------------
        // Step 4: Options — mirror gradingform_benchmarks defaults (see lib.php
        // gradingform_benchmarks_controller::get_default_options()).
        // Remarks are enabled so assessors can record evidence against each item.
        // -----------------------------------------------------------------------
        $options = [
            'alwaysshowdefinition' => 1,
            'showitempointseval'   => 1,
            'showitempointstudent' => 1,
            'enableitemremarks'    => 1,
            'enablegroupremarks'   => 1,
            'showremarksstudent'   => 1,
        ];

        return [
            'title'       => 'RPL Practical Skill Demonstration — ' . $unitlabel,
            'description' => 'Assignment Benchmarks grading criteria for RPL practical skill '
                . 'demonstration against ' . $unitlabel . '. Generated by RPL Kit. '
                . 'Review, contextualise and validate before use. '
                . 'A score is NOT a competency decision — the overall Competent / Not Yet '
                . 'Competent judgement must be made holistically by a credentialled assessor '
                . 'against ALL criteria. All safety-critical criteria must be Satisfactory.',
            'groups'      => $groups,
            'options'     => $options,
        ];
    }

    /**
     * Return the benchmarks criteria as a formatted JSON string ready to use with the
     * gradingform_benchmarks editor or for embedding in a .mbz course backup.
     *
     * @param array $unit @see build_benchmarks_criteria()
     * @return string JSON string (UTF-8, pretty-printed).
     */
    public function build_benchmarks_criteria_json(array $unit): string {
        return json_encode(
            $this->build_benchmarks_criteria($unit),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    // ---------------------------------------------------------------------------
    // Internal helpers.
    // ---------------------------------------------------------------------------

    /**
     * Extract the element number from a PC code such as "1.1" or "2.3.1".
     * Returns null when the code has no recognisable dotted-number prefix.
     *
     * @param string $code PC code, e.g. '1.1', '2.3', '10.2'.
     * @return int|null Element number, or null if none found.
     */
    private static function element_number(string $code): ?int {
        if (preg_match('/^(\d+)\./', trim($code), $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /**
     * Truncate a definition to the maximum length accepted by the benchmarks editor (255 chars).
     * The plugin validates that definitions are ≤ 255 characters (see checklisteditor.php).
     *
     * @param string $s
     * @return string
     */
    private static function truncdef(string $s): string {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        if (mb_strlen($s) <= 255) {
            return $s;
        }
        return mb_substr($s, 0, 254) . '…';
    }

    /**
     * Shorten a string for use in a group description.
     *
     * @param string $s   Source string.
     * @param int    $len Maximum length.
     * @return string
     */
    private static function shorten(string $s, int $len): string {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        if (mb_strlen($s) <= $len) {
            return $s;
        }
        return mb_substr($s, 0, $len - 1) . '…';
    }
}
