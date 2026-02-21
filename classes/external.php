<?php
/**
 * External.
 *
 * @package assignsubmission_cloudpoodll
 * @author  Justin Hunt - Poodll.com
 */


namespace assignsubmission_cloudpoodll;

global $CFG;

//This is for pre M4.0 and post M4.0 to work on same code base
require_once($CFG->libdir . '/externallib.php');
use external_api;
use external_function_parameters;
use external_value;

/*
 * This is for M4.0 and later
 use core_external\external_api;
 use core_external\external_function_parameters;
 use core_external\external_value;
 */

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/submission/cloudpoodll/locallib.php');


/**
 * External class.
 *
 * @package assignsubmission_cloudpoodll
 * @author  Justin Hunt - Poodll.com
 */
class external extends external_api
{

    public static function check_grammar($text, $assignmentid)
    {

        $params = self::validate_parameters(self::check_grammar_parameters(), [
            'text' => $text,
            'assignmentid' => $assignmentid]);
        extract($params);

        $cm = get_coursemodule_from_instance('assign', $assignmentid);
        $modulecontext = \context_module::instance($cm->id);
        $assign = new \assign($modulecontext, $cm, null);
        $cp_sub = new \assign_submission_cloudpoodll($assign, 'cloudpoodll');
        if (!$cp_sub) {
            return "";
        }

        $siteconfig = get_config(constants::M_COMPONENT);
        $token = utils::fetch_token($siteconfig->apiuser, $siteconfig->apisecret);

        $region = $cp_sub->get_config('region');
        $language = $cp_sub->get_config('language');
        $corrections = utils::fetch_grammar_correction($token, $region, $language, $text);
        $ret = new \stdClass();
        if ($corrections) {
            $differer = new FineDiff($text, $corrections, FineDiff::$wordGranularity);
            $ret->diffhtml = $differer->renderDiffToHTML();
        }
        if ($corrections == $text || empty($corrections)) {
            $ret->corrections = "no corrections";
        } else {
            $ret->corrections = $corrections;
        }
        return json_encode($ret);
    }

    public static function check_grammar_parameters()
    {
        return new external_function_parameters([
            'text' => new external_value(PARAM_TEXT),
            'assignmentid' => new external_value(PARAM_INT)
        ]);
    }

    public static function check_grammar_returns()
    {
        return new external_value(PARAM_RAW);
    }

    /**
     * Upload whiteboard image.
     *
     * @param string $base64data
     * @param int $draftitemid
     * @param string $filename
     * @return string
     */
    public static function upload_whiteboard_image($base64data, $draftitemid, $filename)
    {
        global $USER;

        $params = self::validate_parameters(self::upload_whiteboard_image_parameters(), [
            'base64data' => $base64data,
            'draftitemid' => $draftitemid,
            'filename' => $filename,
        ]);
        extract($params);

        // Check there is no metadata prefixed to the base 64.
        $metapos = strrpos($base64data, ",");
        if ($metapos) {
            $base64data = substr($base64data, $metapos + 1);
        }

        // Decode the data.
        $filecontent = base64_decode($base64data);

        // Save the file.
        $fs = get_file_storage();
        $filerecord = new \stdClass();
        $filerecord->contextid = \context_user::instance($USER->id)->id;
        $filerecord->component = 'user';
        $filerecord->filearea = 'draft';
        $filerecord->itemid = $draftitemid;
        $filerecord->filepath = '/';
        $filerecord->filename = $filename;
        $filerecord->userid = $USER->id;

        // If file already exists, delete it.
        if ($fs->file_exists($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid, $filerecord->filepath, $filerecord->filename)) {
            $file = $fs->get_file($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid, $filerecord->filepath, $filerecord->filename);
            $file->delete();
        }

        $fs->create_file_from_string($filerecord, $filecontent);

        return $filename;
    }

    /**
     * Parameters for upload_whiteboard_image.
     *
     * @return external_function_parameters
     */
    public static function upload_whiteboard_image_parameters()
    {
        return new external_function_parameters([
            'base64data' => new external_value(PARAM_RAW, 'Base64 image data'),
            'draftitemid' => new external_value(PARAM_INT, 'Draft item id'),
            'filename' => new external_value(PARAM_FILE, 'Filename')
        ]);
    }

    /**
     * Returns for upload_whiteboard_image.
     *
     * @return external_value
     */
    public static function upload_whiteboard_image_returns()
    {
        return new external_value(PARAM_RAW, 'The filename');
    }
}