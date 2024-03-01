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
 * This file contains a library of functions and constants for the helixmedia module
 *
 * @package    mod
 * @subpackage helixmedia
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @author     Tim Williams (For Streaming LTD)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in URL module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function helixmedia_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Allocate a resource link ID
 */
function helixmedia_preallocate_id() {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/helixmedia/locallib.php');

    $pre = new stdclass();
    $pre->timecreated = time();
    $pre->servicesalt = uniqid('', true);

    $pre->id = $DB->insert_record('helixmedia_pre', $pre);

    // If the value here is 1 then either this is a new install or the auto_increment value has been reset
    // due to the problem with InnoDB not storing this value persistently. Check regardless.
    if ($pre->id == 1) {
        $val = 1;
        // Check the activity mod.
        $sql = "SELECT MAX(preid) AS preid FROM ".$CFG->prefix."helixmedia;";
        $vala = $DB->get_record_sql($sql);
        if ($vala) {
            $val = $vala->preid;
        }
        // Check the Submissions.
        $assigninstalled = $DB->get_records('assign_plugin_config', array('plugin' => 'helixassign'));
        if (count($assigninstalled) > 0) {
            $sql = "SELECT MAX(preid) AS preid FROM ".$CFG->prefix."assignsubmission_helixassign;";
            $valb = $DB->get_record_sql($sql);
            if ($valb && $valb->preid > $val) {
                $val = $valb->preid;
            }
        }
        // Check the Feedback.
        $feedinstalled = $DB->get_records('assign_plugin_config', array('plugin' => 'helixfeedback'));
        if (count($feedinstalled) > 0) {
            $sql = "SELECT MAX(preid) AS preid FROM ".$CFG->prefix."assignfeedback_helixfeedback;";
            $valc = $DB->get_record_sql($sql)->preid;
            if ($valc && $valc->preid > $val) {
                $val = $valc->preid;
            }
        }

        // Checking all the instances created by the HTML editor would be a massive slow query, so
        // i'm going to assume that all the modules get used with a reasonable degree of frequency and just add 100
        // +10% of the highest value found to offest things. This is likely to be a very rare problem, since mitgating steps
        // are being taken else where to prevent this problem, so this exists simply to fix installations that have already
        // gone wrong.

        $val = intval($val / 10) + 100;

        $DB->execute("ALTER TABLE ".$CFG->prefix."helixmedia_pre AUTO_INCREMENT=".$val."");
        $pre = new stdclass();
        $pre->timecreated = time();
        $pre->servicesalt = uniqid('', true);
        $pre->id = $DB->insert_record('helixmedia_pre', $pre);
    }

    return $pre->id;
}

/**
 * Get the resource link id
 * @param $cmid Course module id
 * @return The Resource link id
 */
function helixmedia_get_preid($cmid) {
    global $DB;

    if ($cmid == null) {
        return null;
    }

    $cm = get_coursemodule_from_id('helixmedia', $cmid, 0, false, MUST_EXIST);
    $hmli = $DB->get_record('helixmedia', array('id' => $cm->instance), '*', MUST_EXIST);
    return $hmli->preid;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted helixmedia record
 **/
function helixmedia_add_instance($helixmedia, $mform) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/helixmedia/locallib.php');

    $prerec = $DB->get_record('helixmedia_pre', array('id' => $helixmedia->preid));

    $helixmedia->timecreated = time();
    $helixmedia->timemodified = $helixmedia->timecreated;
    $helixmedia->servicesalt = $prerec->servicesalt;

    if (!isset($helixmedia->showtitlelaunch)) {
        $helixmedia->showtitlelaunch = 0;
    }

    if (!isset($helixmedia->showdescriptionlaunch)) {
        $helixmedia->showdescriptionlaunch = 0;
    }

    /**Set these to some defaults for now.**/
    $helixmedia->icon = "";
    $helixmedia->secureicon = "";

    $helixmedia->id = $DB->insert_record('helixmedia', $helixmedia);

    return $helixmedia->id;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function helixmedia_update_instance($helixmedia, $mform) {
    global $DB, $CFG;

    $helixmedia->timemodified = time();
    $helixmedia->id = $helixmedia->instance;

    if (!isset($helixmedia->showtitlelaunch)) {
        $helixmedia->showtitlelaunch = 0;
    }

    if (!isset($helixmedia->showdescriptionlaunch)) {
        $helixmedia->showdescriptionlaunch = 0;
    }

    return $DB->update_record('helixmedia', $helixmedia);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function helixmedia_delete_instance($id) {
    global $DB;

    if (! $helixmedia = $DB->get_record("helixmedia", array("id" => $id))) {
        return false;
    }

    $result = true;

    return $DB->delete_records("helixmedia", array("id" => $helixmedia->id));
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @TODO: implement this moodle function (if needed)
 **/
function helixmedia_user_outline($course, $user, $mod, $helixmedia) {
    return null;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @TODO: implement this moodle function (if needed)
 **/
function helixmedia_user_complete($course, $user, $mod, $helixmedia) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in helixmedia activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @uses $CFG
 * @return boolean
 * @TODO: implement this moodle function
 **/
function helixmedia_print_recent_activity($course, $isteacher, $timestart) {
    return false;  // True if anything was printed, otherwise false.
}

/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function helixmedia_install() {
     return true;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function helixmedia_uninstall() {
    return true;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $hml        hml object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */

function helixmedia_view($hml, $course, $cm, $context, $user = null) {
    global $USER;
    if ($user == null) {
        $user = $USER;
    }

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $hml->id,
        'userid' => $user->id
    );

    $event = \mod_helixmedia\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('helixmedia', $hml);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}
