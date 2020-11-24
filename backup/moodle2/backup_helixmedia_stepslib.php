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
 * This file contains all the backup steps that will be used
 * by the backup_helixmedia_activity_task
 *
 * @package    mod
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete assignment structure for backup, with file and id annotations
 */
class backup_helixmedia_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $hmli = new backup_nested_element('helixmedia', array('id'), array(
            'preid',
            'course',
            'name',
            'intro',
            'introformat',
            'timecreated',
            'timemodified',
            'launchcontainer',
            'debuglaunch',
            'showtitlelaunch',
            'showdescriptionlaunch',
            'icon',
            'secureicon',
            )
        );

        // Build the tree.

        // Define sources.
        $hmli->set_source_table('helixmedia', array('id' => backup::VAR_ACTIVITYID));

        // Define file annotations.
        $hmli->annotate_files('mod_helixmedia', 'intro', null); // This file areas haven't itemid.

        // Return the root element (hmli), wrapped into standard activity structure.
        return $this->prepare_activity_structure($hmli);
    }
}
