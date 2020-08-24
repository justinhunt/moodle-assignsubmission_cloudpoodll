<?PHP
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
 * This file contains the moodle hooks for the submission cloudpoodll plugin
 *
 * @package    assignsubmission_cloudpoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

use assignsubmission_cloudpoodll\constants;

/**
 * Serves assignment submissions and other files.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function assignsubmission_cloudpoodll_pluginfile($course, $cm, context $context, $filearea, $args, $forcedownload) {
    global $USER, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);
    $itemid = (int)array_shift($args);
	


    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/" . constants::M_COMPONENT . "/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    $forcedownload = true;
    send_stored_file($file, 0, 0, $forcedownload); // download MUST be forced - security!
}



function assignsubmission_cloudpoodll_output_fragment_mform($args) {
    global $CFG, $PAGE, $DB, $OUTPUT;

    $args = (object) $args;
    $o = '';


    $transcriptopts=array( 'component'=>constants::M_COMPONENT,
            'playerid'=> html_writer::random_id(constants::M_COMPONENT ) ,
            'lang'=>$args->lang,
            'size'=>['width'=>480,'height'=>320],
            'containerid'=>html_writer::random_id(constants::M_COMPONENT ),
            'cssprefix'=>constants::M_COMPONENT .'_transcript',
            'mediaurl'=>$args->mediaurl,
            'transcripturl'=>$args->transcripturl);




    $videoplayer=$OUTPUT->render_from_template(constants::M_COMPONENT  . '/videoplayerinteractive', $transcriptopts);
    $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/interactivetranscript", 'init', array($transcriptopts));
    $PAGE->requires->strings_for_js(array('transcripttitle'),constants::M_COMPONENT);
    //$PAGE->requires->js_call_amd(constants::M_COMPONENT . "/standardtranscript", 'init', array($transcriptopts));
    $o .= $videoplayer;

    return $o;
}
