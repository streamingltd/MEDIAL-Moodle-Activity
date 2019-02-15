<?php

/**
 * This file contains en_utf8 translation of the Media Library module
 *
 * @package    mod
 * @subpackage helixmedia
 * @copyright  Streaming LTD
 * @author     Tim Williams (for Streaming LTD)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['choosemedia_title'] = 'Choose Media';
$string['consumer_key'] = 'MEDIAL Consumer Key';
$string['consumer_key2'] = 'The consumer key used for access to the MEDIAL LTI server.';
$string['forcedebug'] = 'Force LTI Launch Debug mode on';
$string['forcedebug_help'] = 'Allows you to debug LTI launch problems by showing the launch parameters.';
$string['helixmedia:addinstance'] = 'Add a new MEDIAL Resource';
$string['helixmedia:view'] = 'View MEDIAL Resources';
$string['helixmedia:manage'] = 'Manage MEDIAL Resources';
$string['helixmediasummary'] = 'Summary';
$string['helixmediatext'] = 'Activity name';
$string['hml_in_new_window'] = 'Open MEDIAL Resource';
$string['hml_in_new_window_message'] = "If a new window doesn't open automatically containing the resource you wish to view, please use the link below to open it.";
$string['invalid_launch'] = 'Invalid parameters supplied for MEDIAL LTI launch request. Aborting.';
$string['launch_url'] = 'MEDIAL LTI Launch URL';
$string['launch_url2'] = 'Put the LTI launch URL of the MEDIAL server here. This should be a URL in the format: https://upload.mymedialserver.org/Lti/Launch';
$string['log_launch'] = 'MEDIAL LTI Launch';
$string['log_launchedit'] = 'MEDIAL LTI Edit Launch';
$string['log_launcheditnew'] = 'MEDIAL LTI New Instance Edit Launch';
$string['migrate_do_button'] = 'Migrate Selected Content';
$string['migrate_found'] = 'All the URL modules linked to active MEDIAL Repository instances are listed below:';
$string['migrate_from_course'] = 'from course';
$string['migrate_finished'] = 'The migration process has finished. You can now exit this page.';
$string['migrate_nothing_found'] = 'No URL module instances linking to the Media Library have been found, all modules appear to have been migated.';
$string['migrate_not_found'] = 'The following URL modules are linked to inactive MEDIAL instances:';
$string['migrate_not_found_2'] = 'Note: This will have been caused by the removal/re-installation of the repository module.';
$string['migrate_not_found_3'] = 'The above content can still be migrated to the new activity module, but this process will only work if the currently installed repository module is still connecting to the same MEDIAL installation which was used by the earlier inactive installations.';
$string['migrate_no_repo_mod'] = 'WARNING: The MEDIAL Repository module is not installed, this is necessary in order to perform the migration process. Please install and configure the Repository module first.';
$string['migrate_url_instance'] = 'Migrating URL instance:';
$string['migrate_vid_not_found'] = 'The linked video cannot be found. URL instance not migrated.';
$string['migrate_ref_invalid'] = 'The linked video has an invalid reference ID. URL instance not migrated.';
$string['modulename'] = 'MEDIAL';
$string['modal_delay'] = 'Video add dialog box close delay in seconds';
$string['modal_delay2'] = 'By default the modal dialogue box used to add videos will automatically close once the video has been chosen. You can optionally delay the closing of this dialogue by the number of seconds specified here, or disable the auto-close by setting this value to -1. Please note, this setting will have no effect on the modal dialogs used by the plugins for the TinyMCE and ATTO editors which will continue to remain open until closed by the user.';
$string['not_authorised'] = 'You are not authorised to perform this MEDIAL operation.';
$string['pluginname'] = 'MEDIAL';
$string['pluginadministration'] = 'MEDIAL';
$string['modulename_help'] = 'The MEDIAL module provides a customised LTI based Moodle for the integration of MEDIAL server into Moodle';
$string['modulename_link'] = 'mod/helixmedia/view';
$string['modulenameplural'] = 'MEDIAL';
$string['modulenamepluralformatted'] = 'MEDIAL Instances';
$string['moodlemobile'] = 'MEDIAL videos embedded in other activity modules are not available via MoodleMobile. Please use the open in browser option to view this video.';
$string['nohelixmedias'] = 'No MEDIAL Instances found.';
$string['org_id'] = 'Organisation ID';
$string['org_id2'] = 'The organisation ID or name which will be sent to the MEDIAL server. The URL of your Moodle installation will be sent if this is left blank.';
$string['repo_migrate_message'] = 'The repository module migration tool will convert content that was created using the MEDIAL repository module so that it uses this activity module instead. Use the link below to start the migration process.';
$string['repo_migrate_link'] = 'Open the Repository Module Migration Tool';
$string['repo_migrate_title'] = 'Repository Module Migration';
$string['restrictdebug'] = 'Restrict the LTI debugging information to admin users.';
$string['restrictdebug_help'] = 'If forced LTI debugging is on, enabling this will restrict the forced debug mode to admins so that you can debug live systems without confusing users.';
$string['shared_secret'] = 'MEDIAL Shared Secret';
$string['shared_secret2'] = 'The shared secret used for comunications between Moodle and the MEDIAL server via LTI.';
$string['search:activity'] = 'MEDIAL Activity Videos';
$string['lti_settings_title'] = 'MEDIAL LTI Settings';
$string['version_check_message'] = 'This version of the MEDIAL plugin is reccomended for MEDIAL versions {$a->min} or better. You are using MEDIAL version {$a->actual}.';
$string['version_check_upgrade'] = 'WARNING: You are recomended to upgrade your MEDIAL version for use with this plugin.';
$string['version_check_not_done'] = 'The MEDIAL activity has not been configured, version check skipped.';
$string['version_check_fail'] = 'The MEDIAL server version could not be retrived. Please check the MEDIAL activity module is correctly configured.';
$string['version_check_title'] = 'MEDIAL Version Check';
