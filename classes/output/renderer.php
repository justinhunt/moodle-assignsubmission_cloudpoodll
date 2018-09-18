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


class renderer extends \plugin_renderer_base {

    public function fetch_delete_submission(){

        $ds= \html_writer::tag('button',
            get_string('deletesubmission',constants::M_COMPONENT),
            array('type'=>'button','id'=>constants::M_COMPONENT .'_deletesubmissionbutton','class'=>constants::M_COMPONENT .'_deletesubmissionbutton btn btn-secondary'));

        return $ds;
    }

    public function prepare_current_submission($responses, $deletesubmission){
        $cs = \html_writer::div($responses . $deletesubmission, constants::M_COMPONENT . '_currentsubmission');
        return $cs;
    }

   
    /**
     * The html part of the recorder
     */
    public function fetch_recorder($r_options,$token){
        global $CFG;


        $r_options->recordertype = 'audio';
        $r_options->recorderskin = 'standard';
        //recorder sizes (for now...)

        switch($r_options->recordertype) {
            case constants::REC_AUDIO:
                //fresh
                if($r_options->recorderskin==constants::SKIN_FRESH){
                    $width = "400";
                    $height = "300";


                }elseif($r_options->recorderskin==constants::SKIN_PLAIN){
                    $width = "360";
                    $height = "190";
                 //bmr 123 once standard
                }else {
                    $width = "360";
                    $height = "240";
                }
                break;
            case constants::REC_VIDEO:
            default:
                //bmr 123 once
                if($r_options->recorderskin==constants::SKIN_BMR) {
                    $width = "360";
                    $height = "450";
                }elseif($r_options->recorderskin==constants::SKIN_123){
                    $width = "360";
                    $height = "410";
                }elseif($r_options->recorderskin==constants::SKIN_ONCE){
                    $width = "350";
                    $height = "290";
                 //standard
                }else {
                    $width = "360";
                    $height = "410";
                }
        }

        //transcribe
        $can_transcribe = utils::can_transcribe($r_options);
        $transcribe = $can_transcribe && $r_options->transcribe  ? "1" : "0";

        //transcode
        $transcode = $r_options->transcode  ? "1" : "0";

        //any recorder hints ... go here..
        $hints = new \stdClass();
        $string_hints = base64_encode (json_encode($hints));

        $recorderdiv= \html_writer::div('', constants::M_COMPONENT  . '_center',
            array('id'=>constants::ID_REC,
                'data-id'=>'therecorder',
                'data-parent'=>$CFG->wwwroot,
                'data-localloader'=>'/mod/assign/submission/cloudpoodll/poodllloader.html',
                'data-media'=>$r_options->recordertype,
                'data-appid'=>constants::APPID,
                'data-type'=>$r_options->recorderskin,
                'data-width'=>$width,
                'data-height'=>$height,
                //'data-iframeclass'=>"letsberesponsive",
                'data-updatecontrol'=>constants::ID_UPDATE_CONTROL,
                'data-timelimit'=> $r_options->timelimit,
                'data-transcode'=>$transcode,
                'data-transcribe'=>$transcribe,
                'data-transcribelanguage'=>$r_options->language,
                'data-expiredays'=>$r_options->expiredays,
                'data-region'=>$r_options->awsregion,
                'data-fallback'=>$r_options->fallback,
                'data-hints'=>$string_hints,
                'data-token'=>$token //localhost
                //'data-token'=>"643eba92a1447ac0c6a882c85051461a" //cloudpoodll
            )
        );
        $containerdiv= \html_writer::div($recorderdiv,constants::CLASS_REC_CONTAINER . " " . constants::M_COMPONENT  . '_center',
            array('id'=>constants::CLASS_REC_CONTAINER));

        //this is the finalhtml
        $recorderhtml = \html_writer::div($containerdiv ,constants::CLASS_REC_OUTER);

        //return html
        return $recorderhtml;
    }

}