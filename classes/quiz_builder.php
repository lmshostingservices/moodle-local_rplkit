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
 * Builds output #1 of the RPL Kit: the THEORY evidence tool — a set of Moodle essay questions
 * (Moodle question-bank XML) mapped to a unit's Knowledge Evidence, each with a model answer
 * and an explicit marking guide, ready to import into a Moodle quiz and mark with the AI grader.
 *
 * Uses Moodle's native question XML format so it imports into any Moodle question bank without a
 * proprietary schema. Compliance is baked in: every question maps to a specific Knowledge
 * Evidence point (sufficiency), asks for APPLIED knowledge (harder to fabricate / AI-generate),
 * ships a benchmark model answer + marking guide (reliability), and the grader info states that
 * an AI grade is a pre-assessment only and the competency decision is the assessor's.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_builder {
    /**
     * Build Moodle question-bank XML of essay questions from a unit's Knowledge Evidence.
     *
     * @param array $unit Unit components. Expected keys:
     *   'code' (string), 'name' (string),
     *   'knowledge_evidence' (array) list of ['ref' => 'KE1', 'text' => '...'] (or plain strings)
     * @return string Moodle question XML (import via Question bank -> Import -> Moodle XML).
     */
    public function build_theory_quiz_xml(array $unit): string {
        $code = trim((string) ($unit['code'] ?? ''));
        $name = trim((string) ($unit['name'] ?? ''));
        $unitlabel = trim($code . ' ' . $name);
        $ke = is_array($unit['knowledge_evidence'] ?? null) ? $unit['knowledge_evidence'] : [];

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<quiz>' . "\n";

        // A category so the imported questions land together, named after the unit.
        $xml .= $this->category_block('top/RPL Theory/' . $unitlabel);

        $i = 0;
        foreach ($ke as $point) {
            $i++;
            if (is_array($point)) {
                $ref  = trim((string) ($point['ref'] ?? ('KE' . $i)));
                $text = trim((string) ($point['text'] ?? ''));
            } else {
                $ref  = 'KE' . $i;
                $text = trim((string) $point);
            }
            if ($text === '') {
                continue;
            }
            $xml .= $this->essay_question($unitlabel, $ref, $text);
        }

        if ($i === 0) {
            // No KE supplied — emit a single guidance question so the import is still valid.
            $xml .= $this->essay_question($unitlabel, 'KE',
                '[RPL Kit: fetch this unit from training.gov.au so a question is generated for '
                . 'each Knowledge Evidence point — every point must be covered for sufficiency.]');
        }

        $xml .= '</quiz>' . "\n";
        return $xml;
    }

    /** One essay question mapped to a single Knowledge Evidence point. */
    private function essay_question(string $unitlabel, string $ref, string $ketext): string {
        $qname = $ref . ' — ' . self::shorten($ketext, 60) . ' (' . $unitlabel . ')';

        // The prompt asks for applied knowledge in a work context (validity + authenticity).
        $questionhtml = '<p><strong>' . self::esc($ref) . ' — Knowledge Evidence:</strong> '
            . self::esc($ketext) . '</p>'
            . '<p>In your own words, and drawing on your own workplace experience, explain and '
            . 'justify the above as it applies to your role. Give specific, real examples so your '
            . 'answer clearly demonstrates your own applied knowledge.</p>';

        // Model answer (benchmark) — a placeholder the RTO completes; drives reliable marking.
        $modelanswer = '<p><strong>Benchmark / model answer (assessor use):</strong></p>'
            . '<p>[RTO to insert the model answer for ' . self::esc($ref) . ': the key points a '
            . 'satisfactory response must contain, drawn from the unit\'s Knowledge Evidence and '
            . 'current industry practice.]</p>';

        // Marking guide (graderinfo) — decision rule + responsible-AI note.
        $graderinfo = '<p><strong>Marking guide — ' . self::esc($ref) . '</strong></p>'
            . '<ul>'
            . '<li>Satisfactory when the response covers the key points of the model answer with '
            . 'correct, applied understanding and a genuine work example.</li>'
            . '<li>Check authenticity first: the answer must be the candidate\'s own work, not '
            . 'plagiarised or AI-generated. Corroborate in the competency conversation if unsure.</li>'
            . '<li>Any AI grade is an <em>AI-assisted pre-assessment, not a competency decision</em> '
            . '— a credentialled assessor confirms Satisfactory / Not Yet Satisfactory.</li>'
            . '</ul>';

        $x  = '  <question type="essay">' . "\n";
        $x .= '    <name><text>' . self::esc($qname) . '</text></name>' . "\n";
        $x .= '    <questiontext format="html"><text><![CDATA[' . $questionhtml . ']]></text></questiontext>' . "\n";
        $x .= '    <generalfeedback format="html"><text><![CDATA[' . $modelanswer . ']]></text></generalfeedback>' . "\n";
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
        $x .= '    <graderinfo format="html"><text><![CDATA[' . $graderinfo . ']]></text></graderinfo>' . "\n";
        $x .= '    <responsetemplate format="html"><text></text></responsetemplate>' . "\n";
        $x .= '  </question>' . "\n";
        return $x;
    }

    /** A question-bank category block so imported questions are grouped under the unit. */
    private function category_block(string $path): string {
        $x  = '  <question type="category">' . "\n";
        $x .= '    <category><text>' . self::esc($path) . '</text></category>' . "\n";
        $x .= '    <info format="html"><text><![CDATA[RPL theory questions generated by RPL Kit, '
            . 'mapped to the unit\'s Knowledge Evidence.]]></text></info>' . "\n";
        $x .= '  </question>' . "\n";
        return $x;
    }

    /** Escape for XML text / CDATA-safe content. */
    private static function esc(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /** Shorten a string for use in a question name. */
    private static function shorten(string $s, int $len): string {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        if (mb_strlen($s) <= $len) {
            return $s;
        }
        return mb_substr($s, 0, $len - 1) . '…';
    }
}
