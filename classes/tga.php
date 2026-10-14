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
 * Fetch a unit of competency from training.gov.au through Assignment Benchmarks' TGA client.
 *
 * RPL Kit has no TGA client of its own: it uses gradingform_benchmarks\tga\training_component
 * (Assignment Benchmarks 1.2.40 or later), so credentials and caching are configured once, on the
 * Assignment Benchmarks settings page.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tga {
    /** @var callable|null Test override: fn(string $code): ?array returning Benchmarks-format unit data. */
    private static $source = null;

    /**
     * Whether a TGA client is available.
     *
     * @return bool
     */
    public static function is_available(): bool {
        return self::$source !== null
            || (benchmarks::is_available() && class_exists('\gradingform_benchmarks\tga\training_component')
                && benchmarks::is_unlocked());
    }

    /**
     * Fetch a unit and convert it to RPL Kit's unit format.
     *
     * @param string $code Unit code.
     * @return array ['unit' => array|null (canonical, see unit::normalise()), 'error' => string]
     */
    public static function fetch(string $code): array {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z0-9]{4,20}$/', $code)) {
            return ['unit' => null, 'error' => get_string('tgainvalidcode', 'local_rplkit')];
        }
        if (!self::is_available()) {
            return ['unit' => null, 'error' => get_string('tganotavailable', 'local_rplkit')];
        }

        $reason = '';
        if (self::$source !== null) {
            $data = (self::$source)($code);
        } else {
            $client = new \gradingform_benchmarks\tga\training_component();
            $data = $client->get_unit($code);
            $reason = method_exists($client, 'get_last_error') ? $client->get_last_error() : '';
        }

        if (!$data || empty($data['elements'])) {
            $key = $reason === 'nocredentials' ? 'tganocredentials' : 'tganotfound';
            return ['unit' => null, 'error' => get_string($key, 'local_rplkit', $code)];
        }
        return ['unit' => self::convert($data, $code), 'error' => ''];
    }

    /**
     * Convert Assignment Benchmarks' TGA result into RPL Kit's canonical unit.
     *
     * @param array $data Benchmarks training_component::get_unit() result.
     * @param string $code Requested code (used when the result has none).
     * @return array Canonical unit.
     */
    public static function convert(array $data, string $code = ''): array {
        $elements = [];
        $pcs = [];
        $n = 0;
        foreach ($data['elements'] ?? [] as $el) {
            $n++;
            $num = (int) ($el['code'] ?? 0) ?: $n;
            $elements[] = ['number' => $num, 'title' => (string) ($el['name'] ?? '')];
            $criteria = $el['criteria'] ?? array_map(fn($t) => ['code' => '', 'text' => $t], $el['performanceCriteria'] ?? []);
            $i = 0;
            foreach ($criteria as $pc) {
                $i++;
                $pccode = trim((string) ($pc['code'] ?? ''));
                $pcs[] = ['code' => $pccode !== '' ? $pccode : $num . '.' . $i, 'text' => (string) ($pc['text'] ?? '')];
            }
        }
        $conditions = $data['assessmentConditions'] ?? [];
        return unit::normalise([
            'code' => $data['code'] ?? $code,
            'name' => ($data['title'] ?? '') !== ($data['code'] ?? $code) ? ($data['title'] ?? '') : '',
            'elements' => $elements,
            'performance_criteria' => $pcs,
            'knowledge_evidence' => array_values($data['knowledgeEvidence'] ?? []),
            'performance_evidence' => array_values($data['performanceEvidence'] ?? []),
            'assessment_conditions' => is_array($conditions) ? implode("\n", $conditions) : (string) $conditions,
        ]);
    }

    /**
     * Replace the TGA source (PHPUnit only).
     *
     * @param callable|null $source fn(string $code): ?array, or null to restore the real client.
     */
    public static function set_source_for_testing(?callable $source): void {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new \coding_exception('set_source_for_testing() is for PHPUnit tests only.');
        }
        self::$source = $source;
    }
}
