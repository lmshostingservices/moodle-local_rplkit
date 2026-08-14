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
 * RPL Kit main page.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/rplkit:manage', $context);

$PAGE->set_url(new moodle_url('/local/rplkit/index.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_rplkit'));
$PAGE->set_heading(get_string('heading', 'local_rplkit'));

echo $OUTPUT->header();
echo $OUTPUT->box(get_string('intro', 'local_rplkit'));

// v0.3.0: all three ASQA evidence outputs are implemented.
//   Output #1: \local_rplkit\quiz_builder::build_theory_quiz_xml()
//   Output #2: \local_rplkit\smartform_builder::build_observation_kit_json()
//   Output #3: \local_rplkit\assignment_benchmarks_builder::build_benchmarks_criteria_json()
// The generator UI (unit selection + download) is wired up in a subsequent release once the
// training.gov.au unit-fetch integration is connected to the platform.
echo html_writer::tag('p', get_string('pluginname', 'local_rplkit') . ' v0.3.0 — all three '
    . 'ASQA evidence outputs are implemented: theory quiz (output #1), SmartForm observation '
    . 'checklist (output #2), and Assignment Benchmarks practical-skill criteria (output #3). '
    . 'The full generator UI will be available once the training.gov.au unit-fetch integration '
    . 'is connected to the platform.');

echo $OUTPUT->footer();
