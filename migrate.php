<?php
    /**
    * Migration code for the repository module
    * @author     Tim Williams (tmw@autotrain.org) for Streaming LTD
    * @package    mod
    * @subpackage helixmedia
    * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
    **/

    require_once('../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once($CFG->dirroot.'/mod/helixmedia/lib.php');
    require_once($CFG->dirroot.'/mod/helixmedia/locallib.php');
    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->dirroot.'/repository/lib.php');

    /**Not having this causes a fatal crash, even though it's not needed here**/
    require_once($CFG->dirroot.'/enrol/imsenterprise/locallib.php'); 

    global $OUTPUT, $PAGE;

    require_login();

    $context = get_context_instance(CONTEXT_SYSTEM);
    $PAGE->set_context($context);
    $PAGE->set_url($CFG->wwwroot."/mod/helixmedia/migrate.php");

    admin_externalpage_setup('managemodules');
    echo $OUTPUT->header();

    if (!has_capability('moodle/site:config', $context))
    {
        echo "<div class='errorbox'><p class='error'>".get_string("not_authorised", "mod_helixmedia")."</p></div>";
    }
    else
    if (!file_exists($CFG->dirroot.'/repository/helix_media_lib/helixlib.php'))
    {
        echo "<div class='errorbox'><p class='error'>".get_string("migrate_no_repo_mod", "mod_helixmedia")."</p></div>";
    }
    else
    {
        require_once($CFG->dirroot.'/repository/helix_media_lib/helixlib.php');

        $process=optional_param("migrate", 0, PARAM_BOOL);
        if ($process)
            migrate_content();
        else
            show_content_migration();
    }
    /**All Done**/
    echo $OUTPUT->footer();

    /*********************************************************************************************************************/

    /**
    * Performs the content migration
    **/

    function migrate_content()
    {
        global $DB;
        $mod=$DB->get_record("modules", array("name"=>"helixmedia"));

        $flist=optional_param("flist", "", PARAM_TEXT);
        $nlist=optional_param("nlist", "", PARAM_TEXT);
        echo "<pre>";
        if (strlen($flist)>0)
            process_ids(explode(",",  $flist), "fid-", $mod->id);

        if (strlen($nlist)>0)
            process_ids(explode(",", $nlist), "nid-", $mod->id);
        echo get_string("migrate_finished", "mod_helixmedia");
        echo "</pre>";
    }

    /**
    * Process a list of URL ids and migrate the content
    * @param $allids The id list
    * @param $pre The string added to the start of the ID in the POST params
    * @param $mod_id The helix media mod id
    **/

    function process_ids($allids, $pre, $mod_id)
    {
        global $DB;
        $url_type=$DB->get_record("modules", array("name"=>"url"));
        foreach($allids as $id)
        {
            $process=optional_param($pre.$id, 0, PARAM_BOOL);
            if ($process)
                process_item($id, $mod_id, $url_type->id);
        }
    }

    /**
    * Process a specific URL module
    * @param $id The id of the module to process
    * @param $mod_id The helix media mod id
    * @param $url_type_id The id of the URL mod in the modules database
    **/

    function process_item($id, $mod_id, $url_type_id)
    {
        global $DB;
        $url=$DB->get_record("url", array("id"=>$id));
        $course=$DB->get_record("course", array("id"=>$url->course));
        $cm=$DB->get_record("course_modules", array("instance"=>$url->id, "course"=>$course->id, "module"=>$url_type_id));

        /**Tell the user what we are migrating**/
        echo get_string('migrate_url_instance', 'mod_helixmedia')." '".$url->name."' (id=".$url->id.") ".
             get_string('migrate_from_course', 'mod_helixmedia')." '".$course->shortname."'.\n";

        /**Add the new Helixmedia mod**/
        if (add_new_helixmedia_instance($url, $cm, $course, $mod_id))
        {
         /**Remove the old URL mod**/
         delete_old_url_instance($cm, $course);
        }
    }

    function add_new_helixmedia_instance($url, $oldcm, $course, $mod_id)
    {
        global $DB, $USER, $CFG;

        /**Find the video ref ID of the old video. It's the 8 digits in the file name returned by the HML API**/
        $mid=get_param($url->externalurl, "mid");
        $rid=get_param($url->externalurl, "rid");
        $repo=repository::get_instance($rid);

        $response=$repo->hml_soap->get_media_with_token($mid, $_SERVER['REMOTE_ADDR']);

        if ($response->details["videoid"]==0)
        {
            echo get_string('migrate_vid_not_found', 'mod_helixmedia')."\n";
            return false;
        }

        $pos=strpos($response->details['filename'], "_lo");
        if ($pos>-1)
            $ref_id=substr($response->details['filename'], 0, $pos);
        else
        {
            echo get_string('migrate_ref_invalid', 'mod_helixmedia')."\n";
            return false;
        }

        /**Add the new HML Activity instance**/
        $helixmedia=new stdclass;
        $helixmedia->preid=helixmedia_preallocate_id();
        $helixmedia->course=$course->id;
        $helixmedia->name=$url->name;
        $helixmedia->intro=$url->intro;
        $helixmedia->introformat=$url->introformat;
        $helixmedia->launchcontainer=LTI_LAUNCH_CONTAINER_DEFAULT;
        $helixmedia->debuglaunch=0;
        $helixmedia->id=helixmedia_add_instance($helixmedia, new stdclass);

        /**Do the course module stuff**/
        /**This needs to be identical to the old one, but with the module and instance changed and ID unset**/
        $cm=clone $oldcm;
        unset($cm->id);
        $cm->instance=$helixmedia->id;
        $cm->module=$mod_id;
        $cm->added=time();
        $id=$DB->insert_record("course_modules", $cm);

        /**Now sort out the section placement**/
        $section=$DB->get_record("course_sections", array("id"=>$oldcm->section));
        $pos=strpos($section->sequence, $oldcm->id);
        $ns=substr($section->sequence, 0, $pos).$id;
        $end=substr($section->sequence, $pos);
        if (strlen($end)>0)
            $ns.=",".$end;
        $section->sequence=$ns;
        $DB->update_record("course_sections", $section);

        /**mod_created event**/
        $eventdata = new stdClass();
        $eventdata->modulename = "helixmedia";
        $eventdata->name       = $helixmedia->name;
        $eventdata->cmid       = $id;
        $eventdata->courseid   = $course->id;
        $eventdata->userid     = $USER->id;
        events_trigger('mod_created', $eventdata);

        rebuild_course_cache($course->id);

        /**Link the new mod to the old resource ID**/
        helixmedia_view_mod($helixmedia, HML_LAUNCH_RELINK, $ref_id)."\n";
        //echo "<iframe src='".$CFG->wwwroot."/mod/helixmedia/launch.php?type=".HML_LAUNCH_EDIT."&amp;id=".$id."&amp;mid=".$ref_id."' width='600' height='300'></iframe>\n";
        return true;
    }

    /**
    * Deletes the URL module
    * @param $cm The URL mod course module instance to delete
    * @param $course The course which contains this mod
    **/

    function delete_old_url_instance($cm, $course)
    {
        global $CFG, $USER;
        require_once($CFG->dirroot."/mod/url/lib.php");

        if (!url_delete_instance($cm->instance)) {
            echo "Could not delete the $cm->modname (instance)\n";
        }

        // Trigger a mod_deleted event with information about this module.
        $eventdata = new stdClass();
        $eventdata->modulename = "url";
        $eventdata->cmid       = $cm->id;
        $eventdata->courseid   = $course->id;
        $eventdata->userid     = $USER->id;
        events_trigger('mod_deleted', $eventdata);

        if (!delete_course_module($cm->id)) {
            echo "Could not delete the $cm->modname (coursemodule)\n";
        }
        if (!delete_mod_from_section($cm->id, $cm->section)) {
            echo "Could not delete the $cm->modname from that section\n";
        }

        rebuild_course_cache($course->id);
    }

    /**
    * Prints the content migration form
    **/

    function show_content_migration()
    {
        global $PAGE, $DB, $CFG;

        /**
        * The moodle tables API seems to have a fit if you try to add stuff to two active tables at the same
        * time, so I'm setting up and printing each table seperatly here using two loops.
        **/

        $baseurl=$CFG->wwwroot."/repository/helix_media_lib";
        $all_urls=$DB->get_records_select("url", "`externalurl` LIKE '%".$baseurl."%' ORDER BY course ASC");

        if (count($all_urls)==0)
        {
            echo "<br /><br /><h5 style='text-align:center;'>".get_string("migrate_nothing_found", "helixmedia")."</h5>";
            return;
        }

        echo "<form id='migrate-form' action='".$CFG->wwwroot."/mod/helixmedia/migrate.php' method='post'>\n".
             "<input type='hidden' name='migrate' value='true' />\n";

        /**Do the URLs for the Repos that exist**/
        $table_ok=get_table('mod-helixmedia-migrate-ok');
        $flist="";
        foreach ($all_urls as $url)
        {
            $repo=$DB->get_record("repository_instances", array("id"=>get_param($url->externalurl,"rid")));
            if ($repo!=null)
            {
                add_entry($table_ok, $url, true);
                $flist.=$url->id.",";
            }
        }

        if (strlen($flist)>0)
        {
            echo "<h3>".get_string("migrate_found", "mod_helixmedia")."</h3>\n";
            select_link($table_ok, "fid");
            $table_ok->print_html();
            echo "<input type='hidden' name='flist' value='".$flist."' />";
        }

        /**Do the URLs for the repos that have been removed**/
        $table_nf=get_table('mod-helixmedia-migrate-nf');
        $nlist="";
        foreach ($all_urls as $url)
        {
            $repo=$DB->get_record("repository_instances", array("id"=>get_param($url->externalurl,"rid")));
            if ($repo==null)
            {
                add_entry($table_nf, $url, false);
                $nlist.=$url->id.",";
                $found=true;
            }
        }

        if (strlen($nlist)>0)
        {
            echo "<br /><br /><h3>".get_string("migrate_not_found", "mod_helixmedia")."</h3>\n".
                 "<p>".get_string("migrate_not_found_2", "mod_helixmedia")."</p>";
            select_link($table_nf, "nid");
            $table_nf->print_html();
            echo "<input type='hidden' name='nlist' value='".$nlist."' />";
        }

        echo "<br /><p>".get_string("migrate_not_found_3", "mod_helixmedia")."</p>".
             "<p style='text-align:center;'><input type='submit' value='".get_string("migrate_do_button", "mod_helixmedia")."' /></p>".
             "</form>";

?>
<script type="text/javascript">
 function selectAll(key, val)
 {
  var form=document.getElementById("migrate-form");
  for (var loop=0; loop<form.elements.length; loop++)
  {
   if (form.elements[loop].name.indexOf(key)>-1)
    form.elements[loop].checked=val;
  }
 }
</script>
<?php
    }

    /**
    * Adds a select/unselect all link
    * @param $table The table to add the link to 
    * @param $key The name key
    **/

    function select_link($table, $key)
    {
        $tdata=array();
        $tdata[]="";
        $tdata[]="";
        $tdata[]="";
        $tdata[]="<a href='javascript:selectAll(\"".$key."\", true);'>".get_string("selectall")."</a><br />".
                 "<a href='javascript:selectAll(\"".$key."\", false);'>".get_string("deselectall")."</a>";
        $table->add_data($tdata);
    }

    /**
    * Adds the specified URL to the moodle table
    * @param $table The table
    * @param $url The URL
    * @param $repo_exists true if this is a repo that still exists in Moodle
    **/

    function add_entry($table, $url, $repo_exists)
    {
        global $DB;
        $crs=$DB->get_record("course", array("id"=>$url->course));
        $tdata=array();
        $tdata[]=$crs->shortname;
        $tdata[]=$url->name;
        $tdata[]=$url->externalurl;
        if ($repo_exists)
            $tdata[]="   <input type='checkbox' name='fid-".$url->id."' checked='checked' />";
        else
            $tdata[]="   <input type='checkbox' name='nid-".$url->id."' />";
        $table->add_data($tdata);
    }

    /**
    * Reads the specified parameter from the supplied URL
    * @param $url The URL to read
    * @return The require parameter
    **/

    function get_param($url, $param)
    {
        $param.="=";
        $start=strpos($url, $param)+strlen($param);
        $end=strpos($url, "&", $start);
        if ($end>-1)
            return substr($url, $start, $end-$start);
        else
            return substr($url, $start);
    }

    /**
    * Gets a Modole table object, pre-set up and ready for the migration data
    * @param $name The table name
    * @return The table 
    */

    function get_table($name)
    {
        global $CFG;
        $tablecolumns = array('crs', 'name', 'url', 'action');
        $tableheaders = array(get_string('course'), get_string('name'), get_string('url'), get_string('action'));

        $table = new flexible_table($name); 

        /// define table columns, headers, and base url
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot.'/mod/helixmedia/migrate.php');

        /// table settings
        $table->sortable(false);
        $table->initialbars(true);
        $table->pageable(false);

        /// set attributes in the table tag
        $table->set_attribute('cellpadding', '4');
        $table->set_attribute('id', 'editcats');
        $table->set_attribute('class', 'generaltable generalbox');
        $table->set_attribute('style', 'margin-left:auto; margin-right:auto;');
        $table->setup();
        return $table;
    }
