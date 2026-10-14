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

namespace local_rplkit\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_rplkit\benchmarks;

/**
 * Choose where the Assignment Benchmarks criteria go: a course, then a new or existing assignment.
 *
 * Custom data: 'courseid' (int, 0 for the course-picker step).
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_form extends \moodleform {
    /** @var array cmid => status for the course's assignments (see benchmarks::get_status()). */
    protected $statuses = [];

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $courseid = (int) ($this->_customdata['courseid'] ?? 0);

        if (!$courseid) {
            // Not named 'courseid': assignment.php reads courseid from the request to pick the step.
            $mform->addElement('course', 'pickcourse', get_string('course'), [
                'requiredcapabilities' => ['moodle/course:manageactivities'],
            ]);
            $mform->addRule('pickcourse', null, 'required', null, 'client');
            $this->add_action_buttons(true, get_string('next'));
            return;
        }

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $course = get_course($courseid);
        $mform->addElement('static', 'coursename', get_string('course'), format_string($course->fullname));

        $assignments = benchmarks::get_assignments($courseid);
        $options = [];
        foreach ($assignments as $cmid => $name) {
            $status = benchmarks::get_status($cmid);
            $this->statuses[$cmid] = $status;
            if ($status['graded']) {
                $note = get_string('statusgraded', 'local_rplkit');
            } else if ($status['defined']) {
                $note = get_string('statusbenchmarks', 'local_rplkit');
            } else if ($status['method']) {
                $note = get_string('statusothermethod', 'local_rplkit', $status['method']);
            } else {
                $note = get_string('statussimple', 'local_rplkit');
            }
            $options[$cmid] = $name . ' — ' . $note;
        }

        $targets = [];
        $targets[] = $mform->createElement('radio', 'target', '', get_string('targetnew', 'local_rplkit'), 'new');
        $targets[] = $mform->createElement(
            'radio',
            'target',
            '',
            get_string('targetexisting', 'local_rplkit'),
            'existing',
            $options ? [] : ['disabled' => 'disabled']
        );
        $mform->addGroup($targets, 'targetgroup', get_string('target', 'local_rplkit'), '<br>', false);
        $mform->setDefault('target', 'new');

        // New assignment.
        $sections = [];
        foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
            if (!empty($section->component)) {
                continue; // Delegated sections (e.g. subsections) are not offered.
            }
            $sections[$section->section] = get_section_name($course, $section);
        }
        $mform->addElement('select', 'section', get_string('section'), $sections);
        $mform->hideIf('section', 'target', 'neq', 'new');
        $mform->addElement('advcheckbox', 'visible', get_string('visibletostudents', 'local_rplkit'));
        $mform->addHelpButton('visible', 'visibletostudents', 'local_rplkit');
        $mform->setDefault('visible', 0);
        $mform->hideIf('visible', 'target', 'neq', 'new');

        // Existing assignment.
        if ($options) {
            $mform->addElement('select', 'cmid', get_string('assignment', 'local_rplkit'), $options);
            $mform->hideIf('cmid', 'target', 'neq', 'existing');
            $mform->addElement('advcheckbox', 'replace', get_string('replacecriteria', 'local_rplkit'));
            $mform->addHelpButton('replace', 'replacecriteria', 'local_rplkit');
            $mform->hideIf('replace', 'target', 'neq', 'existing');
        } else {
            $mform->addElement('static', 'noassignments', '', get_string('noassignments', 'local_rplkit'));
        }

        $this->add_action_buttons(true, get_string('addcriteria', 'local_rplkit'));
    }

    /**
     * Validation: refuse to overwrite criteria that are in use, or without confirmation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['courseid'])) {
            return $errors;
        }
        if (($data['target'] ?? '') === 'existing') {
            $cmid = (int) ($data['cmid'] ?? 0);
            $status = $this->statuses[$cmid] ?? null;
            if (!$status) {
                $errors['cmid'] = get_string('required');
            } else if ($status['graded']) {
                $errors['cmid'] = get_string('errorbenchmarksgraded', 'local_rplkit');
            } else if ($status['defined'] && empty($data['replace'])) {
                $errors['replace'] = get_string('errorbenchmarksexists', 'local_rplkit');
            }
        }
        return $errors;
    }
}
