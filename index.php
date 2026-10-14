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
 * RPL Kit generator: enter a unit of competency, then download the three evidence tools.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_rplkit\kit;
use local_rplkit\unit;

require_login();
$context = context_system::instance();
require_capability('local/rplkit:manage', $context);

$action = optional_param('action', '', PARAM_ALPHA);

$url = new moodle_url('/local/rplkit/index.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_rplkit'));
$PAGE->set_heading(get_string('heading', 'local_rplkit'));

$cache = cache::make('local_rplkit', 'unit');

if ($action === 'new') {
    require_sesskey();
    $cache->delete('current');
    redirect($url);
}

$form = new \local_rplkit\form\unit_form($url);

// Fetch from training.gov.au: fill the form with the unit for review (nothing is generated yet).
$fetcherror = '';
if ($form->no_submit_button_pressed() && optional_param('fetchtga', '', PARAM_TEXT) !== '') {
    require_sesskey();
    $fetched = \local_rplkit\tga::fetch(optional_param('code', '', PARAM_TEXT));
    if ($fetched['unit']) {
        $cache->set('fetched', $fetched['unit']);
        redirect(
            new moodle_url($url, ['action' => 'fetched']),
            get_string('tgafetched', 'local_rplkit', s(unit::label($fetched['unit']))),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    $fetcherror = $fetched['error'];
}

if ($data = $form->get_data()) {
    $cache->set('current', unit::from_form($data));
    redirect(new moodle_url($url, ['action' => 'kit']));
}

$current = $cache->get('current');

if ($action === 'example') {
    $form->set_data(unit::example_form_data());
} else if ($action === 'fetched' && ($fetchedunit = $cache->get('fetched'))) {
    $form->set_data(unit::to_form($fetchedunit));
} else if ($action === 'edit' && $current) {
    $form->set_data(unit::to_form($current));
}

echo $OUTPUT->header();
if ($fetcherror !== '') {
    echo $OUTPUT->notification($fetcherror, \core\output\notification::NOTIFY_ERROR);
}

if ($action === 'kit' && $current) {
    echo $OUTPUT->heading(get_string('kitready', 'local_rplkit', s(unit::label($current))), 3);

    $counts = [
        get_string('elements', 'local_rplkit') => count($current['elements']),
        get_string('performance_criteria', 'local_rplkit') => count($current['performance_criteria']),
        get_string('knowledge_evidence', 'local_rplkit') => count($current['knowledge_evidence']),
        get_string('performance_evidence', 'local_rplkit') => count($current['performance_evidence']),
    ];
    $table = new html_table();
    $table->attributes['class'] = 'generaltable w-auto mb-4';
    $table->head = [get_string('component', 'local_rplkit'), get_string('itemsfound', 'local_rplkit')];
    foreach ($counts as $label => $n) {
        $table->data[] = [$label, $n];
    }
    echo html_writer::table($table);

    // Warnings about gaps that produce placeholders.
    $warnings = [];
    if (empty($current['knowledge_evidence'])) {
        $warnings[] = get_string('warnnoke', 'local_rplkit');
    }
    if (empty($current['performance_criteria'])) {
        $warnings[] = get_string('warnnopc', 'local_rplkit');
    } else if (array_filter($current['performance_criteria'], fn($pc) => !preg_match('/^\d+\./', $pc['code']))) {
        $warnings[] = get_string('warnpcnocode', 'local_rplkit');
    }
    if (empty($current['performance_evidence'])) {
        $warnings[] = get_string('warnnope', 'local_rplkit');
    }
    foreach ($warnings as $w) {
        echo $OUTPUT->notification($w, \core\output\notification::NOTIFY_WARNING);
    }

    $outputs = [
        'quiz' => get_string('outputquiz', 'local_rplkit', (new \local_rplkit\quiz_builder())->count_questions($current)),
        'smartform' => get_string('outputsmartform', 'local_rplkit'),
        'benchmarks' => get_string('outputbenchmarks', 'local_rplkit'),
    ];
    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->head = [get_string('output', 'local_rplkit'), get_string('file'), ''];
    foreach ($outputs as $type => $desc) {
        $dl = new moodle_url('/local/rplkit/download.php', ['type' => $type, 'sesskey' => sesskey()]);
        $table->data[] = [
            $desc,
            html_writer::tag('code', s(kit::filename($current, $type))),
            html_writer::link($dl, get_string('download'), ['class' => 'btn btn-secondary']),
        ];
    }
    echo html_writer::table($table);

    $all = new moodle_url('/local/rplkit/download.php', ['type' => 'all', 'sesskey' => sesskey()]);
    echo html_writer::div(
        html_writer::link($all, get_string('downloadall', 'local_rplkit'), ['class' => 'btn btn-primary mr-2 me-2']) .
        html_writer::link(
            new moodle_url($url, ['action' => 'edit']),
            get_string('editunit', 'local_rplkit'),
            ['class' => 'btn btn-secondary mr-2 me-2']
        ) .
        html_writer::link(
            new moodle_url($url, ['action' => 'new', 'sesskey' => sesskey()]),
            get_string('newunit', 'local_rplkit'),
            ['class' => 'btn btn-secondary']
        ),
        'mb-4'
    );

    // Assignment Benchmarks link: put output #3 straight into an assignment.
    echo $OUTPUT->heading(get_string('benchmarksheading', 'local_rplkit'), 4);
    if (\local_rplkit\benchmarks::is_available() && !\local_rplkit\benchmarks::is_unlocked()) {
        echo html_writer::tag('p', get_string('benchmarkslocked', 'local_rplkit'), ['class' => 'text-muted']);
    } else if (\local_rplkit\benchmarks::is_available()) {
        echo html_writer::tag('p', get_string('benchmarksintro', 'local_rplkit'));
        echo html_writer::div(
            html_writer::link(
                new moodle_url('/local/rplkit/assignment.php'),
                get_string('addtoassignment', 'local_rplkit'),
                ['class' => 'btn btn-primary']
            ),
            'mb-4'
        );
    } else {
        echo html_writer::tag('p', get_string('benchmarksmissing', 'local_rplkit'), ['class' => 'text-muted']);
    }

    echo $OUTPUT->notification(get_string('reviewbeforeuse', 'local_rplkit'), \core\output\notification::NOTIFY_INFO);
} else {
    echo $OUTPUT->box(get_string('intro', 'local_rplkit'));
    echo html_writer::div(
        html_writer::link(new moodle_url($url, ['action' => 'example']), get_string('loadexample', 'local_rplkit')),
        'mb-3'
    );
    $form->display();
}

echo $OUTPUT->footer();
