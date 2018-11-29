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
        return get_string('cloudpoodll', constants::M_COMPONENT);
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
        $recordertype = $this->get_config('recordertype') ? $this->get_config('recordertype') :  $adminconfig->defaultrecorder;
        $recorderskin = $this->get_config('recorderskin') ? $this->get_config('recorderskin') : constants::SKIN_BMR;
		$timelimit = $this->get_config('timelimit') ? $this->get_config('timelimit') :  0;
        $expiredays = $this->get_config('expiredays') ? $this->get_config('expiredays') : $adminconfig->expiredays;

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

        //expiredays
        $this->set_config('expiredays', $data->{constants::M_COMPONENT . '_expiredays'});

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
            $opts = array(
                "component"=> constants::M_COMPONENT
            );

            $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/submissionhelper", 'init', array($opts));
            $PAGE->requires->strings_for_js(array('reallydeletesubmission'),constants::M_COMPONENT);
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
        $r_options->awsregion= get_config(constants::M_COMPONENT, 'awsregion');
        $r_options->fallback= get_config(constants::M_COMPONENT, 'fallback');
        //later we can add instance settings for these
        $r_options->language='en-US';
        $r_options->transcribe=false;
        $r_options->transcode=true;


        //fetch API token
        $api_user = get_config(constants::M_COMPONENT,'apiuser');
        $api_secret = get_config(constants::M_COMPONENT,'apisecret');
        $token = utils::fetch_token($api_user,$api_secret);

        //fetch recorder html
        $recorderhtml = $renderer->fetch_recorder($r_options,$token);
        $mform->addElement('static', 'description', '',$recorderhtml);

		return true;
    }

	/*
	* Fetch the player to show the submitted recording(s)
	*
	*
	*
	*/
	function fetchResponses($submissionid, $checkfordata=false){
		global $CFG;
		

		$responsestring = "";
        $cloudpoodllsubmission = $this->get_cloudpoodll_submission($submissionid);
        if($cloudpoodllsubmission){
            //The path to any media file we should play
            $filename= $cloudpoodllsubmission->filename;
            $rawmediapath =$cloudpoodllsubmission->filename;
            $mediapath = urlencode($rawmediapath);
        }else{
            return '';
        }

        //size params for our response players/images
        //audio is a simple 1 or 0 for display or not
        $size = $this->fetch_response_size($this->get_config('recordertype'));

		//if this is a playback area, for teacher, show a string if no file
		if ($checkfordata  && (empty($filename) || strlen($filename)<3)){
				$responsestring .= "No submission found";
					
		//if the student is viewing and there is no file , just show an empty area
		}elseif(empty($filename) || strlen($filename)<3){
				$responsestring .= "";
				
		}else{

			//prepare our response string, which will parsed and replaced with the necessary player
			switch($this->get_config('recordertype')){

                case constants::REC_AUDIO:
				    if($size) {
                        $responsestring .= format_text("<a href='$rawmediapath'>$filename</a>", FORMAT_HTML);
                    }else{
                        $responsestring=get_string('audioplaceholder',constants::M_COMPONENT);
                    }
                    break;
					
				case constants::REC_VIDEO:
						if($size->width==0){
							$responsestring=get_string('videoplaceholder',constants::M_COMPONENT);
							break;
						}
						$responsestring .= format_text("<a href='$rawmediapath?d=$size->width" . 'x' . "$size->height'>$filename</a>", FORMAT_HTML);
						break;
					
				default:
					$responsestring .= format_text("<a href='$rawmediapath'>$filename</a>", FORMAT_HTML);
					break;	
				
			}//end of switch
		}//end of if (checkfordata ...)
		
		return $responsestring;
		
	}//end of fetchResponses
	
    public function	fetch_response_size($recordertype){

	        //is this a list view
            $islist = optional_param('action','',PARAM_TEXT)=='grading';

            //build our sizes array
            $sizes=array();
            $sizes['0']=new stdClass();$sizes['0']->width=0;$sizes['0']->height=0;
            $sizes['160']=new stdClass();$sizes['160']->width=160;$sizes['160']->height=120;
            $sizes['320']=new stdClass();$sizes['320']->width=320;$sizes['320']->height=240;
            $sizes['480']=new stdClass();$sizes['480']->width=480;$sizes['480']->height=360;
            $sizes['640']=new stdClass();$sizes['640']->width=640;$sizes['640']->height=480;
            $sizes['800']=new stdClass();$sizes['800']->width=800;$sizes['800']->height=600;
            $sizes['1024']=new stdClass();$sizes['1024']->width=1024;$sizes['1024']->height=768;

            $size=$sizes[0];
            $config=get_config(constants::M_COMPONENT);

        //prepare our response string, which will parsed and replaced with the necessary player
        switch($recordertype){
            case constants::REC_VIDEO:
                $size=$islist ? $sizes[$config->displaysize_list] : $sizes[$config->displaysize_single] ;
                break;
            case constants::REC_AUDIO:
                $size=$islist ? $config->displayaudioplayer_list : $config->displayaudioplayer_single ;
                break;
            default:
                break;

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
                $ret = $DB->update_record(constants::M_TABLE, $cloudpoodllsubmission);
            }

        } else {
            $cloudpoodllsubmission = new stdClass();
            $cloudpoodllsubmission->submission = $submission->id;
            $cloudpoodllsubmission->assignment = $this->assignment->get_instance()->id;
            $cloudpoodllsubmission->recorder = $this->get_config('recordertype');
			$cloudpoodllsubmission->filename = $filename;
            $cloudpoodllsubmission->fileexpiry = $fileexpiry;
            $ret = $DB->insert_record(constants::M_TABLE, $cloudpoodllsubmission) > 0;
        }
		 return $ret;

    }
    


    /**
     * Display the list of files  in the submission status table
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true if the list of files is long
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
    	$showviewlink = false;

		//our response, this will output a player/image, and optionally a portfolio export link
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


