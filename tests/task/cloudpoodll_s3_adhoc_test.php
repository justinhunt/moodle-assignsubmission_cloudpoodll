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

namespace assignsubmission_cloudpoodll\task;

use assignsubmission_cloudpoodll\constants;

/**
 * Tests for the task that fetches transcripts.
 *
 * @package    assignsubmission_cloudpoodll
 * @category   test
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignsubmission_cloudpoodll\task\cloudpoodll_s3_adhoc
 * @covers     \assignsubmission_cloudpoodll\utils::fetch_transcriptdata
 */
final class cloudpoodll_s3_adhoc_test extends \advanced_testcase {
    /** @var string the URL of a recording */
    const MEDIAURL = 'https://example.com/recording.mp3';

    /**
     * Add a submission row.
     *
     * @param string $filename
     * @param string $recorder
     * @return \stdClass the row
     */
    private function create_row(string $filename, string $recorder = constants::REC_AUDIO): \stdClass {
        global $DB;
        $row = (object) [
            'assignment' => 1,
            'submission' => 1,
            'recorder' => $recorder,
            'filename' => $filename,
            'fileexpiry' => 123,
            'vectordata' => '',
        ];
        $row->id = $DB->insert_record(constants::M_TABLE, $row);
        return $row;
    }

    /**
     * Run a task made from a copy of a row.
     *
     * @param \stdClass $snapshot the row as it was when the task was queued
     */
    private function run_task(\stdClass $snapshot): void {
        $task = new cloudpoodll_s3_adhoc();
        $task->set_custom_data((object) ['submission' => $snapshot, 'taskcreationtime' => time()]);
        ob_start();
        try {
            $task->execute();
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Mock the three transcript files. curl pops its mock responses, so the last one added is first.
     */
    private function mock_transcript_files(): void {
        \curl::mock_response('the vtt');
        \curl::mock_response('the json');
        \curl::mock_response('the text');
    }

    /**
     * A transcript is saved, and only the transcript fields are touched.
     */
    public function test_saves_transcript_and_nothing_else(): void {
        global $DB;
        $this->resetAfterTest();
        $row = $this->create_row(self::MEDIAURL);

        // Something else changes on the row after the task is queued.
        $snapshot = clone $row;
        $DB->set_field(constants::M_TABLE, 'fileexpiry', 456, ['id' => $row->id]);

        $this->mock_transcript_files();
        $this->run_task($snapshot);

        $saved = $DB->get_record(constants::M_TABLE, ['id' => $row->id]);
        $this->assertSame('the text', $saved->transcript);
        $this->assertSame('the json', $saved->fulltranscript);
        $this->assertSame('the vtt', $saved->vttdata);
        $this->assertEquals(456, $saved->fileexpiry);
        $this->assertSame(self::MEDIAURL, $saved->filename);
    }

    /**
     * A task for a recording that has since been replaced or deleted does nothing.
     *
     * @dataProvider changed_filename_provider
     * @param string $newfilename
     */
    public function test_does_nothing_if_filename_changed(string $newfilename): void {
        global $DB;
        $this->resetAfterTest();
        $row = $this->create_row(self::MEDIAURL);
        $snapshot = clone $row;
        $DB->set_field(constants::M_TABLE, 'filename', $newfilename, ['id' => $row->id]);

        // No transcript is requested, so these are left over and would be saved if one were.
        $this->mock_transcript_files();
        $this->run_task($snapshot);
        $this->clear_mock_responses();

        $saved = $DB->get_record(constants::M_TABLE, ['id' => $row->id]);
        $this->assertSame($newfilename, $saved->filename);
        $this->assertEmpty($saved->transcript);
    }

    /**
     * The changes that can be made to a recording while its task is pending.
     *
     * @return array
     */
    public static function changed_filename_provider(): array {
        return [
            're-recorded' => ['https://example.com/newrecording.mp3'],
            'deleted by the student' => ['-1'],
        ];
    }

    /**
     * A task for a row that no longer exists does nothing.
     */
    public function test_does_nothing_if_row_removed(): void {
        global $DB;
        $this->resetAfterTest();
        $row = $this->create_row(self::MEDIAURL);
        $DB->delete_records(constants::M_TABLE, ['id' => $row->id]);

        $this->run_task($row);

        $this->assertSame(0, $DB->count_records(constants::M_TABLE));
        $this->assertEmpty(\core\task\manager::get_adhoc_tasks(cloudpoodll_s3_adhoc::class));
    }

    /**
     * A task already queued for a whiteboard does nothing and is not retried.
     */
    public function test_does_nothing_for_whiteboard(): void {
        global $DB;
        $this->resetAfterTest();
        $row = $this->create_row('img123.jpg', constants::REC_WHITEBOARD);

        $this->run_task($row);

        $this->assertEmpty($DB->get_field(constants::M_TABLE, 'transcript', ['id' => $row->id]));
        $this->assertEmpty(\core\task\manager::get_adhoc_tasks(cloudpoodll_s3_adhoc::class));
    }

    /**
     * A failed fetch does not save the error message as the transcript, and is retried.
     */
    public function test_failed_fetch_is_retried_and_not_saved(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $row = $this->create_row(self::MEDIAURL);

        // With no mock response waiting, curl refuses the blocked host and returns an error message.
        $CFG->curlsecurityblockedhosts = 'example.com';
        $this->run_task($row);
        $this->resetDebugging();

        $this->assertEmpty($DB->get_field(constants::M_TABLE, 'transcript', ['id' => $row->id]));
        $this->assertCount(1, \core\task\manager::get_adhoc_tasks(cloudpoodll_s3_adhoc::class));
    }

    /**
     * Remove any mock responses that were not used, so that they do not reach another test.
     */
    private function clear_mock_responses(): void {
        $mocks = new \ReflectionProperty(\curl::class, 'mockresponses');
        $mocks->setValue(null, []);
    }
}
