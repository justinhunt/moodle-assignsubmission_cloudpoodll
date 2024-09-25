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
class external extends external_api {

    public static function check_grammar($text, $assignmentid) {

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
        $corrections = utils::fetch_grammar_correction($token,$region,$language,$text);
        $ret = new \stdClass();
        if ($corrections) {
            $differer = new FineDiff($text, $corrections, FineDiff::$wordGranularity);
            $ret->diffhtml = $differer->renderDiffToHTML();
        }
        if($corrections==$text || empty($corrections)){
            $ret->corrections="no corrections";
        }else{
            $ret->corrections = $corrections;
        }
        return json_encode($ret);
    }

    public static function check_grammar_parameters() {
        return new external_function_parameters([
            'text' => new external_value(PARAM_TEXT),
            'assignmentid' => new external_value(PARAM_INT)
        ]);
    }

    public static function check_grammar_returns() {
        return new external_value(PARAM_RAW);
    }
}
