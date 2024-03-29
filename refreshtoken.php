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
 * A token refreshing helper for the Cloud Poodll Assignment submission
 *
 *
 * @package    assignsubmission_cloudpoodll
 * @copyright  Justin Hunt (justin@poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');

use \assignsubmission_cloudpoodll\utils;
use \assignsubmission_cloudpoodll\constants;

$debug = optional_param('debug', 0, PARAM_INT);

require_login(0, false);
$systemcontext = context_system::instance();

if(has_capability('moodle/site:config',$systemcontext)){
    $apiuser = get_config(constants::M_COMPONENT,'apiuser');
    $apisecret=get_config(constants::M_COMPONENT,'apisecret');
    $force=true;
    if($apiuser && $apisecret) {
      $ret =  utils::fetch_token($apiuser, $apisecret, $force,$debug);
      if($debug){
          echo "<html lang='en-US'><body><pre>";
          if(!$ret){
              echo "no debug details";
          }else{
              echo json_encode($ret,JSON_PRETTY_PRINT);
          }
          echo "</pre></body></html>";
          die;
      }
    }
}
redirect($CFG->wwwroot . '/admin/settings.php?section=assignsubmission_cloudpoodll');