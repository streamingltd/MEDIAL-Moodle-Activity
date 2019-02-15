<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html  dir="ltr" lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head><title>HML Container</title>
</head>
<body>
<?php

/**
 * This page acts as a container for the launch code
 *
 * @package    mod
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/helixmedia/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$aid = optional_param('aid', 0, PARAM_INT);
$l  = optional_param('l', 0, PARAM_INT);  // HML ID
$c  = optional_param('course', -1, PARAM_INT);
$w  = optional_param('w', 1000, PARAM_INT);
$h  = optional_param('h', 600, PARAM_INT);
$ret  = optional_param('ret', "", PARAM_TEXT);
$n_assign = optional_param('n_assign', 0, PARAM_INT);
$e_assign = optional_param('e_assign', 0, PARAM_INT);
$n_feed = optional_param('n_feed', 0, PARAM_INT);
$e_feed = optional_param('e_feed', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$type = optional_param('type', -1, PARAM_INT);
$name  = optional_param('name', "", PARAM_TEXT);
$intro  = optional_param('intro', "", PARAM_TEXT);

if ($l>0)
    $param="l=".$l;
else
if ($id>0)
    $param="id=".$id;
else
if ($n_assign>0)
    $param="n_assign=".$n_assign."&aid=".$aid;
else
if ($e_assign>0)
    $param="e_assign=".$e_assign;
else
if ($n_feed>0)
    $param="n_feed=".$n_feed."&aid=".$aid;
else
if ($e_feed>0)
    $param="e_feed=".$e_feed;

if ($userid>0)
    $param="userid=".$userid."&amp;".$param;

if ($type>0)
    $param="type=".$type."&amp;".$param;

if (strlen($ret)>0)
    $param=$param."&amp;ret=".$ret;

if (strlen($name)>0)
    $param=$param."&amp;name=".$name;

if (strlen($intro)>0)
    $param=$param."&amp;intro=".$intro;

$h=$h-32;
$w=$w-14;

$launch_url = get_config("helixmedia", "launchurl");
$allow = 'allow="microphone '.$launch_url.'; camera '.$launch_url.'"';

echo '<iframe style="margin-left:7px;margin-top:25px;border:0px;background:#ffffff;" width="'.$w.'" height="'.$h.'" '.
   'src="'.$CFG->wwwroot.'/mod/helixmedia/launch.php?course='.$c.'&amp;'.$param.'" '.$allow.'></iframe>';

?>
</body>
</html>