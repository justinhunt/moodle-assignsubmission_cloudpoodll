<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace assignsubmission_cloudpoodll\output;

use assignsubmission_cloudpoodll\constants;
use assignsubmission_cloudpoodll\utils;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {

    public function fetch_delete_submission(){

        $ds= \html_writer::tag('button',
            get_string('deletesubmission',constants::M_COMPONENT),
            array('type'=>'button','id'=>constants::M_COMPONENT .'_deletesubmissionbutton','class'=>constants::M_COMPONENT .'_deletesubmissionbutton btn btn-secondary'));

        return $ds;
    }

    public function prepare_current_submission($responses, $deletesubmission){
        $toggletext = \html_writer::tag('span',get_string('clicktoshow',constants::M_COMPONENT),array('class'=>'toggletext'));
        $togglebutton = \html_writer::tag('span','',array('class'=>'fa fa-2x fa-toggle-off togglebutton','aria-hidden'=>'true'));
        $toggle =\html_writer::div($togglebutton . $toggletext, constants::M_COMPONENT . '_togglecontainer');
        $cs = \html_writer::div($responses . $deletesubmission, constants::M_COMPONENT . '_currentsubmission',array('style'=>'display: none;'));
        return $toggle . $cs;
    }

   
    /**
     * The html part of the recorder
     */
    public function fetch_recorder($r_options,$token){
        global $CFG, $USER;

        //set token
        $r_options->token = $token;

        //set width and height
        switch($r_options->recordertype) {
            case constants::REC_AUDIO:
                //fresh
                if($r_options->recorderskin==constants::SKIN_FRESH){
                    $r_options->width = "400";
                    $r_options->height = "300";


                }elseif($r_options->recorderskin==constants::SKIN_PLAIN){
                    $r_options->width = "360";
                    $r_options->height = "190";

                }elseif($r_options->recorderskin==constants::SKIN_UPLOAD){
                    $r_options->width = "360";
                    $r_options->height = "150";

                 //bmr 123 once standard
                }else {
                    $r_options->width = "360";
                    $r_options->height = "240";
                }
                break;
            case constants::REC_VIDEO:
            default:
                //bmr 123 once
                if($r_options->recorderskin==constants::SKIN_BMR) {
                    $r_options->width = "360";
                    $r_options->height = "450";
                }elseif($r_options->recorderskin==constants::SKIN_123){
                    $r_options->width = "450";//"360";
                    $r_options->height = "550";//"410";
                }elseif($r_options->recorderskin==constants::SKIN_ONCE){
                    $r_options->width = "350";
                    $r_options->height = "290";
                }elseif($r_options->recorderskin==constants::SKIN_UPLOAD){
                    $r_options->width = "350";
                    $r_options->height = "310";
                 //standard
                }else {
                    $r_options->width = "360";
                    $r_options->height = "410";
                }
        }

        //transcribe
        $can_transcribe = utils::can_transcribe($r_options);
        if($can_transcribe && $r_options->transcribe){
            if($r_options->recordertype==constants::REC_AUDIO) {
                //do nothing ... accept defaults
            }else{
                $r_options->transcribe = constants::TRANSCRIBER_AMAZONTRANSCRIBE;
            }
        }

        //any recorder hints ... go here..
        //Set encoder to stereoaudio if TRANSCRIBER_GOOGLECLOUDSPEECH:
        $hints = new \stdClass();
        if($r_options->transcribe == constants::TRANSCRIBER_GOOGLECLOUDSPEECH) {
            $hints->encoder = 'stereoaudio';
        }else{
            $hints->encoder = 'auto';
        }
        $r_options->string_hints = base64_encode(json_encode($hints));

        //Set subtitles
        switch($r_options->transcribe){
            case constants::TRANSCRIBER_AMAZONTRANSCRIBE:
            case constants::TRANSCRIBER_GOOGLECLOUDSPEECH:
                $r_options->subtitle="1";
                break;
            default:
                $r_options->subtitle="0";
                break;
        }

        //transcode
        $r_options->transcode  = $r_options->transcode  ? "1" : "0";

        $r_options->localloader = '/mod/assign/submission/cloudpoodll/poodllloader.html';
        $r_options->recid = constants::ID_REC;
        $r_options->dataid = 'therecorder';
        $r_options->appid = constants::APPID;
        $r_options->parent = $CFG->wwwroot;
        $r_options->owner = hash('md5',$USER->username);
        $r_options->updatecontrol = constants::ID_UPDATE_CONTROL;


        if($r_options->recordertype==constants::REC_AUDIO) {
            $r_options->iframeclass=constants::CLASS_AUDIOREC_IFRAME;
            $recorderhtml = $this->render_from_template(constants::M_COMPONENT . '/audiorecordercontainer', $r_options);
        }else{
            $r_options->iframeclass=constants::CLASS_VIDEOREC_IFRAME;
            $recorderhtml = $this->render_from_template(constants::M_COMPONENT . '/videorecordercontainer', $r_options);
        }
        return $recorderhtml;

    }

    /**
     * Return HTML to display message about problem
     */
    public function show_problembox($msg) {
        $output = '';
        $output .= $this->output->box_start(constants::M_COMPONENT . '_problembox');
        $output .= $this->notification($msg, 'warning');
        $output .= $this->output->box_end();
        return $output;
    }
}