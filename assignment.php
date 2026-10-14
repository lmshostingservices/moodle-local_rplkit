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

/**
 * Add the unit's Assignment Benchmarks criteria to a new or existing assignment.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_rplkit\benchmarks;
use local_rplkit\unit;

require_login();
$context = context_system::instance();
require_capability('local/rplkit:manage', $context);

$courseid = optional_param('courseid', 0, PARAM_INT);

$url = new moodle_url('/local/rplkit/assignment.php', $courseid ? ['courseid' => $courseid] : []);
$kiturl = new moodle_url('/local/rplkit/index.php', ['action' => 'kit']);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('addtoassignment', 'local_rplkit'));
$PAGE->set_heading(get_string('heading', 'local_rplkit'));

$unit = cache::make('local_rplkit', 'unit')->get('current');
if (!$unit) {
    redirect(
        new moodle_url('/local/rplkit/index.php'),
        get_string('errornounit', 'local_rplkit'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
if (!benchmarks::is_available()) {
    redirect($kiturl, get_string('errornobenchmarks', 'local_rplkit'), null, \core\output\notification::NOTIFY_ERROR);
}
if (!benchmarks::is_unlocked()) {
    redirect($kiturl, get_string('errorbenchmarkslocked', 'local_rplkit'), null, \core\output\notification::NOTIFY_ERROR);
}
if ($courseid) {
    require_capability('moodle/course:manageactivities', context_course::instance($courseid));
}

$form = new \local_rplkit\form\assignment_form($url, ['courseid' => $courseid]);

if ($form->is_cancelled()) {
    redirect($kiturl);
}

if ($data = $form->get_data()) {
    if (!$courseid) {
        redirect(new moodle_url('/local/rplkit/assignment.php', ['courseid' => (int) $data->pickcourse]));
    }
    if ($data->target === 'new') {
        $result = benchmarks::create_assignment($courseid, (int) $data->section, $unit, !empty($data->visible));
        $message = get_string('createdassignment', 'local_rplkit', (object) $result);
    } else {
        require_capability('moodle/grade:managegradingforms', context_module::instance((int) $data->cmid));
        $result = benchmarks::apply_to_assignment((int) $data->cmid, $unit, !empty($data->replace));
        $result['cmid'] = (int) $data->cmid;
        $message = get_string('appliedcriteria', 'local_rplkit', (object) $result);
    }
    redirect(
        new moodle_url('/grade/grading/manage.php', ['areaid' => $result['areaid']]),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addtoassignment', 'local_rplkit'), 3);
echo html_writer::tag('p', get_string('addtoassignmentintro', 'local_rplkit', s(unit::label($unit))));
$form->display();
echo $OUTPUT->footer();
