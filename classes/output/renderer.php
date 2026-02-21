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

class renderer extends \plugin_renderer_base
{

    public function fetch_delete_submission()
    {

        $ds = \html_writer::tag('button',
            get_string('deletesubmission', constants::M_COMPONENT),
            array('type' => 'button', 'id' => constants::M_COMPONENT . '_deletesubmissionbutton', 'class' => constants::M_COMPONENT . '_deletesubmissionbutton btn btn-secondary'));

        return $ds;
    }

    public function prepare_current_submission($responses, $deletesubmission)
    {
        $toggletext = \html_writer::tag('span', get_string('clicktoshow', constants::M_COMPONENT), array('class' => 'toggletext'));
        $togglebutton = \html_writer::tag('span', '', array('class' => 'fa fa-2x fa-toggle-off togglebutton', 'aria-hidden' => 'true'));
        $toggle = \html_writer::div($togglebutton . $toggletext, constants::M_COMPONENT . '_togglecontainer');
        $cs = \html_writer::div($responses . $deletesubmission, constants::M_COMPONENT . '_currentsubmission', array('style' => 'display: none;'));
        return $toggle . $cs;
    }


    /**
     * The html part of the recorder
     */
    public function fetch_recorder($recoptions, $token)
    {
        global $CFG, $USER;

        // Set token.
        $recoptions->token = $token;

        // Set width and height.
        switch ($recoptions->recordertype) {
            case constants::REC_AUDIO:
                // Fresh
                if ($recoptions->recorderskin == constants::SKIN_FRESH) {
                    $recoptions->width = "400";
                    $recoptions->height = "300";
                } else if ($recoptions->recorderskin == constants::SKIN_PLAIN) {
                    $recoptions->width = "360";
                    $recoptions->height = "190";

                } else if ($recoptions->recorderskin == constants::SKIN_UPLOAD) {
                    $recoptions->width = "360";
                    $recoptions->height = "150";

                // Bmr 123 once standard
                } else {
                    $recoptions->width = "360";
                    $recoptions->height = "240";
                }
                break;
            case constants::REC_WHITEBOARD:
                $bits = explode('x', $recoptions->boardsize);
                $recoptions->width = $bits[0];
                $recoptions->height = $bits[1];
                break;
            case constants::REC_VIDEO:
            default:
                // Bmr 123 once
                if ($recoptions->recorderskin == constants::SKIN_BMR) {
                    $recoptions->width = "360";
                    $recoptions->height = "450";
                } elseif ($recoptions->recorderskin == constants::SKIN_123) {
                    $recoptions->width = "450";
                    $recoptions->height = "550";
                } elseif ($recoptions->recorderskin == constants::SKIN_ONCE ||
                $recoptions->recorderskin == constants::SKIN_SCREEN) {
                    $recoptions->width = "350";
                    $recoptions->height = "290";
                } elseif ($recoptions->recorderskin == constants::SKIN_UPLOAD) {
                    $recoptions->width = "350";
                    $recoptions->height = "310";
                // Standard
                } else {
                    $recoptions->width = "360";
                    $recoptions->height = "410";
                }
        }

        // First lets do the white board which is simpler
        if ($recoptions->recordertype == constants::REC_WHITEBOARD) {
            $recoptions->vectorcontrol = constants::M_VECTORCONTROL;
            $recoptions->updatecontrol = constants::ID_UPDATE_CONTROL;
            $recorderhtml = $this->render_from_template(constants::M_COMPONENT . '/whiteboardrecorder', $recoptions);
            return $recorderhtml;
        }

        // Continue on if its an audio or video controller.
        // Transcribe ?
        $cantranscribe = utils::can_transcribe($recoptions);
        if ($cantranscribe && $recoptions->transcribe) {
            if ($recoptions->recordertype == constants::REC_AUDIO) {
            // Do nothing ... accept defaults
            } else {
                $recoptions->transcribe = constants::TRANSCRIBER_AMAZONTRANSCRIBE;
            }
        }

        // Any recorder hints ... go here..
        // Set encoder to stereoaudio if TRANSCRIBER_GOOGLECLOUDSPEECH:
        $hints = new \stdClass();
        if ($recoptions->transcribe == constants::TRANSCRIBER_GOOGLECLOUDSPEECH) {
            $hints->encoder = 'stereoaudio';
        } else {
            $hints->encoder = 'auto';
        }
        if ($recoptions->shadowing) {
            $hints->shadowing = 1;
        }
        $recoptions->string_hints = base64_encode(json_encode($hints));

        // Set subtitles.
        switch ($recoptions->transcribe) {
            case constants::TRANSCRIBER_AMAZONTRANSCRIBE:
            case constants::TRANSCRIBER_GOOGLECLOUDSPEECH:
                $recoptions->subtitle = "1";
                break;
            default:
                $recoptions->subtitle = "0";
                break;
        }

        // Transcode.
        $recoptions->transcode = $recoptions->transcode ? "1" : "0";

        $recoptions->localloader = '/mod/assign/submission/cloudpoodll/poodlllocalloader.php';
        $recoptions->cloudpoodllurl = utils::get_cloud_poodll_server();
        $recoptions->recid = constants::ID_REC;
        $recoptions->dataid = 'therecorder';
        $recoptions->appid = constants::APPID;
        $recoptions->parent = $CFG->wwwroot;
        $recoptions->owner = hash('md5', $USER->username);
        $recoptions->updatecontrol = constants::ID_UPDATE_CONTROL;


        if ($recoptions->recordertype == constants::REC_AUDIO) {
            $recoptions->iframeclass = constants::CLASS_AUDIOREC_IFRAME;
            $recorderhtml = $this->render_from_template(constants::M_COMPONENT . '/audiorecordercontainer', $recoptions);
        } else {
            $recoptions->iframeclass = constants::CLASS_VIDEOREC_IFRAME;
            $recorderhtml = $this->render_from_template(constants::M_COMPONENT . '/videorecordercontainer', $recoptions);
        }
        return $recorderhtml;

    }

    /**
     * Return HTML to display message about problem
     */
    public function show_problembox($msg)
    {
        $output = '';
        $output .= $this->output->box_start(constants::M_COMPONENT . '_problembox');
        $output .= $this->notification($msg, 'warning');
        $output .= $this->output->box_end();
        return $output;
    }
}