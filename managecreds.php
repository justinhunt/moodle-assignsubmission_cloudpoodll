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

/**
 * A utility to make it easier to get API Creds
 *
 *
 * @package    assignsubmission_cloudpoodll
 * @copyright  Justin Hunt (justin@poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');

use \assignsubmission_cloudpoodll\utils;
use \assignsubmission_cloudpoodll\constants;

require_login(0, false);
$systemcontext = context_system::instance();
require_capability('moodle/site:config',$systemcontext);

//get any requested action or from (other poodll plugin)
$action = optional_param('action', '', PARAM_TEXT);
$from = optional_param('from', '', PARAM_TEXT);

//Otherwise lets show some options
$PAGE->set_context($systemcontext);
$PAGE->set_url(constants::M_URL . '/managecreds.php',array());
$PAGE->set_title(get_string("pluginname", constants::M_COMPONENT));
$PAGE->set_heading(get_string("managecredsheading", constants::M_COMPONENT));
//$PAGE->set_pagelayout('popup');
$PAGE->set_pagelayout('course');

// Render template and display page.
echo $OUTPUT->header();
echo "hello";
//If we already have credentials we just refresh the token and return
$apiuser = get_config(constants::M_COMPONENT,'apiuser');
$apisecret=get_config(constants::M_COMPONENT,'apisecret');

if(!empty($action)){
    switch($action){
        case "refreshtoken":
            $force=true;
            if(!empty($apiuser) && !empty($apisecret)) {
                utils::fetch_token($apiuser, $apisecret, $force);
            }
            //we could add a switch here to check for an expired free trial ...
            redirect($CFG->wwwroot . '/admin/settings.php?section=assignsubmission_cloudpoodll');
        case "pushcreds":
            $components=array('mod_readaloud','mod_pchat','mod_solo','mod_wordcards','mod_minilesson',
                'qtype_cloudpoodll','atto_cloudpoodll','tinymce_cloudpoodll',
                'assignsubmission_cloudpoodll','assignfeedback_cloudpoodll', 'filter_poodll');
            $pushcount = 0;
            $candidatecount = 0;
            foreach($components as $component){
                switch($component) {
                    case "filter_poodll":
                        $apiusersetting = "cpapiuser";
                        $apisecretsetting = "cpapisecret";
                        break;
                    default:
                        $apiusersetting = "apiuser";
                        $apisecretsetting = "apisecret";
                }

               $current_apiuser = get_config($component,$apiusersetting);
               //if the plugin is not installed or config not exist for the plugin, it will be false
                //and we should move on ..
               if($current_apiuser !== false) {
                   $candidatecount++;
                   $current_apisecret = get_config($component, $apisecretsetting);
                   if($current_apisecret !== false) {
                       //if the config exists but its empty, set it
                       if (empty($current_apiuser) && empty($current_apisecret)) {
                           set_config($apiusersetting, $apiuser, $component);
                           set_config($apisecretsetting, $apisecret, $component);
                           $pushcount++;
                       }
                   }
               }
            }//end of for each component loop
            $a = new stdClass();
            $a->candidatecount=$candidatecount;
            $a->pushcount=$pushcount;
            $message = get_string('pushedcreds',constants::M_COMPONENT,$a);
            redirect($CFG->wwwroot . '/admin/settings.php?section=assignsubmission_cloudpoodll');

        case "freetrial":

            //

        case "fetchcreds":

    }

}

$items =[];

//Refresh Token
$refreshtoken = new \single_button(
    new \moodle_url(constants::M_URL . '/managecreds.php',
        array('action' => 'refreshtoken')),
    get_string('refreshtoken', constants::M_COMPONENT), 'get');
$items[] = ['title'=>get_string('refreshtoken', constants::M_COMPONENT),
    'description'=>get_string('refreshtoken_desc', constants::M_COMPONENT),
    'content'=>$OUTPUT->render($refreshtoken)];

$pushcreds = new \single_button(
    new \moodle_url(constants::M_URL . '/managecreds.php',
        array('action' => 'pushcreds')),
    get_string('pushcreds', constants::M_COMPONENT), 'get');
$items[] = ['title'=>get_string('pushcreds', constants::M_COMPONENT),
    'description'=>get_string('pushcreds_desc', constants::M_COMPONENT),
    'content'=>$OUTPUT->render($pushcreds)];

$fetchcreds = new \single_button(
    new \moodle_url(constants::M_URL . '/managecreds.php',
        array('action' => 'fetchcreds')),
    get_string('fetchcreds', constants::M_COMPONENT), 'get');
$items[] = ['title'=>get_string('fetchcreds', constants::M_COMPONENT),
    'description'=>get_string('fetchcreds_desc', constants::M_COMPONENT),
    'content'=>$OUTPUT->render($fetchcreds)];
/*
$freetrial = new \single_button(
    new \moodle_url(constants::M_URL . '/managecreds.php',
        array('action' => 'freetrial')),
    get_string('freetrial', constants::M_COMPONENT), 'get');
*/
//$freetrial = '<script src = "https://js.chargebee.com/v2/chargebee.js"  data-cb-site = "poodllcom" > </script>';
$freetrial = '<a class="btn btn-secondary poodll_pop_cb" href="javascript:void(0)"  data-planpriceid="Poodll-Free-Trial-USD-Daily">'
    . get_string('freetrial', constants::M_COMPONENT)
    . '</a>';

$items[] = ['title'=>get_string('freetrial', constants::M_COMPONENT),
    'description'=>get_string('freetrial_desc', constants::M_COMPONENT),
    'content'=>$freetrial];

$memberdashboard = new \single_button(
    new \moodle_url(constants::M_URL . '/managecreds.php',
        array('action' => 'memberdashboard')),
    get_string('memberdashboard', constants::M_COMPONENT), 'get');
$items[] = ['title'=>get_string('memberdashboard', constants::M_COMPONENT),
    'description'=>get_string('memberdashboard_desc', constants::M_COMPONENT),
    'content'=>$OUTPUT->render($memberdashboard)];

//Generate and return options menu
echo $OUTPUT->render_from_template( constants::M_COMPONENT . '/managecreds',
    ['items'=>$items, 'poodllcbsite'=>'poodllcom', 'component'=>constants::M_COMPONENT,'wwwroot'=>$CFG->wwwroot]);


echo $OUTPUT->footer();
