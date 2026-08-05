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
 * Builds a SmartForm-compatible JSON document (output #2 of the RPL Kit): a practical,
 * forms-based RPL evidence tool — an observation / third-party checklist — mapped to a unit
 * of competency's Performance Criteria and gated by the ASQA rules of evidence.
 *
 * The output matches the SmartForm AI schema exactly (title, description, confidence,
 * suggestions, fields[]) so it can be pasted straight into the SmartForm app. Every generated
 * item carries an explicit unit-component mapping in its helpText, per the ASQA requirement
 * that no Performance Criterion is left unmapped, and the form embeds the authenticity and
 * third-party controls that RPL evidence requires.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class smartform_builder {
    /** @var int Running counter used to mint unique, stable field ids. */
    private $seq = 0;

    /**
     * Build the complete SmartForm document for a unit's practical observation RPL tool.
     *
     * @param array $unit Unit-of-competency components. Expected keys:
     *   'code'  (string)  national unit code, e.g. 'SITHFAB021'
     *   'name'  (string)  unit title
     *   'performance_criteria' (array) list of ['code' => '1.1', 'text' => '...']
     *   'assessment_conditions' (string, optional) mandatory conditions text
     *   'performance_evidence'  (array,  optional) list of strings (frequency/volume)
     * @return array SmartForm JSON structure (encode with json_encode()).
     */
    public function build_observation_kit(array $unit): array {
        $this->seq = 0;

        $code = trim((string) ($unit['code'] ?? ''));
        $name = trim((string) ($unit['name'] ?? ''));
        $pcs  = is_array($unit['performance_criteria'] ?? null) ? $unit['performance_criteria'] : [];
        $conditions = trim((string) ($unit['assessment_conditions'] ?? ''));
        $pevidence  = is_array($unit['performance_evidence'] ?? null) ? $unit['performance_evidence'] : [];

        $unitlabel = trim($code . ' ' . $name);
        $fields = [];

        // --- Header ---------------------------------------------------------------
        $fields[] = $this->section('Practical Observation Checklist (RPL Evidence) — ' . $unitlabel);
        $fields[] = $this->paragraph(
            'This is a practical evidence-gathering tool for Recognition of Prior Learning against '
            . self::esc($unitlabel) . '. The assessor observes the candidate (or verifies workplace '
            . 'evidence) against each Performance Criterion below, records what was observed, gathers a '
            . 'third-party report where the evidence is from the workplace, and makes an overall '
            . 'Competent / Not Yet Competent decision. A score is NOT a competency decision.'
        );

        // --- Candidate & assessor identity ---------------------------------------
        $fields[] = $this->section('Candidate & Assessment Details');
        $fields[] = $this->text('Candidate name', true, '50%');
        $fields[] = $this->text('Candidate identifier / USI', false, '50%');
        $fields[] = $this->text('Assessor name', true, '50%');
        $fields[] = $this->date('Date of observation', true, '50%');
        $fields[] = $this->text('Location / workplace context', false, '100%');

        // --- Assessment conditions (from the training product) --------------------
        if ($conditions !== '') {
            $fields[] = $this->section('Assessment Conditions (mandatory)');
            $fields[] = $this->paragraph(self::esc($conditions));
        }

        // --- The observation matrix: one row per Performance Criterion ------------
        // Each PC row is judged Satisfactory / Not yet satisfactory with an evidence note —
        // this is the observable-behaviour checklist ASQA expects, fully mapped to the unit.
        $fields[] = $this->section('Observation against Performance Criteria');
        if (!empty($pcs)) {
            $rows = [];
            foreach ($pcs as $pc) {
                $pccode = trim((string) ($pc['code'] ?? ''));
                $pctext = trim((string) ($pc['text'] ?? (is_string($pc) ? $pc : '')));
                $rowlabel = trim(($pccode !== '' ? $pccode . ' ' : '') . $pctext);
                $rows[] = [
                    'id'      => 'pc_' . $this->next(),
                    'label'   => $rowlabel,
                    'columns' => [
                        [
                            'id'      => 'c_outcome_' . $this->seq,
                            'type'    => 'radio',
                            'label'   => 'Outcome',
                            'options' => ['Satisfactory', 'Not yet satisfactory'],
                            'width'   => '25%',
                        ],
                        [
                            'id'    => 'c_evidence_' . $this->seq,
                            'type'  => 'textarea',
                            'label' => 'Evidence observed',
                            'width' => 'auto',
                        ],
                    ],
                ];
            }
            $fields[] = [
                'id'    => 'pc_matrix_' . $this->next(),
                'type'  => 'matrix',
                'label' => 'Performance Criteria — observation record',
                'required' => true,
                'helpText' => 'Every Performance Criterion of ' . self::esc($unitlabel)
                    . ' must be observed as Satisfactory, with evidence, for a Competent decision.',
                'matrixRows' => $rows,
                'matrixSettings' => [
                    'exportBorders' => true,
                    'showBorders'   => true,
                    'headerStyle'   => 'both',
                    'cellPadding'   => 'normal',
                    'alternateRows' => true,
                ],
                'style' => ['width' => '100%'],
            ];
        } else {
            $fields[] = $this->paragraph('[RPL Kit: no Performance Criteria were supplied for '
                . self::esc($unitlabel) . '. Fetch the unit from training.gov.au so every criterion '
                . 'is mapped — an unmapped criterion is the most common RPL non-compliance.]');
        }

        // --- Performance Evidence sufficiency (frequency/volume) ------------------
        if (!empty($pevidence)) {
            $fields[] = $this->section('Performance Evidence — sufficiency');
            $opts = [];
            foreach ($pevidence as $pe) {
                $opts[] = self::esc(trim((string) $pe));
            }
            $fields[] = [
                'id'       => 'pe_' . $this->next(),
                'type'     => 'checkbox',
                'label'    => 'Confirm each Performance Evidence requirement has been demonstrated the required number of times / over the required range',
                'required' => true,
                'options'  => $opts,
                'helpText' => 'Sufficiency (ASQA rule of evidence): the volume/frequency in the '
                    . 'unit\'s Performance Evidence must be met, not a single instance.',
                'style'    => ['width' => '100%'],
            ];
        }

        // --- Third-party report (workplace evidence) -----------------------------
        $fields[] = $this->section('Third-Party Report (for workplace evidence)');
        $fields[] = $this->paragraph('Complete when evidence is verified by a workplace supervisor. '
            . 'A third-party report is SUPPORTING evidence only — the assessor must corroborate it '
            . '(e.g. via a competency conversation) and makes the final judgement.');
        $fields[] = $this->text('Third party name', false, '50%');
        $fields[] = $this->text('Role / position', false, '50%');
        $fields[] = $this->text('Relationship to candidate', false, '50%');
        $fields[] = $this->tel('Contact number', false, '50%');
        $fields[] = $this->text('Period / context over which the candidate was observed', false, '100%');
        $fields[] = $this->radio('Do you confirm the candidate performed these tasks themselves, to the required standard?',
            ['Yes', 'No'], false, '100%');
        $fields[] = $this->textarea('Specific tasks / criteria you are attesting to', false, '100%');
        $fields[] = $this->signature('Third party signature', false);
        $fields[] = $this->date('Date', false, '50%');

        // --- Authenticity declaration (candidate) --------------------------------
        $fields[] = $this->section('Authenticity Declaration');
        $fields[] = [
            'id'       => 'auth_' . $this->next(),
            'type'     => 'checkbox',
            'label'    => 'Candidate declaration',
            'required' => true,
            'options'  => [
                'I declare that the evidence provided for this RPL is my own genuine work, performed by me, and is not plagiarised or generated by artificial intelligence.',
            ],
            'helpText' => 'Authenticity (ASQA rule of evidence) — RPL requires the evidence to be the candidate\'s own genuine work.',
            'style'    => ['width' => '100%'],
        ];
        $fields[] = $this->signature('Candidate signature', true);
        $fields[] = $this->date('Date', true, '50%');

        // --- Assessor decision (competency judgement, not a score) ---------------
        $fields[] = $this->section('Assessor Decision');
        $fields[] = $this->radio('Overall judgement for this unit',
            ['Competent', 'Not Yet Competent'], true, '100%');
        $fields[] = $this->textarea('Assessor rationale for the decision (reference the evidence above)', true, '100%');
        $fields[] = $this->paragraph('Reminder: competency is a holistic Competent / Not Yet Competent '
            . 'judgement against ALL criteria — a score or percentage is never a pass mark, and all '
            . 'safety-critical criteria must be Satisfactory.');
        $fields[] = $this->signature('Assessor signature', true);
        $fields[] = $this->autodate('Submitted at');

        return [
            'title'       => 'RPL Practical Observation — ' . $unitlabel,
            'description' => 'ASQA-mapped RPL practical evidence tool for ' . $unitlabel
                . '. Generated by RPL Kit. Review, contextualise and validate before use; the '
                . 'competency decision must be made by a credentialled assessor.',
            'confidence'  => 0.9,
            'suggestions' => [
                'Add at least one assessor-witnessed live component (competency conversation or challenge test) for authenticity and currency.',
                'Confirm the unit version is current on training.gov.au before use.',
            ],
            'fields' => $fields,
        ];
    }

    /** Convenience: return the built kit as a JSON string ready to paste into SmartForm. */
    public function build_observation_kit_json(array $unit): string {
        return json_encode($this->build_observation_kit($unit), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // ---------------------------------------------------------------------------
    // Field factory helpers — each returns a schema-valid SmartForm field object.
    // ---------------------------------------------------------------------------

    private function next(): int {
        return ++$this->seq;
    }

    private function section(string $label): array {
        return [
            'id'    => 'sec_' . $this->next(),
            'type'  => 'section-header',
            'label' => self::esc($label),
            'required' => false,
            'style' => ['width' => '100%'],
        ];
    }

    private function paragraph(string $html): array {
        return [
            'id'      => 'p_' . $this->next(),
            'type'    => 'text-paragraph',
            'label'   => '',
            'content' => '<p>' . $html . '</p>',
            'contentFormat' => 'rich',
            'required' => false,
            'style'   => ['width' => '100%'],
        ];
    }

    private function text(string $label, bool $required, string $width): array {
        return [
            'id'    => 'txt_' . $this->next(),
            'type'  => 'text',
            'label' => self::esc($label),
            'required' => $required,
            'style' => ['width' => $width],
        ];
    }

    private function textarea(string $label, bool $required, string $width): array {
        return [
            'id'    => 'ta_' . $this->next(),
            'type'  => 'textarea',
            'label' => self::esc($label),
            'required' => $required,
            'style' => ['width' => $width],
        ];
    }

    private function tel(string $label, bool $required, string $width): array {
        return [
            'id'    => 'tel_' . $this->next(),
            'type'  => 'tel',
            'label' => self::esc($label),
            'required' => $required,
            'style' => ['width' => $width],
        ];
    }

    private function date(string $label, bool $required, string $width): array {
        return [
            'id'    => 'date_' . $this->next(),
            'type'  => 'date',
            'label' => self::esc($label),
            'required' => $required,
            'style' => ['width' => $width],
        ];
    }

    private function autodate(string $label): array {
        return [
            'id'    => 'auto_' . $this->next(),
            'type'  => 'auto-date',
            'label' => self::esc($label),
            'required' => false,
            'autoDateFormat' => 'datetime',
            'style' => ['width' => '50%'],
        ];
    }

    private function radio(string $label, array $options, bool $required, string $width): array {
        return [
            'id'      => 'radio_' . $this->next(),
            'type'    => 'radio',
            'label'   => self::esc($label),
            'required' => $required,
            'options' => array_map([self::class, 'esc'], $options),
            'style'   => ['width' => $width],
        ];
    }

    private function signature(string $label, bool $required): array {
        return [
            'id'    => 'sig_' . $this->next(),
            'type'  => 'signature',
            'label' => self::esc($label),
            'required' => $required,
            'style' => ['width' => '100%'],
        ];
    }

    /** Escape text for safe inclusion in JSON labels/content (no HTML injection). */
    private static function esc(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}
