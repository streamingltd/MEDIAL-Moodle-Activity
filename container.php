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

require_once("../../config.php");
require_login();
?>
<!DOCTYPE html>
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

require_once($CFG->dirroot.'/mod/helixmedia/locallib.php');

$id = optional_param('id', 0, PARAM_INT);
$aid = optional_param('aid', 0, PARAM_INT);
$l = optional_param('l', 0, PARAM_INT);
$c = optional_param('course', -1, PARAM_INT);
$w = optional_param('w', 1000, PARAM_INT);
$h = optional_param('h', 600, PARAM_INT);
$ret = optional_param('ret', "", PARAM_TEXT);
$nassign = optional_param('n_assign', 0, PARAM_INT);
$eassign = optional_param('e_assign', 0, PARAM_INT);
$nfeed = optional_param('n_feed', 0, PARAM_INT);
$efeed = optional_param('e_feed', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$type = optional_param('type', -1, PARAM_INT);
$name = optional_param('name', "", PARAM_TEXT);
$intro = optional_param('intro', "", PARAM_TEXT);
$modtype = optional_param('modtype', "", PARAM_ALPHANUM);

if ($l > 0) {
    $param = "l=".$l;
} else {
    if ($id > 0) {
        $param = "id=".$id;
    } else {
        if ($nassign > 0) {
            $param = "n_assign=".$nassign."&aid=".$aid;
        } else {
            if ($eassign > 0) {
                $param = "e_assign=".$eassign;
            } else {
                if ($nfeed > 0) {
                    $param = "n_feed=".$nfeed."&aid=".$aid;
                } else {
                    if ($efeed > 0) {
                        $param = "e_feed=".$efeed;
                    }
                }
            }
        }
    }
}

if ($userid > 0) {
    $param = "userid=".$userid."&amp;".$param;
}
if ($type > 0) {
    $param = "type=".$type."&amp;".$param;
}
if (strlen($ret) > 0) {
    $param = $param."&amp;ret=".$ret;
}
if (strlen($name) > 0) {
    $param = $param."&amp;name=".$name;
}
if (strlen($intro) > 0) {
    $param = $param."&amp;intro=".$intro;
}
if (strlen($modtype) > 0) {
    $param = $param."&amp;modtype=".$modtype;
}

$h = $h - 32;
$w = $w - 14;

$launchurl = get_config("helixmedia", "launchurl");
$allow = 'allow="microphone '.$launchurl.'; camera '.$launchurl.'"';

echo '<iframe style="margin-left:7px;margin-top:25px;border:0px;background:#ffffff;" width="'.$w.'" height="'.$h.'" '.
   'src="'.$CFG->wwwroot.'/mod/helixmedia/launch.php?course='.$c.'&amp;'.$param.'" '.$allow.'></iframe>';

?>
</body>
</html>
