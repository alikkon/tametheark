<!DOCTYPE html>
<?php
  if (!file_exists('../conf/conf.php')) { print "Create conf.php before running this script."; }
  $conf = parse_ini_file('../conf/conf.php');
?>
<html>
<head>
<title>Tame The Ark</title>
<link rel=stylesheet href="<?php print $conf['scriptpath']; ?>css/main.css">
<script type="text/javascript" src="<?php print $conf['scriptpath']; ?>resources/jquery-2.2.0.min.js"></script>
<script>
    if (!Date.now) {
        Date.now = function() { return new Date().getTime(); }
    }
    function timestamp() {
        return (Date.now() / 1000 | 0)
    }

    var watchers = {};
    var trackers = {};

    function ajaxRunClusterCommand( cluster, command, parameters) {
    }

    function ajaxRunServerCommand ( server, command, parameters) {
        var domobj = $( '#status' + server );
        var reqData = {};
        reqData['server'] = server;
        reqData['command'] = command;
	if (parameters) {
            reqData['parameters'] = parameters;
	}
        $.ajax({
            url: '//<?php print $_SERVER['SERVER_NAME'].$conf['scriptpath']; ?>worker.php',
            dataType: 'json',
            data: reqData,
            method: 'post',
            error: function(reqObj,errmsg) { console.log("Error",errmsg); domobj.html( errmsg ); },
            success: function(response) {
                if (typeof(response['error']) != 'undefined') {
                    domobj.html( response['error'] );
                } else if (response['success']) {
                    domobj.html( response['success'][0] );
                    if (response['success'].length > 1) {
                        for (i = 0; i < response['success'][1].length; i++) {
                            domobj.html( domobj.html() + "<br>" + response['success'][1][i] );
                        }
                    }
                } else {
                    domobj.innerHTML = 'got nothing back!';
                }
                if (typeof(response['overridecommand']) != 'undefined') {
                    if (typeof(watchers[server] != 'undefined')) {
                        clearTimeout(watchers[server]);
                    }
                    watchers[server] = setTimeout(function() { ajaxRunServerCommand(server,response['overridecommand']) },response['newtimer']||15000);
                    trackers[server] = timestamp();
                } else {
                    clearTimeout(watchers[server]);
                    watchers[server] = setTimeout(function() { ajaxRunServerCommand(server,'status') },15000);
                    trackers[server] = timestamp();
                }
            }
        })
        return 0;
    }

    function watchTheWatchers() {
        $.each(trackers,function(server, lastseen) {
            if (lastseen < (timestamp() - 60)) {
                clearTimeout(watchers[server]);
                ajaxRunServerCommand( server, 'status' );
                console.log('Connection to ' + server + ' timed out. Attempting to re-establish.');
            }
        });
        setTimeout(function() { watchTheWatchers() },15000);
    }

    setTimeout(function() { watchTheWatchers() },15000);

    function showSendMessageDialog( server ) {
        $( '#arkmsgserver' ).val( server );
        $( '#arkmsg' ).val( '' );
        $( '#arkmsgdisplay' ).html( server );
        $( '#messagebox' ).removeClass('hiddenconfig');
	$( '#messagebox' ).addClass('configoverlay');
        return 0;
    }

    function sendMessageToArk() {
        ajaxRunServerCommand($( '#arkmsgserver' ).val(), 'message', $( '#arkmsg' ).val());
        $( '#arkmsgserver' ).val( '' );
        $( '#arkmsg' ).val( '' );
        $( '#arkmsgdisplay' ).html( '' );
        $( '#messagebox' ).addClass('hiddenconfig');
        $( '#messagebox' ).removeClass('configoverlay');
        return 0;
    }

    function cancelMessageToArk() {
        $( '#arkmsgserver' ).val( '' );
        $( '#arkmsg' ).val( '' );
        $( '#arkmsgdisplay' ).html( '' );
        $( '#messagebox' ).addClass('hiddenconfig');
        $( '#messagebox' ).removeClass('configoverlay');
        return 0;
    }
</script>
</head>
<body>
<h1>Tame The Ark</h1>
<?php
  include('functions.php');
  $arks = getAllConfs();
  $clusters = array();

  foreach ($arks as $name => $ark) {
    if ( isset($ark['clusterid']) && !in_array($ark['clusterid'],$clusters) ) {
      array_push($clusters,$ark['clusterid']);
    }
  }
  
  if ($clusters) { sort($clusters); foreach ($clusters as $cluster) { addArkCluster($arks, $cluster); } }
  addArkCluster($arks);

  function addArkCluster($arks, $cluster = '') {
    $solocmds = array(
                'save' => array('title' => 'Save', 'help' => "Sends the 'Save' command"),
                'shutdown' => array('title' => 'Shutdown', 'help' => 'Shuts down this Ark'),
                'startup' => array('title' => 'Startup', 'help' => 'Starts this Ark'),
                'update' => array('title' => 'Update', 'help' => "Stops, updates, restarts this Ark"),
                'maintenance' => array('title' => 'Maintain', 'help' => "Like update, but with warning messages in-game"),
                'restart' => array('title' => 'Restart', 'help' => "Stop and restart this Ark"),
                'clearop' => array('title' => "Clear Op", 'help' => 'Clear the operation marker')
               );
    $sharedcmds = array(
                'save' => array('title' => 'Save', 'help' => "Sends the 'Save' command"),
                'shutdown' => array('title' => 'Shutdown', 'help' => 'Shuts down this Ark'),
                'startup' => array('title' => 'Startup', 'help' => 'Starts this Ark'),
                'update' => array('title' => 'Update', 'help' => "Stops, updates, restarts this Ark"),
                'maintenance' => array('title' => 'Maintain', 'help' => "Like update, but with warning messages in-game"),
                'restart' => array('title' => 'Restart', 'help' => "Stop and restart this Ark"),
                'clearop' => array('title' => "Clear Op", 'help' => 'Clear the operation marker')
               );
    $clustercmds = array(
                'save' => array('title' => 'Save', 'help' => "Sends the 'Save' command to all nodes in a cluster"),
                'shutdown' => array('title' => 'Shutdown', 'help' => "Shuts down all Arks in this cluster"),
                'startup' => array('title' => 'Startup', 'help' => "Starts up all Arks in this cluster"),
                'update' => array('title' => 'Update', 'help' => "Updates the Ark software for this cluster"),
                'maintenance' => array('title' => 'Maintain', 'help' => 'Sends in-game warning messages, shuts down all Arks in cluster, updates Ark software, then starts any Arks that were running when this command was initiated'),
                'restart' => array('title' => 'Restart', 'help' => 'Restarts all running nodes in cluster'),
                'clearop' => array('title' => 'Clear Op', 'help' => 'Clear the operation marker')
               );
    if ($cluster != '') {
      print "<div class='incluster'>\n";
      print "<a href='#'><h2>$cluster</h2></a>\n";
    } else {
      print "<div class='incluster'>\n";
    } 
    foreach ($arks as $name => $ark) {
      if (
        (isset($ark['clusterid']) &&
        ($ark['clusterid'] == $cluster)) ||
        (!isset($ark['clusterid']) &&
        ($cluster == ''))
      ) {
        print "<div>\n";
        print "<h3><a id=\"connect$name\" href=\"steam://connect/".$_SERVER['SERVER_NAME'].':'.$ark['queryport']."/".$ark['serverpassword']."\">Connect</a>\n";
        print "<span>$name (".$ark['map'].")</span></h3>\n";
        print "<div id=\"status$name\"></div>\n";
        print "<script>";
        print "ajaxRunServerCommand('$name','status');";
        print "watchers['$name'] = setTimeout(ajaxRunServerCommand('$name','status'),15000);";
        print "</script>\n";
        print "<div>Commands: ";
        if ((!$cluster) or ($ark['clusterbinary'] == 0)) {
          foreach ($solocmds as $cmd => $info) {
            print "<a href=\"#\" onClick=\"javascript:ajaxRunServerCommand('$name','$cmd'); return 0;\" title=\"".$info['help']."\">".$info['title']."</a> ";
          }
        } else {
          foreach ($sharedcmds as $cmd => $info) {
            print "<a href=\"#\" onClick=\"javascript:ajaxRunServerCommand('$name','$cmd'); return 0;\" title=\"".$info['help']."\">".$info['title']."</a> ";
          }
        }
        print "<a href=\"#\" title=\"send messages to servers\" onClick=\"showSendMessageDialog('$name');\">Message</a> ";
        print "<a href=\"#\" title=\"configure this server\" class=\"disabled\">Configure</a> ";
        print "</div>\n";
        print "</div>\n";
      }
    }
    if ($cluster != '') {
      print "<div class='clustercmds'>Cluster Commands: ";
      foreach ($clustercmds as $cmd => $info) {
        print "<a href=\"#\" onClick=\"javascript:ajaxRunClusterCommand('$name','$cmd'); return 0;\" title=\"".$info['help']."\">".$info['title']."</a> ";
      }
      print "</div>";
    }
    print "</div>\n";
  }
?>
<div id="messagebox" class="hiddenconfig">
<div>
<h3>Send a message to the players on <span id='arkmsgdisplay'></span>:</h3>
<input type="hidden" name="arkmsgserver" id="arkmsgserver" value="">
<label for="arkmsg">Message text</label><input type="text" name="arkmsg" id="arkmsg"><br>
<!--<label for="msgtocluster">Send to Cluster</label><input type="checkbox" name="msgtocluster" id="msgtocluster" value="q"><br>-->
<!--<label for="msgtoall">Send to All</label><input type="checkbox" name="msgtoall" id="msgtoall" value=1><br>-->
<!--<label for="setmotd">Set MOTD</label><input type="checkbox" name="setmotd" id="setmotd" value=1><br>-->
<button type="button" onClick="sendMessageToArk();">Send</button> <button type="button" onClick="cancelMessageToArk();">Cancel</button>
</div>
</div>
<div id="confbox" class="hiddenconfig">
<!-- Build a series of linked tab boxes for configuration options. -->
<!-- Each box should be separated by purpose. -->
<!-- -->
<div id="confnav">
<span id="basics">Basics</span>
<span id="server">Server</span>
<span id="resources">Resources</span>
<span id="dinos">Dinos</span>
</div>
<div id="pagebasics">
<label for="steampath">Steam Path*</label><input type="text" name="steampath" id="steampath" class="required"><br>
<label for="servername">Server Name*</label><input type="text" name="servername" id="servername" class="required"><br>
<label for="serverpassword">Server Password</label><input type="text" name="serverpassword" id="serverpassword"><br>
<label for="adminpassword">Admin Password</label><input type="text" name="adminpassword" id="adminpassword" class="required"><br>
<label for="rconport">Management Port</label><input type="text" name="rconport" id="rconport" class="required"><br>
<label for="queryport">Steam Port</label><input type="text" name="queryport" id="queryport" class="required"><br>
<label for="port">Client Port</label><input type="text" name="port" id="port" class="required"><br>
<label for="map">Game Map</label><select name="map" id="map" class="required"><option>TheIsland</option><option>TheCenter</option><option>ScorchedEarth_P</option></select><br>
<label for="clusterid">Cluster</label><input type="text" name="clusterid" id="clusterid">
</div>
<div id="pageserver">
</div>
</div>
</body>
</html>
