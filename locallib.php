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
 * @author     Tim Williams (For Streaming LTD)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');

// Activity types.
define('HML_LAUNCH_NORMAL', 1);
define('HML_LAUNCH_THUMBNAILS', 2);
define('HML_LAUNCH_EDIT', 3);

// Special type for migration from the repository module.
define('HML_LAUNCH_RELINK', 4);

// Assignment submission types.
define('HML_LAUNCH_STUDENT_SUBMIT', 5);
define('HML_LAUNCH_STUDENT_SUBMIT_PREVIEW', 17);
define('HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS', 6);
define('HML_LAUNCH_VIEW_SUBMISSIONS', 7);
define('HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS', 8);

// TinyMCE types. Do not change these values, they are embedded in the TinyMCE plugin html code.
define('HML_LAUNCH_TINYMCE_EDIT', 9);
define('HML_LAUNCH_TINYMCE_VIEW', 10);

// Submission Feedback types.
define('HML_LAUNCH_FEEDBACK', 11);
define('HML_LAUNCH_FEEDBACK_THUMBNAILS', 12);

// Submission Feedback types.
define('HML_LAUNCH_VIEW_FEEDBACK', 13);
define('HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS', 14);

// ATTO Types. Do not change these values, they are embedded in the ATTO plugin html code.
define('HML_LAUNCH_ATTO_EDIT', 15);
define('HML_LAUNCH_ATTO_VIEW', 16);

// Note next ID should be 18.

// For version check.
define('MEDIAL_MIN_VERSION', '6.0.020');

/**
 * Prints a Helix Media activity
 *
 * @param $instance The helixmedia instance.
 * @param $type The Helix Launch Type
 * @param $ref The value for the custom_video_ref parameter
 * @param $modtype The module type, use to check if we can use the more permissive
 * @param $ret The return URL to set for the modal dialogue
 */
function helixmedia_view_mod($instance, $type=HML_LAUNCH_NORMAL, $ref=-1, $ret="", $user = null, $modtype = "") {
    global $PAGE, $CFG, $DB, $USER;

    if ($user == null) {
        $user = $USER;
    }

    $modconfig = get_config("helixmedia");

    if (property_exists($instance, "version")) {
        $version = $hml->version;
    } else {
        $version = get_config('mod_helixmedia', 'version');
    }

    // Check to see if the DB has duplicate preid's for the assignment submission, if it does send an
    // old version number to trigger the fix for this problem. The check doesn't need to be exhaustive.
    // Either the whole lot will match, or none will.
    if ($type == HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS || $type == HML_LAUNCH_VIEW_SUBMISSIONS) {
        $ass = $DB->get_record("course_modules", array("id" => $instance->cmid));
        $recs = $DB->get_records("assignsubmission_helixassign", array("assignment" => $ass->instance));
        $num = -1;
        foreach ($recs as $rec) {
            if ($num == -1) {
                $num = $rec->preid;
            } else {
                if ($num == $rec->preid) {
                    $version = 2014111700;
                    break;
                }
            }
        }
    }

    // Set up the type config.
    $typeconfig = (array)$instance;
    $typeconfig['sendname'] = $modconfig->sendname;
    $typeconfig['sendemailaddr'] = $modconfig->sendemailaddr;
    $typeconfig['customparameters'] = $modconfig->custom_params."\nhml_version=".$version;

    switch ($type) {
        case HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS:
        case HML_LAUNCH_THUMBNAILS:
        case HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS:
        case HML_LAUNCH_FEEDBACK_THUMBNAILS:
        case HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS:
            $typeconfig['customparameters'] .= "\nthumbnail=Y\nthumbnail_width=176\nthumbnail_height=99";
            break;
    }

    switch ($type) {
        case HML_LAUNCH_NORMAL:
        case HML_LAUNCH_TINYMCE_VIEW:
        case HML_LAUNCH_ATTO_VIEW:
            $typeconfig['customparameters'] .= "\nview_only=Y\nno_horiz_borders=Y";
            break;
        case HML_LAUNCH_EDIT:
        case HML_LAUNCH_TINYMCE_EDIT:
        case HML_LAUNCH_ATTO_EDIT:
            $typeconfig['customparameters'] .= "\nno_horiz_borders=Y";
            break;
        case HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS:
            // Nothing to do here.
            break;
        case HML_LAUNCH_STUDENT_SUBMIT:
            $typeconfig['customparameters'] .= "\nlink_response=Y\nlink_type=Assignment";
            $typeconfig['customparameters'] .= "\nassignment_ref=".$instance->cmid;
            $typeconfig['customparameters'] .= "\ntemp_assignment_ref=".helixmedia_get_assign_into_refs($instance->cmid)."\n";
            $typeconfig['customparameters'] .= "\ngroup_assignment=".helixmedia_is_group_assign($instance->cmid);
            break;
        case HML_LAUNCH_STUDENT_SUBMIT_PREVIEW:
            $typeconfig['customparameters'] .= "\nlink_type=Assignment";
            $typeconfig['customparameters'] .= "\nassignment_ref=".$instance->cmid."\n";
            /**Note play_only is redundant in HML 3.1.007 onwards and will be ignored**/
            $typeconfig['customparameters'] .= "\nplay_only=Y\nno_horiz_borders=Y";
            $typeconfig['customparameters'] .= "\ntemp_assignment_ref=".helixmedia_get_assign_into_refs($instance->cmid)."\n";
            $typeconfig['customparameters'] .= "\ngroup_assignment=".helixmedia_is_group_assign($instance->cmid);
            break;
        case HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS:
        case HML_LAUNCH_VIEW_SUBMISSIONS:
            $typeconfig['customparameters'] .= "\nresponse_user_id=".$instance->userid;
            break;
        case HML_LAUNCH_VIEW_FEEDBACK:
            $typeconfig['customparameters'] .= "\nplay_only=Y\nno_horiz_borders=Y";
            break;
    }
    if ($ref > -1) {
        $typeconfig['customparameters'] .= "\nvideo_ref=".$ref;
    }

    $typeconfig['customparameters'] .= "\nlaunch_type=".$type;
    $typeconfig['acceptgrades'] = 0;
    $typeconfig['allowroster'] = 1;
    $typeconfig['forcessl'] = '0';
    $typeconfig['launchcontainer'] = $modconfig->default_launch;

    // Default the organizationid if not specified.
    if (!empty($modconfig->org_id)) {
        $typeconfig['organizationid'] = $modconfig->org_id;
    } else {
        $urlparts = parse_url($CFG->wwwroot);
        $typeconfig['organizationid'] = $urlparts['host'];
    }

    $endpoint = trim($modconfig->launchurl);

    $orgid = $typeconfig['organizationid'];

    $course = $DB->get_record("course", array("id" => $instance->course));
    $requestparams = helixmedia_build_request($instance, $typeconfig, $course, $type, $user, $modtype);
    $launchcontainer = lti_get_launch_container($instance, $typeconfig);

    if ($orgid) {
        $requestparams["tool_consumer_instance_guid"] = $orgid;
    }

    switch ($type) {
        case HML_LAUNCH_EDIT:
        case HML_LAUNCH_STUDENT_SUBMIT:
        case HML_LAUNCH_FEEDBACK:
        case HML_LAUNCH_TINYMCE_EDIT:
        case HML_LAUNCH_TINYMCE_VIEW:
        case HML_LAUNCH_ATTO_EDIT:
        case HML_LAUNCH_ATTO_VIEW:
            break;
        default:
            // Mobile devices launch without the Moodle frame, so we need a return URL here.

            if (method_exists("core_useragent", "check_browser_version")) {
                $devicetype = core_useragent::get_device_type();
            } else {
                $devicetype = get_device_type();
            }
            if ($devicetype === 'mobile' || $devicetype === 'tablet' ) {
                $returnurlparams = array('id' => $course->id);
                $url = new moodle_url('/course/view.php', $returnurlparams);
                $returnurl = $url->out(false);
                $requestparams['launch_presentation_return_url'] = $returnurl;
            }
    }

    $params = lti_sign_parameters($requestparams, $endpoint, "POST", $modconfig->consumer_key, $modconfig->shared_secret);

    if (isset($instance->debuglaunch)) {
        $debuglaunch = ( $instance->debuglaunch == 1 );
        // Moodle 2.8 strips this out at the form submission stage, so this needs to be added after the request
        // is signed in 2.8 since the remote server will never see this parameter.
        if ($CFG->version >= 2014111000) {
            $submittext = get_string('press_to_submit', 'lti');
            $params['ext_submit'] = $submittext;
        }
    } else {
        $debuglaunch = false;
    }

    if ($type == HML_LAUNCH_RELINK) {
        return helixmedia_curl_post_launch_html($params, $endpoint);
    } else {
        echo lti_post_launch_html($params, $endpoint, $debuglaunch);
    }
}

function helixmedia_is_group_assign($cmid) {
    global $DB;
    $cm = $DB->get_record('course_modules', array('id' => $cmid));
    $assign = $DB->get_record('assign', array('id' => $cm->instance));

    if ($assign->teamsubmission) {
         return "Y";
    } else {
         return "N";
    }
}

function helixmedia_get_assign_into_refs($assignid) {
    global $DB;
    $refs = "";

    $module = $DB->get_record("course_modules", array("id" => $assignid));

    if (!$module) {
        return "";
    }

    $assignment = $DB->get_record("assign", array("id" => $module->instance));

    if (!$assignment) {
        return "";
    }

    $first = true;
    $pos = strpos($assignment->intro, "/mod/helixmedia/launch.php");

    while ($pos != false) {

        $l = strpos($assignment->intro, "l=", $pos);

        if ($l != false) {
            $l = $l + 2;
            $e = strpos($assignment->intro, "\"", $l);
            if ($e != false) {
                if (!$first) {
                    $refs .= ",";
                } else {
                    $first = false;
                }
                $refs .= substr($assignment->intro, $l, $e - $l);
            }
        }
        $pos = strpos($assignment->intro, "/mod/helixmedia/launch.php", $pos + 1);
    }
    return $refs;
}

function helixmedia_curl_post_launch_html($params, $endpoint) {
    global $CFG;
    $modconfig = get_config("helixmedia");
    $params['oauth_consumer_key'] = $modconfig->consumer_key;

    set_time_limit(0);
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; curl; like Firefox)");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $cookiesfile = $CFG->dataroot.DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR."helixmedia-curl-cookies-".microtime(true).".tmp";
    while (file_exists($cookiesfile)) {
        $cookiesfile = $CFG->dataroot.DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR.
            "helixmedia-curl-cookies-".microtime(true).".tmp";
    }
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiesfile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiesfile);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

    // Uncomment this for verbose debugging
    // curl_setopt($ch, CURLOPT_VERBOSE, true);
    // $verbose = fopen('php://temp', 'rw+');
    // curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        notice("CURL Error connecting to HML LTI: ".curl_error($ch));
    }
    curl_close($ch);

    // Uncomment this for verbose debugging
    // if ($result === FALSE) {
    // printf("cUrl error (#%d): %s<br>\n", curl_errno($curlHandle),
    // htmlspecialchars(curl_error($curlHandle)));
    // }
    // rewind($verbose);
    // $verboseLog = stream_get_contents($verbose);
    // echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";

    if (file_exists($cookiesfile)) {
        unlink($cookiesfile);
    }

    return $result;
}

/**
 * This function builds the request that must be sent to the tool producer
 *
 * @param object    $instance       HML instance object
 * @param object    $typeconfig     HML tool configuration
 * @param object    $course         Course object
 * @param int       $type           The launch type
 * @param object    $user           User object if the launch isn't for the current user
 * @param boolean   $modtype          Set to true if we are in a modtype
 *
 * @return array    $request        Request details
 */
function helixmedia_build_request($instance, $typeconfig, $course, $type, $user = null, $modtype = "") {
    global $USER, $CFG;

    if ($user == null) {
        $user = $USER;
    }

    if (empty($instance->cmid)) {
        $instance->cmid = 0;
    }

    // We need to always use CRLF line endings for LTI, otherwise the signature validation will fail.
    // Moodle backup/restore sometimes converts the line endings to LF only.
    // The Moodle core code uses a straight str_replace of \n with \r\n
    // which won't cope properly with text where the line endings have been mixed up and \r only from the mac.
    $intro = html_to_text($instance->intro);
    $intro = preg_replace('/\r\n|\r|\n/', "\r\n", $intro);

    $role = helixmedia_get_ims_role($user, $instance->cmid, $course->id, $type, $modtype);

    $requestparams = array(
        'resource_link_id' => $instance->preid,
        'resource_link_title' => $instance->name,
        'resource_link_description' => substr($intro, 0, 1000),
        'user_id' => $user->id,
        'roles' => $role,
        'context_id' => $course->id,
        'context_label' => $course->shortname,
        'context_title' => $course->fullname,
        'launch_presentation_locale' => current_language()
    );

    $placementsecret = $instance->servicesalt;

    if (isset($placementsecret)) {
        $sourcedid = json_encode(lti_build_sourcedid($instance->id, $user->id, null, $placementsecret));
    }

    if (isset($placementsecret) &&
         ($typeconfig['acceptgrades'] == LTI_SETTING_ALWAYS ||
         ($typeconfig['acceptgrades'] == LTI_SETTING_DELEGATE && $instance->instructorchoiceacceptgrades == LTI_SETTING_ALWAYS ))) {
        $requestparams['lis_result_sourcedid'] = $sourcedid;

        if ($typeconfig['forcessl'] == '1') {
            $serviceurl = lti_ensure_url_is_https($serviceurl);
        }

        $requestparams['lis_outcome_service_url'] = $serviceurl;
    }

    // Send user's name and email data if appropriate.
    if ( $typeconfig['sendname'] == LTI_SETTING_ALWAYS ||
         ( $typeconfig['sendname'] == LTI_SETTING_DELEGATE && $instance->instructorchoicesendname == LTI_SETTING_ALWAYS ) ) {
        $requestparams['lis_person_name_given'] = $user->firstname;
        $requestparams['lis_person_name_family'] = $user->lastname;
        $requestparams['lis_person_name_full'] = $user->firstname." ".$user->lastname;
    }

    if ( $typeconfig['sendemailaddr'] == LTI_SETTING_ALWAYS ||
        ($typeconfig['sendemailaddr'] == LTI_SETTING_DELEGATE &&
        $instance->instructorchoicesendemailaddr == LTI_SETTING_ALWAYS ) ) {
        $requestparams['lis_person_contact_email_primary'] = $user->email;
    }

    // Concatenate the custom parameters from the administrator and the instructor
    // Instructor parameters are only taken into consideration if the administrator
    // has given permission.
    $customstr = $typeconfig['customparameters'];

    $instructorcustomstr = "";
    $custom = array();
    $instructorcustom = array();
    if ($customstr) {
        $custom = helix_split_custom_parameters($customstr);
    }

    if (isset($typeconfig['allowinstructorcustom']) && $typeconfig['allowinstructorcustom'] == LTI_SETTING_NEVER) {
        $requestparams = array_merge($custom, $requestparams);
    } else {
        if ($instructorcustomstr) {
            $instructorcustom = helix_split_custom_parameters($instructorcustomstr);
        }
        foreach ($instructorcustom as $key => $val) {
            // Ignore the instructor's parameter.
            if (!array_key_exists($key, $custom)) {
                $custom[$key] = $val;
            }
        }
        $requestparams = array_merge($custom, $requestparams);
    }

    // Make sure we let the tool know what LMS they are being called from.
    $requestparams["ext_lms"] = "moodle-2";
    $requestparams['tool_consumer_info_product_family_code'] = 'moodle';
    $requestparams['tool_consumer_info_version'] = strval($CFG->version);

    // Add oauth_callback to be compliant with the 1.0A spec.
    $requestparams['oauth_callback'] = 'about:blank';

    // The submit button needs to be part of the signature as it gets posted with the form.
    // This needs to be here to support launching without javascript.

    // Moodle 2.8 strips this parameter out when the launch form is submitted, so if we add it here,
    // it will be included in the signature and the signature verification will fail on the remote server.
    // However, Moodle 2.7 and lower always submits this, so it must be processed as part of the signature.
    if ($CFG->version < 2014111000) {
        $submittext = get_string('press_to_submit', 'lti');
        $requestparams['ext_submit'] = $submittext;
    }

    $requestparams['lti_version'] = 'LTI-1p0';
    $requestparams['lti_message_type'] = 'hml-launch-request';

    return $requestparams;
}

/**
 * Splits the custom parameters field to the various parameters
 *
 * @param string $customstr     String containing the parameters
 *
 * @return Array of custom parameters
 */
function helix_split_custom_parameters($customstr) {
    $lines = preg_split("/[\n;]/", $customstr);
    $retval = array();
    foreach ($lines as $line) {
        $pos = strpos($line, "=");
        if ( $pos === false || $pos < 1 ) {
            continue;
        }
        $key = trim(core_text::substr($line, 0, $pos));
        $val = trim(core_text::substr($line, $pos + 1, strlen($line)));
        $key = lti_map_keyname($key);
        $retval['custom_'.$key] = $val;
    }
    return $retval;
}

/**
 * Gets the IMS role string for the specified user and Helixmedia course module.
 *
 * @param mixed $user User object or user id
 * @param int $cmid The course module id of the LTI activity
 * @param int $courseid The course id
 * @param int $type The launch type
 * @param boolean $modtype Set to true if we are in a modtype
 *
 * @return string A role string suitable for passing with an LTI launch
 */
function helixmedia_get_ims_role($user, $cmid, $courseid, $type, $modtype) {
    $roles = array();

    // Always use the Learner role if this is a student submission or a view operation, MEDIAL expects this.
    if ($type == HML_LAUNCH_NORMAL ||
        $type == HML_LAUNCH_TINYMCE_VIEW ||
        $type == HML_LAUNCH_ATTO_VIEW ||
        $type == HML_LAUNCH_STUDENT_SUBMIT ||
        $type == HML_LAUNCH_STUDENT_SUBMIT_PREVIEW ||
        $type == HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS) {
        return "Learner";
    }

    if (empty($cmid) || $cmid == -1) {
        // If no cmid is passed, check if the user is a teacher in the course
        // This allows other modules to programmatically "fake" a launch without
        // a real Helixmedia instance.
        $coursecontext = context_course::instance($courseid);

        $cap = helixmedia_get_visiblecap($modtype);

        if (has_capability($cap, $coursecontext)) {
            array_push($roles, 'Instructor');
        } else {
            if (has_capability('moodle/course:manageactivities', $coursecontext)) {
                array_push($roles, 'Instructor');
            } else {
                array_push($roles, 'Learner');
            }
        }
    } else {
        $context = context_module::instance($cmid);

        if (has_capability('mod/helixmedia:manage', $context)) {
            array_push($roles, 'Instructor');
        } else {
            array_push($roles, 'Learner');
        }
    }

    if (is_siteadmin($user)) {
        array_push($roles, 'urn:lti:sysrole:ims/lis/Administrator');
    }

    return join(',', $roles);
}

/**
 * Checks the moduletype we are viewing here to see if we can use the more permissive modtype permission
 * @param $modtype The module type
 * @return The permission to use
 **/
function helixmedia_get_visiblecap($modtype = false) {
    if (!$modtype) {
        return 'atto/helixatto:visible';
    }

    global $DB;
    $config = get_config('atto_helixatto', 'modtypeperm');
    $types = explode("\n", $config);

    for ($i = 0; $i < count($types); $i++) {
        $types[$i] = trim($types[$i]);
        if (strlen($types[$i]) > 0 && $types[$i] == $modtype && $DB->get_record('modules', array('name' => $types[$i]))) {
            return 'atto/helixatto:visiblemodtype';
        }
    }

    return 'atto/helixatto:visible';
}

/**
 * Gets the modal dialog using the supplied params
 * @param pre_id The resource link ID
 * @param params_thumb The get request parameters for the thumbnail
 * @param params_link The get request parameters for the modal link
 * @param style An optional style for the containing table
 * @param linkimage Optional link image file name
 * @param linkimagewidth The width of the link image in px, -1 for none
 * @param linkimageheight The height of the link image in px, -1 for none
 * @param c The course ID, or -1 if not known
 * @param statusCheck true if the statusCheck method should be used
 * @param splitline true If the view button should be below the thumbnail
 * @return The HTML for the dialog
 **/
function helixmedia_get_modal_dialog($preid, $paramsthumb, $paramslink, $style = "",
    $linkimage = "", $linkimagewidth = "", $linkimageheight = "", $c = -1, $statuscheck = "true", $splitline = false) {
    global $CFG, $PAGE, $COURSE, $DB, $USER;

    if ($linkimage == "") {
        $linkimage = "moodle-lti-upload-btn.png";
    }

    if ($linkimagewidth == "") {
        $linkimagewidth = "202";
    }

    if ($linkimageheight == "") {
        $linkimageheight = "56";
    }

    if (!$statuscheck) {
        $statuscheck = "false";
    }

    if ($c > -1) {
        $course = $DB->get_record("course", array("id" => $c));
    } else {
        $course = $COURSE;
    }
    $paramsthumb = 'course='.$course->id.'&'.$paramsthumb;
    $paramslink = 'course='.$course->id.'&ret='.base64_encode(curpageurl()).'&'.$paramslink;

    if ($statuscheck != "true") {
        $frameid = "thumbframeview";
    } else {
        $frameid = "thumbframe";
    }

    if ($linkimagewidth < 0 && $linkimagewidth < 0) {
        $html = '<a class="pop_up_selector_link" href="'.$CFG->wwwroot.'/mod/helixmedia/container.php?'.
            htmlspecialchars($paramslink).'">'.$linkimage.'</a>';
    } else {
        $launchurl = get_config("helixmedia", "launchurl");
        $allow = 'allow="microphone '.$launchurl.'; camera '.$launchurl.'"';
        if ($splitline) {
            $html = '<table style="'.$style.'"><tr><td>'.
                '<iframe id="'.$frameid.'" style="border-width:0px;width:200px;height:128px;" scrolling="no" frameborder="0" '.
                'src="'.$CFG->wwwroot.'/mod/helixmedia/launch.php?'.htmlspecialchars($paramsthumb).'" '.$allow.'></iframe>'.
                '</td></tr><tr><td style="vertical-align:top;margin-top:0px;">'.
                '<a class="pop_up_selector_link" href="'.$CFG->wwwroot.'/mod/helixmedia/container.php?'.
                htmlspecialchars($paramslink).'">'.
                '<img src="'.$CFG->wwwroot.'/mod/helixmedia/icons/'.$linkimage.'" width="'.$linkimagewidth.'" height="'.
                $linkimageheight.'" alt="'.
                get_string('choosemedia_title', 'helixmedia').'" title="" /></a>'.
                '</td></tr></table>';
        } else {
            $html = '<div style="display:flex;flex-wrap:wrap;'.$style.'"><div style="order:0;">'.
                '<iframe id="'.$frameid.'" style="border-width:0px;width:200px;height:128px;" scrolling="no" frameborder="0" '.
                'src="'.$CFG->wwwroot.'/mod/helixmedia/launch.php?'.htmlspecialchars($paramsthumb).'" '.$allow.'></iframe>'.
                '</div><div style="order:1;">'.
                '<a class="pop_up_selector_link" href="'.$CFG->wwwroot.'/mod/helixmedia/container.php?'.
                htmlspecialchars($paramslink).'">'.
                '<img src="'.$CFG->wwwroot.'/mod/helixmedia/icons/'.$linkimage.'" alt="'.
                get_string('choosemedia_title', 'helixmedia').'" title="" '.
                'style="width:'.$linkimagewidth.';height:'.$linkimageheight.';margin-top:50px;" /></a>'.
                '</div></div>';
        }
    }
    $modconfig = get_config("helixmedia");

    $html .= '<script type="text/javascript">'.
        'var thumburl="'.$CFG->wwwroot.'/mod/helixmedia/launch.php?'.$paramsthumb.'";'.
        'var resID='.$preid.';'.
        'var userID='.$USER->id.';'.
        'var statusURL="'.helixmedia_get_status_url().'";'.
        'var oauthConsumerKey = "'.$modconfig->consumer_key.'";'.
        'var doStatusCheck='.$statuscheck.';'.
        '</script>'.
        '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/helixmedia/hml_form_js.php"></script>';

    return $html;
}

function curpageurl() {
    $pageurl = 'http';
    if (array_key_exists("HTTPS", $_SERVER) && $_SERVER["HTTPS"] == "on") {
        $pageurl .= "s";
    }

    $pageurl .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageurl .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
        $pageurl .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageurl;
}

function helixmedia_get_instance_size($preid, $course) {
    global $CFG;
    $url = trim(get_config("helixmedia", "launchurl"));
    $pos = str_contains(strtolower($url), "/launch", true);
    $url = substr($url, 0, $pos)."PlayerWidth";
    $retdata = helixmedia_curl_post_launch_html(array("context_id" => $course, "resource_link_id" => $preid,
        "include_height" => "Y"), $url);

    $parts = explode(":", $retdata);
    // If there is more than one part, then MEDIAL undersatnds the include_height param.
    if (count($parts) > 1) {
        $vals = new stdclass();
        $vals->width = intval($parts[0]);
        $vals->height = intval($parts[1]);
        if (count($parts) > 1 && $parts[2] == 'Y') {
            $vals->audioonly = true;
        } else {
            $vals->audioonly = false;
        }
        return $vals;
    }

    // Old version of MEDIAL, return standard data.
    $vals = new stdclass();
    $vals->width = intval($retdata);
    $vals->height = -1;
    $vals->audioonly = false;
    return $vals;
}

function helixmedia_get_status_url() {
    return helixmedia_get_alturl("SessionStatus");
}

function helixmedia_get_upload_url() {
    return helixmedia_get_alturl("UploadStatus");
}

function helixmedia_get_alturl($alt) {
    $statusurl = trim(get_config("helixmedia", "launchurl"));
    $pos = str_contains(strtolower($statusurl), "/launch", true);
    return substr($statusurl, 0, $pos).$alt;
}

function helixmedia_is_preid_empty($preid, $as, $userid) {
    global $CFG;

    $retdata = helixmedia_curl_post_launch_html(array("resource_link_id" => $preid, "user_id" => $userid),
        helixmedia_get_upload_url());

    // We got a 404, the MEDIAL server doesn't support this call, so return false.
    // The old method was to check for the presence of a resource link ID so this is consistent.
    if (strpos($retdata, "HTTP 404") > 0) {
        return false;
    }

    if ($retdata == "Y") {
        return false;
    }

    return true;
}


function str_contains($haystack, $needle, $ignorecase = false) {
    if ($ignorecase) {
        $haystack = strtolower($haystack);
        $needle = strtolower($needle);
    }
    $needlepos = strpos($haystack, $needle);
    return ($needlepos === false ? false : ($needlepos + 1));
}


function helixmedia_version_check() {
    $statusurl = trim(get_config("helixmedia", "launchurl"));
    if (strlen($statusurl) == 0) {
        return "<p>".get_string("version_check_not_done", "helixmedia")."</p>";
    }
    $pos = str_contains(strtolower($statusurl), "/lti/launch", true);
    $endpoint = substr($statusurl, 0, $pos)."/version.txt";

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; curl; like Firefox)");
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = trim(curl_exec($ch));
    if (curl_errno($ch)) {
        notice("CURL Error connecting to MEDIAL: ". curl_error($ch));
        return "<p>".get_string("version_check_fail", "helixmedia")."</p>";
    }
    curl_close($ch);

    $v = new stdclass();
    $v->min = MEDIAL_MIN_VERSION;
    $v->actual = $result;
    $message = "<p>".get_string('version_check_message', 'helixmedia', $v)."</p>";

    $reqver = parse_medial_version(MEDIAL_MIN_VERSION);
    $actualver = parse_medial_version($result);

    if ($actualver < $reqver) {
        $message .= "<p class='warning'>".get_string('version_check_upgrade', 'helixmedia')."</p>";
    }

    return $message."<br />";
}

function parse_medial_version($str) {
    $parts = explode('.', $str);
    $concat = '';
    for ($loop = 0; $loop < count($parts); $loop++) {
        $concat .= $parts[$loop];
    }
    return intval($concat);
}
