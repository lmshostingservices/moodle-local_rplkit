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
 * Builds a SmartForm-compatible JSON document (output #2 of the RPL Kit): a practical,
 * forms-based RPL evidence tool — an observation / third-party checklist — mapped to a unit
 * of competency's Performance Criteria and gated by the ASQA rules of evidence.
 *
 * The output uses the SmartForm document structure (title, description, confidence,
 * suggestions, fields[]) so it can be pasted into the SmartForm app. Every Performance Criterion
 * gets its own row in the observation matrix, labelled with its PC code, so no criterion is left
 * unmapped, and the form embeds the authenticity and third-party controls RPL evidence requires.
 *
 * Text conventions: labels, options and helpText are PLAIN TEXT (JSON is the encoding layer, so
 * they are not HTML-escaped). Only the 'content' of rich text-paragraph fields is HTML, and any
 * unit text placed in it is HTML-escaped.
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
     * @param array $unit Unit data (see {@see unit::normalise()}).
     * @return array SmartForm JSON structure (encode with json_encode()).
     */
    public function build_observation_kit(array $unit): array {
        $this->seq = 0;

        $unit = unit::normalise($unit);
        $pcs = $unit['performance_criteria'];
        $conditions = $unit['assessment_conditions'];
        $pevidence = $unit['performance_evidence'];
        $unitlabel = unit::label($unit);
        $fields = [];

        // Header.
        $fields[] = $this->section('Practical Observation Checklist (RPL Evidence) — ' . $unitlabel);
        $fields[] = $this->paragraph(
            'This is a practical evidence-gathering tool for Recognition of Prior Learning against '
            . self::esc($unitlabel) . '. The assessor observes the candidate (or verifies workplace '
            . 'evidence) against each Performance Criterion below, records what was observed, gathers a '
            . 'third-party report where the evidence is from the workplace, and makes an overall '
            . 'Competent / Not Yet Competent decision. A score is NOT a competency decision.'
        );

        // Candidate & assessor identity.
        $fields[] = $this->section('Candidate & Assessment Details');
        $fields[] = $this->text('Candidate name', true, '50%');
        $fields[] = $this->text('Candidate identifier / USI', false, '50%');
        $fields[] = $this->text('Assessor name', true, '50%');
        $fields[] = $this->date('Date of observation', true, '50%');
        $fields[] = $this->text('Location / workplace context', false, '100%');

        // Assessment conditions (from the training product).
        if ($conditions !== '') {
            $fields[] = $this->section('Assessment Conditions (mandatory)');
            $fields[] = $this->paragraph(self::esc($conditions));
        }

        // The observation matrix: one row per Performance Criterion.
        // Each PC row is judged Satisfactory / Not yet satisfactory with an evidence note —
        // this is the observable-behaviour checklist ASQA expects, fully mapped to the unit.
        $fields[] = $this->section('Observation against Performance Criteria');
        if (!empty($pcs)) {
            $rows = [];
            foreach ($pcs as $pc) {
                $rowlabel = trim($pc['code'] . ' ' . $pc['text']);
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
                'helpText' => 'Every Performance Criterion of ' . $unitlabel
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

        // Performance Evidence sufficiency (frequency/volume).
        if (!empty($pevidence)) {
            $fields[] = $this->section('Performance Evidence — sufficiency');
            $opts = [];
            foreach ($pevidence as $pe) {
                $opts[] = $pe['ref'] . ' ' . $pe['text'];
            }
            $fields[] = [
                'id'       => 'pe_' . $this->next(),
                'type'     => 'checkbox',
                'label'    => 'Confirm each Performance Evidence requirement has been demonstrated '
                    . 'the required number of times / over the required range',
                'required' => true,
                'options'  => $opts,
                'helpText' => 'Sufficiency (ASQA rule of evidence): the volume/frequency in the '
                    . 'unit\'s Performance Evidence must be met, not a single instance.',
                'style'    => ['width' => '100%'],
            ];
        }

        // Third-party report (workplace evidence).
        $fields[] = $this->section('Third-Party Report (for workplace evidence)');
        $fields[] = $this->paragraph('Complete when evidence is verified by a workplace supervisor. '
            . 'A third-party report is SUPPORTING evidence only — the assessor must corroborate it '
            . '(e.g. via a competency conversation) and makes the final judgement.');
        $fields[] = $this->text('Third party name', false, '50%');
        $fields[] = $this->text('Role / position', false, '50%');
        $fields[] = $this->text('Relationship to candidate', false, '50%');
        $fields[] = $this->tel('Contact number', false, '50%');
        $fields[] = $this->text('Period / context over which the candidate was observed', false, '100%');
        $fields[] = $this->radio(
            'Do you confirm the candidate performed these tasks themselves, to the required standard?',
            ['Yes', 'No'],
            false,
            '100%'
        );
        $fields[] = $this->textarea('Specific tasks / criteria you are attesting to', false, '100%');
        $fields[] = $this->signature('Third party signature', false);
        $fields[] = $this->date('Date', false, '50%');

        // Authenticity declaration (candidate).
        $fields[] = $this->section('Authenticity Declaration');
        $fields[] = [
            'id'       => 'auth_' . $this->next(),
            'type'     => 'checkbox',
            'label'    => 'Candidate declaration',
            'required' => true,
            'options'  => [
                'I declare that the evidence provided for this RPL is my own genuine work, performed by me, '
                    . 'and is not plagiarised or generated by artificial intelligence.',
            ],
            'helpText' => 'Authenticity (ASQA rule of evidence) — RPL requires the evidence to be the '
                . 'candidate\'s own genuine work.',
            'style'    => ['width' => '100%'],
        ];
        $fields[] = $this->signature('Candidate signature', true);
        $fields[] = $this->date('Date', true, '50%');

        // Assessor decision (competency judgement, not a score).
        $fields[] = $this->section('Assessor Decision');
        $fields[] = $this->radio('Overall judgement for this unit', ['Competent', 'Not Yet Competent'], true, '100%');
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
                'Add at least one assessor-witnessed live component (competency conversation or challenge '
                    . 'test) for authenticity and currency.',
                'Confirm the unit version is current on training.gov.au before use.',
            ],
            'fields' => $fields,
        ];
    }

    /**
     * Convenience: return the built kit as a JSON string ready to paste into SmartForm.
     *
     * @param array $unit Unit data.
     * @return string JSON.
     */
    public function build_observation_kit_json(array $unit): string {
        return json_encode(
            $this->build_observation_kit($unit),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    // Field factory helpers - each returns one SmartForm field object.

    /**
     * Mint the next field id number.
     *
     * @return int
     */
    private function next(): int {
        return ++$this->seq;
    }

    /**
     * A section-header field.
     *
     * @param string $label Plain-text heading.
     * @return array
     */
    private function section(string $label): array {
        return [
            'id'    => 'sec_' . $this->next(),
            'type'  => 'section-header',
            'label' => $label,
            'required' => false,
            'style' => ['width' => '100%'],
        ];
    }

    /**
     * A rich text-paragraph field.
     *
     * @param string $html Already-escaped HTML body (wrapped in a paragraph).
     * @return array
     */
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

    /**
     * A single-line text field.
     *
     * @param string $label Plain-text label.
     * @param bool $required
     * @param string $width CSS width.
     * @return array
     */
    private function text(string $label, bool $required, string $width): array {
        return [
            'id'    => 'txt_' . $this->next(),
            'type'  => 'text',
            'label' => $label,
            'required' => $required,
            'style' => ['width' => $width],
        ];
    }

    /**
     * A multi-line text field.
     *
     * @param string $label Plain-text label.
     * @param bool $required
     * @param string $width CSS width.
     * @return array
     */
    private function textarea(string $label, bool $required, string $width): array {
        return [
            'id'    => 'ta_' . $this->next(),
            'type'  => 'textarea',
            'label' => $label,
            'required' => $required,
            'style' => ['width' => $width],
        ];
    }

    /**
     * A telephone field.
     *
     * @param string $label Plain-text label.
     * @param bool $required
     * @param string $width CSS width.
     * @return array
     */
    private function tel(string $label, bool $required, string $width): array {
        return [
            'id'    => 'tel_' . $this->next(),
            'type'  => 'tel',
            'label' => $label,
            'required' => $required,
            'style' => ['width' => $width],
        ];
    }

    /**
     * A date field.
     *
     * @param string $label Plain-text label.
     * @param bool $required
     * @param string $width CSS width.
     * @return array
     */
    private function date(string $label, bool $required, string $width): array {
        return [
            'id'    => 'date_' . $this->next(),
            'type'  => 'date',
            'label' => $label,
            'required' => $required,
            'style' => ['width' => $width],
        ];
    }

    /**
     * An auto-filled submission timestamp field.
     *
     * @param string $label Plain-text label.
     * @return array
     */
    private function autodate(string $label): array {
        return [
            'id'    => 'auto_' . $this->next(),
            'type'  => 'auto-date',
            'label' => $label,
            'required' => false,
            'autoDateFormat' => 'datetime',
            'style' => ['width' => '50%'],
        ];
    }

    /**
     * A radio-button field.
     *
     * @param string $label Plain-text label.
     * @param string[] $options Plain-text options.
     * @param bool $required
     * @param string $width CSS width.
     * @return array
     */
    private function radio(string $label, array $options, bool $required, string $width): array {
        return [
            'id'      => 'radio_' . $this->next(),
            'type'    => 'radio',
            'label'   => $label,
            'required' => $required,
            'options' => $options,
            'style'   => ['width' => $width],
        ];
    }

    /**
     * A signature field.
     *
     * @param string $label Plain-text label.
     * @param bool $required
     * @return array
     */
    private function signature(string $label, bool $required): array {
        return [
            'id'    => 'sig_' . $this->next(),
            'type'  => 'signature',
            'label' => $label,
            'required' => $required,
            'style' => ['width' => '100%'],
        ];
    }

    /**
     * HTML-escape unit text placed inside rich 'content'.
     *
     * @param string $s
     * @return string
     */
    private static function esc(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}
