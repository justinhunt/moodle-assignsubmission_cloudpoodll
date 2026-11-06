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

namespace assignsubmission_cloudpoodll\external;

use assignsubmission_cloudpoodll\constants;
use assignsubmission_cloudpoodll\localrecorder\upload_details;
use assignsubmission_cloudpoodll\utils;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Gets the S3 upload details for one local recorder recording.
 *
 * The browser never talks to Cloud Poodll or sees the API token. Everything that decides where the
 * recording goes and how it is processed comes from the assignment and site settings, not the browser.
 *
 * @package    assignsubmission_cloudpoodll
 * @copyright  2026 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fetch_upload_details extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'The course module id of the assignment'),
            'sourcemimetype' => new external_value(PARAM_TEXT, 'The mime type of the recorded file'),
        ]);
    }

    /**
     * Gets the upload details for a new recording.
     *
     * @param int $cmid The course module id of the assignment.
     * @param string $sourcemimetype The mime type of the recorded file.
     * @return array
     */
    public static function execute(int $cmid, string $sourcemimetype): array {
        global $CFG, $USER;

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['cmid' => $cmid, 'sourcemimetype' => $sourcemimetype]
        );

        [$course, $cm] = get_course_and_cm_from_cmid($params['cmid'], 'assign');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/assign:submit', $context);

        $assign = new \assign($context, $cm, $course);
        $plugin = $assign->get_submission_plugin_by_type('cloudpoodll');
        if (!$plugin || !$plugin->is_enabled() || !$plugin->is_visible()) {
            throw new \moodle_exception('lr_cannotrecord', constants::M_COMPONENT);
        }
        if (!$assign->submissions_open($USER->id)) {
            throw new \moodle_exception('lr_cannotrecord', constants::M_COMPONENT);
        }

        // The media type comes from the assignment, so the browser can not choose it.
        $mediatype = $plugin->get_config('recordertype');
        if ($mediatype !== constants::REC_AUDIO && $mediatype !== constants::REC_VIDEO) {
            throw new \moodle_exception('lr_cannotrecord', constants::M_COMPONENT);
        }

        // Video is always transcribed with Amazon Transcribe, as the iframe recorders do.
        $transcribe = (int) $plugin->get_config('enabletranscription');
        if ($transcribe && $mediatype === constants::REC_VIDEO) {
            $transcribe = constants::TRANSCRIBER_AMAZONTRANSCRIBE;
        }

        $siteconfig = get_config(constants::M_COMPONENT);
        $token = utils::fetch_token($siteconfig->apiuser, $siteconfig->apisecret);

        $details = upload_details::fetch(utils::get_cloud_poodll_server(), is_string($token) ? $token : '', [
            'mediatype' => $mediatype,
            'parent' => $CFG->wwwroot,
            'appid' => constants::APPID,
            'owner' => hash('md5', $USER->username),
            'region' => $siteconfig->awsregion,
            'expiredays' => $plugin->get_config('expiredays') ?: $siteconfig->expiredays,
            'transcribe' => $transcribe,
            'subtitle' => $transcribe == constants::TRANSCRIBER_AMAZONTRANSCRIBE ? 1 : 0,
            'language' => $plugin->get_config('language') ?: ($siteconfig->language ?? 'en-US'),
            'vocab' => 'none',
            'sourcemimetype' => $params['sourcemimetype'],
        ]);

        if (!$details['success']) {
            throw new \moodle_exception('lr_uploaddetailsfailed', constants::M_COMPONENT, '', s($details['message']));
        }

        return [
            'posturl' => $details['posturl'],
            'mediaurl' => $details['mediaurl'],
        ];
    }

    /**
     * Describes the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'posturl' => new external_value(PARAM_RAW, 'The presigned S3 URL to PUT the recording to'),
            'mediaurl' => new external_value(PARAM_URL, 'The URL the converted recording will appear at'),
        ]);
    }
}
