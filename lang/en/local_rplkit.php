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
 * Language strings for local_rplkit.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addcriteria'] = 'Add criteria';
$string['addtoassignment'] = 'Add to an assignment';
$string['addtoassignmentintro'] = 'Add the Assignment Benchmarks criteria for {$a} to a course assignment: either a new assignment for the practical skill demonstration, or one that already exists. Benchmarks becomes the assignment\'s grading method.';
$string['appliedcriteria'] = 'Assignment Benchmarks criteria added: {$a->groups} groups, {$a->items} items (maximum {$a->maxscore} points).';
$string['assessment_conditions'] = 'Assessment conditions';
$string['assessment_conditions_help'] = 'Optional. The unit\'s mandatory assessment conditions. Shown at the top of the SmartForm observation checklist.';
$string['assignintro'] = 'RPL practical skill demonstration for {$a}. Upload your evidence (for example video, photos or workplace documents) and describe how it shows each Performance Criterion. Your assessor grades it against the Assignment Benchmarks criteria.';
$string['assignment'] = 'Assignment';
$string['assignname'] = 'RPL Practical Skill Demonstration — {$a}';
$string['benchmarksheading'] = 'Assignment Benchmarks';
$string['benchmarksintro'] = 'Assignment Benchmarks is installed, so the practical skill demonstration criteria (output 3) can be added straight into a course assignment instead of being downloaded.';
$string['benchmarkslocked'] = 'Assignment Benchmarks is installed but not unlocked, so criteria cannot be added to assignments from here. Unlock it with your AI Grader credentials on the Assignment Benchmarks settings page.';
$string['benchmarksmissing'] = 'Install the Assignment Benchmarks grading method (gradingform_benchmarks) to add these criteria straight into an assignment.';
$string['benchmarkssettings'] = 'Assignment Benchmarks settings';
$string['cachedef_unit'] = 'Unit currently loaded in the RPL Kit generator (session only)';
$string['component'] = 'Unit component';
$string['createdassignment'] = 'New assignment created with Assignment Benchmarks criteria: {$a->groups} groups, {$a->items} items (maximum {$a->maxscore} points).';
$string['downloadall'] = 'Download all three (ZIP)';
$string['editunit'] = 'Edit unit';
$string['elements'] = 'Elements';
$string['elements_help'] = "One element per line, numbered, e.g.\n\n<pre>1 Work safely\n2 Implement workplace safety requirements</pre>\n\nElement titles become the group headings in the Assignment Benchmarks criteria.";
$string['errorbenchmarksexists'] = 'This assignment already has Assignment Benchmarks criteria. Tick "Replace existing criteria" to overwrite them.';
$string['errorbenchmarksgraded'] = 'Students have already been graded with this assignment\'s Benchmarks criteria, so they cannot be replaced. Choose another assignment or create a new one.';
$string['errorbenchmarkslocked'] = 'Assignment Benchmarks is not unlocked on this site.';
$string['errornobenchmarks'] = 'The Assignment Benchmarks grading method (gradingform_benchmarks) is not installed.';
$string['errornocomponents'] = 'Enter at least the Performance Criteria or the Knowledge Evidence.';
$string['errornounit'] = 'No unit is loaded. Enter a unit to generate the RPL kit.';
$string['errorzip'] = 'Could not create the RPL kit ZIP file.';
$string['generate'] = 'Generate RPL kit';
$string['generator'] = 'RPL Kit generator';
$string['heading'] = 'RPL Kit — Recognition of Prior Learning assessment-kit generator';
$string['intro'] = 'Enter a unit of competency and RPL Kit generates three ASQA-mapped evidence tools: a theory question set (essay questions mapped to Knowledge Evidence, Moodle XML), a forms-based practical checklist (SmartForm JSON mapped to Performance Criteria), and practical skill-demonstration grading criteria (Assignment Benchmarks JSON mapped to Performance Criteria and Performance Evidence). Copy the unit details from training.gov.au, one item per line.';
$string['itemsfound'] = 'Items found';
$string['kitready'] = 'RPL kit ready: {$a}';
$string['knowledge_evidence'] = 'Knowledge Evidence';
$string['knowledge_evidence_help'] = 'One Knowledge Evidence point per line. Each point becomes one essay question. Points are referenced KE1, KE2, … automatically, or you can start a line with your own reference, e.g. <code>KE4: Emergency procedures</code>.';
$string['loadexample'] = 'Load example (shows the input format)';
$string['newunit'] = 'Start a new unit';
$string['noassignments'] = 'There are no assignments in this course that you can set grading criteria for.';
$string['output'] = 'Output';
$string['outputbenchmarks'] = '3. Practical skill demonstration — Assignment Benchmarks criteria (JSON, requires gradingform_benchmarks)';
$string['outputquiz'] = '1. Theory evidence — {$a} essay question(s), Moodle XML for the question bank';
$string['outputsmartform'] = '2. Practical forms-based evidence — observation / third-party checklist (SmartForm JSON)';
$string['performance_criteria'] = 'Performance Criteria';
$string['performance_criteria_help'] = "One Performance Criterion per line, starting with its code, e.g.\n\n<pre>1.1 Identify and follow workplace procedures\n1.2 Use personal protective equipment</pre>\n\nThe number before the dot sets the element group. Lines without a code are kept in a separate group.";
$string['performance_evidence'] = 'Performance Evidence';
$string['performance_evidence_help'] = 'One Performance Evidence requirement per line (frequency / volume). Each becomes a sufficiency item.';
$string['pluginname'] = 'RPL Kit';
$string['privacy:metadata'] = 'The RPL Kit plugin does not store any personal data; it generates assessment templates from unit-of-competency data.';
$string['replacecriteria'] = 'Replace existing criteria';
$string['replacecriteria_help'] = 'If the assignment already has Assignment Benchmarks criteria, tick this to overwrite them. Criteria that students have already been graded with are never replaced.';
$string['reviewbeforeuse'] = 'Review, contextualise and validate every tool before use, and confirm the unit version is current on training.gov.au. A score is never a competency decision.';
$string['rplkit:manage'] = 'Create and manage RPL assessment kits';
$string['statusbenchmarks'] = 'has Benchmarks criteria';
$string['statusgraded'] = 'Benchmarks criteria in use (graded)';
$string['statusothermethod'] = 'graded with {$a}';
$string['statussimple'] = 'simple direct grading';
$string['target'] = 'Add the criteria to';
$string['targetexisting'] = 'An existing assignment';
$string['targetnew'] = 'A new assignment';
$string['tgafetch'] = 'Fetch from training.gov.au';
$string['tgafetched'] = 'Unit {$a} fetched from training.gov.au. Check it below, complete anything missing, then click Generate RPL kit.';
$string['tgafetchhelp'] = 'Enter the unit code above and fetch the unit to fill in the fields below. Uses Assignment Benchmarks\' training.gov.au connection.';
$string['tgainvalidcode'] = 'Enter a valid unit code (letters and numbers only, e.g. BSBWHS211).';
$string['tganocredentials'] = 'Unit {$a} could not be read from training.gov.au. The training.gov.au web service credentials need to be added on the Assignment Benchmarks settings page. You can still paste the unit in below.';
$string['tganotavailable'] = 'Fetching units needs Assignment Benchmarks 1.2.40 or later, installed and unlocked.';
$string['tganotfound'] = 'Unit {$a} could not be fetched from training.gov.au. Check the code, or paste the unit in below.';
$string['unitcode'] = 'Unit code';
$string['unitcomponents'] = 'Unit components';
$string['unitdetails'] = 'Unit of competency';
$string['unitname'] = 'Unit title';
$string['visibletostudents'] = 'Visible to students';
$string['visibletostudents_help'] = 'Leave unticked to create the assignment hidden, so you can review the criteria and instructions before students see it.';
$string['warnnoke'] = 'No Knowledge Evidence was entered, so the theory question set contains a single placeholder question.';
$string['warnnopc'] = 'No Performance Criteria were entered, so the checklist and the Benchmarks criteria contain placeholders only.';
$string['warnnope'] = 'No Performance Evidence was entered, so the Benchmarks criteria and checklist have no sufficiency section.';
$string['warnpcnocode'] = 'Some Performance Criteria have no code (e.g. 1.1). They are grouped separately instead of under their element.';
