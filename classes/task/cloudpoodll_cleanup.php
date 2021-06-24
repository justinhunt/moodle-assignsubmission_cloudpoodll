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
 * The assignsubmission_cloudpoodll scheduled task
 *
 * @package    assignsubmission_cloudpoodll
 * @copyright  Justin Hunt (https://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_cloudpoodll\task;
use \assignsubmission_cloudpoodll\constants;
use \assignsubmission_cloudpoodll\utils;


defined('MOODLE_INTERNAL') || die();

class cloudpoodll_cleanup extends \core\task\scheduled_task {
		
	public function get_name() {
        // Shown in admin screens
        return get_string('cloudpoodll_cleanup_task', constants::M_COMPONENT);
    }
	
	 /**
     *  Run the task
      */
	 public function execute(){
		$trace = new \text_progress_trace();
		$trace->output('running cloudpoodll_cleanup_task task now');
        utils::cleanup_files();
	}

}

