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
 *
 *
 * @package   assignsubmission_cloudpoodll
 * @copyright 2018 Justin Hunt {@link http://www.poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace assignsubmission_cloudpoodll;


defined('MOODLE_INTERNAL') || die();

class utils
{

    //const CLOUDPOODLL = 'http://localhost/moodle';
    const CLOUDPOODLL = 'https://cloud.poodll.com';

    public static function fetch_options_videosize(){
        //The size of the video player on the various screens
        $size_options = array(
                constants::SIZE_NONE => new \lang_string('shownovideo', constants::M_COMPONENT),
                constants::SIZE_LIGHTBOX => new \lang_string('showvideolightbox', constants::M_COMPONENT),
                '480' => '480x360');
        return $size_options;
    }

    public static function fetch_options_audiosize(){
        //The size of the audio player on the various screens
        $size_options = array(

                constants::SIZE_AUDIO_LINK => get_string("shownoaudio", constants::M_COMPONENT),
                constants::SIZE_AUDIO_LIGHTBOX => get_string("showaudiolightbox", constants::M_COMPONENT),
                constants::SIZE_AUDIO_SHOW => get_string("yes", constants::M_COMPONENT));
        return $size_options;
    }

    public static function fetch_options_recorders(){
        $rec_options = array( constants::REC_AUDIO => get_string("recorderaudio", constants::M_COMPONENT),
            constants::REC_VIDEO  => get_string("recordervideo", constants::M_COMPONENT));
        return $rec_options;
    }

    public static function fetch_options_fallback(){
        $options = array( constants::FALLBACK_UPLOAD => get_string("fallbackupload", constants::M_COMPONENT),
            constants::FALLBACK_IOSUPLOAD  => get_string("fallbackiosupload", constants::M_COMPONENT),
            constants::FALLBACK_WARNING  => get_string("fallbackwarning", constants::M_COMPONENT));
        return $options;
    }

    public static function fetch_options_interactivetranscript(){
        $options = array( constants::PLAYERTYPE_DEFAULT => get_string("playertypedefault", constants::M_COMPONENT),
            constants::PLAYERTYPE_INTERACTIVETRANSCRIPT  => get_string("playertypeinteractivetranscript", constants::M_COMPONENT),
        constants::PLAYERTYPE_STANDARDTRANSCRIPT  => get_string("playertypestandardtranscript", constants::M_COMPONENT));
        return $options;
    }

    public static function fetch_options_skins(){
        $rec_options = array( constants::SKIN_PLAIN => get_string("skinplain", constants::M_COMPONENT),
            constants::SKIN_BMR => get_string("skinbmr", constants::M_COMPONENT),
            constants::SKIN_123 => get_string("skin123", constants::M_COMPONENT),
            constants::SKIN_FRESH => get_string("skinfresh", constants::M_COMPONENT),
            constants::SKIN_ONCE => get_string("skinonce", constants::M_COMPONENT),
            constants::SKIN_UPLOAD => get_string("skinupload", constants::M_COMPONENT));
        return $rec_options;
    }

    public static function get_region_options(){
        return array(
            constants::REGION_USEAST1 => get_string("useast1",constants::M_COMPONENT),
            constants::REGION_TOKYO => get_string("tokyo",constants::M_COMPONENT),
            constants::REGION_SYDNEY => get_string("sydney",constants::M_COMPONENT),
            constants::REGION_DUBLIN => get_string("dublin",constants::M_COMPONENT),
            constants::REGION_OTTAWA => get_string("ottawa",constants::M_COMPONENT),
            constants::REGION_FRANKFURT => get_string("frankfurt",constants::M_COMPONENT),
            constants::REGION_LONDON => get_string("london",constants::M_COMPONENT),
            constants::REGION_SAOPAULO => get_string("saopaulo",constants::M_COMPONENT),
            constants::REGION_SINGAPORE => get_string("singapore",constants::M_COMPONENT),
            constants::REGION_MUMBAI => get_string("mumbai",constants::M_COMPONENT),
            constants::REGION_CAPETOWN => get_string("capetown",constants::M_COMPONENT),
            constants::REGION_BAHRAIN => get_string("bahrain",constants::M_COMPONENT)
        );
    }

    public static function get_transcriber_options(){
        return array(
                constants::TRANSCRIBER_NONE => get_string("transcribernone",constants::M_COMPONENT),
                constants::TRANSCRIBER_AMAZONTRANSCRIBE => get_string("transcriberamazon",constants::M_COMPONENT),
                constants::TRANSCRIBER_GOOGLECLOUDSPEECH => get_string("transcribergooglecloud",constants::M_COMPONENT)
        );
    }

    public static function get_expiredays_options(){
        return array(
            "1"=>"1",
            "3"=>"3",
            "7"=>"7",
            "30"=>"30",
            "90"=>"90",
            "180"=>"180",
            "365"=>"365",
            "730"=>"730",
            "9999"=>get_string('forever',constants::M_COMPONENT)
        );
    }

    public static function get_lang_options()
    {
        return array(
                constants::LANG_ENUS => get_string('en-us', constants::M_COMPONENT),
                constants::LANG_ENAU => get_string('en-au', constants::M_COMPONENT),
                constants::LANG_ENGB => get_string('en-gb', constants::M_COMPONENT),
                constants::LANG_ENIE => get_string('en-ie', constants::M_COMPONENT),
                constants::LANG_ENWL => get_string('en-wl', constants::M_COMPONENT),
                constants::LANG_ENAB => get_string('en-ab', constants::M_COMPONENT),
                constants::LANG_ENIN => get_string('en-in', constants::M_COMPONENT),
                constants::LANG_ARAE => get_string('ar-ae', constants::M_COMPONENT),
                constants::LANG_ARSA => get_string('ar-sa', constants::M_COMPONENT),
                constants::LANG_ESES => get_string('es-es', constants::M_COMPONENT),
                constants::LANG_ESUS => get_string('es-us', constants::M_COMPONENT),
                constants::LANG_FRCA => get_string('fr-ca', constants::M_COMPONENT),
                constants::LANG_FRFR => get_string('fr-fr', constants::M_COMPONENT),
                constants::LANG_ITIT => get_string('it-it', constants::M_COMPONENT),
                constants::LANG_PTPT => get_string('pt-pt', constants::M_COMPONENT),
                constants::LANG_PTBR => get_string('pt-br', constants::M_COMPONENT),
                constants::LANG_JAJP => get_string('ja-jp', constants::M_COMPONENT),
                constants::LANG_KOKR => get_string('ko-kr', constants::M_COMPONENT),
                constants::LANG_ZHCN => get_string('zh-cn', constants::M_COMPONENT),
                constants::LANG_DEDE => get_string('de-de', constants::M_COMPONENT),
                constants::LANG_DECH => get_string('de-ch', constants::M_COMPONENT),
                constants::LANG_NLNL => get_string('nl-nl', constants::M_COMPONENT),
                constants::LANG_HIIN => get_string('hi-in', constants::M_COMPONENT),
                constants::LANG_TAIN => get_string('ta-in', constants::M_COMPONENT),
                constants::LANG_TEIN => get_string('te-in', constants::M_COMPONENT),
                constants::LANG_RURU => get_string('ru-ru', constants::M_COMPONENT),
                constants::LANG_FAIR => get_string('fa-ir', constants::M_COMPONENT),
                constants::LANG_HEIL => get_string('he-il', constants::M_COMPONENT),
                constants::LANG_IDID => get_string('id-id', constants::M_COMPONENT),
                constants::LANG_MSMY => get_string('ms-my', constants::M_COMPONENT),
                constants::LANG_TRTR => get_string('tr-tr', constants::M_COMPONENT),
        );
    }

    //are we willing and able to transcribe submissions?
    public static function can_transcribe($instance)
    {

        //The regions that can transcribe
        switch($instance->awsregion){

            default:
                $ret = true;
        }
        return $ret;
    }

    //we use curl to fetch transcripts from AWS and Tokens from cloudpoodll
    //this is our helper
    public static function curl_fetch($url,$postdata=false)
    {
        global $CFG;

        require_once($CFG->libdir.'/filelib.php');
        $curl = new \curl();

        $result = $curl->get($url, $postdata);
        return $result;
    }

    //This is called from the settings page and we do not want to make calls out to cloud.poodll.com on settings
    //page load, for performance and stability issues. So if the cache is empty and/or no token, we just show a
    //"refresh token" links
    public static function fetch_token_for_display($apiuser,$apisecret){
        global $CFG;

        //First check that we have an API id and secret
        //refresh token
        $refresh = \html_writer::link($CFG->wwwroot . '/mod/assign/submission/cloudpoodll/refreshtoken.php',
                get_string('refreshtoken',constants::M_COMPONENT)) . '<br>';


        $message = '';
        $apiuser = trim($apiuser);
        $apisecret = trim($apisecret);
        if(empty($apiuser)){
            $message .= get_string('noapiuser',constants::M_COMPONENT) . '<br>';
        }
        if(empty($apisecret)){
            $message .= get_string('noapisecret',constants::M_COMPONENT);
        }

        if(!empty($message)){
            return $refresh . $message;
        }

        //Fetch from cache and process the results and display
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');

        //if we have no token object the creds were wrong ... or something
        if(!($tokenobject)){
            $message = get_string('notokenincache',constants::M_COMPONENT);
            //if we have an object but its no good, creds werer wrong ..or something
        }elseif(!property_exists($tokenobject,'token') || empty($tokenobject->token)){
            $message = get_string('credentialsinvalid',constants::M_COMPONENT);
            //if we do not have subs, then we are on a very old token or something is wrong, just get out of here.
        }elseif(!property_exists($tokenobject,'subs')){
            $message = 'No subscriptions found at all';
        }
        if(!empty($message)){
            return $refresh . $message;
        }

        //we have enough info to display a report. Lets go.
        foreach ($tokenobject->subs as $sub){
            $sub->expiredate = date('d/m/Y',$sub->expiredate);
            $message .= get_string('displaysubs',constants::M_COMPONENT, $sub) . '<br>';
        }

        //is site registered
        $haveauthsite = false;
        foreach ($tokenobject->sites as $site) {
            if (self::check_registered_url($site)) {
                $haveauthsite = true;
                break;
            }
        }

        //Is app authorised
        if($haveauthsite && in_array(constants::M_COMPONENT,$tokenobject->apps)){
            $message .= get_string('appauthorised',constants::M_COMPONENT) . '<br>';
        }else{
            $message .= get_string('appnotauthorised',constants::M_COMPONENT) . '<br>';
        }

        return $refresh . $message;

    }

    public static function check_registered_url($theurl, $wildcardok = true) {
        global $CFG;

        //get arrays of the wwwroot and registered url
        //just in case, lowercase'ify them
        $thewwwroot = strtolower($CFG->wwwroot);
        $theregisteredurl = strtolower($theurl);
        $theregisteredurl = trim($theregisteredurl);

        //add http:// or https:// to URLs that do not have it
        if (strpos($theregisteredurl, 'https://') !== 0 &&
                strpos($theregisteredurl, 'http://') !== 0) {
            $theregisteredurl = 'https://' . $theregisteredurl;
        }

        //if neither parsed successfully, that a no straight up
        $wwwroot_bits = parse_url($thewwwroot);
        $registered_bits = parse_url($theregisteredurl);
        if (!$wwwroot_bits || !$registered_bits) {
            return false;
        }

        //get the subdomain widlcard address, ie *.a.b.c.d.com
        $wildcard_subdomain_wwwroot = '';
        if (array_key_exists('host', $wwwroot_bits)) {
            $wildcardparts = explode('.', $wwwroot_bits['host']);
            $wildcardparts[0] = '*';
            $wildcard_subdomain_wwwroot = implode('.', $wildcardparts);
        } else {
            return false;
        }

        //match either the exact domain or the wildcard domain or fail
        if (array_key_exists('host', $registered_bits)) {
            //this will cover exact matches and path matches
            if ($registered_bits['host'] === $wwwroot_bits['host']) {
                return true;
                //this will cover subdomain matches but only for institution bigdog and enterprise license
            } else if (($registered_bits['host'] === $wildcard_subdomain_wwwroot) && $wildcardok) {
                //yay we are registered!!!!
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    //check token and tokenobject(from cache)
    //return error message or blank if its all ok
    public static function fetch_token_error($token){
        global $CFG;

        //check token authenticated
        if(empty($token)) {
            $message = get_string('novalidcredentials', constants::M_COMPONENT,
                    $CFG->wwwroot . constants::M_PLUGINSETTINGS);
            return $message;
        }

        // Fetch from cache and process the results and display.
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');

        //we should not get here if there is no token, but lets gracefully die, [v unlikely]
        if (!($tokenobject)) {
            $message = get_string('notokenincache', constants::M_COMPONENT);
            return $message;
        }

        //We have an object but its no good, creds were wrong ..or something. [v unlikely]
        if (!property_exists($tokenobject, 'token') || empty($tokenobject->token)) {
            $message = get_string('credentialsinvalid', constants::M_COMPONENT);
            return $message;
        }
        // if we do not have subs.
        if (!property_exists($tokenobject, 'subs')) {
            $message = get_string('nosubscriptions', constants::M_COMPONENT);
            return $message;
        }
        // Is app authorised?
        if (!property_exists($tokenobject, 'apps') || !in_array(constants::M_COMPONENT, $tokenobject->apps)) {
            $message = get_string('appnotauthorised', constants::M_COMPONENT);
            return $message;
        }

        //just return empty if there is no error.
        return '';
    }

    //Fetch the plugin dn record for a submission id
    //used when exporting data from privacy provider (at least)
    public static function fetch_submission_data($submissionid){
        global $DB;
        return $DB->get_record(constants::M_TABLE,array('submission'=>$submissionid));
    }

    //We need a Poodll token to make this happen
    public static function fetch_token($apiuser, $apisecret, $force=false)
    {

        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');
        $tokenuser = $cache->get('recentpoodlluser');
        $apiuser = trim($apiuser);
        $apisecret = trim($apisecret);

        //if we got a token and its less than expiry time
        // use the cached one
        if($tokenobject && $tokenuser && $tokenuser==$apiuser && !$force){
            if($tokenobject->validuntil == 0 || $tokenobject->validuntil > time()){
                return $tokenobject->token;
            }
        }

        // Send the request & save response to $resp
        $token_url =self::CLOUDPOODLL . "/local/cpapi/poodlltoken.php";
        $postdata = array(
            'username' => $apiuser,
            'password' => $apisecret,
            'service'=>'cloud_poodll'
        );
        $token_response = self::curl_fetch($token_url,$postdata);
        if ($token_response) {
            $resp_object = json_decode($token_response);
            if($resp_object && property_exists($resp_object,'token')) {
                $token = $resp_object->token;
                //store the expiry timestamp and adjust it for diffs between our server times
                if($resp_object->validuntil) {
                    $validuntil = $resp_object->validuntil - ($resp_object->poodlltime - time());
                    //we refresh one hour out, to prevent any overlap
                    $validuntil = $validuntil - (1 * HOURSECS);
                }else{
                    $validuntil = 0;
                }

                //cache the token
                $tokenobject = new \stdClass();
                $tokenobject->token = $token;
                $tokenobject->validuntil = $validuntil;
                $tokenobject->subs=false;
                $tokenobject->apps=false;
                $tokenobject->sites=false;
                if(property_exists($resp_object,'subs')){
                    $tokenobject->subs = $resp_object->subs;
                }
                if(property_exists($resp_object,'apps')){
                    $tokenobject->apps = $resp_object->apps;
                }
                if(property_exists($resp_object,'sites')){
                    $tokenobject->sites = $resp_object->sites;
                }
                $cache->set('recentpoodlltoken', $tokenobject);
                $cache->set('recentpoodlluser', $apiuser);

            }else{
                $token = '';
                if($resp_object && property_exists($resp_object,'error')) {
                    //ERROR = $resp_object->error
                }
            }
        }else{
            $token='';
        }
        return $token;
    }

    //transcripts become ready in their own time, fetch them here
    public static function fetch_transcriptdata($fileurl){
        $url = $fileurl;
        $transcript = self::curl_fetch($url);
        if(strpos($transcript,"<Error><Code>AccessDenied</Code>")>0){
            return false;
        }
        return $transcript;
    }

    public static function remove_user_submission($mediaurl){
        $config = get_config(constants::M_COMPONENT);
        $token = utils::fetch_token($config->apiuser,$config->apisecret);

        //The REST API we are calling
        $functionname = 'local_cpapi_remove_user_submission';

        //log.debug(params);
        $params = array();
        $params['wstoken'] = $token;
        $params['wsfunction'] = $functionname;
        $params['moodlewsrestformat'] = 'json';
        $params['appid'] = constants::M_COMPONENT;;
        $params['mediaurl'] = $mediaurl;
        $serverurl = self::CLOUDPOODLL . '/webservice/rest/server.php';
        $response = self::curl_fetch($serverurl, $params);
        if (!self::is_json($response)) {
            return false;
        }
        $payloadobject = json_decode($response);

        //returnCode > 0  indicates an error
        if (!isset($payloadobject->returnCode) || $payloadobject->returnCode > 0) {
            return false;
            //if all good, then lets do the embed
        } else {
            return true;
        }
    }

    //see if this is truly json or some error
    public static function is_json($string) {
        if (!$string) {
            return false;
        }
        if (empty($string)) {
            return false;
        }
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}