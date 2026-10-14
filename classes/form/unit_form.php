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

/**
 * Unit-of-competency entry form for the RPL Kit generator.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unit_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'unithdr', get_string('unitdetails', 'local_rplkit'));
        $mform->addElement('text', 'code', get_string('unitcode', 'local_rplkit'), ['size' => 20, 'maxlength' => 30]);
        $mform->setType('code', PARAM_TEXT);
        $mform->addRule('code', null, 'required', null, 'client');
        $mform->addRule('code', null, 'maxlength', 30, 'client');

        if (\local_rplkit\tga::is_available()) {
            // Fills the rest of the form from training.gov.au (via Assignment Benchmarks) for review.
            $mform->addElement('submit', 'fetchtga', get_string('tgafetch', 'local_rplkit'));
            $mform->registerNoSubmitButton('fetchtga');
            $mform->addElement('static', 'fetchtgahelp', '', get_string('tgafetchhelp', 'local_rplkit'));
        }

        $mform->addElement('text', 'name', get_string('unitname', 'local_rplkit'), ['size' => 70, 'maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255, 'client');

        $textareas = [
            'elements' => 6,
            'performance_criteria' => 12,
            'knowledge_evidence' => 10,
            'performance_evidence' => 6,
            'assessment_conditions' => 4,
        ];
        $mform->addElement('header', 'componentshdr', get_string('unitcomponents', 'local_rplkit'));
        $mform->setExpanded('componentshdr');
        foreach ($textareas as $field => $rows) {
            $mform->addElement('textarea', $field, get_string($field, 'local_rplkit'), ['rows' => $rows, 'cols' => 90]);
            $mform->setType($field, PARAM_TEXT);
            $mform->addHelpButton($field, $field, 'local_rplkit');
        }

        $this->add_action_buttons(false, get_string('generate', 'local_rplkit'));
    }

    /**
     * Validation: at least Performance Criteria or Knowledge Evidence must be supplied.
     *
     * @param array $data Submitted data.
     * @param array $files Uploaded files.
     * @return array Errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $unit = \local_rplkit\unit::from_form($data);
        if ($unit['code'] === '') {
            $errors['code'] = get_string('required');
        }
        if ($unit['name'] === '') {
            $errors['name'] = get_string('required');
        }
        if (empty($unit['performance_criteria']) && empty($unit['knowledge_evidence'])) {
            $errors['performance_criteria'] = get_string('errornocomponents', 'local_rplkit');
        }
        return $errors;
    }
}
