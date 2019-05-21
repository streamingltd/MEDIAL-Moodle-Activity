<?php

/**
 * This page contains the code for the modal popup dialog
 *
 * @package    mod
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../../config.php");
header("Content-Type: text/javascript");
global $CFG;
?>
var activity_dialog;
var gotIn=false;
var medial_interval;

function checkStatus()
{
    var xmlDoc=null;

    if (typeof window.ActiveXObject != 'undefined' )
        xmlDoc = new ActiveXObject("Microsoft.XMLHTTP");
    else
        xmlDoc = new XMLHttpRequest();

    var params="resource_link_id="+resID+"&user_id="+userID+"&oauth_consumer_key="+oauthConsumerKey;
    xmlDoc.open("POST", statusURL , false);
    xmlDoc.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    xmlDoc.send(params);

    if (xmlDoc.responseText=="IN")
        gotIn=true;
    
    if (xmlDoc.responseText!="OUT" || gotIn==false) {
        if (medial_interval == null) {
            medial_interval = setInterval(checkStatus, 2000);
        }
    } else {
<?php 
    $mod_config=get_config("helixmedia");
    $delay = intval($mod_config->modal_delay);
    if ($delay==0)
        echo "closeDialogue();\n";
    else if ($delay>0)
        echo "setTimeout(closeDialogue, ".($delay*1000).");\n";
?>
    }
}

function closeDialogue()
{
    clearInterval(medial_interval);
    var tframe=document.getElementById("thumbframe");
    if (tframe!=null && typeof(thumburl)!="undefined")
     tframe.contentWindow.location=thumburl;
    activity_dialog.hide();
    setTimeout(function() {
        document.getElementById('yui_act_sel_dialog').innerHTML = "";
    }, 500);

    var mform1=document.getElementById("mform1");
    if (mform1==null) {
        var elements = document.getElementsByClassName("mform");
        mform1 = elements[0];
    }

    if (mform1!=null)
    {
        if (typeof mform1.elements['helixassign_activated']!="undefined")
        {
            mform1.elements['helixassign_activated'].value="1";
        }
        else
        if (typeof mform1.elements['helixfeedback_activated']!="undefined")
        {
            mform1.elements['helixfeedback_activated'].value="1";
        }
    }

    activity_dialog=null;

    // Sometimes the yui_act_sel_dialog_mask element get duplicated (bug in Moodle?) and only one will be
    // unmasked when the dialog is closed. So we need to find the duplicates and then hide them as well. Using the
    // the id doesn't work because it only finds the first duplicate so we need to search for the mask class.

    var maskdivs = document.getElementsByClassName("mask");
    for (i=0; i < maskdivs.length; i++) {
        if (maskdivs[i].id == "yui_act_sel_dialog_mask") {
            maskdivs[i].style.display = "none";
        }
    }

    while (maskdivs.length>1) {
        maskdivs[0].parentNode.removeChild(maskdivs[0]);
        maskdivs = document.getElementsByClassName("mask");
    }
}


YUI().use('yui2-container', 'yui2-connection', function(Y) {
var YAHOO = Y.YUI2;


YUI().use('node-base', function(Y) {
    function init() {
     //We need to create a hidden div to act as the container for the modal popup
      var yui_act_sel_dialog = Y.one('#yui_act_sel_dialog');
        if (!yui_act_sel_dialog) {
            var el = document.createElement('div');
            el.id = 'yui_act_sel_dialog';
            document.getElementsByTagName('body')[0].appendChild(el);
        }
 
       //links which have the pop_up_selector_link class will be turned into modal form windows
        Y.all(".pop_up_selector_link").on('click', function(e) {
            var a = new com.uol.PopupHandler(e.currentTarget.get('href'), false);
            a.ajax();
            if (e.preventDefault) {
                e.preventDefault();
            }
            return false;
        });
    }
 
    Y.on("domready", init);
 
 
});



if (typeof com == 'undefined') {
    var com = {};
}

if (typeof com.uol == 'undefined') {
    com.uol = {};
}
(function() {
    /**
     * Constructor for the PopupHandler class
     * @param url
     */
    this.PopupHandler = function(url) {
        this.url = url;
        this.setWindowSize();
        this.url += '&w='+this.width+'&h='+this.height;
    },

    this.PopupHandler.prototype.setWindowSize = function() {

        var wWidth=0;
        var wHeight=0;
        var top;

        wWidth=document.documentElement.clientWidth;
        wHeight=document.documentElement.clientHeight;
        top=document.documentElement.scrollTop;

        if (this.detectMobile()) {
            this.width = wWidth;
            this.height = wHeight;
        } else {
            this.width = wWidth-6;
            this.height = wHeight;
        }

        if (this.width > 1000) {
            this.width = 1000;
        }
        if (this.height > 1400) {
            this.height = 1400;
        }

        this.y = top;
        this.x=(wWidth-this.width)/2;

        if (this.x>8) {
            this.x=this.x-8;
        } else {
            this.x=0;
        }
    },

    this.PopupHandler.prototype.detectMobile = function() { 
        if( navigator.userAgent.match(/Android/i)
            || navigator.userAgent.match(/webOS/i)
            || navigator.userAgent.match(/iPhone/i)
            || navigator.userAgent.match(/iPad/i)
            || navigator.userAgent.match(/iPod/i)
            || navigator.userAgent.match(/BlackBerry/i)
            || navigator.userAgent.match(/Windows Phone/i)
        ){
            return true;
        } else {
            return false;
        }
},

    /**
     * Method to call load the form
     */
    this.PopupHandler.prototype.ajax = function() {
        //Assignment call to solve the scope problem in ajax_callback
        this.ajax_callback.argument[0] = this.width;
        this.ajax_callback.argument[1] = this.height;
        this.ajax_callback.argument[2] = this.x;
        this.ajax_callback.argument[3] = this.y;

        var furl=this.url;
        var nameEle=document.getElementById("id_name");
        if (nameEle!=null) {
            furl+="&name="+encodeURIComponent(nameEle.value);
        }
        var introEle=document.getElementById("id_introeditor");
        if (introEle!=null) {
            furl+="&intro="+encodeURIComponent(introEle.value.replace(/<(?:.|\n)*?>/gm, '').substring(0,1000));
        }

        //make the ajax call
        YAHOO.util.Connect.asyncRequest('GET', furl, this.ajax_callback, null);
    },
 
        this.PopupHandler.prototype.ajax_callback = {
            success: function(e) {
                document.getElementById('yui_act_sel_dialog').innerHTML = e.responseText;

                activity_dialog = new YAHOO.widget.Dialog('yui_act_sel_dialog', {
                    x:e.argument[2],
                    y:e.argument[3],
                    modal: true,
                    width: e.argument[0] + 'px',
                    height: e.argument[1] + 'px',
                    iframe: false,
                    zIndex: 9999,
                    fixedcenter: false,
                    visible: false,
                    close: true,
                    constraintoviewport: false,
                    postmethod: 'async',
                    hideaftersubmit:true
                });

                activity_dialog.render();
                activity_dialog.show();

                YAHOO.util.Event.removeListener(activity_dialog.close, "click");
                YAHOO.util.Event.addListener(activity_dialog.close, "click", closeDialogue);

                if(doStatusCheck)
                    setTimeout(checkStatus, 5000);
            }
            ,
            failure: function(e) {
                alert("Browser error: Couldn't open modal dialogue");
            },
            argument: []
        }
 
 
}).call(com.uol);


});

