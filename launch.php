<?php
/**
 * This file contains all necessary code to view a helixmedia activity instance
 *
 * @package    mod
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $COURSE, $PAGE;

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/helixmedia/locallib.php');
require_once($CFG->dirroot.'/mod/helixmedia/lib.php');

?>
<!DOCTYPE html>
<html  dir="ltr" lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head><title>HML LTI Launcher</title></head>
<body>
<?php

//Course module ID
$id = optional_param('id', 0, PARAM_INT); // Course Module ID

//Assignment course module ID
$aid = optional_param('aid', 0, PARAM_INT);

//HML preid, only used here for a Fake launch for new instances
$l  = optional_param('l', 0, PARAM_INT);  // HML ID

//Hidden option to force debug lanuch
$debug = optional_param('debuglaunch', 0, PARAM_INT);

//Course ID
//Note using $COURSE->id here seems to give random results.
global $USER;
if (property_exists($USER, 'currentcourseaccess')) {
    $cid=array_keys($USER->currentcourseaccess);
    if(array_key_exists(0, $cid))
        $cid=$cid[0];
    else
        $cid=1;
} else {
    $cid=$COURSE->id;
}
$c  = optional_param('course', $cid, PARAM_INT);

//New assignment submission ID
$n_assign = optional_param('n_assign', 0, PARAM_INT);

//Existing assignment submission ID
$e_assign = optional_param('e_assign', 0, PARAM_INT);

//New feedback ID
$n_feed = optional_param('n_feed', 0, PARAM_INT);

//Existing feedback ID
$e_feed = optional_param('e_feed', 0, PARAM_INT);

//User ID for student submission viewing
$userid = optional_param('userid', 0, PARAM_INT);

//Launch type
$type = required_param('type', PARAM_INT); 

//Used for migration only
$mid  = optional_param('mid', -1, PARAM_INT);

//Base64 encoded return URL
$ret  = optional_param('ret', "", PARAM_TEXT);

//Item name
$name  = optional_param('name', "", PARAM_TEXT);

//Item Intro text
$intro  = optional_param('intro', "", PARAM_TEXT);

if (strlen($ret)>0)
    $ret=base64_decode($ret);


$hmli=null;
$cmid=-1;

if ($l || $n_assign || $n_feed || $type==HML_LAUNCH_TINYMCE_EDIT || $type==HML_LAUNCH_TINYMCE_VIEW ||
    $type==HML_LAUNCH_ATTO_EDIT || $type==HML_LAUNCH_ATTO_VIEW) {
    /**This means that we're doing a "fake" launch for a new instance or viewing via a link created in TinyMCE/ATTO**/

    $hmli=new stdclass();
    $hmli->id = -1;

    if ($l)
        $hmli->preid=$l;
    else
    if ($n_assign)
        $hmli->preid=$n_assign;
    else
    if ($n_feed)
        $hmli->preid=$n_feed;
    else
    if ($type==HML_LAUNCH_TINYMCE_EDIT || HML_LAUNCH_ATTO_EDIT)
    {
        $hmli->preid=helixmedia_preallocate_id();
        echo "<script type=\"text/javascript\">\n".
             "window.parent.postMessage('preid_".$hmli->preid."', '*');\n".
             "</script>\n";
    }


    if ($type==HML_LAUNCH_TINYMCE_VIEW || $type==HML_LAUNCH_ATTO_VIEW)
    {
        if (strpos($_SERVER ['HTTP_USER_AGENT'], 'MoodleMobile') !== false) {
            echo "<p>".get_string('moodlemobile', 'helixmedia')."</p>";
            echo "</body></html>";
            return;
        }

        /**This handles dynamic sizing of the launch frame**/
        $size=helixmedia_get_instance_size($hmli->preid, $c);

        if ($size->width == 0)
        {
            $ratio = 0.605;
            // If height is -1, use old size rules
            if ($size->height == -1) {
                $ratio = 0.85;
            }
            echo "<script type=\"text/javascript\">\n".
                 "var vid=parent.document.getElementById('hmlvid-".$hmli->preid."');\n".
                 "var h=parseInt(vid.parentElement.offsetWidth*".$ratio.");\n".
                 "vid.style.width='100%';\n".
                 "vid.style.height=h+'px';\n".
                 "</script>\n";
        }
        else
        {
            // If height is -1, use old size rules
            if ($size->height==-1)
            {
                $w="530px";
                $h="420px";
                if ($size->width==640)
                {
                    $w="680px";
                    $h="570px";
                }
                else
                if ($size->width==835)
                {
                    $w="880px";
                    $h="694px";
                }
            } else {
                if ($size->audioonly) {
                    $w = $size->width."px";
                    $h = $size->height."px";
                } else {
                    $w="380px";
                    $h="340px";
                    if ($size->width==640)
                    {
                        $w="680px";
                        $h="455px";
                    }
                    else
                    if ($size->width==835)
                    {
                        $w="875px";
                        $h="575px";
                    }
                }
            }


            echo "<script type=\"text/javascript\">\n".
                 "var vid=parent.document.getElementById('hmlvid-".$hmli->preid."');".
                 "vid.style.width='".$w."';\n".
                 "vid.style.height='".$h."';\n".
                 "</script>\n";
        }
    }

    $hmli->course=$c;
    $hmli->intro="";
    $hmli->introformat=1;
    $hmli->timecreated=time();
    $hmli->timemodified=$hmli->timecreated;
    $hmli->showtitlelaunch=0;
    $hmli->showdescriptionlaunch=0;            
    $hmli->servicesalt=uniqid('', true);
    $hmli->icon="";
    $hmli->secureicon="";

    if ($aid)
    {
        $cm=get_coursemodule_from_id('assign', $aid, 0, false, MUST_EXIST);
        $assign=$DB->get_record('assign', array('id' => $cm->instance), '*', MUST_EXIST);
        $hmli->name=$assign->name;
        $hmli->intro=$assign->intro;
        $hmli->cmid=$aid;
    }
    else
    {
        if (strlen($name)>0) {
            $hmli->name=$name;
        } else {
            $hmli->name="Untitled (Launch Type=".$type.")";
        }
        if (strlen($intro)>0) {
            $hmli->intro=$intro;
        }
        $hmli->cmid = -1;
    }
    $course = $DB->get_record('course', array('id' => $c), '*', MUST_EXIST);
    if (method_exists("context_course", "instance"))
        $context = context_course::instance($course->id);
    else
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $PAGE->set_context($context);
} else {

    /**Normal launch**/
    if ($id)
    {
        $cm = get_coursemodule_from_id('helixmedia', $id, 0, false, MUST_EXIST);
        $cmid=$cm->id;
        $hmli = $DB->get_record('helixmedia', array('id' => $cm->instance), '*', MUST_EXIST);
        $hmli->cmid = $cm->id;
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    }
    else
    if ($e_assign)
    {
        $hml_assign=$DB->get_record('assignsubmission_helixassign', array('preid' => $e_assign));
        $hmli=$DB->get_record('assign', array('id' => $hml_assign->assignment));
        $cm = get_coursemodule_from_instance('assign', $hmli->id, 0, false, MUST_EXIST);
        $cmid=$cm->id;
        $hmli->cmid = $cm->id;
        $hmli->preid = $hml_assign->preid;
        $hmli->servicesalt = $hml_assign->servicesalt;
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    }
    else
    if ($e_feed)
    {
        $hml_feed=$DB->get_record('assignfeedback_helixfeedback', array('preid' => $e_feed));
        $hmli=$DB->get_record('assign', array('id' => $hml_feed->assignment));
        $cm = get_coursemodule_from_instance('assign', $hmli->id, 0, false, MUST_EXIST);
        $cmid=$cm->id;
        $hmli->cmid = $cm->id;
        $hmli->preid = $hml_feed->preid;
        $hmli->servicesalt = $hml_feed->servicesalt;
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    }
    else
    {
        echo "<p>".get_string('invalid_launch', 'helixmedia')."</p>";
        echo "</body></html>";
        die;
    }

    $PAGE->set_cm($cm, $course); 
    if (method_exists("context_module", "instance"))
        $context = context_module::instance($cm->id);
    else
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $PAGE->set_context($context);
}

require_login($course);

//Do some permissions stuff
$cap=null;
switch ($type) {
    case HML_LAUNCH_RELINK:
        //$cap="moodle/restore:restoreactivity";
        break;
    case HML_LAUNCH_NORMAL:
    case HML_LAUNCH_THUMBNAILS:
    case HML_LAUNCH_TINYMCE_VIEW:
    case HML_LAUNCH_ATTO_VIEW:
    case HML_LAUNCH_VIEW_FEEDBACK:
    case HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS:
        $cap='mod/helixmedia:view';
        break;
    case HML_LAUNCH_EDIT:
    case HML_LAUNCH_TINYMCE_EDIT:
        $cap='mod/helixmedia:addinstance';
        break;
    case HML_LAUNCH_ATTO_EDIT:
        $cap='atto/helixatto:visible';
        break;
    case HML_LAUNCH_STUDENT_SUBMIT:
    case HML_LAUNCH_STUDENT_SUBMIT_PREVIEW:
    case HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS:
        $cap='mod/assign:submit';
        break;
    case HML_LAUNCH_VIEW_SUBMISSIONS:
    case HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS:
    case HML_LAUNCH_FEEDBACK:
    case HML_LAUNCH_FEEDBACK_THUMBNAILS:
        $cap='mod/assign:grade';
        break;
}

if ($cap==null || !has_capability($cap, $context)) {
    echo "<p>".get_string('not_authorised', 'helixmedia')."</p>";
    echo "</body></html>";
    die;
}

$hmli->debuglaunch = 0;
$mod_config=get_config("helixmedia");
if ( ($mod_config->forcedebug && $mod_config->restrictdebug && is_siteadmin()) ||
     ($mod_config->restrictdebug == false && $mod_config->forcedebug)) {
    $hmli->debuglaunch = 1;
}


//Do the logging
if ($type==HML_LAUNCH_NORMAL || $type==HML_LAUNCH_EDIT)
{
    $logURL="launch.php?type=".$type."&";

    if ($type==HML_LAUNCH_EDIT) {
        if ($l) {
            $event = \mod_helixmedia\event\lti_launch_edit_new::create(array(
                'objectid' => $hmli->id,
                'context' => $context
            ));
        }
        else {
            $event = \mod_helixmedia\event\lti_launch_edit::create(array(
                'objectid' => $hmli->id,
                'context' => $context
            ));
        }
    } else {
        $event = \mod_helixmedia\event\lti_launch::create(array(
            'objectid' => $hmli->id,
            'context' => $context
        ));
    }

    if (isset($cm)) {
        $event->add_record_snapshot('course_modules', $cm);
    }

    $event->add_record_snapshot('course', $course);

    // The launch container may not be set for a new instance but Moodle will complain if it's missing, so set default here.
    if (!property_exists($hmli, "launchcontainer")) {
        $hmli->launchcontainer=LTI_LAUNCH_CONTAINER_DEFAULT;
    }

    $event->add_record_snapshot('helixmedia', $hmli);
    $event->trigger();
}

if ($type==HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS || $type==HML_LAUNCH_VIEW_SUBMISSIONS)
   $hmli->userid=$userid;

if ($type==HML_LAUNCH_NORMAL && $CFG->version>=2015111600) {
   helixmedia_view($hmli, $course, $cm, $context);
}

/* Add these fields to stop the lti mod launch code complaining */


helixmedia_view_mod($hmli, $type, $mid, $ret);

?>

<!-- <a href="javascript:closethis();">Close Dialog</a> -->
<script type="text/javascript">
function closethis()
{
 window.parent.postMessage('close_modal', '*');
}
</script>
</body>
</html>
