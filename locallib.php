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
        if (!has_capability('assignsubmission/' . constants::M_SUBPLUGIN . ':use', $context)) {
            return false;
        }
        return parent::is_configurable();
    }

    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name() {
        if (get_config(constants::M_COMPONENT, 'customname')) {
            return get_config(constants::M_COMPONENT, 'customname');
        } else {
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

        // show a divider to keep settings manageable
        $pluginname = get_string('cloudpoodll', constants::M_COMPONENT);
        $customname = get_config(constants::M_COMPONENT, 'customname');
        if (!empty($customname)) {
            $args = new stdClass();
            $args->pluginname = $pluginname;
            $args->customname = $customname;
            $divider = get_string('customdivider', constants::M_COMPONENT, $args);
        } else {
            $divider = get_string('divider', constants::M_COMPONENT, $pluginname);
        }

        // If  M3.4 or lower we show a divider to make it easier to figure where poodll ends and starts
        if ($CFG->version < 2017111300) {
            $mform->addElement('static', constants::M_COMPONENT . '_divider', '', $divider);
        }

        $recordertype = $this->get_config('recordertype') ? $this->get_config('recordertype') : $adminconfig->defaultrecorder;
        $recorderskin = $this->get_config('recorderskin') ? $this->get_config('recorderskin') : constants::SKIN_123;
        $timelimit = $this->get_config('timelimit') ? $this->get_config('timelimit') : 0;
        $safesave = $this->get_config('safesave') ? $this->get_config('safesave') : 0;
        $expiredays = $this->get_config('expiredays') ? $this->get_config('expiredays') : $adminconfig->expiredays;
        $language = $this->get_config('language') ? $this->get_config('language') : $adminconfig->language;
        $playertype = $this->get_config('playertype') ? $this->get_config('playertype') : $adminconfig->defaultplayertype;
        $playertypestudent = $this->get_config('playertypestudent') ? $this->get_config('playertypestudent') : $adminconfig->defaultplayertypestudent;

        // in this case false means unset
        $enabletranscription = $this->get_config('enabletranscription') !== false ? $this->get_config('enabletranscription') : $adminconfig->enabletranscription;
        $audiolistdisplay = $this->get_config('audiolistdisplay') !== false ? $this->get_config('audiolistdisplay') : $adminconfig->displayaudioplayer_list;
        $audiosingledisplay = $this->get_config('audiosingledisplay') !== false ? $this->get_config('audiosingledisplay') : $adminconfig->displayaudioplayer_single;
        $videolistdisplay = $this->get_config('videolistdisplay') !== false ? $this->get_config('videolistdisplay') : $adminconfig->displaysize_list;
        $videosingledisplay = $this->get_config('videosingledisplay') !== false ? $this->get_config('videosingledisplay') : $adminconfig->displaysize_single;
        $boardsize = $this->get_config('boardsize') !== false ? $this->get_config('boardsize') : '600x400';
        $secureplayback = $this->get_config('secureplayback') !== false ? $this->get_config('secureplayback') : $adminconfig->secureplayback;
        $noaudiofilters = $this->get_config('noaudiofilters') !== false ? $this->get_config('noaudiofilters') : $adminconfig->noaudiofilters;
        // We made transcoding compulsory: Justin 20210428
        // $enabletranscode = $this->get_config('enabletranscode')!==false ? $this->get_config('enabletranscode') : $adminconfig->enabletranscode;

        // to enable/disable recorders from the admin
        $recoptions = [];
        if ($adminconfig->enableaudio) {
            $recoptions[constants::REC_AUDIO] = get_string("recorderaudio", constants::M_COMPONENT);
        }

        if ($adminconfig->enablevideo) {
            $recoptions[constants::REC_VIDEO] = get_string("recordervideo", constants::M_COMPONENT);
        }

        // in the case that neither enableaudio nor enablevideo are true, then the user messed up, or more likely the install/update step missed the setting
        // so we enable both. We might remove this after the Dec 2021 update where the setting was introduced has been around for a while
        if (count($recoptions) == 0) {
            $recoptions = utils::fetch_options_recorders();
        }
        // Always add whiteboard to choices.
        if (!isset($recoptions[constants::REC_WHITEBOARD])) {
            $recoptions[constants::REC_WHITEBOARD] = get_string("recorderwhiteboard", constants::M_COMPONENT);
        }
        $mform->addElement('select', constants::M_COMPONENT . '_recordertype', get_string("recordertype", constants::M_COMPONENT), $recoptions);
        $mform->setDefault(constants::M_COMPONENT . '_recordertype', $recordertype);
        $mform->disabledIf(constants::M_COMPONENT . '_recordertype', constants::M_COMPONENT . '_enabled', 'notchecked');

        $skinoptions = utils::fetch_options_skins();
        $mform->addElement('select', constants::M_COMPONENT . '_recorderskin', get_string("recorderskin", constants::M_COMPONENT), $skinoptions);
        $mform->setDefault(constants::M_COMPONENT . '_recorderskin', $recorderskin);
        $mform->disabledIf(constants::M_COMPONENT . '_recorderskin', constants::M_COMPONENT . '_enabled', 'notchecked');

        // Add a place to set a maximum recording time.
        $mform->addElement('duration', constants::M_COMPONENT . '_timelimit', get_string('timelimit', constants::M_COMPONENT));
        $mform->setDefault(constants::M_COMPONENT . '_timelimit', $timelimit);
        $mform->disabledIf(constants::M_COMPONENT . '_timelimit', constants::M_COMPONENT . '_enabled', 'notchecked');

        // Add expire days
        $expireoptions = utils::get_expiredays_options();
        $mform->addElement('select', constants::M_COMPONENT . '_expiredays', get_string("expiredays", constants::M_COMPONENT), $expireoptions);
        $mform->setDefault(constants::M_COMPONENT . '_expiredays', $expiredays);
        $mform->disabledIf(constants::M_COMPONENT . '_expiredays', constants::M_COMPONENT . '_enabled', 'notchecked');

        // Transcode settings.
        // We force transcoding to be always on: Justin 20210428
        $mform->addElement('hidden', constants::M_COMPONENT . '_enabletranscode', 1);
        $mform->setType(constants::M_COMPONENT . '_enabletranscode', PARAM_INT);
        /*
         $mform->addElement('advcheckbox', constants::M_COMPONENT . '_enabletranscode', get_string("enabletranscode", constants::M_COMPONENT));
         $mform->setDefault(constants::M_COMPONENT . '_enabletranscode', $enabletranscode);
         $mform->disabledIf(constants::M_COMPONENT . '_enabletranscode', constants::M_COMPONENT . '_enabled', 'notchecked');
         */

        // Transcription settings.
        // Here add googlecloudspeech or amazontranscrobe options.
        $transcriberoptions = utils::get_transcriber_options();
        $mform->addElement('select', constants::M_COMPONENT . '_enabletranscription', get_string("enabletranscription", constants::M_COMPONENT), $transcriberoptions);
        $mform->setDefault(constants::M_COMPONENT . '_enabletranscription', $enabletranscription);
        $mform->disabledIf(constants::M_COMPONENT . '_enabletranscription', constants::M_COMPONENT . '_enabled', 'notchecked');
        // We force transcoding to be always on: Justin 20210428
        // $mform->disabledIf(constants::M_COMPONENT . '_enabletranscription', constants::M_COMPONENT . '_enabletranscode', 'notchecked');

        // Language options.
        $langoptions = utils::get_lang_options();
        $mform->addElement('select', constants::M_COMPONENT . '_language', get_string("language", constants::M_COMPONENT), $langoptions);
        $mform->setDefault(constants::M_COMPONENT . '_language', $language);
        $mform->disabledIf(constants::M_COMPONENT . '_language', constants::M_COMPONENT . '_enabled', 'notchecked');
        $mform->disabledIf(constants::M_COMPONENT . '_language', constants::M_COMPONENT . '_enabletranscription', 'eq', 0);
        // We force transcoding to be always on: Justin 20210428
        // $mform->disabledIf(constants::M_COMPONENT . '_language', constants::M_COMPONENT . '_enabletranscode', 'notchecked');

        // playertype : teacher
        $playertypeoptions = utils::fetch_options_interactivetranscript();
        $mform->addElement('select', constants::M_COMPONENT . '_playertype', get_string("playertype", constants::M_COMPONENT), $playertypeoptions);
        $mform->setDefault(constants::M_COMPONENT . '_playertype', $playertype);
        $mform->disabledIf(constants::M_COMPONENT . '_playertype', constants::M_COMPONENT . '_enabled', 'notchecked');
        $mform->disabledIf(constants::M_COMPONENT . '_playertype', constants::M_COMPONENT . '_enabletranscription', 'eq', 0);
        // we force transcoding to be always on: Justin 20210428
        // $mform->disabledIf(constants::M_COMPONENT . '_playertype', constants::M_COMPONENT . '_enabletranscode', 'notchecked');

        // playertype: student
        $playertypeoptions = utils::fetch_options_interactivetranscript();
        $mform->addElement('select', constants::M_COMPONENT . '_playertypestudent', get_string("playertypestudent", constants::M_COMPONENT), $playertypeoptions);
        $mform->setDefault(constants::M_COMPONENT . '_playertypestudent', $playertypestudent);
        $mform->disabledIf(constants::M_COMPONENT . '_playertypestudent', constants::M_COMPONENT . '_enabled', 'notchecked');
        $mform->disabledIf(constants::M_COMPONENT . '_playertypestudent', constants::M_COMPONENT . '_enabletranscription', 'eq', 0);
        // We force transcoding to be always on: Justin 20210428

        // Audio size options.
        $asizeoptions = utils::fetch_options_audiosize();
        $mform->addElement('select', constants::M_COMPONENT . '_audiosingledisplay', get_string("audiosingledisplay", constants::M_COMPONENT), $asizeoptions);
        $mform->setDefault(constants::M_COMPONENT . '_audiosingledisplay', $audiosingledisplay);
        $mform->disabledIf(constants::M_COMPONENT . '_audiosingledisplay', constants::M_COMPONENT . '_enabled', 'notchecked');

        $mform->addElement('select', constants::M_COMPONENT . '_audiolistdisplay', get_string("audiolistdisplay", constants::M_COMPONENT), $asizeoptions);
        $mform->setDefault(constants::M_COMPONENT . '_audiolistdisplay', $audiolistdisplay);
        $mform->disabledIf(constants::M_COMPONENT . '_audiolistdisplay', constants::M_COMPONENT . '_enabled', 'notchecked');

        // Video size options.
        $vsizeoptions = utils::fetch_options_videosize();
        $mform->addElement('select', constants::M_COMPONENT . '_videosingledisplay', get_string("videosingledisplay", constants::M_COMPONENT), $vsizeoptions);
        $mform->setDefault(constants::M_COMPONENT . '_videosingledisplay', $videosingledisplay);
        $mform->disabledIf(constants::M_COMPONENT . '_videosingledisplay', constants::M_COMPONENT . '_enabled', 'notchecked');

        $mform->addElement('select', constants::M_COMPONENT . '_videolistdisplay', get_string("videolistdisplay", constants::M_COMPONENT), $vsizeoptions);
        $mform->setDefault(constants::M_COMPONENT . '_videolistdisplay', $videolistdisplay);
        $mform->disabledIf(constants::M_COMPONENT . '_videolistdisplay', constants::M_COMPONENT . '_enabled', 'notchecked');

        // Whiteboard size options.
        $wbsizeoptions = utils::fetch_options_boardsizes();
        $mform->addElement('select', constants::M_COMPONENT . '_boardsize', get_string("boardsize", constants::M_COMPONENT), $wbsizeoptions);
        $mform->setDefault(constants::M_COMPONENT . '_boardsize', $boardsize);
        $mform->disabledIf(constants::M_COMPONENT . '_boardsize', constants::M_COMPONENT . '_enabled', 'notchecked');
        $mform->disabledIf(constants::M_COMPONENT . '_boardsize', constants::M_COMPONENT . '_recordertype', 'neq', constants::REC_WHITEBOARD);

        // Backimage.
        $itemid = 0;
        $draftitemid = file_get_submitted_draft_itemid('backimage_filemanager');
        $acontext = $this->assignment->get_context();
        if ($acontext) {
            $contextid = $acontext->id;
        } else {
            $contextid = null;
        }
        file_prepare_draft_area(
            $draftitemid,
            $contextid,
            constants::M_COMPONENT,
            constants::M_WB_FILEAREA,
            $itemid,
            ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]
        );
        $mform->addElement(
            'filemanager',
            'backimage_filemanager',
            get_string('backimage', constants::M_COMPONENT),
            null,
            ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]
        );
        $mform->setDefault('backimage_filemanager', $draftitemid);
        $mform->disabledIf('backimage_filemanager', constants::M_COMPONENT . '_enabled', 'notchecked');
        $mform->disabledIf('backimage_filemanager', constants::M_COMPONENT . '_recordertype', 'neq', constants::REC_WHITEBOARD);

        // Safe save settings.
        $mform->addElement('advcheckbox', constants::M_COMPONENT . '_safesave', get_string("safesave", constants::M_COMPONENT));
        $mform->setDefault(constants::M_COMPONENT . '_safesave', $safesave);
        $mform->disabledIf(constants::M_COMPONENT . '_safesave', constants::M_COMPONENT . '_enabled', 'notchecked');

        // Secure playback settings.
        $mform->addElement(
            'advcheckbox',
            constants::M_COMPONENT . '_secureplayback',
            get_string("enablesecureplayback", constants::M_COMPONENT) . ' - ' . get_string("enablesecureplayback_details", constants::M_COMPONENT)
        );
        $mform->setDefault(constants::M_COMPONENT . '_secureplayback', $secureplayback);
        $mform->disabledIf(constants::M_COMPONENT . '_secureplayback', constants::M_COMPONENT . '_enabled', 'notchecked');

        $mform->addElement('advcheckbox', constants::M_COMPONENT . '_noaudiofilters', get_string("noaudiofilters_desc", constants::M_COMPONENT));
        $mform->setDefault(constants::M_COMPONENT . '_noaudiofilters', $noaudiofilters);
        $mform->disabledIf(constants::M_COMPONENT . '_noaudiofilters', constants::M_COMPONENT . '_enabled', 'notchecked');

        // If  lower then M3.4 we show a divider to make it easier to figure where poodll ends and starts
        if ($CFG->version < 2017111300) {
            $mform->addElement(
                'static',
                constants::M_COMPONENT . '_divider',
                '',
                get_string('divider', constants::M_COMPONENT, '')
            );
        }

        // If M3.4 or higher we can hide elements when we need to
        if ($CFG->version >= 2017111300) {
            $mform->hideIf(constants::M_COMPONENT . '_recordertype', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_recorderskin', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_timelimit', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_expiredays', constants::M_COMPONENT . '_enabled', 'notchecked');
            // we force transcoding to be always on: Justin 20210428
            // $mform->hideIf(constants::M_COMPONENT . '_enabletranscode', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_enabletranscription', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_language', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_playertype', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_playertypestudent', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_audiosingledisplay', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_audiolistdisplay', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_videosingledisplay', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_videolistdisplay', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_boardsize', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf('backimage_filemanager', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_safesave', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_secureplayback', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_noaudiofilters', constants::M_COMPONENT . '_enabled', 'notchecked');
        }

    }

    /**
     * Save the settings for Cloud Poodll submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        // recorder type
        $this->set_config('recordertype', $data->{ constants::M_COMPONENT . '_recordertype'});
        // recorder skin
        $this->set_config('recorderskin', $data->{ constants::M_COMPONENT . '_recorderskin'});

        // if we have a time limit, set it
        if (isset($data->{ constants::M_COMPONENT . '_timelimit'})) {
            $this->set_config('timelimit', $data->{ constants::M_COMPONENT . '_timelimit'});
        } else {
            $this->set_config('timelimit', 0);
        }

        // safesave
        if (isset($data->{ constants::M_COMPONENT . '_safesave'})) {
            $this->set_config('safesave', $data->{ constants::M_COMPONENT . '_safesave'});
        } else {
            $this->set_config('safesave', 0);
        }

        // secureplayback
        if (isset($data->{ constants::M_COMPONENT . '_secureplayback'})) {
            $this->set_config('secureplayback', $data->{ constants::M_COMPONENT . '_secureplayback'});
        } else {
            $this->set_config('secureplayback', 0);
        }

        // no audio filters
        if (isset($data->{ constants::M_COMPONENT . '_noaudiofilters'})) {
            $this->set_config('noaudiofilters', $data->{ constants::M_COMPONENT . '_noaudiofilters'});
        } else {
            $this->set_config('noaudiofilters', 0);
        }

        // if we dont have display options set them
        $adminconfig = get_config(constants::M_COMPONENT);
        if (!isset($data->{ constants::M_COMPONENT . '_audiosingledisplay'})) {
            $data->{ constants::M_COMPONENT . '_audiosingledisplay'} =
                $adminconfig->displayaudioplayer_single;
        }
        if (!isset($data->{ constants::M_COMPONENT . '_audiolistdisplay'})) {
            $data->{ constants::M_COMPONENT . '_audiolistdisplay'} =
                $adminconfig->displayaudioplayer_list;
        }
        if (!isset($data->{ constants::M_COMPONENT . '_videosingledisplay'})) {
            $data->{ constants::M_COMPONENT . '_videosingledisplay'} =
                $adminconfig->displaysize_single;
        }
        if (!isset($data->{ constants::M_COMPONENT . '_videolistdisplay'})) {
            $data->{ constants::M_COMPONENT . '_videolistdisplay'} =
                $adminconfig->displaysize_list;
        }

        // expiredays
        $this->set_config('expiredays', $data->{ constants::M_COMPONENT . '_expiredays'});

        // audio size display
        $this->set_config('audiosingledisplay', $data->{ constants::M_COMPONENT . '_audiosingledisplay'});
        $this->set_config('audiolistdisplay', $data->{ constants::M_COMPONENT . '_audiolistdisplay'});

        // video size display
        $this->set_config('videosingledisplay', $data->{ constants::M_COMPONENT . '_videosingledisplay'});
        $this->set_config('videolistdisplay', $data->{ constants::M_COMPONENT . '_videolistdisplay'});

        // whiteboard size display
        $this->set_config('boardsize', $data->{ constants::M_COMPONENT . '_boardsize'});

        // language
        $this->set_config('language', $data->{ constants::M_COMPONENT . '_language'});
        // trancribe
        $this->set_config('enabletranscription', $data->{ constants::M_COMPONENT . '_enabletranscription'});
        // transcode
        $this->set_config('enabletranscode', $data->{ constants::M_COMPONENT . '_enabletranscode'});
        // No audio filters
        $this->set_config('noaudiofilters', $data->{ constants::M_COMPONENT . '_noaudiofilters'});
        // playertype
        $this->set_config('playertype', $data->{ constants::M_COMPONENT . '_playertype'});

        // playertype student
        $this->set_config('playertypestudent', $data->{ constants::M_COMPONENT . '_playertypestudent'});

        // save backimage
        if (isset($data->backimage_filemanager)) {
            file_save_draft_area_files(
                $data->backimage_filemanager,
                $this->assignment->get_context()->id,
                constants::M_COMPONENT,
                constants::M_WB_FILEAREA,
                0,
                ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]
            );
        }

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

        return $DB->get_record(constants::M_TABLE, ['submission' => $submissionid]);
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

        // Prepare the AMD javascript for deletesubmission and showing the recorder.
        $opts = [
            "component" => constants::M_COMPONENT,
            "safesave" => $this->get_config('safesave'),
            "recordertype" => $this->get_config('recordertype'),
        ];
        $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/submissionhelper", 'init', [$opts]);
        $PAGE->requires->strings_for_js(['reallydeletesubmission', 'clicktohide', 'clicktoshow'], constants::M_COMPONENT);

        // Get our renderers.
        $renderer = $PAGE->get_renderer(constants::M_COMPONENT);
        $elements = [];

        $submissionid = $submission ? $submission->id : 0;
        $draftitemid = file_get_submitted_draft_itemid(constants::ID_UPDATE_CONTROL);
        file_prepare_draft_area($draftitemid, $this->assignment->get_context()->id, constants::M_COMPONENT, constants::M_FILEAREA, $submissionid);

        if ($submission && get_config(constants::M_COMPONENT, 'showcurrentsubmission')) {
            $cloudpoodllsubmission = $this->get_cloudpoodll_submission($submission->id);
            $size = get_config(constants::M_COMPONENT, 'displaysize_single');

            // show the previous response in a player or whatever and a delete button
            $responses = $this->fetchResponses($submission->id, false);
            if ($responses != '') {
                $deletesubmission = $renderer->fetch_delete_submission();

                // show current submission
                $currentsubmission = $renderer->prepare_current_submission($responses, $deletesubmission);

                $mform->addElement(
                    'static',
                    'currentsubmission',
                    get_string('currentsubmission', constants::M_COMPONENT),
                    $currentsubmission
                );
            }
        }

        // output our hidden field which has the filename
        $mform->addElement('hidden', constants::NAME_UPDATE_CONTROL, '', ['id' => constants::ID_UPDATE_CONTROL]);
        $mform->setType(constants::NAME_UPDATE_CONTROL, PARAM_TEXT);

        $mform->addElement('hidden', 'wb_draftitemid', $draftitemid);
        $mform->setType('wb_draftitemid', PARAM_INT);
        $mform->setDefault('wb_draftitemid', $draftitemid);

        // Whiteboard vector data from existing submission
        $vdata = "";
        if ($this->get_config('recordertype') == constants::REC_WHITEBOARD) {
            $mform->addElement('hidden', constants::M_VECTORCONTROL, '', ['id' => constants::M_VECTORCONTROL]);
            $mform->setType(constants::M_VECTORCONTROL, PARAM_RAW);
            if (($submission && $submission->id)) {
                $cloudpoodllsubmission = $this->get_cloudpoodll_submission($submission->id);
                if (!empty($cloudpoodllsubmission->vectordata)) {
                    $vdata = $cloudpoodllsubmission->vectordata;
                    $mform->setDefault(constants::M_VECTORCONTROL, $vdata);
                }
            }
        }

        // recorder data
        $recoptions = new stdClass();
        $recoptions->recordertype = $this->get_config('recordertype');
        $recoptions->recorderskin = $this->get_config('recorderskin');
        $recoptions->timelimit = $this->get_config('timelimit');
        $recoptions->expiredays = $this->get_config('expiredays');
        // we made this compulsory Justin 20210428
        $recoptions->transcode = 1; // $this->get_config('enabletranscode');
        $recoptions->transcribe = $this->get_config('enabletranscription');
        $recoptions->language = $this->get_config('language');
        $recoptions->awsregion = get_config(constants::M_COMPONENT, 'awsregion');
        $recoptions->fallback = get_config(constants::M_COMPONENT, 'fallback');
        // No audio filters
        // if we are shadowing or a music class, or something we can disable noise supression and echo cancellation
        $recoptions->shadowing = $this->get_config('noaudiofilters') ? 1 : 0;
        $recoptions->boardsize = $this->get_config('boardsize');
        $recoptions->draftitemid = $draftitemid;
        $recoptions->vdata = $vdata;

        // Backimage URL.
        $backimageurl = '';
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, constants::M_COMPONENT, constants::M_WB_FILEAREA, 0, 'id', false);
        if ($files) {
            $file = reset($files);
            $backimageurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
        }
        $recoptions->backimage = $backimageurl;

        // Fetch API token.
        $apiuser = get_config(constants::M_COMPONENT, 'apiuser');
        $apisecret = get_config(constants::M_COMPONENT, 'apisecret');

        // Check user has entered credentials.
        if (empty($apiuser) || empty($apisecret)) {
            $message = get_string(
                'nocredentials',
                constants::M_COMPONENT,
                $CFG->wwwroot . constants::M_PLUGINSETTINGS
            );
            $recorderhtml = $renderer->show_problembox($message);
        } else {
            $token = utils::fetch_token($apiuser, $apisecret);

            // Check token authenticated and no errors in it.
            $errormessage = utils::fetch_token_error($token);
            if (!empty($errormessage)) {
                $recorderhtml = $renderer->show_problembox($errormessage);

            } else {
                // All good. So lets fetch recorder html
                $recorderhtml = $renderer->fetch_recorder($recoptions, $token);
            }
        }

        // Get recorder onscreen title.
        $displayname = get_config(constants::M_COMPONENT, 'customname');
        if (empty($displayname)) {
            $displayname = get_string('recorderdisplayname', constants::M_COMPONENT);
        }

        $mform->addElement(
            'static',
            'description',
            $displayname,
            $recorderhtml,
            ['class' => 'w-100'],
            ['class' => 'w-100']
        );

        return true;
    }

    /*
     * Fetch the player to show the submitted recording(s)
     *
     *
     *
     */
    function fetchresponses($submissionid, $checkfordata = false) {
        global $CFG, $PAGE, $OUTPUT;

        $responsestring = "";
        $cloudpoodllsubmission = $this->get_cloudpoodll_submission($submissionid);
        $transcript = '';
        $wordcountmessage = '';
        // Is this a list page?
        $islist = $this->is_list();

        // Do we have a submission.
        if (!$cloudpoodllsubmission) {
            return "";
        }

        // The path to any media file we should play.
        $filename = $cloudpoodllsubmission->filename;
        $rawmediapath = $cloudpoodllsubmission->filename;
        $mediapath = urlencode($rawmediapath);
        if (empty($cloudpoodllsubmission->vttdata)) {
            $vttdata = false;
        } else {
            $vttdata = $cloudpoodllsubmission->vttdata;
        }

        // Are we a person who can grade?
        $isgrader = false;
        if (has_capability('mod/assign:grade', $this->assignment->get_context())) {
            $isgrader = true;
        }

        // Get transcript.
        $transcript = $cloudpoodllsubmission->transcript;

        // Wordcountmessage.
        if (!empty($transcript)) {
            $wordcountmessage = get_string('numwords', constants::M_COMPONENT, count_words($transcript));
        }

        // For right to left languages we want to add the RTL direction and right justify.
        switch ($this->get_config('language')) {
            case constants::LANG_ARAE:
            case constants::LANG_ARSA:
            case constants::LANG_FAIR:
            case constants::LANG_HEIL:
                $rtl = constants::M_COMPONENT . '_rtl';
                break;
            default:
                $rtl = '';
        }

        // Size params for our response players/images.
        // Audio is a simple 1 or 0 for display or not.
        $size = $this->fetch_response_size($this->get_config('recordertype'));

        // Player type.
        $playertype = constants::PLAYERTYPE_DEFAULT;
        // Show transcript teaser.
        $teaser = false;
        if ($vttdata && !$islist) {
            switch ($isgrader) {
                case true:
                    $playertype = $this->get_config('playertype');
                    break;
                case false:
                    $playertype = $this->get_config('playertypestudent');
                    break;
            }
        } else if ($vttdata && $islist && $isgrader) {
            // Show teaser.
            $teaser = true;
        }

        // If this is a playback area, for teacher, show a string if no file.
        if ($checkfordata && (empty($filename) || strlen($filename) < 3)) {
            $responsestring .= "No submission found";

            // If the student is viewing and there is no file , just show an empty area.
        } else if (empty($filename) || strlen($filename) < 3) {
            $responsestring .= "";

        } else {

            // Prepare our response string, which will parsed and replaced with the necessary player.
            switch ($this->get_config('recordertype')) {

                case constants::REC_AUDIO:

                    $playerid = html_writer::random_id(constants::M_COMPONENT . '_');
                    $containerid = html_writer::random_id(constants::M_COMPONENT . '_');

                    // Player template.
                    $randomid = html_writer::random_id('cloudpoodll_');

                    // Prepare props for amd and templates.
                    $playeropts = [
                        'component' => constants::M_COMPONENT,
                        'playerid' => $playerid,
                        'contextid' => $this->assignment->get_context()->id,
                        'assignmentid' => $this->assignment->get_course_module()->instance,
                        'filename' => basename($rawmediapath),
                        'rtl' => $rtl,
                        'lang' => $this->get_config('language'),
                        'size' => $size,
                        'containerid' => $containerid,
                        'cssprefix' => constants::M_COMPONENT . '_transcript',
                        'mediaurl' => $rawmediapath,
                        'transcripturl' => '',
                    ];
                    if (empty($transcript)) {
                        $playeropts['notranscript'] = 'true';
                    } else {
                        $playeropts['transcripturl'] = $rawmediapath . '.vtt';
                        // This just prevents the container border showing when we not show transcript.
                        if ($playertype == constants::PLAYERTYPE_DEFAULT) {
                            $playeropts['notranscript'] = 'true';
                        }
                    }
                    if ($islist) {
                        $playeropts['islist'] = true;
                    }

                    // If we are using secure URLs then we need to secure the mediaurl and transcripturl.
                    if ($this->get_config('secureplayback')) {
                        $playeropts['mediaurl'] = utils::fetch_secure_url($playeropts['mediaurl']);
                        if (!empty($playeropts['transcripturl'])) {
                            $playeropts['transcripturl'] = utils::fetch_secure_url($playeropts['transcripturl']);
                        }
                    }

                    switch ($size->key) {

                        case constants::SIZE_AUDIO_SHOW:
                            $audioplayer =
                                $OUTPUT->render_from_template(constants::M_COMPONENT . '/audioplayerstandard', $playeropts);
                            // If there is no transcript just set and move on.
                            if (empty($transcript)) {
                                $responsestring .= $audioplayer;
                                break;
                            }

                            // if we have a transcript, figure out how to display it.
                            switch ($playertype) {
                                case constants::PLAYERTYPE_DEFAULT:
                                    $responsestring .= $audioplayer;
                                    break;
                                case constants::PLAYERTYPE_INTERACTIVETRANSCRIPT:

                                    $responsestring .= $audioplayer . $wordcountmessage;

                                    // Prepare AMD javascript for displaying submission.
                                    $PAGE->requires->js_call_amd(
                                        constants::M_COMPONENT . "/interactivetranscript",
                                        'init',
                                        [$playeropts]
                                    );
                                    $PAGE->requires->strings_for_js(['transcripttitle'], constants::M_COMPONENT);
                                    break;

                                case constants::PLAYERTYPE_STANDARDTRANSCRIPT:

                                    $responsestring .= $audioplayer . $wordcountmessage;
                                    // Prepare AMD javascript for displaying submission.
                                    if (!empty($transcript)) {
                                        $playeropts['transcripturl'] = $rawmediapath . '.txt';
                                    }
                                    $PAGE->requires->js_call_amd(
                                        constants::M_COMPONENT . "/standardtranscript",
                                        'init',
                                        [$playeropts]
                                    );
                                    $PAGE->requires->strings_for_js(['transcripttitle'], constants::M_COMPONENT);
                                    break;
                            }
                            break;

                        case constants::SIZE_AUDIO_LIGHTBOX:
                            $responsestring .=
                                $OUTPUT->render_from_template(constants::M_COMPONENT . '/audioplayerlink', $playeropts);
                            break;
                        case constants::SIZE_AUDIO_LINK:
                        default:
                            $responsestring =
                                $OUTPUT->render_from_template(constants::M_COMPONENT . '/mediafilelink', $playeropts);
                            break;

                    }
                    break;

                case constants::REC_VIDEO:

                    $playerid = html_writer::random_id(constants::M_COMPONENT . '_');
                    $containerid = html_writer::random_id(constants::M_COMPONENT . '_');

                    // Player template.
                    $randomid = html_writer::random_id('cloudpoodll_');

                    // Prepare props for amd and templates.
                    $playeropts = [
                        'component' => constants::M_COMPONENT,
                        'playerid' => $playerid,
                        'contextid' => $this->assignment->get_context()->id,
                        'assignmentid' => $this->assignment->get_course_module()->instance,
                        'filename' => basename($rawmediapath),
                        'lang' => $this->get_config('language'),
                        'rtl' => $rtl,
                        'size' => $size,
                        'containerid' => $containerid,
                        'cssprefix' => constants::M_COMPONENT . '_transcript',
                        'mediaurl' => $rawmediapath,
                        'transcripturl' => '',
                    ];
                    if (empty($transcript)) {
                        $playeropts['notranscript'] = 'true';
                    } else {
                        $playeropts['transcripturl'] = $rawmediapath . '.vtt';
                        // This just prevents the container border showing when we not show transcript.
                        if ($playertype == constants::PLAYERTYPE_DEFAULT) {
                            $playeropts['notranscript'] = 'true';
                        }
                    }
                    if ($islist) {
                        $playeropts['islist'] = true;
                    }

                    // If we are using secure URLs then we need to secure the mediaurl and transcripturl.
                    if ($this->get_config('secureplayback')) {
                        $playeropts['mediaurl'] = utils::fetch_secure_url($playeropts['mediaurl']);
                        if (!empty($playeropts['transcripturl'])) {
                            $playeropts['transcripturl'] = utils::fetch_secure_url($playeropts['transcripturl']);
                        }
                    }

                    switch ($size->key) {
                        case constants::SIZE_LIGHTBOX:
                            $responsestring .=
                                $OUTPUT->render_from_template(constants::M_COMPONENT . '/videoplayerlink', $playeropts);
                            break;

                        case constants::SIZE_LINK:
                        case constants::SIZE_NONE:
                            $responsestring .=
                                $OUTPUT->render_from_template(constants::M_COMPONENT . '/mediafilelink', $playeropts);
                            break;

                        default:
                            $videoplayer = $OUTPUT->render_from_template(
                                constants::M_COMPONENT . '/videoplayerstandard',
                                $playeropts
                            );

                            // If there is no transcript just set and move on.
                            if (empty($transcript)) {
                                $responsestring .= $videoplayer;
                                break;
                            }

                            if ($playertype == constants::PLAYERTYPE_INTERACTIVETRANSCRIPT) {

                                $PAGE->requires->js_call_amd(
                                    constants::M_COMPONENT . "/interactivetranscript",
                                    'init',
                                    [$playeropts]
                                );
                                $PAGE->requires->strings_for_js(['transcripttitle'], constants::M_COMPONENT);
                                $responsestring .= $videoplayer . $wordcountmessage;

                            } else if ($playertype == constants::PLAYERTYPE_STANDARDTRANSCRIPT) {
                                if (!empty($transcript)) {
                                    $playeropts['transcripturl'] = $rawmediapath . '.txt';
                                }
                                $PAGE->requires->js_call_amd(
                                    constants::M_COMPONENT . "/standardtranscript",
                                    'init',
                                    [$playeropts]
                                );
                                $PAGE->requires->strings_for_js(['transcripttitle'], constants::M_COMPONENT);
                                $responsestring .= $videoplayer . $wordcountmessage;

                            } else {
                                $responsestring .= $videoplayer;
                            }

                            break;
                    } //end of switch -KEY
                    break;

                case constants::REC_WHITEBOARD:
                    $playerid = html_writer::random_id(constants::M_COMPONENT . '_');
                    $containerid = html_writer::random_id(constants::M_COMPONENT . '_');
                    $imgmediapath = $CFG->wwwroot . '/pluginfile.php/' .
                        $this->assignment->get_context()->id . '/' . constants::M_COMPONENT . '/'
                        . constants::M_FILEAREA . '/' . $submissionid . '/' . $filename;

                    $playeropts = [
                        'component' => constants::M_COMPONENT,
                        'playerid' => $playerid,
                        'contextid' => $this->assignment->get_context()->id,
                        'assignmentid' => $this->assignment->get_course_module()->instance,
                        'filename' => $filename,
                        'size' => $size,
                        'containerid' => $containerid,
                        'mediaurl' => $imgmediapath,
                        'vectordata' => $cloudpoodllsubmission->vectordata,
                    ];
                    $responsestring .= $OUTPUT->render_from_template(constants::M_COMPONENT . '/whiteboardplayer', $playeropts);
                    break;
            } // End of switch recordertype.

            // If we need a teaser (of the transcript) lets add it here.
            if ($teaser) {
                $transcript = $cloudpoodllsubmission->transcript;
                if ($transcript) {
                    // The shortened version of the submission text.
                    $shorttext = shorten_text($transcript, 120);
                    $responsestring .= html_writer::div(
                        $shorttext . ' ' . $wordcountmessage,
                        constants::M_COMPONENT . '_transcriptteaser'
                    );
                }

            }

        } // End of if (checkfordata ...).

        return $responsestring;

    } // End of fetchResponses.


    public function fetch_response_size($recordertype) {

        // is this a list view
        $islist = $this->is_list();

        // we might need this if user has admin but not local settings for size
        $adminconfig = get_config(constants::M_COMPONENT);

        // prepare our response string, which will parsed and replaced with the necessary player
        switch ($recordertype) {
            case constants::REC_VIDEO:
                $listsize = $this->get_config('videolistdisplay');
                if ($listsize === false) {
                    $listsize = $adminconfig->displaysize_list;
                }
                $singlesize = $this->get_config('videosingledisplay');
                if ($singlesize === false) {
                    $singlesize = $adminconfig->displayaudioplayer_single;
                }

                // build our sizes array
                $sizes = [];
                $sizes[constants::SIZE_NONE] = new stdClass();
                $sizes[constants::SIZE_NONE]->width = 0;
                $sizes[constants::SIZE_NONE]->height = 0;
                $sizes[constants::SIZE_LIGHTBOX] = new stdClass();
                $sizes[constants::SIZE_LIGHTBOX]->width = 0;
                $sizes[constants::SIZE_LIGHTBOX]->height = 0;
                $sizes[constants::SIZE_LINK] = new stdClass();
                $sizes[constants::SIZE_LINK]->width = 0;
                $sizes[constants::SIZE_LINK]->height = 0;
                $sizes['480'] = new stdClass();
                $sizes['480']->width = 480;
                $sizes['480']->height = 360;
                $key = $islist ? $listsize : $singlesize;
                if (!array_key_exists($key, $sizes)) {
                    $key = '480';
                }
                $size = $sizes[$key];
                $size->key = $key;

                break;
            case constants::REC_AUDIO:
            default:
                $listsize = $this->get_config('audiolistdisplay');
                if ($listsize === false) {
                    $listsize = $adminconfig->displayaudioplayer_list;
                }
                $singlesize = $this->get_config('audiosingledisplay');
                if ($singlesize === false) {
                    $singlesize = $adminconfig->displayaudioplayer_single;
                }

                $size = new stdClass();
                $size->key = $islist ? $listsize : $singlesize;
                break;

            case constants::REC_WHITEBOARD:
                $boardsize = $this->get_config('boardsize');
                if ($boardsize === false) {
                    $boardsize = '600x400';
                }
                $size = new stdClass();
                $size->key = $boardsize;
                $bits = explode('x', $boardsize);
                $size->width = $bits[0];
                $size->height = $bits[1];
                break;
        } //end of switch
        return $size;

    }

    public function is_list() {
        // is this a list view
        return optional_param('action', '', PARAM_TEXT) == 'grading';
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

        // if filename is false, or empty, no update. possibly used changed something else on page
        // possibly they did not record ... that will be caught elsewhere
        $filename = $data->filename;
        if ($filename === false || empty($filename)) {
            return true;
        }

        // Move the file from draft to permanent storage.
        $this->shift_draft_file($submission, $data);

        // get expiretime of this record
        $expiredays = $this->get_config("expiredays");
        if ($expiredays < 9999) {
            $fileexpiry = time() + DAYSECS * $expiredays;
        } else {
            $fileexpiry = 0;
        }

        $vectordata = optional_param(constants::M_VECTORCONTROL, '', PARAM_RAW);

        $cloudpoodllsubmission = $this->get_cloudpoodll_submission($submission->id);
        if ($cloudpoodllsubmission) {
            if ($filename == '-1') {
                // this is a flag to delete the submission
                // we actually do not delete because we get caught in is_empty and it returns us to submission page
                // with an "empty submission" error even thugh the plugin has been removed.
                // So we just do not display "-1" filename files

                // $ret = $DB->delete_records(constants::M_TABLE, array('id'=>$cloudpoodllsubmission->id));
                $cloudpoodllsubmission->filename = $filename;
                $cloudpoodllsubmission->fileexpiry = 0;
                $cloudpoodllsubmission->vectordata = '';
                $ret = $DB->update_record(constants::M_TABLE, $cloudpoodllsubmission);
            } else {
                $cloudpoodllsubmission->filename = $filename;
                $cloudpoodllsubmission->fileexpiry = $fileexpiry;
                $cloudpoodllsubmission->transcript = '';
                $cloudpoodllsubmission->fulltranscript = '';
                $cloudpoodllsubmission->vttdata = '';
                $cloudpoodllsubmission->vectordata = $vectordata;
                $ret = $DB->update_record(constants::M_TABLE, $cloudpoodllsubmission);
                // If its an audio or video recording we look for a transcript
                if ($this->get_config('recordertype') !== constants::REC_WHITEBOARD) {
                    $this->register_fetch_transcript_task($cloudpoodllsubmission);
                }
            }

        } else {
            $cloudpoodllsubmission = new stdClass();
            $cloudpoodllsubmission->submission = $submission->id;
            $cloudpoodllsubmission->assignment = $this->assignment->get_instance()->id;
            $cloudpoodllsubmission->recorder = $this->get_config('recordertype');
            $cloudpoodllsubmission->filename = $filename;
            $cloudpoodllsubmission->fileexpiry = $fileexpiry;
            $cloudpoodllsubmission->vectordata = $vectordata;
            $ret = $DB->insert_record(constants::M_TABLE, $cloudpoodllsubmission);
            // register our adhoc task
            if ($ret) {
                $cloudpoodllsubmission->id = $ret;
                $this->register_fetch_transcript_task($cloudpoodllsubmission);
            }
        }
        return $ret;

    }

    // register an adhoc task to pick up transcripts
    public function register_fetch_transcript_task($cloudpoodllsubmission) {
        $fetchtask = new \assignsubmission_cloudpoodll\task\cloudpoodll_s3_adhoc();
        $fetchtask->set_component(constants::M_COMPONENT);

        $customdata = new \stdClass();
        $customdata->submission = $cloudpoodllsubmission;
        $customdata->taskcreationtime = time();

        $fetchtask->set_custom_data($customdata);
        // queue it
        \core\task\manager::queue_adhoc_task($fetchtask);
        return true;
    }


    /**
     * Display the response in student's summary, view submissions, or grading page
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true to show the submission on a new page
     * @return string
     */
    public function view_summary(stdClass $submission, &$showviewlink) {
        // if this is the view all submissions page, we may want to show a full player
        // or entire transcript on click, so we add a view link
        $islist = optional_param('action', '', PARAM_TEXT) == 'grading';
        $showviewlink = $islist; // is this a list page

        // our response, this will output a player, and optionally a portfolio export link
        return $this->fetchResponses($submission->id, false) . $this->get_p_links($submission->id);
        // rely on get_files from now on to generate portfolio links Justin 19/06/2014

    }

    /**
     * Produce a list of files suitable for export that represent this feedback or submission
     *
     * @param stdClass $submission The submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, ?stdClass $user = null) {
        global $CFG;
        $result = [];

        $cloudpoodllsubmission = $this->get_cloudpoodll_submission($submission->id);
        if ($cloudpoodllsubmission && isset($cloudpoodllsubmission->filename)) {
            $filebits = explode('/', $cloudpoodllsubmission->filename);
            $shortfilename = end($filebits);
            // create the file record for our new file
            $filerecord = new stdClass();
            $filerecord->userid = $submission->userid;
            $filerecord->contextid = $this->assignment->get_context()->id;
            $filerecord->component = constants::M_COMPONENT;
            $filerecord->filearea = constants::M_FILEAREA;
            $filerecord->itemid = $submission->id;
            $filerecord->filepath = '/';
            $filerecord->filename = $shortfilename;
            $filerecord->license = $CFG->sitedefaultlicense;
            $filerecord->author = 'Moodle User';
            $filerecord->source = '';
            $filerecord->timecreated = time();
            $filerecord->timemodified = time();
            $fs = get_file_storage();
            try {
                if ($fs->file_exists($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid, $filerecord->filepath, $filerecord->filename)) {
                    $file = $fs->get_file($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid, $filerecord->filepath, $filerecord->filename);
                } else {
                    $file = $fs->create_file_from_url($filerecord, $cloudpoodllsubmission->filename, null, true);
                }
                if ($file) {
                    $result[$shortfilename] = $file;
                }
            } catch (Exception $e) {
                // the file was probably too old, or had never made it
            }
        }
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
            $result = $this->fetchResponses($submission->id, false);
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
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     */
    public function get_config_for_external() {
        return (array)$this->get_config();
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
            $DB->delete_records(constants::M_TABLE, ['submission' => $submissionid]);
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
        $DB->delete_records(constants::M_TABLE, ['assignment' => $this->assignment->get_instance()->id]);
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
        if ($cpsubmission && !empty($cpsubmission->filename)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Move the file from draft to permanent storage.
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return void
     */
    public function shift_draft_file($submission, $data) {
        $draftitemid = optional_param('wb_draftitemid', 0, PARAM_INT);
        if ($draftitemid) {
            file_save_draft_area_files(
                $draftitemid,
                $this->assignment->get_context()->id,
                constants::M_COMPONENT,
                constants::M_FILEAREA,
                $submission->id
            );
        }
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return [constants::M_FILEAREA => constants::M_COMPONENT];
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_config_file_areas() {
        return [constants::M_WB_FILEAREA];
    }
}
