<?php
/**
 * This page contains the global config for the HML activity
 *
 * @package    mod
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot.'/mod/helixmedia/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');


$settings->add(new admin_setting_configtext('helixmedia/launchurl', get_string("launch_url", "helixmedia"),
                   get_string("launch_url2", "helixmedia"), "", PARAM_URL));

$settings->add(new admin_setting_configtext('helixmedia/consumer_key', get_string("consumer_key", "helixmedia"),
                   get_string("consumer_key2", "helixmedia"), "", PARAM_TEXT));

$settings->add(new admin_setting_configpasswordunmask('helixmedia/shared_secret', get_string("shared_secret", "helixmedia"),
                   get_string("shared_secret2", "helixmedia"), "", PARAM_TEXT));

$settings->add(new admin_setting_configtext('helixmedia/org_id', get_string("org_id", "helixmedia"),
                   get_string("org_id2", "helixmedia"), "", PARAM_TEXT));

$launchoptions=array();
$launchoptions[LTI_LAUNCH_CONTAINER_EMBED] = get_string('embed', 'lti');
$launchoptions[LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS] = get_string('embed_no_blocks', 'lti');
$launchoptions[LTI_LAUNCH_CONTAINER_WINDOW] = get_string('new_window', 'lti');

$settings->add(new admin_setting_configselect('helixmedia/default_launch', get_string('default_launch_container', 'lti'),
                   "", LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS, $launchoptions));

$options=array();
$options[0] = get_string('never', 'lti');
$options[1] = get_string('always', 'lti');

$settings->add(new admin_setting_configselect('helixmedia/sendname', get_string('share_name_admin', 'lti'),
                   "", '1', $options));

$settings->add(new admin_setting_configselect('helixmedia/sendemailaddr', get_string('share_email_admin', 'lti'),
                   "", '1', $options));

$settings->add(new admin_setting_configtextarea('helixmedia/custom_params', get_string('custom', 'lti'),
                   "", "", PARAM_TEXT));

$settings->add(new admin_setting_configtext('helixmedia/modal_delay', get_string("modal_delay", "helixmedia"),
                   get_string("modal_delay2", "helixmedia"), 0, PARAM_INT));

$settings->add(new admin_setting_heading('helixmedia/repo_migrate', get_string("repo_migrate_title", "mod_helixmedia"),
  "<p>".get_string("repo_migrate_message", "mod_helixmedia")."</p>".
  "<p style='text-align:center;font-weight:bold;'>".
  "<a href='".$CFG->wwwroot."/mod/helixmedia/migrate.php'>".get_string("repo_migrate_link", "mod_helixmedia")."</a></p>"));

?>
