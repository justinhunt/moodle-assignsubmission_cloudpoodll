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
 * This file contains CloudPoodll Assignment Submission mobile config
 *
 * @package    mod
 * @subpackage assign/cloudpoodll
 * @author     Justin Hunt (justin@poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'assignsubmission_cloudpoodll' => [
        'handlers' => [ 
            'courseassignsubmission_cloudpoodll' => [
                'displaydata' => [
                    'title' => 'cloudpoodll submission',
                    'icon' => $CFG->wwwroot . '/mod/assign/submission/cloudpoodll/pix/icon.png',
                    'class' => '',
                ],
 
                'delegate' => 'AddonModAssignSubmissionDelegate', 
                'method' => 'mobile_get_cloudpoodll',
                'styles' => [
                    'url' => 'mod/assign/submission/cloudpoodll/mobile/styles_app.css',
                    'version' => '1.00'
                ]
            ],
        ],
        'lang' => [
            ['pluginname', 'assignsubmission_cloudpoodll'],
            ['mobilesub', 'assignsubmission_cloudpoodll'],
            ['mobilelink', 'assignsubmission_cloudpoodll']
        ]
    ],
];
