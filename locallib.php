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
 * This file contains the definition for the library class for cloudpoodll submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package    assignsubmission_cloudpoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



use assignsubmission_cloudpoodll\constants;
use assignsubmission_cloudpoodll\utils;

defined('MOODLE_INTERNAL') || die();

/**
 * library class for cloudpoodll submission plugin extending submission plugin base class
 *
 * @package    assignsubmission_cloudpoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_cloudpoodll extends assign_submission_plugin {
		
  public function is_enabled() {
      return $this->get_config('enabled') && $this->is_configurable();
  }

  public function is_configurable() {
      $context = context_course::instance($this->assignment->get_course()->id);
      if ($this->get_config('enabled')) {
          return true;
      }
      if (!has_capability('assignsubmission/' .  constants::M_SUBPLUGIN . ':use', $context)) {
          return false;
      }
      return parent::is_configurable();
  }
		
    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name() {
        if(get_config(constants::M_COMPONENT,'customname')){
            return get_config(constants::M_COMPONENT,'customname');
        }else {
            return get_string('cloudpoodll', constants::M_COMPONENT);
        }
    }

	    /**
     * Get the settings for Cloud PoodLLsubmission plugin form
     *
     * @global stdClass $CFG
     * @global stdClass $COURSE
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        $adminconfig = get_config(constants::M_COMPONENT);

        //show a divider to keep settings manageable
        $pluginname = get_string('cloudpoodll',constants::M_COMPONENT);
        $customname = get_config(constants::M_COMPONENT, 'customname');
        if(!empty($customname)){
            $args =new stdClass();
            $args->pluginname = $pluginname;
            $args->customname = $customname;
            $divider = get_string('customdivider', constants::M_COMPONENT,$args);
        }else{
            $divider = get_string('divider',constants::M_COMPONENT,$pluginname);
        }

        //If  M3.4 or lower we show a divider to make it easier to figure where poodll ends and starts
        if($CFG->version < 2017111300) {
            $mform->addElement('static', constants::M_COMPONENT . '_divider', '', $divider);
        }

        $recordertype = $this->get_config('recordertype') ? $this->get_config('recordertype') :  $adminconfig->defaultrecorder;
        $recorderskin = $this->get_config('recorderskin') ? $this->get_config('recorderskin') : constants::SKIN_BMR;
		$timelimit = $this->get_config('timelimit') ? $this->get_config('timelimit') :  0;
        $safesave = $this->get_config('safesave') ? $this->get_config('safesave') :  0;
        $expiredays = $this->get_config('expiredays') ? $this->get_config('expiredays') : $adminconfig->expiredays;
        $language = $this->get_config('language') ? $this->get_config('language') : $adminconfig->language;
        $playertype = $this->get_config('playertype') ? $this->get_config('playertype') : $adminconfig->defaultplayertype;
        $playertypestudent = $this->get_config('playertypestudent') ? $this->get_config('playertypestudent') : $adminconfig->defaultplayertypestudent;

        //in this case false means unset
        $enabletranscription = $this->get_config('enabletranscription')!==false ? $this->get_config('enabletranscription') : $adminconfig->enabletranscription;
        $enabletranscode = $this->get_config('enabletranscode')!==false ? $this->get_config('enabletranscode') : $adminconfig->enabletranscode;
        $audiolistdisplay = $this->get_config('audiolistdisplay')!==false ? $this->get_config('audiolistdisplay') : $adminconfig->displayaudioplayer_list;
        $audiosingledisplay = $this->get_config('audiosingledisplay')!==false  ? $this->get_config('audiosingledisplay') : $adminconfig->displayaudioplayer_single;
        $videolistdisplay = $this->get_config('videolistdisplay')!==false  ? $this->get_config('videolistdisplay') : $adminconfig->displaysize_list;
        $videosingledisplay = $this->get_config('videosingledisplay')!==false  ? $this->get_config('videosingledisplay') : $adminconfig->displaysize_single;


        $rec_options = utils::fetch_options_recorders();
		$mform->addElement('select', constants::M_COMPONENT . '_recordertype', get_string("recordertype", constants::M_COMPONENT), $rec_options);
        $mform->setDefault(constants::M_COMPONENT . '_recordertype',$recordertype);
		$mform->disabledIf(constants::M_COMPONENT . '_recordertype', constants::M_COMPONENT . '_enabled', 'notchecked');


        $skin_options = utils::fetch_options_skins();
        $mform->addElement('select', constants::M_COMPONENT . '_recorderskin', get_string("recorderskin", constants::M_COMPONENT), $skin_options);
        $mform->setDefault(constants::M_COMPONENT . '_recorderskin', $recorderskin);
        $mform->disabledIf(constants::M_COMPONENT . '_recorderskin', constants::M_COMPONENT . '_enabled', 'notchecked');


        //Add a place to set a maximum recording time.
	   $mform->addElement('duration', constants::M_COMPONENT . '_timelimit', get_string('timelimit', constants::M_COMPONENT));
       $mform->setDefault(constants::M_COMPONENT . '_timelimit', $timelimit);
       $mform->disabledIf(constants::M_COMPONENT . '_timelimit', constants::M_COMPONENT . '_enabled', 'notchecked');

        //Add expire days
        $expire_options = utils::get_expiredays_options();
        $mform->addElement('select', constants::M_COMPONENT . '_expiredays', get_string("expiredays", constants::M_COMPONENT), $expire_options);
        $mform->setDefault(constants::M_COMPONENT . '_expiredays', $expiredays);
        $mform->disabledIf(constants::M_COMPONENT . '_expiredays', constants::M_COMPONENT . '_enabled', 'notchecked');

        //transcode settings
        $mform->addElement('advcheckbox', constants::M_COMPONENT . '_enabletranscode', get_string("enabletranscode", constants::M_COMPONENT));
        $mform->setDefault(constants::M_COMPONENT . '_enabletranscode', $enabletranscode);
        $mform->disabledIf(constants::M_COMPONENT . '_enabletranscode', constants::M_COMPONENT . '_enabled', 'notchecked');

        //transcription settings
        //here add googlecloudspeech or amazontranscrobe options
        $transcriber_options = utils::get_transcriber_options();
        $mform->addElement('select', constants::M_COMPONENT . '_enabletranscription', get_string("enabletranscription", constants::M_COMPONENT), $transcriber_options);
        $mform->setDefault(constants::M_COMPONENT . '_enabletranscription', $enabletranscription);
        $mform->disabledIf(constants::M_COMPONENT . '_enabletranscription', constants::M_COMPONENT . '_enabled', 'notchecked');
        $mform->disabledIf(constants::M_COMPONENT . '_enabletranscription', constants::M_COMPONENT . '_enabletranscode', 'notchecked');

        //lang options
        $lang_options = utils::get_lang_options();
        $mform->addElement('select', constants::M_COMPONENT . '_language', get_string("language", constants::M_COMPONENT), $lang_options);
        $mform->setDefault(constants::M_COMPONENT . '_language', $language);
        $mform->disabledIf(constants::M_COMPONENT . '_language', constants::M_COMPONENT . '_enabled', 'notchecked');
        $mform->disabledIf(constants::M_COMPONENT . '_language', constants::M_COMPONENT . '_enabletranscription', 'eq',0);
        $mform->disabledIf(constants::M_COMPONENT . '_language', constants::M_COMPONENT . '_enabletranscode', 'notchecked');


        //playertype : teacher
        $playertype_options = utils::fetch_options_interactivetranscript();
        $mform->addElement('select', constants::M_COMPONENT . '_playertype', get_string("playertype", constants::M_COMPONENT), $playertype_options);
        $mform->setDefault(constants::M_COMPONENT . '_playertype', $playertype);
        $mform->disabledIf(constants::M_COMPONENT . '_playertype', constants::M_COMPONENT . '_enabled', 'notchecked');
        $mform->disabledIf(constants::M_COMPONENT . '_playertype', constants::M_COMPONENT . '_enabletranscription', 'eq',0);
        $mform->disabledIf(constants::M_COMPONENT . '_playertype', constants::M_COMPONENT . '_enabletranscode', 'notchecked');


        //playertype: student
        $playertype_options = utils::fetch_options_interactivetranscript();
        $mform->addElement('select', constants::M_COMPONENT . '_playertypestudent', get_string("playertypestudent", constants::M_COMPONENT), $playertype_options);
        $mform->setDefault(constants::M_COMPONENT . '_playertypestudent', $playertypestudent);
        $mform->disabledIf(constants::M_COMPONENT . '_playertypestudent', constants::M_COMPONENT . '_enabled', 'notchecked');
        $mform->disabledIf(constants::M_COMPONENT . '_playertypestudent', constants::M_COMPONENT . '_enabletranscription', 'eq',0);
        $mform->disabledIf(constants::M_COMPONENT . '_playertypestudent', constants::M_COMPONENT . '_enabletranscode', 'notchecked');



        //audio size options
        $asize_options = utils::fetch_options_audiosize();
        $mform->addElement('select', constants::M_COMPONENT . '_audiosingledisplay', get_string("audiosingledisplay", constants::M_COMPONENT), $asize_options);
        $mform->setDefault(constants::M_COMPONENT . '_audiosingledisplay', $audiosingledisplay);
        $mform->disabledIf(constants::M_COMPONENT . '_audiosingledisplay', constants::M_COMPONENT . '_enabled', 'notchecked');

        $mform->addElement('select', constants::M_COMPONENT . '_audiolistdisplay', get_string("audiolistdisplay", constants::M_COMPONENT), $asize_options);
        $mform->setDefault(constants::M_COMPONENT . '_audiolistdisplay', $audiolistdisplay);
        $mform->disabledIf(constants::M_COMPONENT . '_audiolistdisplay', constants::M_COMPONENT . '_enabled', 'notchecked');


        //video size options
        $vsize_options = utils::fetch_options_videosize();
        $mform->addElement('select', constants::M_COMPONENT . '_videosingledisplay', get_string("videosingledisplay", constants::M_COMPONENT), $vsize_options);
        $mform->setDefault(constants::M_COMPONENT . '_videosingledisplay', $videosingledisplay);
        $mform->disabledIf(constants::M_COMPONENT . '_videosingledisplay', constants::M_COMPONENT . '_enabled', 'notchecked');


        $mform->addElement('select', constants::M_COMPONENT . '_videolistdisplay', get_string("videolistdisplay", constants::M_COMPONENT), $vsize_options);
        $mform->setDefault(constants::M_COMPONENT . '_videolistdisplay', $videolistdisplay);
        $mform->disabledIf(constants::M_COMPONENT . '_videolistdisplay', constants::M_COMPONENT . '_enabled', 'notchecked');


        //safe save settings
        $mform->addElement('advcheckbox', constants::M_COMPONENT . '_safesave', get_string("safesave", constants::M_COMPONENT));
        $mform->setDefault(constants::M_COMPONENT . '_safesave', $safesave);
        $mform->disabledIf(constants::M_COMPONENT . '_safesave', constants::M_COMPONENT . '_enabled', 'notchecked');


        //If  lower then M3.4 we show a divider to make it easier to figure where poodll ends and starts
        if($CFG->version < 2017111300) {
            $mform->addElement('static', constants::M_COMPONENT . '_divider', '',
                    get_string('divider', constants::M_COMPONENT, ''));
        }

        //If M3.4 or higher we can hide elements when we need to
        if($CFG->version >= 2017111300) {
            $mform->hideIf(constants::M_COMPONENT . '_recordertype', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_recorderskin', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_timelimit', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_expiredays', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_enabletranscode', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_enabletranscription', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_language', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_playertype', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_playertypestudent', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_audiosingledisplay', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_audiolistdisplay', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_videosingledisplay', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_videolistdisplay', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_safesave', constants::M_COMPONENT . '_enabled', 'notchecked');
        }

    }
    
    /**
     * Save the settings for Cloud Poodll submission plugin
     *
     * @param stdClass $data
     * @return bool 
     */
    public function save_settings(stdClass $data) {
        //recorder type
        $this->set_config('recordertype', $data->{constants::M_COMPONENT . '_recordertype'});
        //recorder skin
        $this->set_config('recorderskin', $data->{constants::M_COMPONENT . '_recorderskin'});
		
		//if we have a time limit, set it
		if(isset($data->{constants::M_COMPONENT . '_timelimit'})){
			$this->set_config('timelimit', $data->{constants::M_COMPONENT . '_timelimit'});
		}else{
			$this->set_config('timelimit', 0);
		}

        //safesave
        if(isset($data->{constants::M_COMPONENT . '_safesave'})) {
            $this->set_config('safesave', $data->{constants::M_COMPONENT . '_safesave'});
        }else{
            $this->set_config('safesave', 0);
        }

        //if we dont have display options set them
        $adminconfig = get_config(constants::M_COMPONENT);
        if(!isset($data->{constants::M_COMPONENT . '_audiosingledisplay'})){
            $data->{constants::M_COMPONENT . '_audiosingledisplay'}=
                    $adminconfig->displayaudioplayer_single;
        }
        if(!isset($data->{constants::M_COMPONENT . '_audiolistdisplay'})){
            $data->{constants::M_COMPONENT . '_audiolistdisplay'}=
                    $adminconfig->displayaudioplayer_list;
        }
        if(!isset($data->{constants::M_COMPONENT . '_videosingledisplay'})){
            $data->{constants::M_COMPONENT . '_videosingledisplay'} =
                    $adminconfig->displaysize_single;
        }
        if(!isset($data->{constants::M_COMPONENT . '_videolistdisplay'})){
            $data->{constants::M_COMPONENT . '_videolistdisplay'} =
                    $adminconfig->displaysize_list;
        }


        //expiredays
        $this->set_config('expiredays', $data->{constants::M_COMPONENT . '_expiredays'});

        //audio size display
        $this->set_config('audiosingledisplay', $data->{constants::M_COMPONENT . '_audiosingledisplay'});
        $this->set_config('audiolistdisplay', $data->{constants::M_COMPONENT . '_audiolistdisplay'});

        //video size display
        $this->set_config('videosingledisplay', $data->{constants::M_COMPONENT . '_videosingledisplay'});
        $this->set_config('videolistdisplay', $data->{constants::M_COMPONENT . '_videolistdisplay'});

		//language
        $this->set_config('language', $data->{constants::M_COMPONENT . '_language'});
        //trancribe
        $this->set_config('enabletranscription', $data->{constants::M_COMPONENT . '_enabletranscription'});
        //transcode
        $this->set_config('enabletranscode', $data->{constants::M_COMPONENT . '_enabletranscode'});
        //playertype
        $this->set_config('playertype', $data->{constants::M_COMPONENT . '_playertype'});

        //playertype student
        $this->set_config('playertypestudent', $data->{constants::M_COMPONENT . '_playertypestudent'});

        return true;
    }

   /**
    * Get cloudpoodll submission information from the database
    *
    * @param  int $submissionid
    * @return mixed
    */
    private function get_cloudpoodll_submission($submissionid) {
        global $DB;

        return $DB->get_record(constants::M_TABLE, array('submission'=>$submissionid));
    }

    /**
     * Add form elements cloudpoodll submissions
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
		 global $CFG, $USER, $PAGE;

		 //prepare the AMD javascript for deletesubmission and showing the recorder
        $opts = array(
            "component"=> constants::M_COMPONENT,
            "safesave"=>$this->get_config('safesave')
        );
        $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/submissionhelper", 'init', array($opts));
        $PAGE->requires->strings_for_js(array('reallydeletesubmission','clicktohide','clicktoshow'),constants::M_COMPONENT);

        //Get our renderers
        $renderer = $PAGE->get_renderer(constants::M_COMPONENT);
        $elements = array();

        $submissionid = $submission ? $submission->id : 0;


        if ($submission && get_config(constants::M_COMPONENT, 'showcurrentsubmission')) {
            $cloudpoodllsubmission = $this->get_cloudpoodll_submission($submission->id);
            $size=get_config(constants::M_COMPONENT, 'displaysize_single');

            //show the previous response in a player or whatever and a delete button
            $responses = $this->fetchResponses($submission->id,false);
            if($responses != ''){
                $deletesubmission = $renderer->fetch_delete_submission();

                //show current submission
                $currentsubmission = $renderer->prepare_current_submission($responses,$deletesubmission);

                $mform->addElement('static', 'currentsubmission',
                    get_string('currentsubmission', constants::M_COMPONENT) ,
                    $currentsubmission);
            }
        }

        //output our hidden field which has the filename
        $mform->addElement('hidden', constants::NAME_UPDATE_CONTROL, '',array('id' => constants::ID_UPDATE_CONTROL));
        $mform->setType(constants::NAME_UPDATE_CONTROL,PARAM_TEXT);

        //recorder data
        $r_options = new stdClass();
        $r_options->recordertype=$this->get_config('recordertype');
        $r_options->recorderskin=$this->get_config('recorderskin');
        $r_options->timelimit=$this->get_config('timelimit');
        $r_options->expiredays=$this->get_config('expiredays');
        $r_options->transcode=$this->get_config('enabletranscode');
        $r_options->transcribe=$this->get_config('enabletranscription');
        $r_options->language=$this->get_config('language');
        $r_options->awsregion= get_config(constants::M_COMPONENT, 'awsregion');
        $r_options->fallback= get_config(constants::M_COMPONENT, 'fallback');

        //fetch API token
        $api_user = get_config(constants::M_COMPONENT,'apiuser');
        $api_secret = get_config(constants::M_COMPONENT,'apisecret');

        //check user has entered cred
        if(empty($api_user) || empty($api_secret)){
            $message = get_string('nocredentials',constants::M_COMPONENT,
                    $CFG->wwwroot . constants::M_PLUGINSETTINGS);
            $recorderhtml = $renderer->show_problembox($message);
        }else {
            $token = utils::fetch_token($api_user, $api_secret);

            //check token authenticated and no errors in it
            $errormessage = utils::fetch_token_error($token);
            if(!empty($errormessage)){
                $recorderhtml = $renderer->show_problembox($errormessage);

            }else{
                //All good. So lets fetch recorder html
                $recorderhtml = $renderer->fetch_recorder($r_options, $token);
            }
        }

        //get recorder onscreen title
        $displayname = get_config(constants::M_COMPONENT, 'customname');
        if(empty($displayname)){$displayname = get_string('recorderdisplayname',constants::M_COMPONENT);}

        $mform->addElement('static', 'description',
                $displayname,
                $recorderhtml);

		return true;
    }

	/*
	* Fetch the player to show the submitted recording(s)
	*
	*
	*
	*/
	function fetchResponses($submissionid, $checkfordata=false){
		global $CFG, $PAGE,$OUTPUT;
		

		$responsestring = "";
        $cloudpoodllsubmission = $this->get_cloudpoodll_submission($submissionid);
        $transcript='';
        $wordcountmessage='';

        if($cloudpoodllsubmission){
            //The path to any media file we should play
            $filename= $cloudpoodllsubmission->filename;
            $rawmediapath =$cloudpoodllsubmission->filename;
            $mediapath = urlencode($rawmediapath);
            if(empty($cloudpoodllsubmission->vttdata)){
                $vttdata = false;
            }else{
                $vttdata = $cloudpoodllsubmission->vttdata;
            }

            //are we a person who can grade?
            $isgrader=false;
            if(has_capability('mod/assign:grade',$this->assignment->get_context())){
                $isgrader=true;
            }
            //is this a list page
            $islist = optional_param('action','',PARAM_TEXT)=='grading';

            //get transcript
            $transcript = $cloudpoodllsubmission->transcript;

            //wordcountmessage
            if(!empty($transcript)) {
                $wordcountmessage = get_string('numwords', constants::M_COMPONENT, count_words($transcript));
            }

        } else {
            return '';
        }

        //size params for our response players/images
        //audio is a simple 1 or 0 for display or not
        $size = $this->fetch_response_size($this->get_config('recordertype'));

        //player type
        $playertype = constants::PLAYERTYPE_DEFAULT;
        //show transcript teaser
        $teaser=false;
        if($vttdata && !$islist) {
            switch($isgrader) {
                case true:
                    $playertype = $this->get_config('playertype');
                    break;
                case false:
                    $playertype = $this->get_config('playertypestudent');
                    break;
            }
        }else if($vttdata && $islist && $isgrader){
            //show teaser
            $teaser=true;
        }


		//if this is a playback area, for teacher, show a string if no file
		if ($checkfordata  && (empty($filename) || strlen($filename)<3)){
				$responsestring .= "No submission found";
					
		//if the student is viewing and there is no file , just show an empty area
		}elseif(empty($filename) || strlen($filename)<3){
				$responsestring .= "";
				
		}else {

            //prepare our response string, which will parsed and replaced with the necessary player
            switch ($this->get_config('recordertype')) {

                case constants::REC_AUDIO:

                    $playerid = html_writer::random_id(constants::M_COMPONENT . '_');
                    $containerid = html_writer::random_id(constants::M_COMPONENT . '_');

                    //player template
                    $randomid = html_writer::random_id('cloudpoodll_');

                    //prepare props for amd and templates
                    $transcriptopts = array('component' => constants::M_COMPONENT, 'playerid' => $playerid,
                            'contextid' => $this->assignment->get_context()->id,
                            'filename' => basename($rawmediapath),
                            'lang' => $this->get_config('language'), 'size' => $size,
                            'containerid' => $containerid, 'cssprefix' => constants::M_COMPONENT . '_transcript',
                            'mediaurl' => $rawmediapath . '?cachekiller=' . $randomid, 'transcripturl' => '');
                    if(empty($transcript)){
                        $transcriptopts['notranscript']= 'true';
                    }else{
                        $transcriptopts['transcripturl']= $rawmediapath . '.vtt';
                        //this just prevents the container border showing when we not show transcript
                        if($playertype==constants::PLAYERTYPE_DEFAULT){
                            $transcriptopts['notranscript']= 'true';
                        }
                    }
                    switch ($size->key) {

                        case constants::SIZE_AUDIO_SHOW:
                            $audioplayer =
                                    $OUTPUT->render_from_template(constants::M_COMPONENT . '/audioplayerstandard', $transcriptopts);
                            //if there is no transcript just set and move on
                            if(empty($transcript)){
                                $responsestring .= $audioplayer;
                                break;
                            }

                            //if we have a transcript, figure out how to display it.
                            switch ($playertype) {
                                case constants::PLAYERTYPE_DEFAULT:
                                    //$responsestring .= format_text("<a href='$rawmediapath'>$filename</a>", FORMAT_HTML);
                                    //just use the raw audio tags response string
                                    $responsestring .= $audioplayer;
                                    break;
                                case constants::PLAYERTYPE_INTERACTIVETRANSCRIPT:

                                    $responsestring .= $audioplayer . $wordcountmessage;

                                    //prepare AMD javascript for displaying submission
                                    $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/interactivetranscript", 'init',
                                            array($transcriptopts));
                                    $PAGE->requires->strings_for_js(array('transcripttitle'), constants::M_COMPONENT);
                                    break;

                                case constants::PLAYERTYPE_STANDARDTRANSCRIPT:

                                    $responsestring .= $audioplayer . $wordcountmessage;
                                    //prepare AMD javascript for displaying submission
                                    if(!empty($transcript)) {
                                        $transcriptopts['transcripturl'] = $rawmediapath . '.txt';
                                    }
                                    $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/standardtranscript", 'init',
                                            array($transcriptopts));
                                    $PAGE->requires->strings_for_js(array('transcripttitle'), constants::M_COMPONENT);
                                    break;
                            }
                            break;

                        case constants::SIZE_AUDIO_LIGHTBOX:
                            $responsestring .=
                                    $OUTPUT->render_from_template(constants::M_COMPONENT . '/audioplayerlink', $transcriptopts);
                            break;
                        case constants::SIZE_AUDIO_LINK:
                        default:
                            $responsestring =
                                    $OUTPUT->render_from_template(constants::M_COMPONENT . '/mediafilelink', $transcriptopts);
                            break;

                    }
                    break;//end of case contants::REC_AUDIO

                case constants::REC_VIDEO:

                    $playerid = html_writer::random_id(constants::M_COMPONENT . '_');
                    $containerid = html_writer::random_id(constants::M_COMPONENT . '_');

                    //player template
                    $randomid = html_writer::random_id('cloudpoodll_');

                    //prepare props for amd and templates
                    $transcriptopts = array('component' => constants::M_COMPONENT, 'playerid' => $playerid,
                            'contextid' => $this->assignment->get_context()->id,
                            'filename' => basename($rawmediapath),
                            'lang' => $this->get_config('language'), 'size' => $size,
                            'containerid' => $containerid, 'cssprefix' => constants::M_COMPONENT . '_transcript',
                            'mediaurl' => $rawmediapath . '?cachekiller=' . $randomid, 'transcripturl' =>'');
                    if(empty($transcript)){
                        $transcriptopts['notranscript']= 'true';
                    }else{
                        $transcriptopts['transcripturl']= $rawmediapath . '.vtt';
                        //this just prevents the container border showing when we not show transcript
                        if($playertype==constants::PLAYERTYPE_DEFAULT){
                            $transcriptopts['notranscript']= 'true';
                        }
                    }

                    switch ($size->key) {
                        case constants::SIZE_LIGHTBOX:
                            $responsestring .=
                                    $OUTPUT->render_from_template(constants::M_COMPONENT . '/videoplayerlink', $transcriptopts);
                            break;

                        case constants::SIZE_LINK:
                        case constants::SIZE_NONE:
                        $responsestring .=
                                $OUTPUT->render_from_template(constants::M_COMPONENT . '/mediafilelink', $transcriptopts);
                            break;

                        default:
                            $videoplayer = $OUTPUT->render_from_template(constants::M_COMPONENT . '/videoplayerstandard',
                                    $transcriptopts);

                            //if there is no transcript just set and move on
                            if(empty($transcript)){
                                $responsestring .= $videoplayer;
                                break;
                            }

                            if ($playertype == constants::PLAYERTYPE_INTERACTIVETRANSCRIPT) {

                                $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/interactivetranscript", 'init',
                                        array($transcriptopts));
                                $PAGE->requires->strings_for_js(array('transcripttitle'), constants::M_COMPONENT);
                                $responsestring .= $videoplayer . $wordcountmessage;

                            } else if ($playertype == constants::PLAYERTYPE_STANDARDTRANSCRIPT) {
                                if(!empty($transcript)) {
                                    $transcriptopts['transcripturl'] = $rawmediapath . '.txt';
                                }
                                $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/standardtranscript", 'init',
                                        array($transcriptopts));
                                $PAGE->requires->strings_for_js(array('transcripttitle'), constants::M_COMPONENT);
                                $responsestring .= $videoplayer . $wordcountmessage;

                            } else {
                                //constants::PLAYERTYPE_DEFAULT:
                                $responsestring .= $videoplayer;
                            }

                    }//end of switch -KEY

            }//end of switch recordertype


            //if we need a teaser (of the transcript) lets add it here
            if($teaser){
			    $transcript = $cloudpoodllsubmission->transcript;
			    if($transcript) {
                    // The shortened version of the submission text.
                    $shorttext = shorten_text($transcript, 120);
                    $responsestring .= html_writer::div($shorttext . ' ' .  $wordcountmessage,
                            constants::M_COMPONENT . '_transcriptteaser');
                }

            }

		}//end of if (checkfordata ...)
		
		return $responsestring;
		
	}//end of fetchResponses


    public function	fetch_response_size($recordertype){

	        //is this a list view
            $islist = optional_param('action','',PARAM_TEXT)=='grading';
           //we might need this if user has admin but not local settings for size
           $adminconfig = get_config(constants::M_COMPONENT);

        //prepare our response string, which will parsed and replaced with the necessary player
        switch($recordertype){
            case constants::REC_VIDEO:
                $listsize = $this->get_config('videolistdisplay');
                if($listsize===false){$listsize=$adminconfig->displaysize_list;}
                $singlesize = $this->get_config('videosingledisplay');
                if($singlesize===false){$singlesize=$adminconfig->displayaudioplayer_single;}

                //build our sizes array
                $sizes=array();
                $sizes[constants::SIZE_NONE]=new stdClass();$sizes[constants::SIZE_NONE]->width=0;$sizes[constants::SIZE_NONE]->height=0;
                $sizes[constants::SIZE_LIGHTBOX]=new stdClass();$sizes[constants::SIZE_LIGHTBOX]->width=0;$sizes[constants::SIZE_LIGHTBOX]->height=0;
                $sizes[constants::SIZE_LINK]=new stdClass();$sizes[constants::SIZE_LINK]->width=0;$sizes[constants::SIZE_LINK]->height=0;
                $sizes['480']=new stdClass();$sizes['480']->width=480;$sizes['480']->height=360;
                $key = $islist ? $listsize : $singlesize ;
                if(!array_key_exists($key,$sizes)) {$key='480';}
                $size = $sizes[$key];
                $size->key = $key;


                break;
            case constants::REC_AUDIO:
            default:
                $listsize = $this->get_config('audiolistdisplay');
                if($listsize===false){$listsize=$adminconfig->displayaudioplayer_list;}
                $singlesize = $this->get_config('audiosingledisplay');
                if($singlesize===false){$singlesize=$adminconfig->displayaudioplayer_single;}

                $size=new stdClass();
                $size->key=$islist ? $listsize : $singlesize ;

        }//end of switch
        return $size;

    }
	
     /**
      * Save data to the database
      *
      * @param stdClass $submission
      * @param stdClass $data
      * @return bool
      */
     public function save(stdClass $submission, stdClass $data) {
        global $DB;

		//if filename is false, or empty, no update. possibly used changed something else on page
         //possibly they did not record ... that will be caught elsewhere
		$filename = $data->filename;
		if($filename === false || empty($filename)){return true;}

		//get expiretime of this record
        $expiredays = $this->get_config("expiredays");
		if($expiredays < 9999) {
            $fileexpiry = time() + DAYSECS * $expiredays;
        }else{
            $fileexpiry = 0;
        }

        $cloudpoodllsubmission = $this->get_cloudpoodll_submission($submission->id);
        if ($cloudpoodllsubmission) {
            if($filename=='-1'){
                //this is a flag to delete the submission
                //we actually do not delete because we get caught in is_empty and it returns us to submission page
                //with an "empty submission" error even thugh the plugin has been removed.
                // So we just do not display "-1" filename files

               // $ret = $DB->delete_records(constants::M_TABLE, array('id'=>$cloudpoodllsubmission->id));
                $cloudpoodllsubmission->filename = $filename;
                $cloudpoodllsubmission->fileexpiry = 0;
                $ret = $DB->update_record(constants::M_TABLE, $cloudpoodllsubmission);
            }else{
                $cloudpoodllsubmission->filename = $filename;
                $cloudpoodllsubmission->fileexpiry = $fileexpiry;
                $cloudpoodllsubmission->transcript = '';
                $cloudpoodllsubmission->fulltranscript = '';
                $cloudpoodllsubmission->vttdata = '';
                $ret = $DB->update_record(constants::M_TABLE, $cloudpoodllsubmission);
                $this->register_fetch_transcript_task($cloudpoodllsubmission);
            }

        } else {
            $cloudpoodllsubmission = new stdClass();
            $cloudpoodllsubmission->submission = $submission->id;
            $cloudpoodllsubmission->assignment = $this->assignment->get_instance()->id;
            $cloudpoodllsubmission->recorder = $this->get_config('recordertype');
			$cloudpoodllsubmission->filename = $filename;
            $cloudpoodllsubmission->fileexpiry = $fileexpiry;
            $ret = $DB->insert_record(constants::M_TABLE, $cloudpoodllsubmission);
            //register our adhoc task
            if($ret){
                $cloudpoodllsubmission->id = $ret;
                $this->register_fetch_transcript_task($cloudpoodllsubmission);
            }
        }
		 return $ret;

    }

    //register an adhoc task to pick up transcripts
    public function register_fetch_transcript_task($cloudpoodllsubmission){
        $fetch_task = new \assignsubmission_cloudpoodll\task\cloudpoodll_s3_adhoc();
        $fetch_task->set_component(constants::M_COMPONENT);

        $customdata = new \stdClass();
        $customdata->submission = $cloudpoodllsubmission;
        $customdata->taskcreationtime = time();

        $fetch_task->set_custom_data($customdata);
        // queue it
        \core\task\manager::queue_adhoc_task($fetch_task);
        return true;
    }


    /**
     * Display the response in student's summary, view submissions, or grading page
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true to show the submission on a new page
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        //if this is the view all submissions page, we may want to show a full player
        //or entire transcript on click, so we add a view link
        $islist = optional_param('action','',PARAM_TEXT)=='grading';
        $showviewlink = $islist;//is this a list page


		//our response, this will output a player, and optionally a portfolio export link
		return $this->fetchResponses($submission->id,false) . $this->get_p_links($submission->id) ;
		//rely on get_files from now on to generate portfolio links Justin 19/06/2014

    }

      /**
     * Produce a list of files suitable for export that represent this feedback or submission
     *
     * @param stdClass $submission The submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user=null) {
        $result = array();
        /*
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id, constants::M_COMPONENT, ASSIGNSUBMISSION_CLOUDPOODLL_FILEAREA, $submission->id, "timemodified", false);

        foreach ($files as $file) {
		
			//let NOT return splash images for videos
			if($this->get_config('recordertype')== OP_REPLYVIDEO){
				$fname = $file->get_filename();
				$fext = pathinfo($fname, PATHINFO_EXTENSION);				
				if($fext == 'jpg' || $fext == 'png'){
					continue;
				}
			}
		
            $result[$file->get_filename()] = $file;
        }
        */
        return $result;
    }

    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        $result = '';

        $cloudpoodllsubmission = $this->get_cloudpoodll_submission($submission->id);
        if ($cloudpoodllsubmission) {
            // show our responses in a player
			$result = $this->fetchResponses($submission->id,false);
        }

        return $result;
    }
	
	
	    /**
     * Produces a list of portfolio links to the file recorded byuser
     *
     * @param $submissionid this submission's id
     * @return string the portfolio export link
     */
    public function get_p_links($submissionid) {
        global $CFG, $OUTPUT, $DB;
/*
		$output ="";
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, 
					constants::M_COMPONENT,
					ASSIGNSUBMISSION_CLOUDPOODLL_FILEAREA,
					$submissionid, "id", false);
					
        if (!empty($files)) {
        	//this was nec for portfolios prior to M2.7. 
        	if(file_exists($CFG->dirroot . '/mod/assignment/locallib.php')){
            	require_once($CFG->dirroot . '/mod/assignment/locallib.php');
            }
            if ($CFG->enableportfolios) {
                require_once($CFG->libdir.'/portfoliolib.php');
            }
            
			//Add portfolio download links if appropriate
            foreach ($files as $file) {
				
				//in the case of splash images we will have two files.
				//we just want one link, and for the video file
				if($this->get_config('recordertype')== OP_REPLYVIDEO){
					$fname = $file->get_filename();
					$fext = pathinfo($fname, PATHINFO_EXTENSION);				
					if($fext == 'jpg' || $fext == 'png'){
						continue;
					}
				}
                
				
				if ($CFG->enableportfolios && has_capability('mod/assign:exportownsubmission', $this->assignment->get_context())){
					require_once($CFG->libdir . '/portfoliolib.php');
					$button = new portfolio_add_button();
                   
				   //API changes. See https://github.com/moodle/moodle/blob/master/portfolio/upgrade.txt#L6
				   if($CFG->version < 2012120300){
						$finalparam='/mod/assign/portfolio_callback.php';
					}else{
						$finalparam='mod_assign';
					}
				   
					$button->set_callback_options('assign_portfolio_caller', 
							array('cmid' => $this->assignment->get_course_module()->id,
											'component' => constants::M_COMPONENT,
											'area'=>ASSIGNSUBMISSION_CLOUDPOODLL_FILEAREA,
											'sid' => $submissionid),
							$finalparam);
                    $button->set_format_by_file($file);
                    $output .= $button->to_html(PORTFOLIO_ADD_TEXT_LINK);
                }
               
                $output .= '<br />' ;
            }
        }

        $output = '<div class="files" style="float:left;margin-left:5px;">'.$output.'</div><br clear="all" />';
        
        return $output;
*/

    }

     /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {

        return false;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The new submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // format the info for each submission plugin add_to_log
      //  $cloudpoodllsubmission = $this->get_cloudpoodll_submission($submission->id);
        $cloudpoodllloginfo = '';

        $cloudpoodllloginfo .= "submission id:" . $submission->id . " added.";

        return $cloudpoodllloginfo;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     */
    public function get_config_for_external() {
        return (array) $this->get_config();
    }

    /**
     * Copy the student's submission from a previous submission. Used when a student opts to base their resubmission
     * on the last submission.
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;
        // Copy the assignsubmission plugin record.
        $thesubmission = $this->get_cloudpoodll_submission($sourcesubmission->id);
        if ($thesubmission) {
            unset($thesubmission->id);
            $thesubmission->submission = $destsubmission->id;
            $DB->insert_record(constants::M_TABLE, $thesubmission);
        }
        return true;
    }

    /**
     * Remove a submission.
     *
     * @param stdClass $submission The submission
     * @return boolean
     */
    public function remove(stdClass $submission) {
        global $DB;

        $submissionid = $submission ? $submission->id : 0;
        if ($submissionid) {
            $DB->delete_records(constants::M_TABLE, array('submission' => $submissionid));
        }
        return true;
    }


    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // will throw exception on failure
        $DB->delete_records(constants::M_TABLE, array('assignment'=>$this->assignment->get_instance()->id));
        return true;
    }

    /**
     * No recording is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        $cpsubmission = $this->get_cloudpoodll_submission($submission->id);
        if($cpsubmission && !empty($cpsubmission->filename)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array();
    }

}


