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

/**
 * Tests for the recorder skin mapping and the media URL check.
 *
 * @package    assignsubmission_cloudpoodll
 * @category   test
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignsubmission_cloudpoodll\utils
 */
final class utils_test extends \advanced_testcase {
    /**
     * Old skins show as the local recorders, unless the site uses the old recorders.
     */
    public function test_effective_skin(): void {
        $this->resetAfterTest();
        $cases = [
            // Saved skin => [shown normally, shown with the old recorders].
            constants::SKIN_123 => [constants::SKIN_LOCAL123, constants::SKIN_123],
            constants::SKIN_PLAIN => [constants::SKIN_LOCAL123, constants::SKIN_123],
            constants::SKIN_BMR => [constants::SKIN_LOCAL123, constants::SKIN_123],
            constants::SKIN_FRESH => [constants::SKIN_LOCAL123, constants::SKIN_123],
            constants::SKIN_ONCE => [constants::SKIN_LOCALONCE, constants::SKIN_ONCE],
            constants::SKIN_LOCAL123 => [constants::SKIN_LOCAL123, constants::SKIN_123],
            constants::SKIN_LOCALONCE => [constants::SKIN_LOCALONCE, constants::SKIN_ONCE],
            constants::SKIN_UPLOAD => [constants::SKIN_UPLOAD, constants::SKIN_UPLOAD],
            constants::SKIN_SCREEN => [constants::SKIN_SCREEN, constants::SKIN_SCREEN],
        ];
        foreach ([0, 1] as $legacy) {
            set_config('uselegacyrecorders', $legacy, constants::M_COMPONENT);
            foreach ($cases as $saved => $expected) {
                $this->assertSame($expected[$legacy], utils::effective_skin($saved), "$saved, legacy $legacy");
            }
            // A new assignment has no saved skin.
            $this->assertSame($legacy ? constants::SKIN_123 : constants::SKIN_LOCAL123, utils::effective_skin(false));
            // Every skin shown must be one the teacher can pick.
            foreach (array_keys($cases) as $saved) {
                $this->assertArrayHasKey(utils::effective_skin($saved), utils::fetch_options_skins());
            }
        }
    }

    /**
     * Only URLs in Cloud Poodll's S3 buckets are accepted as recordings.
     */
    public function test_is_cloudpoodll_media_url(): void {
        $good = [
            'https://s3.amazonaws.com/poodll-audioprocessing-out-us-east-1/CP/365/u/a.mp3',
            'https://s3-eu-west-2.amazonaws.com/poodll-videoprocessing-out-eu-west-2/CP/9999/u/v.mp4',
            'https://s3.me-south-1.amazonaws.com/poodll-audioprocessing-out-me-south-1/CP/1/u/a.mp3',
            'https://s3.cn-northwest-1.amazonaws.com.cn/poodll-audioprocessing-out-cn-northwest-1/CP/1/u/a.mp3',
            'https://poodll-audioprocessing-out-us-east-1.s3.amazonaws.com/CP/1/u/a.mp3',
        ];
        $bad = [
            'http://s3.amazonaws.com/poodll-audioprocessing-out-us-east-1/a.mp3',
            'https://s3.amazonaws.com/someoneelse/a.mp3',
            'https://s3.amazonaws.com/poodll-x/',
            'https://evil.example.com/poodll-x/a.mp3',
            'https://amazonaws.com.evil.example/poodll-x/a.mp3',
            'https://s3.amazonaws.com:8443/poodll-x/a.mp3',
            'https://user@s3.amazonaws.com/poodll-x/a.mp3',
            "https://s3.amazonaws.com/poodll-x/a'><script>alert(1)</script>",
            'javascript:alert(1)',
            'img123.jpg',
            '',
        ];
        foreach ($good as $url) {
            $this->assertTrue(utils::is_cloudpoodll_media_url($url), $url);
        }
        foreach ($bad as $url) {
            $this->assertFalse(utils::is_cloudpoodll_media_url($url), $url);
        }
    }
}
