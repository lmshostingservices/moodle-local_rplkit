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

use context_module;
use stdClass;

/**
 * Link to the Assignment Benchmarks grading method (gradingform_benchmarks).
 *
 * Writes the output #3 criteria straight into an assignment's advanced-grading definition through
 * gradingform_benchmarks' own controller (update_definition), so the result is exactly what the
 * Benchmarks editor would have saved. Can also create a new assignment for the practical skill
 * demonstration with Benchmarks as its grading method.
 *
 * gradingform_benchmarks is optional: everything here checks {@see self::is_available()}.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class benchmarks {
    /** @var string Grading method name of the Assignment Benchmarks plugin. */
    public const METHOD = 'benchmarks';

    /**
     * Whether gradingform_benchmarks is installed.
     *
     * @return bool
     */
    public static function is_available(): bool {
        $dir = \core_component::get_component_directory('gradingform_benchmarks');
        if (!$dir || !file_exists($dir . '/lib.php')) {
            return false;
        }
        return (bool) get_config('gradingform_benchmarks', 'version');
    }

    /**
     * Whether Assignment Benchmarks' licence is active (its own unlock check, when it has one).
     *
     * RPL Kit applies the same check as the Benchmarks editor, so it never writes Benchmarks
     * criteria or uses its training.gov.au client while the plugin is locked.
     *
     * @return bool
     */
    public static function is_unlocked(): bool {
        if (!self::is_available()) {
            return false;
        }
        if (!class_exists('\\gradingform_benchmarks\\unlock_verifier')) {
            return true;
        }
        return \gradingform_benchmarks\unlock_verifier::is_unlocked();
    }

    /**
     * Assignments in a course that the current user may attach grading criteria to.
     *
     * @param int $courseid
     * @return array cmid => assignment name, in course order.
     */
    public static function get_assignments(int $courseid): array {
        $out = [];
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->get_instances_of('assign') as $cm) {
            if ($cm->deletioninprogress ?? false) {
                continue;
            }
            if (has_capability('moodle/grade:managegradingforms', context_module::instance($cm->id))) {
                $out[$cm->id] = $cm->get_formatted_name();
            }
        }
        return $out;
    }

    /**
     * Describe the grading set-up of an existing assignment.
     *
     * @param int $cmid Assignment course-module id.
     * @return array ['method' => string|null, 'defined' => bool, 'graded' => bool]
     */
    public static function get_status(int $cmid): array {
        self::require_lib();
        $manager = get_grading_manager(context_module::instance($cmid), 'mod_assign', 'submissions');
        $method = $manager->get_active_method() ?: null;
        $status = ['method' => $method, 'defined' => false, 'graded' => false];
        if (self::is_available()) {
            $controller = $manager->get_controller(self::METHOD);
            $status['defined'] = $controller->is_form_defined();
            $status['graded'] = $status['defined'] && $controller->has_active_instances();
        }
        return $status;
    }

    /**
     * Write the unit's Benchmarks criteria into an assignment and make Benchmarks its grading method.
     *
     * @param int $cmid Assignment course-module id.
     * @param array $unit Unit data.
     * @param bool $replace Replace an existing Benchmarks definition (only if nobody has been graded with it).
     * @return array ['areaid' => int, 'definitionid' => int, 'groups' => int, 'items' => int, 'maxscore' => float]
     * @throws \moodle_exception When Benchmarks is missing, or existing criteria would be overwritten.
     */
    public static function apply_to_assignment(int $cmid, array $unit, bool $replace = false): array {
        if (!self::is_available()) {
            throw new \moodle_exception('errornobenchmarks', 'local_rplkit');
        }
        if (!self::is_unlocked()) {
            throw new \moodle_exception('errorbenchmarkslocked', 'local_rplkit');
        }
        self::require_lib();
        $context = context_module::instance($cmid);
        require_capability('moodle/grade:managegradingforms', $context);

        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        $controller = $manager->get_controller(self::METHOD);
        if ($controller->is_form_defined()) {
            if ($controller->has_active_instances()) {
                throw new \moodle_exception('errorbenchmarksgraded', 'local_rplkit');
            }
            if (!$replace) {
                throw new \moodle_exception('errorbenchmarksexists', 'local_rplkit');
            }
        }

        $criteria = (new assignment_benchmarks_builder())->build_benchmarks_criteria($unit);

        $definition = new stdClass();
        $definition->name = \core_text::substr($criteria['title'], 0, 255);
        $definition->description_editor = [
            'text' => \html_writer::tag('p', s($criteria['description'])),
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ];
        $definition->status = \gradingform_controller::DEFINITION_STATUS_READY;
        $definition->checklist = [
            'groups' => $criteria['groups'],
            'options' => $criteria['options'],
        ];
        $controller->update_definition($definition);
        $manager->set_active_method(self::METHOD);

        // Reload and report what was saved.
        $controller = $manager->get_controller(self::METHOD);
        $saved = $controller->get_definition(true);
        $items = 0;
        foreach ($saved->checklist_groups as $group) {
            $items += count($group['items']);
        }
        $range = $controller->get_min_max_score();
        return [
            'areaid' => (int) $controller->get_areaid(),
            'definitionid' => (int) $saved->id,
            'groups' => count($saved->checklist_groups),
            'items' => $items,
            'maxscore' => (float) ($range['maxscore'] ?? 0),
        ];
    }

    /**
     * Create a new assignment for the practical skill demonstration, graded with Benchmarks, and
     * load the unit's criteria into it.
     *
     * @param int $courseid
     * @param int $section Section number to add it to.
     * @param array $unit Unit data.
     * @param bool $visible Whether students can see it straight away.
     * @return array As {@see self::apply_to_assignment()}, plus 'cmid'.
     */
    public static function create_assignment(int $courseid, int $section, array $unit, bool $visible = false): array {
        global $CFG, $DB;
        if (!self::is_available()) {
            throw new \moodle_exception('errornobenchmarks', 'local_rplkit');
        }
        if (!self::is_unlocked()) {
            throw new \moodle_exception('errorbenchmarkslocked', 'local_rplkit');
        }
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $course = get_course($courseid);
        require_capability('moodle/course:manageactivities', \context_course::instance($courseid));

        $unit = unit::normalise($unit);
        $config = get_config('assign');
        $intro = \html_writer::tag('p', get_string('assignintro', 'local_rplkit', s(unit::label($unit))));

        $moduleinfo = (object) [
            'modulename' => 'assign',
            'module' => (int) $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST),
            'course' => $course->id,
            'section' => $section,
            'visible' => $visible ? 1 : 0,
            'visibleoncoursepage' => 1,
            'cmidnumber' => '',
            'groupmode' => 0,
            'groupingid' => 0,
            'name' => \core_text::substr(get_string('assignname', 'local_rplkit', unit::label($unit)), 0, 255),
            'introeditor' => [
                'text' => $intro,
                'format' => FORMAT_HTML,
                'itemid' => file_get_unused_draft_itemid(),
            ],
            'showdescription' => 0,
            'alwaysshowdescription' => 1,
            'submissiondrafts' => 1,
            'requiresubmissionstatement' => 0,
            'sendnotifications' => 0,
            'sendstudentnotifications' => 1,
            'sendlatenotifications' => 0,
            'duedate' => 0,
            'allowsubmissionsfromdate' => 0,
            'cutoffdate' => 0,
            'gradingduedate' => 0,
            'grade' => 100,
            'teamsubmission' => 0,
            'requireallteammemberssubmit' => 0,
            'teamsubmissiongroupingid' => 0,
            'preventsubmissionnotingroup' => 0,
            'blindmarking' => 0,
            'hidegrader' => 0,
            'attemptreopenmethod' => $config->attemptreopenmethod ?? 'untilpass',
            'maxattempts' => $config->maxattempts ?? 1,
            'markingworkflow' => 0,
            'markingallocation' => 0,
            'markercount' => 0,
            'optionalmarkercount' => 0,
            'multimarkmethod' => null,
            'multimarkrounding' => null,
            'markinganonymous' => 0,
            'activityformat' => 0,
            'timelimit' => 0,
            'submissionattachments' => 0,
            'completionsubmit' => 0,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => get_config('assignsubmission_file', 'maxfiles') ?: 20,
            'assignsubmission_file_maxsizebytes' => get_config('assignsubmission_file', 'maxbytes') ?: 0,
            'assignsubmission_onlinetext_enabled' => 1,
            'assignfeedback_comments_enabled' => 1,
            'advancedgradingmethod_submissions' => self::METHOD,
        ];
        $moduleinfo = create_module($moduleinfo);

        $result = self::apply_to_assignment((int) $moduleinfo->coursemodule, $unit);
        $result['cmid'] = (int) $moduleinfo->coursemodule;
        return $result;
    }

    /**
     * Load the core advanced-grading library.
     */
    private static function require_lib(): void {
        global $CFG;
        require_once($CFG->dirroot . '/grade/grading/lib.php');
    }
}
