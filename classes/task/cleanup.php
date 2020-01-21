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
 * This file defines the version of helixmedia
 *
 * @package    mod
 * @subpackage helixmedia
 * @copyright  2013 Tim Williams (For Streaming LTD)
 * @author     Tim Williams
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_helixmedia\task;
 
/**
 * Cleanup task for HelixMedia;
 */



class cleanup extends \core\task\scheduled_task {
 
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanup', 'mod_helixmedia');
    }
 
    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;
        $pre_recs=$DB->get_records('helixmedia_pre');

        /**If there is only one entry in the table, leave it alone regardless. This is needed to stop InnoDB from
        incorrectly recalculating the AUTO_INCREMENT value if the DB is restarted with an empty table.**/
        if (count($pre_recs)<2)
             return;

        //Remove the last element so that the most recent preid value always explicitly remains in the database for the benefit of InnoDB
        array_pop($pre_recs);

        $subplugins = \core_plugin_manager::instance()->get_installed_plugins('assignsubmission');
        if (array_key_exists('helixassign', $subplugins)) {
            $assign_installed=true;
        } else {
            $assign_installed=false;
        }

        $feedplugins = \core_plugin_manager::instance()->get_installed_plugins('assignfeedback');
        if (array_key_exists('helixfeedback', $feedplugins)) {
            $feed_installed=true;
        } else {
            $feed_installed=false;
        }

        foreach ($pre_recs as $pre_rec) {
            $hm=$DB->get_record('helixmedia', array('preid'=> $pre_rec->id));
            if (!$hm && $assign_installed)
                $hm=$DB->get_record('assignsubmission_helixassign', array('preid'=> $pre_rec->id));
            if (!$hm && $feed_installed)
                $hm=$DB->get_record('assignfeedback_helixfeedback', array('preid'=> $pre_rec->id));

            /**Clean out anything with an ID that is now in the main table or older than the session time out**/
            if ($hm || $pre_rec->timecreated+$CFG->sessiontimeout < time())
                $DB->delete_records('helixmedia_pre', array('id'=>$pre_rec->id));

        }
    }
}
