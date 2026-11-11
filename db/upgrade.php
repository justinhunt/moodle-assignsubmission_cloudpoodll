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
 * Upgrade code for install
 *
 * @package    assignsubmission_cloudpoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_cloudpoodll\constants;

defined('MOODLE_INTERNAL') || die();

/**
 * Stub for upgrade code
 * @param int $oldversion
 * @return bool
 */
function xmldb_assignsubmission_cloudpoodll_upgrade($oldversion)
{
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    //do some upgrading
    if ($oldversion < 2017052201) {

        // Cloud Poodll savepoint reached
        upgrade_plugin_savepoint(true, 2017052201, 'assignsubmission', constants::M_SUBPLUGIN);
    }

    //add filename field.
    if ($oldversion < 2021062400) {
        // The table is still named with the legacy name at this point; it is renamed in a later step.
        $table = new xmldb_table('assignsubmission_cpoodll');
        $field = new xmldb_field('secureplayback', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);


        // Conditionally launch add field filename.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // online PoodLL savepoint reached
        upgrade_plugin_savepoint(true, 2021062400, 'assignsubmission', constants::M_SUBPLUGIN);

    }

    if ($oldversion < 2026021901) {

        // Define field vectordata to be added to the legacy assignsubmission_cpoodll table (renamed in a later step).
        $table = new xmldb_table('assignsubmission_cpoodll');
        $field = new xmldb_field('vectordata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'secureplayback');

        // Conditionally launch add field vectordata.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cloud Poodll savepoint reached.
        upgrade_plugin_savepoint(true, 2026021901, 'assignsubmission', constants::M_SUBPLUGIN);
    }

    if ($oldversion < 2026101500) {
        // Rename the table to begin with the plugin's frankenstyle name.
        $oldtable = new xmldb_table('assignsubmission_cpoodll');
        $newtable = new xmldb_table(constants::M_TABLE);
        if ($dbman->table_exists($oldtable) && !$dbman->table_exists($newtable)) {
            $dbman->rename_table($oldtable, constants::M_TABLE);
        }

        // Cloud Poodll savepoint reached.
        upgrade_plugin_savepoint(true, 2026101500, 'assignsubmission', constants::M_SUBPLUGIN);
    }

    if ($oldversion < 2026103100) {
        // Recordings are always transcoded, so the setting is gone.
        unset_config('enabletranscode', constants::M_COMPONENT);
        $DB->delete_records('assign_plugin_config', [
            'plugin' => constants::M_SUBPLUGIN,
            'subtype' => 'assignsubmission',
            'name' => 'enabletranscode',
        ]);

        // The transcript task used to run on whiteboard images and save curl's error message as the
        // transcript. A whiteboard image never has a transcript, so clear them all.
        $select = "recorder = :recorder AND " . $DB->sql_like('filename', ':url', false, true, true);
        $params = ['recorder' => constants::REC_WHITEBOARD, 'url' => 'http%'];
        foreach (['transcript', 'fulltranscript', 'vttdata'] as $field) {
            $DB->set_field_select(constants::M_TABLE, $field, null, $select, $params);
        }

        // Cloud Poodll savepoint reached.
        upgrade_plugin_savepoint(true, 2026103100, 'assignsubmission', constants::M_SUBPLUGIN);
    }

    if ($oldversion < 2026110800) {
        // The new recorders do not make the student click "Use this recording" before they can
        // submit, so turn safe save on in every assignment that uses Cloud Poodll submissions.
        // MySQL cannot update a table from a subquery on itself, so fetch the assignments first.
        $enabledparams = [
            'plugin' => constants::M_SUBPLUGIN,
            'subtype' => 'assignsubmission',
            'name' => 'enabled',
            'value' => '1',
        ];
        $assignmentids = $DB->get_fieldset_select(
            'assign_plugin_config',
            'assignment',
            'plugin = :plugin AND subtype = :subtype AND name = :name AND value = :value',
            $enabledparams
        );
        foreach (array_chunk($assignmentids, 1000) as $chunk) {
            [$insql, $params] = $DB->get_in_or_equal($chunk, SQL_PARAMS_NAMED);
            $params += ['plugin' => constants::M_SUBPLUGIN, 'subtype' => 'assignsubmission', 'name' => 'safesave'];
            $select = "plugin = :plugin AND subtype = :subtype AND name = :name AND assignment $insql";
            $DB->set_field_select('assign_plugin_config', 'value', '1', $select, $params);
        }

        // Cloud Poodll savepoint reached.
        upgrade_plugin_savepoint(true, 2026110800, 'assignsubmission', constants::M_SUBPLUGIN);
    }

    return true;
}