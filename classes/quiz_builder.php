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
 * Builds output #1 of the RPL Kit: the THEORY evidence tool.
 *
 * A set of Moodle essay questions (Moodle XML question format) mapped to a unit's Knowledge
 * Evidence, each with a model answer and an explicit marking guide, ready to import into a Moodle
 * question bank and add to a quiz.
 *
 * Compliance is baked in: every question maps to a specific Knowledge Evidence point
 * (sufficiency), asks for APPLIED knowledge (harder to fabricate / AI-generate), ships a benchmark
 * model answer + marking guide (reliability), and the grader info states that an AI grade is a
 * pre-assessment only and the competency decision is the assessor's.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_builder {
    /**
     * Build Moodle question XML of essay questions from a unit's Knowledge Evidence.
     *
     * @param array $unit Unit data (see {@see unit::normalise()}).
     * @return string Moodle XML (import via Question bank -> Import -> Moodle XML format).
     */
    public function build_theory_quiz_xml(array $unit): string {
        $unit = unit::normalise($unit);
        $unitlabel = unit::label($unit);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<quiz>' . "\n";

        // A category so the imported questions land together, named after the unit.
        $xml .= $this->category_block('top/RPL Theory/' . ($unitlabel !== '' ? $unitlabel : 'Unit'));

        foreach ($unit['knowledge_evidence'] as $point) {
            $xml .= $this->essay_question($unitlabel, $point['ref'], $point['text']);
        }

        if (empty($unit['knowledge_evidence'])) {
            // No KE supplied - emit a single guidance question so the import is still valid.
            $xml .= $this->essay_question(
                $unitlabel,
                'KE',
                '[RPL Kit: no Knowledge Evidence was supplied. Add the unit\'s Knowledge Evidence so a '
                    . 'question is generated for each point - every point must be covered for sufficiency.]'
            );
        }

        $xml .= '</quiz>' . "\n";
        return $xml;
    }

    /**
     * Number of essay questions the XML will contain for this unit.
     *
     * @param array $unit Unit data.
     * @return int
     */
    public function count_questions(array $unit): int {
        return max(1, count(unit::normalise($unit)['knowledge_evidence']));
    }

    /**
     * One essay question mapped to a single Knowledge Evidence point.
     *
     * @param string $unitlabel Unit code and name.
     * @param string $ref Knowledge Evidence reference, e.g. KE1.
     * @param string $ketext Knowledge Evidence text.
     * @return string XML fragment.
     */
    private function essay_question(string $unitlabel, string $ref, string $ketext): string {
        $qname = $ref . ' — ' . self::shorten($ketext, 60) . ($unitlabel !== '' ? ' (' . $unitlabel . ')' : '');
        $qname = self::shorten($qname, 250);

        // The prompt asks for applied knowledge in a work context (validity + authenticity).
        $questionhtml = '<p><strong>' . self::esc($ref) . ' — Knowledge Evidence:</strong> '
            . self::esc($ketext) . '</p>'
            . '<p>In your own words, and drawing on your own workplace experience, explain and '
            . 'justify the above as it applies to your role. Give specific, real examples so your '
            . 'answer clearly demonstrates your own applied knowledge.</p>';

        // Model answer (benchmark) - a placeholder the RTO completes; drives reliable marking.
        $modelanswer = '<p><strong>Benchmark / model answer (assessor use):</strong></p>'
            . '<p>[RTO to insert the model answer for ' . self::esc($ref) . ': the key points a '
            . 'satisfactory response must contain, drawn from the unit\'s Knowledge Evidence and '
            . 'current industry practice.]</p>';

        // Marking guide (graderinfo) - decision rule + responsible-AI note.
        $graderinfo = '<p><strong>Marking guide — ' . self::esc($ref) . '</strong></p>'
            . '<ul>'
            . '<li>Satisfactory when the response covers the key points of the model answer with '
            . 'correct, applied understanding and a genuine work example.</li>'
            . '<li>Check authenticity first: the answer must be the candidate\'s own work, not '
            . 'plagiarised or AI-generated. Corroborate in the competency conversation if unsure.</li>'
            . '<li>Any AI grade is an <em>AI-assisted pre-assessment, not a competency decision</em> '
            . '— a credentialled assessor confirms Satisfactory / Not Yet Satisfactory.</li>'
            . '</ul>';

        $x = '  <question type="essay">' . "\n";
        $x .= '    <name><text>' . self::esc($qname) . '</text></name>' . "\n";
        $x .= '    <questiontext format="html"><text>' . self::cdata($questionhtml) . '</text></questiontext>' . "\n";
        $x .= '    <generalfeedback format="html"><text>' . self::cdata($modelanswer) . '</text></generalfeedback>' . "\n";
        $x .= '    <defaultgrade>1.0000000</defaultgrade>' . "\n";
        $x .= '    <penalty>0.0000000</penalty>' . "\n";
        $x .= '    <hidden>0</hidden>' . "\n";
        $x .= '    <responseformat>editor</responseformat>' . "\n";
        $x .= '    <responserequired>1</responserequired>' . "\n";
        $x .= '    <responsefieldlines>12</responsefieldlines>' . "\n";
        $x .= '    <minwordlimit></minwordlimit>' . "\n";
        $x .= '    <maxwordlimit></maxwordlimit>' . "\n";
        $x .= '    <attachments>0</attachments>' . "\n";
        $x .= '    <attachmentsrequired>0</attachmentsrequired>' . "\n";
        $x .= '    <graderinfo format="html"><text>' . self::cdata($graderinfo) . '</text></graderinfo>' . "\n";
        $x .= '    <responsetemplate format="html"><text></text></responsetemplate>' . "\n";
        $x .= '  </question>' . "\n";
        return $x;
    }

    /**
     * A question-bank category block so imported questions are grouped under the unit.
     *
     * @param string $path Category path, e.g. top/RPL Theory/BSBWHS211 ...
     * @return string XML fragment.
     */
    private function category_block(string $path): string {
        $x = '  <question type="category">' . "\n";
        $x .= '    <category><text>' . self::esc($path) . '</text></category>' . "\n";
        $x .= '    <info format="html"><text>' . self::cdata('RPL theory questions generated by RPL Kit, '
            . 'mapped to the unit\'s Knowledge Evidence.') . '</text></info>' . "\n";
        $x .= '  </question>' . "\n";
        return $x;
    }

    /**
     * Escape for XML text content.
     *
     * @param string $s
     * @return string
     */
    private static function esc(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Wrap HTML in a CDATA section, splitting any embedded terminator.
     *
     * @param string $html
     * @return string
     */
    private static function cdata(string $html): string {
        return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $html) . ']]>';
    }

    /**
     * Shorten a string for use in a question name.
     *
     * @param string $s
     * @param int $len Maximum length in characters.
     * @return string
     */
    private static function shorten(string $s, int $len): string {
        $s = trim(preg_replace('/\s+/u', ' ', $s));
        if (\core_text::strlen($s) <= $len) {
            return $s;
        }
        return \core_text::substr($s, 0, $len - 1) . '…';
    }
}
