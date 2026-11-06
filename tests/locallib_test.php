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

namespace assignsubmission_cloudpoodll;

use assignsubmission_cloudpoodll\task\cloudpoodll_s3_adhoc;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Tests for when saving a submission queues the task that fetches its transcript.
 *
 * @package    assignsubmission_cloudpoodll
 * @category   test
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assign_submission_cloudpoodll
 */
final class locallib_test extends \advanced_testcase {
    /**
     * Save a submission twice, as an insert and then as an update, counting the tasks queued.
     *
     * @param string $recordertype
     * @param int $transcriber
     * @param string $filename
     * @return int[] the number of tasks queued after the insert, and after the update
     */
    private function count_tasks_after_saves(string $recordertype, int $transcriber, string $filename): array {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $assign = new \assign(\context_module::instance($cm->id), $cm, $course);

        $plugin = $assign->get_submission_plugin_by_type('cloudpoodll');
        $plugin->set_config('recordertype', $recordertype);
        $plugin->set_config('enabletranscription', $transcriber);
        $plugin->set_config('expiredays', 365);

        $submission = (object) ['id' => 1];
        $counts = [];
        foreach ([$filename, $filename . '2'] as $savedfilename) {
            $plugin->save($submission, (object) ['filename' => $savedfilename]);
            $counts[] = count(\core\task\manager::get_adhoc_tasks(cloudpoodll_s3_adhoc::class));
        }
        return $counts;
    }

    /**
     * Each recording that will have a transcript gets a task.
     */
    public function test_recording_with_transcription_queues_task(): void {
        $counts = $this->count_tasks_after_saves(
            constants::REC_AUDIO,
            constants::TRANSCRIBER_AMAZONTRANSCRIBE,
            'https://example.com/recording.mp3'
        );
        $this->assertSame([1, 2], $counts);
    }

    /**
     * A recording made with transcription off has no transcript to wait for.
     */
    public function test_recording_without_transcription_queues_no_task(): void {
        $counts = $this->count_tasks_after_saves(
            constants::REC_AUDIO,
            constants::TRANSCRIBER_NONE,
            'https://example.com/recording.mp3'
        );
        $this->assertSame([0, 0], $counts);
    }

    /**
     * A whiteboard never has a transcript, whatever the transcription setting says.
     */
    public function test_whiteboard_queues_no_task(): void {
        $counts = $this->count_tasks_after_saves(
            constants::REC_WHITEBOARD,
            constants::TRANSCRIBER_AMAZONTRANSCRIBE,
            'img123.jpg'
        );
        $this->assertSame([0, 0], $counts);
    }
}
