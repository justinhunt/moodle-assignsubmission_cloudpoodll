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
 * This file contains assignsubmission_cloudpoodll mobile code
 *
 * @package assignsubmission_cloudpoodll
 * @author  Justin Hunt - Poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_cloudpoodll\output;

defined('MOODLE_INTERNAL') || die();


class mobile {
 
    /**
     * Returns the cloudpoodll submission template data for mobile app
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_get_cloudpoodll($args) {
        global $CFG, $OUTPUT;
        $data=[];
        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('assignsubmission_cloudpoodll/mobile_recorder', $data),
                    ]
            ],
            'javascript' => file_get_contents($CFG->dirroot . '/mod/assign/submission/cloudpoodll/mobile/mobile.js')
        ];
    }

}
