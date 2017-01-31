<?php

/**
 * This page lists all the instances of helixmedia in a particular course
 *
 * @package    mod
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once($CFG->dirroot.'/mod/helixmedia/lib.php');

$id = required_param('id', PARAM_INT);   // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

require_login($course);
$PAGE->set_pagelayout('incourse');

$event = \mod_helixmedia\event\course_module_instance_list_viewed::create(array('context' => context_course::instance($course->id)));
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/helixmedia/index.php', array('id' => $course->id));
$pagetitle = strip_tags($course->shortname.': '.get_string("modulenamepluralformatted", "helixmedia"));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Print the main part of the page
echo $OUTPUT->heading(get_string("modulenamepluralformatted", "helixmedia"));

// Get all the appropriate data
if (! $hmlis = get_all_instances_in_course("helixmedia", $course)) {
    notice(get_string('nohelixmedias', 'helixmedia'), "../../course/view.php?id=$course->id");
    die;
}

// Print the list of instances (your module will probably extend this)
$timenow = time();
$strname = get_string("name");
$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = array ($strsectionname, $strname);
    $table->align = array ("center", "left");
} else {
    $table->head  = array ($strname);
}

foreach ($hmlis as $hmli) {
    if (!$hmli->visible) {
        //Show dimmed if the mod is hidden
        $link = "<a class=\"dimmed\" href=\"view.php?id=$hmli->coursemodule\">$hmli->name</a>";
    } else {
        //Show normal if the mod is visible
        $link = "<a href=\"view.php?id=$hmli->coursemodule\">$hmli->name</a>";
    }

    if ($usesections) {
        //MDL2.4+ works slightly differently here
        if ($CFG->version>=2012120300)
            $table->data[] = array (get_section_name($course, $hmli->section), $link);
        else
            $table->data[] = array ($hmli->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo "<br />";

echo html_writer::table($table);

// Finish the page
echo $OUTPUT->footer();
