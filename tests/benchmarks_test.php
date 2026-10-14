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

global $CFG;
require_once($CFG->dirroot . '/grade/grading/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Tests for the Assignment Benchmarks link (needs gradingform_benchmarks installed).
 *
 * @package    local_rplkit
 * @category   test
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_rplkit\benchmarks
 */
#[\PHPUnit\Framework\Attributes\CoversClass(benchmarks::class)]
final class benchmarks_test extends \advanced_testcase {
    /**
     * Skip when gradingform_benchmarks is not installed.
     */
    protected function setUp(): void {
        parent::setUp();
        if (!benchmarks::is_available()) {
            $this->markTestSkipped('gradingform_benchmarks is not installed.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();
        self::unlock_benchmarks();
    }

    /**
     * Mark Assignment Benchmarks as unlocked (its licence check caches its result in core/config).
     */
    public static function unlock_benchmarks(): void {
        if (class_exists('\\gradingform_benchmarks\\unlock_verifier')) {
            \cache::make('core', 'config')->set(
                \gradingform_benchmarks\unlock_verifier::CACHE_KEY,
                ['unlocked' => true, 'expires' => time() + 3600]
            );
        }
    }

    /**
     * Criteria are not written while Assignment Benchmarks is locked.
     */
    public function test_locked_benchmarks_refused(): void {
        if (!class_exists('\\gradingform_benchmarks\\unlock_verifier')) {
            $this->markTestSkipped('This Benchmarks version has no licence check.');
        }
        \cache::make('core', 'config')->set(
            \gradingform_benchmarks\unlock_verifier::CACHE_KEY,
            ['unlocked' => false, 'expires' => time() + 3600]
        );
        $course = $this->getDataGenerator()->create_course();
        $this->assertFalse(benchmarks::is_unlocked());
        $this->expectException(\moodle_exception::class);
        benchmarks::create_assignment((int) $course->id, 0, $this->unit());
    }

    /**
     * The fixture unit.
     *
     * @return array
     */
    private function unit(): array {
        return require(__DIR__ . '/fixtures/unit.php');
    }

    /**
     * Load the saved Benchmarks definition for an assignment.
     *
     * @param int $cmid
     * @return \gradingform_benchmarks_controller
     */
    private function controller(int $cmid): \gradingform_benchmarks_controller {
        $manager = get_grading_manager(\context_module::instance($cmid), 'mod_assign', 'submissions');
        return $manager->get_controller('benchmarks');
    }

    /**
     * A new assignment is created hidden, graded with Benchmarks, with every group and item saved.
     */
    public function test_create_assignment(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $result = benchmarks::create_assignment((int) $course->id, 1, $this->unit());

        $cm = get_coursemodule_from_id('assign', $result['cmid'], 0, false, MUST_EXIST);
        $this->assertEquals(0, $cm->visible);
        $this->assertEquals(1, $cm->sectionnum ?? $DB->get_field('course_sections', 'section', ['id' => $cm->section]));
        $this->assertStringStartsWith(
            'RPL Practical Skill Demonstration — BSBWHS211',
            $DB->get_field('assign', 'name', ['id' => $cm->instance])
        );

        $manager = get_grading_manager(\context_module::instance($cm->id), 'mod_assign', 'submissions');
        $this->assertSame('benchmarks', $manager->get_active_method());

        $this->assertSame(5, $result['groups']);
        $this->assertSame(9, $result['items']);
        $this->assertEquals(9.0, $result['maxscore']);

        $definition = $this->controller($cm->id)->get_definition(true);
        $this->assertEquals(\gradingform_controller::DEFINITION_STATUS_READY, $definition->status);
        $groups = array_values($definition->checklist_groups);
        $this->assertSame('Element 1 — Work safely', $groups[0]['description']);
        $this->assertSame('Element 10 — Element ten criterion (sort-order test)', $groups[3]['description']);
        $this->assertSame('Performance Evidence — sufficiency requirements', $groups[4]['description']);
        $items = array_values($groups[0]['items']);
        $this->assertSame('1.1 Identify and follow workplace procedures for working safely', $items[0]['definition']);
        $this->assertTrue($this->controller($cm->id)->is_form_available());
    }

    /**
     * Criteria can be added to an existing assignment; existing criteria are only replaced on request
     * and never once a student has been graded with them.
     */
    public function test_apply_existing_and_replace_rules(): void {
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $status = benchmarks::get_status((int) $assign->cmid);
        $this->assertFalse($status['defined']);

        $first = benchmarks::apply_to_assignment((int) $assign->cmid, $this->unit());
        $this->assertSame(9, $first['items']);
        $this->assertTrue(benchmarks::get_status((int) $assign->cmid)['defined']);
        $this->assertSame([(int) $assign->cmid => $assign->name], benchmarks::get_assignments((int) $course->id));

        try {
            benchmarks::apply_to_assignment((int) $assign->cmid, $this->unit());
            $this->fail('Existing criteria were overwritten without confirmation.');
        } catch (\moodle_exception $e) {
            $this->assertSame('errorbenchmarksexists', $e->errorcode);
        }

        $smaller = [
            'code' => 'X1',
            'name' => 'Smaller',
            'performance_criteria' => [['code' => '1.1', 'text' => 'One'], ['code' => '1.2', 'text' => 'Two']],
        ];
        $second = benchmarks::apply_to_assignment((int) $assign->cmid, $smaller, true);
        $this->assertSame(1, $second['groups']);
        $this->assertSame(2, $second['items']);
        $this->assertSame($first['definitionid'], $second['definitionid']);
    }

    /**
     * End to end: an assessor grades a student with the generated criteria through the assignment,
     * and the grade reaches the gradebook; afterwards the criteria are locked.
     */
    public function test_grade_student_with_generated_criteria(): void {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $result = benchmarks::create_assignment((int) $course->id, 0, $this->unit(), true);
        $cm = get_coursemodule_from_id('assign', $result['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $assign = new \assign($context, $cm, $course);

        // Tick 3 of the 9 items.
        $definition = $this->controller($cm->id)->get_definition(true);
        $groups = [];
        $ticked = 0;
        foreach ($definition->checklist_groups as $gid => $group) {
            $groups[$gid] = ['items' => []];
            foreach ($group['items'] as $iid => $item) {
                $groups[$gid]['items'][$iid] = $ticked < 3 ? ['id' => $iid, 'remark' => 'Observed'] : [];
                $ticked++;
            }
        }
        $assign->save_grade($student->id, (object) [
            'attemptnumber' => -1,
            'addattempt' => 0,
            'workflowstate' => '',
            'applytoall' => 0,
            'sendstudentnotifications' => false,
            'advancedgrading' => ['groups' => $groups],
            'assignfeedbackcomments_editor' => ['text' => 'Assessor feedback', 'format' => FORMAT_HTML],
        ]);

        $grades = grade_get_grades($course->id, 'mod', 'assign', $cm->instance, $student->id);
        $this->assertEqualsWithDelta(33.33, (float) $grades->items[0]->grades[$student->id]->grade, 0.5);

        $this->assertTrue(benchmarks::get_status($cm->id)['graded']);
        $this->expectException(\moodle_exception::class);
        benchmarks::apply_to_assignment($cm->id, $this->unit(), true);
    }

    /**
     * The definition texts pass the Benchmarks editor's own 255-byte limit.
     */
    public function test_long_text_fits_editor_limit(): void {
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $long = str_repeat('Māori cultural safety ✓ ', 40);
        benchmarks::apply_to_assignment((int) $assign->cmid, [
            'code' => 'L', 'name' => 'Long',
            'performance_criteria' => [['code' => '1.1', 'text' => $long]],
        ]);
        foreach ($this->controller((int) $assign->cmid)->get_definition(true)->checklist_groups as $group) {
            $this->assertLessThanOrEqual(255, strlen($group['description']));
            foreach ($group['items'] as $item) {
                $this->assertLessThanOrEqual(255, strlen($item['definition']));
            }
        }
    }
}
