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
 * Privacy Subsystem implementation for assignsubmission_cloudpoodll.
 *
 * @package    assignsubmission_cloudpoodll
 * @copyright  2018 Justin Hunt https://poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_cloudpoodll\privacy;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request;
use \mod_assign\privacy\assign_plugin_request_data;
use assignsubmission_cloudpoodll\constants;

/**
 * Privacy Subsystem for assignsubmission_cloudpoodll implementing null_provider.
 *
 * @copyright  2018 Justin Hunt https://poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class provider implements metadataprovider,
    \mod_assign\privacy\assignsubmission_provide,
    \mod_assign\privacy\assignsubmission_user_provider {

    use \core_privacy\local\legacy_polyfill;
    use \mod_assign\privacy\submission_legacy_polyfill;



    /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function _get_metadata(collection $collection) {

        $detail = [
            'assignment' => 'privacy:metadata:assignmentid',
            'submission' => 'privacy:metadata:submissionpurpose',
            'filename' => 'privacy:metadata:filepurpose',
            'transcript' => 'privacy:metadata:transcriptpurpose',
            'fulltranscript' => 'privacy:metadata:fulltranscriptpurpose',
            'vttdata' => 'privacy:metadata:vttpurpose'
        ];
        $collection->add_database_table('assignsubmission_cpoodll', $detail, 'privacy:metadata:tablepurpose');
        $collection->add_external_location_link('cloud.poodll.com', [
            'userid' => 'privacy:metadata:cloudpoodllcom:userid'
        ], 'privacy:metadata:cloudpoodllcom');
        return $collection;
    }

    /**
     * This is covered by mod_assign provider and the query on assign_submissions.
     *
     * @param  int $userid The user ID that we are finding contexts for.
     * @param  contextlist $contextlist A context list to add sql and params to for contexts.
     */
    public static function _get_context_for_userid_within_submission($userid, $contextlist) {
        // This is already fetched from mod_assign.
    }

    /**
     * This is also covered by the mod_assign provider and it's queries.
     *
     * @param  \mod_assign\privacy\useridlist $useridlist An object for obtaining user IDs of students.
     */
    public static function _get_student_user_ids($useridlist) {
        // No need.
    }

    /**
     * If you have tables that contain userids and you can generate entries in your tables without creating an
     * entry in the assign_submission table then please fill in this method.
     *
     * @param  userlist $userlist The userlist object
     */
    public static function _get_userids_from_context($userlist) {
        // Not required.
    }
    
    /**
     * Export all user data for this plugin.
     *
     * @param  submission_request_data $exportdata Data used to determine which context and user to export and other useful
     * information to help with exporting.
     */
    /**
     * Export all user data for this plugin.
     *
     * @param  assign_plugin_request_data $exportdata Data used to determine which context and user to export and other useful
     * information to help with exporting.
     */
    public static function _export_submission_user_data($exportdata) {
        // We currently don't show submissions to teachers when exporting their data.
        if ($exportdata->get_user() != null) {
            return null;
        }
        // Retrieve text for this submission.
        $submission = $exportdata->get_pluginobject();
        $filename='';
        if($submission) {
            $filename = $submission->filename;
            $context = $exportdata->get_context();
        }
        if (!empty($filename)) {
            $submissiondata = new \stdClass();
            $submissiondata->filename = $filename;
            $submissiondata->transcript =  $submission->transcript;
            $submissiondata->fulltranscript =  $submission->fulltranscript;
            $submissiondata->vttdata =  $submission->vttdata;
            $currentpath = $exportdata->get_subcontext();
            $currentpath[] = get_string('privacy:path', 'assignsubmission_cloudpoodll');
            writer::with_context($context)
                // Add the text to the exporter.
                ->export_data($currentpath, $submissiondata);

            // Handle plagiarism data.
            $coursecontext = $context->get_course_context();
            $userid = $submission->userid;
            \core_plagiarism\privacy\provider::export_plagiarism_user_data($userid, $context, $currentpath, [
                'cmid' => $context->instanceid,
                'course' => $coursecontext->instanceid,
                'userid' => $userid,
                'content' => $filename,
                'assignment' => $submission->assignment
            ]);
        }
    }

    /**
     * Any call to this method should delete all user data for the context defined in the deletion_criteria.
     *
     * @param  submission_request_data $requestdata Information useful for deleting user data.
     */
    public static function _delete_submission_for_context($requestdata) {
        global $DB;

        \core_plagiarism\privacy\provider::delete_plagiarism_for_context($requestdata->get_context());

        // Delete records from assignsubmission_file table.
        $DB->delete_records(constants::M_TABLE, ['assignment' => $requestdata->get_assign()->get_instance()->id]);
    }



    /**
     * A call to this method should delete user data (where practical) using the userid and submission.
     *
     * @param  submission_request_data $exportdata Details about the user and context to focus the deletion.
     */
    public static function _delete_submission_for_userid($deletedata) {
        global $DB;

        \core_plagiarism\privacy\provider::delete_plagiarism_for_user($deletedata->get_user()->id, $deletedata->get_context());

        $submissionid = $deletedata->get_pluginobject()->id;


        $DB->delete_records(constants::M_TABLE, ['assignment' => $deletedata->get_assign()->get_instance()->id,
            'submission' => $submissionid]);
    }

    /**
     * Deletes all submissions for the submission ids / userids provided in a context.
     * assign_plugin_request_data contains:
     * - context
     * - assign object
     * - submission ids (pluginids)
     * - user ids
     * @param  assign_plugin_request_data $deletedata A class that contains the relevant information required for deletion.
     */
    public static function _delete_submissions($deletedata) {
        global $DB;

        \core_plagiarism\privacy\provider::delete_plagiarism_for_users($deletedata->get_userids(), $deletedata->get_context());
        if (empty($deletedata->get_submissionids())) {
            return;
        }

        list($sql, $params) = $DB->get_in_or_equal($deletedata->get_submissionids(), SQL_PARAMS_NAMED);

        $params['assignid'] = $deletedata->get_assignid();
        $DB->delete_records_select(constants::M_TABLE, "assignment = :assignid AND submission $sql", $params);
    }


}
