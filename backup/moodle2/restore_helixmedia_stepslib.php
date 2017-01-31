<?php

/**
 * This file contains all the restore steps that will be used
 * by the restore_helixmedia_activity_task
 *
 * @package    mod
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/helixmedia/lib.php');

/**
 * Structure step to restore one helixmedia activity
 */
class restore_helixmedia_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('helixmedia', '/activity/helixmedia');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_helixmedia($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->servicesalt = uniqid('', true);

        $newitemid = $DB->insert_record('helixmedia', $data);

        // TODO - This needs to be more elegant, but this will prevent restores from mucking up new instances if the preid is higher
        // There doesn't seem to be a standard SQL way to retrieve the next value in a sequence. So just get the next pre_id and check
        // that it is greater than the one we are adding here and if it isn't keep going until it is.
        $next_preid=helixmedia_preallocate_id();
        while ($next_preid < $data->preid) {
            $DB->delete_records('helixmedia_pre', array('id'=>$next_preid));
            $next_preid=helixmedia_preallocate_id();
        }
        $DB->delete_records('helixmedia_pre', array('id'=>$next_preid));

        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function after_execute() {
        // Add helixmedia related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_helixmedia', 'intro', null);
    }
}
