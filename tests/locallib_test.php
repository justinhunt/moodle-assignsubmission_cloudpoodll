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
            'https://s3.amazonaws.com/poodll-audioprocessing-out-us-east-1/CP/365/user/recording.mp3'
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
            'https://s3.amazonaws.com/poodll-audioprocessing-out-us-east-1/CP/365/user/recording.mp3'
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

    /**
     * A filename that the recorder could not have made is refused, and nothing is saved.
     */
    public function test_save_refuses_foreign_url(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $assign = new \assign(\context_module::instance($cm->id), $cm, $course);
        $plugin = $assign->get_submission_plugin_by_type('cloudpoodll');
        $plugin->set_config('recordertype', constants::REC_AUDIO);
        $plugin->set_config('expiredays', 365);

        $submission = (object) ['id' => 1];
        $this->assertFalse($plugin->save($submission, (object) ['filename' => 'https://evil.example.com/x.mp3']));
        $this->assertNotEmpty($plugin->get_error());
        $this->assertFalse($plugin->save($submission, (object) ['filename' => "https://s3.amazonaws.com/poodll-x/a'><script>"]));
        $this->assertSame(0, $DB->count_records(constants::M_TABLE));

        // Deleting is still allowed.
        $this->assertNotEmpty($plugin->save($submission, (object) ['filename' => '-1']));
    }

    /**
     * Disabled fields are not posted, so saving the settings must not need them.
     *
     * Board size is disabled unless the type is whiteboard, and the transcript settings are disabled
     * while transcription is off.
     */
    public function test_save_settings_without_disabled_fields(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $assign = new \assign(\context_module::instance($cm->id), $cm, $course);
        $plugin = $assign->get_submission_plugin_by_type('cloudpoodll');
        $p = constants::M_COMPONENT . '_';

        // A new video assignment with transcription off, so nothing it never had is saved as empty.
        $DB->delete_records('assign_plugin_config', ['assignment' => $module->id, 'plugin' => 'cloudpoodll',
            'subtype' => 'assignsubmission']);
        $data = (object) [
            $p . 'recordertype' => constants::REC_VIDEO,
            $p . 'recorderskin' => constants::SKIN_LOCAL123,
            $p . 'expiredays' => 365,
            $p . 'enabletranscription' => constants::TRANSCRIBER_NONE,
            $p . 'noaudiofilters' => 0,
        ];
        $this->assertTrue($plugin->save_settings($data));
        $this->assertSame(get_config(constants::M_COMPONENT, 'boardsize'), $plugin->get_config('boardsize'));
        $this->assertSame(get_config(constants::M_COMPONENT, 'language'), $plugin->get_config('language'));

        // A value saved earlier is kept when its field is disabled.
        $plugin->set_config('boardsize', '600x400');
        $plugin->set_config('language', 'fr-FR');
        $this->assertTrue($plugin->save_settings($data));
        $this->assertSame('600x400', $plugin->get_config('boardsize'));
        $this->assertSame('fr-FR', $plugin->get_config('language'));
    }
}
