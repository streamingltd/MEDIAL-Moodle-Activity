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


namespace mod_helixmedia\output;
defined('MOODLE_INTERNAL') || die();

/**
 * Search form renderable.
 *
 * @package    mod_helixmedia
 * @copyright  2021 Tim Williams <tmw@autotrain.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/mod/helixmedia/locallib.php');

use renderable;
use renderer_base;
use templatable;
use moodle_url;


/**
 * Container renderable class.
 *
 * @package    mod_helixmedia
 * @copyright  2021 Tim Williams <tmw@autotrain.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modal implements renderable, templatable {

    /**
     * Gets the modal dialog using the supplied params
     * @param pre_id The resource link ID
     * @param params_thumb The get request parameters for the thumbnail as an array
     * @param params_link The get request parameters for the modal link as an array
     * @param type The image to use for the button, false for no icon
     * @param text The text to use for the button (if no icon) and frame title
     * @param c The course ID, or -1 if not known
     * @param statusCheck true if the statusCheck method should be used
     * @param flex Flex type for display. Row for side by side and column for vertical
     * @param extraid An extra ID item to append on the div id
     * @return The HTML for the dialog
     **/
    public function __construct($preid, $paramsthumb, $paramslink, $image,
        $text = false, $c = false, $statuscheck = true, $flextype = 'row', $extraid = false) {
        global $CFG, $COURSE, $DB, $USER;

        if (!$text) {
            $text = get_string('choosemedia_title', 'helixmedia');
        }

        $this->preid = $preid;
        $this->text = $text;
        $this->flextype = $flextype;
        if ($extraid !== false) {
            $this->extraid = '_'.$extraid;
        } else {
            $this->extraid = '';
        }
        if ($c !== false) {
            $course = $DB->get_record("course", array("id" => $c));
        } else {
            $course = $COURSE;
        }

        $paramsthumb['course'] = $course->id;
        $paramslink['course'] = $course->id;
        $paramslink['ret'] = base64_encode(curpageurl());

        $this->thumblaunchurl = new moodle_url('/mod/helixmedia/launch.php', $paramsthumb);
        $this->thumblaunchurl = $this->thumblaunchurl->out(false);
        $launchurl = new moodle_url('/mod/helixmedia/launch.php', $paramslink);
        $launchurl = $launchurl->out(false);
        if ($image ) {
            $this->imgurl = new moodle_url('/mod/helixmedia/icons/'.$image);
            $this->imgurl = $this->imgurl->out(false);
        } else {
            $this->imgurl = false;
        }
        if ($statuscheck != "true") {
            $this->frameid = "thumbframeview";
        } else {
            $this->frameid = "thumbframe";
        }

        $modconfig = get_config("helixmedia");
        $this->jsparams = array(
            $this->frameid,
            $launchurl,
            $this->thumblaunchurl,
            $preid,
            $USER->id,
            helixmedia_get_status_url(),
            $modconfig->consumer_key,
            $statuscheck,
            $CFG->wwwroot."/mod/helixmedia/session.php",
            ($CFG->sessiontimeout / 2) * 1000,
            intval($modconfig->modal_delay),
            $this->extraid
        );
    }

    public function inc_js() {
        global $PAGE;
        $PAGE->requires->js_call_amd('mod_helixmedia/module', 'init', $this->jsparams);
    }


    public function export_for_template(renderer_base $output) {
        global $CFG, $PAGE;

        $data = [
            'thumblaunchurl' => $this->thumblaunchurl,
            'medialurl' => get_config("helixmedia", "launchurl"),
            'imgurl' => $this->imgurl,
            'preid' => $this->preid,
            'text' => $this->text,
            'frameid' => $this->frameid,
            'flextype' => $this->flextype,
            'extraid' => $this->extraid
        ];
        return $data;
    }
}
