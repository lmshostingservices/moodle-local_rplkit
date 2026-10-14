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
 * Builds output #3 of the RPL Kit: the PRACTICAL SKILL DEMONSTRATION evidence tool.
 *
 * Assignment Benchmarks advanced-grading criteria (gradingform_benchmarks structure:
 * groups -> items -> scores) mapped to a unit of competency's Performance Criteria (one item per
 * criterion, one group per element) and Performance Evidence (one sufficiency group).
 *
 * Compliance is baked in: every Performance Criterion maps to a named item in its element group
 * (validity + sufficiency), remarks are enabled for evidence notes, and the description states
 * that a score is not a competency decision.
 *
 * The structure follows the gradingform_benchmarks form-data shape (unsaved ids are 'NEWID{n}').
 * It must be loaded into an assignment that uses the Assignment Benchmarks grading method; this
 * plugin does not write to gradingform_benchmarks itself.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_benchmarks_builder {
    /** @var int Maximum definition length, in characters AND bytes (database column limit). */
    public const MAX_DEFINITION = 255;

    /** @var int Counter for NEWID keys. */
    private $newid = 0;

    /**
     * Build the Assignment Benchmarks grading criteria structure for a unit of competency.
     *
     * @param array $unit Unit data (see {@see unit::normalise()}). When 'elements' are supplied,
     *   element titles are used as group descriptions; otherwise the description is synthesised
     *   from the element number and the first PC.
     * @return array Criteria structure with 'title', 'description', 'groups', 'options'.
     */
    public function build_benchmarks_criteria(array $unit): array {
        $this->newid = 0;
        $unit = unit::normalise($unit);
        $unitlabel = unit::label($unit);

        $elementtitles = [];
        foreach ($unit['elements'] as $el) {
            $elementtitles[$el['number']] = $el['title'];
        }

        // Group Performance Criteria by element number ("2.3" -> element 2). PCs without a dotted
        // code land in a single catch-all group.
        $byelement = [];
        $nocode = [];
        foreach ($unit['performance_criteria'] as $pc) {
            $elnum = self::element_number($pc['code']);
            if ($elnum !== null) {
                $byelement[$elnum][] = $pc;
            } else {
                $nocode[] = $pc;
            }
        }
        ksort($byelement, SORT_NUMERIC);

        $groups = [];
        foreach ($byelement as $elnum => $elpcs) {
            if (!empty($elementtitles[$elnum])) {
                $desc = 'Element ' . $elnum . ' — ' . $elementtitles[$elnum];
            } else {
                $desc = 'Element ' . $elnum . ' — ' . self::shorten($elpcs[0]['text'], 60);
            }
            $this->add_group($groups, $desc, array_map(fn($pc) => trim($pc['code'] . ' ' . $pc['text']), $elpcs));
        }
        if (!empty($nocode)) {
            $this->add_group($groups, 'Performance Criteria', array_map(fn($pc) => trim($pc['code'] . ' ' . $pc['text']), $nocode));
        }
        if (empty($groups)) {
            // No PCs provided - a single placeholder so the definition is still valid.
            $this->add_group($groups, 'Performance Criteria', [
                '[RPL Kit: no Performance Criteria were supplied. Add the unit\'s Performance Criteria so '
                    . 'an item is generated for each criterion - every criterion must be assessed.]',
            ]);
        }

        // Performance Evidence (frequency / volume): the assessor ticks each once met.
        if (!empty($unit['performance_evidence'])) {
            $this->add_group(
                $groups,
                'Performance Evidence — sufficiency requirements',
                array_map(fn($pe) => $pe['ref'] . ' ' . $pe['text'], $unit['performance_evidence'])
            );
        }

        // Options mirror the gradingform_benchmarks defaults, with remarks enabled so assessors can
        // record evidence against each item.
        $options = [
            'alwaysshowdefinition' => 1,
            'showitempointseval' => 1,
            'showitempointstudent' => 1,
            'enableitemremarks' => 1,
            'enablegroupremarks' => 1,
            'showremarksstudent' => 1,
        ];

        return [
            'title' => 'RPL Practical Skill Demonstration — ' . $unitlabel,
            'description' => 'Assignment Benchmarks grading criteria for RPL practical skill '
                . 'demonstration against ' . $unitlabel . '. Generated by RPL Kit. '
                . 'Review, contextualise and validate before use. '
                . 'A score is NOT a competency decision — the overall Competent / Not Yet '
                . 'Competent judgement must be made holistically by a credentialled assessor '
                . 'against ALL criteria. All safety-critical criteria must be Satisfactory.',
            'groups' => $groups,
            'options' => $options,
        ];
    }

    /**
     * Return the benchmarks criteria as a pretty-printed JSON string.
     *
     * @param array $unit Unit data.
     * @return string JSON (UTF-8).
     */
    public function build_benchmarks_criteria_json(array $unit): string {
        return json_encode(
            $this->build_benchmarks_criteria($unit),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Append a group with one 1-point item per definition.
     *
     * @param array $groups Groups array, modified in place.
     * @param string $description Group description.
     * @param string[] $definitions Item definitions.
     */
    private function add_group(array &$groups, string $description, array $definitions): void {
        $groupid = 'NEWID' . (++$this->newid);
        $items = [];
        $sortorder = 1;
        foreach ($definitions as $def) {
            $items['NEWID' . (++$this->newid)] = [
                'definition' => self::truncdef($def),
                'score' => 1,
                'sortorder' => $sortorder++,
            ];
        }
        $groups[$groupid] = [
            'description' => self::truncdef($description),
            'sortorder' => count($groups) + 1,
            'items' => $items,
        ];
    }

    /**
     * Extract the element number from a PC code such as "1.1" or "2.3.1".
     *
     * @param string $code PC code.
     * @return int|null Element number, or null if none found.
     */
    private static function element_number(string $code): ?int {
        if (preg_match('/^(\d+)\./', trim($code), $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /**
     * Truncate a definition so it fits in 255 characters AND 255 bytes.
     *
     * @param string $s
     * @return string
     */
    private static function truncdef(string $s): string {
        $s = trim(preg_replace('/\s+/u', ' ', $s));
        if (strlen($s) <= self::MAX_DEFINITION) {
            return $s;
        }
        $ellipsis = '…';
        $len = min(\core_text::strlen($s), self::MAX_DEFINITION - 1);
        do {
            $cut = rtrim(\core_text::substr($s, 0, $len)) . $ellipsis;
            $len--;
        } while (strlen($cut) > self::MAX_DEFINITION && $len > 0);
        return $cut;
    }

    /**
     * Shorten a string for use in a group description.
     *
     * @param string $s Source string.
     * @param int $len Maximum length.
     * @return string
     */
    private static function shorten(string $s, int $len): string {
        if (\core_text::strlen($s) <= $len) {
            return $s;
        }
        return \core_text::substr($s, 0, $len - 1) . '…';
    }
}
