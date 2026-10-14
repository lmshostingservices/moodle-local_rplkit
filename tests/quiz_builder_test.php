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

global $CFG;
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');

/**
 * Tests for output #1, including a real import into a question bank.
 *
 * @package    local_rplkit
 * @category   test
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_rplkit\quiz_builder
 */
#[\PHPUnit\Framework\Attributes\CoversClass(quiz_builder::class)]
final class quiz_builder_test extends \advanced_testcase {
    /**
     * The XML is well formed with one essay question per Knowledge Evidence point.
     */
    public function test_structure(): void {
        $xml = (new quiz_builder())->build_theory_quiz_xml(require(__DIR__ . '/fixtures/unit.php'));
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
        $this->assertCount(5, $doc->xpath('//question[@type="essay"]'));
        $this->assertCount(1, $doc->xpath('//question[@type="category"]'));
    }

    /**
     * Missing or all-blank Knowledge Evidence produces exactly one placeholder question.
     */
    public function test_placeholder(): void {
        $qb = new quiz_builder();
        foreach ([['code' => 'X'], ['code' => 'X', 'knowledge_evidence' => ['', '  ', ['text' => '']]]] as $unit) {
            $doc = simplexml_load_string($qb->build_theory_quiz_xml($unit));
            $this->assertCount(1, $doc->xpath('//question[@type="essay"]'));
            $this->assertSame(1, $qb->count_questions($unit));
        }
    }

    /**
     * The generated XML imports into a real question bank as essay questions with the model
     * answer and marking guide intact.
     */
    public function test_import_into_question_bank(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        if ((int) $CFG->branch >= 500) {
            $qbank = \core_question\local\bank\question_bank_helper::get_default_open_instance_system_type($course, true);
            $context = \context_module::instance($qbank->id);
        } else {
            $context = \context_course::instance($course->id);
        }
        $defaultcategory = question_make_default_categories([$context]);

        $file = make_request_directory() . '/q.xml';
        file_put_contents($file, (new quiz_builder())->build_theory_quiz_xml(require(__DIR__ . '/fixtures/unit.php')));

        $format = new \qformat_xml();
        $format->setCategory($defaultcategory);
        $format->setContexts([$context]);
        $format->setCourse($course);
        $format->setFilename($file);
        $format->setRealfilename('q.xml');
        $format->setMatchgrades('error');
        $format->setCatfromfile(true);
        $format->setContextfromfile(true);
        $format->setStoponerror(true);
        ob_start();
        $ok = $format->importpreprocess() && $format->importprocess() && $format->importpostprocess();
        $log = ob_get_clean();
        $this->assertTrue($ok, strip_tags($log));

        $category = $DB->get_record('question_categories', [
            'contextid' => $context->id,
            'name' => 'BSBWHS211 Contribute to the health and safety of self and others',
        ], '*', MUST_EXIST);
        $this->assertSame('RPL Theory', $DB->get_field('question_categories', 'name', ['id' => $category->parent]));

        $questions = array_values($DB->get_records_sql(
            'SELECT q.* FROM {question} q
               JOIN {question_versions} qv ON qv.questionid = q.id
               JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
              WHERE qbe.questioncategoryid = ?
           ORDER BY q.id',
            [$category->id]
        ));
        $this->assertCount(5, $questions);
        foreach ($questions as $q) {
            $this->assertSame('essay', $q->qtype);
            $this->assertStringContainsString('Benchmark / model answer', $q->generalfeedback);
            $options = $DB->get_record('qtype_essay_options', ['questionid' => $q->id], '*', MUST_EXIST);
            $this->assertSame('editor', $options->responseformat);
            $this->assertStringContainsString('Marking guide', $options->graderinfo);
        }
        $this->assertStringStartsWith('KE1 — ', $questions[0]->name);
        $this->assertStringStartsWith('KE3 — ', $questions[2]->name);
        $this->assertStringContainsString('&', $questions[1]->name);
        $this->assertStringNotContainsString('&amp;', $questions[1]->name);
        $this->assertStringContainsString('&lt;incidents&gt;', $questions[3]->questiontext);
        $this->assertStringContainsString(']]&gt;', $questions[3]->questiontext);
        $this->assertStringContainsString('Māori', $questions[4]->questiontext);
    }
}
