<!DOCTYPE html>
<?php
  include('conf.php');
?>
<html>
<head>
<title>Tame The Ark</title>
<link rel=stylesheet href="/css/main.css">
<script>
    var watchers = new Array();
    function ajaxRunServerCommand ( server, command, parameters) {
        var reqObj = new XMLHttpRequest();
        var reqData = new FormData();
        var url = '//<?php print $_SERVER['SERVER_NAME'].$scriptpath; ?>worker.php';
        var domobj = document.getElementById('status' + server);
        domobj.innerHTML = 'Refreshing...';
        reqData.append('server',server);
        reqData.append('command',command);
	if (parameters) {
		reqData.append('parameters',parameters);
	}
        reqObj.onreadystatechange = function (oEvent) {
            if (reqObj.readyState == 4) {
                if (reqObj.status == 200) {
                    var response = eval('(' + reqObj.responseText + ')');
                    if (typeof(response['error']) != 'undefined') {
                        domobj.innerHTML = response['error'];
                    } else if (response['success']) {
                        domobj.innerHTML = response['success'][0];
                        if (response['success'].length > 1) {
                            for (i = 0; i < response['success'][1].length; i++) {
                                domobj.innerHTML += "<br>" + response['success'][1][i];
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
                    } else {
                        clearTimeout(watchers[server]);
                        watchers[server] = setTimeout(function() { ajaxRunServerCommand(server,'status') },15000);
                    }
                } else {
                    console.log("Error",reqObj.statusText);
                }
            }
        }
        reqObj.open("POST",url,true);
        reqObj.send(reqData);
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
    $cmds = array('save' => array('title' => 'Save', 'help' => "Sends the 'Save' command"),
                'shutdown' => array('title' => 'Shutdown', 'help' => 'Shuts down Ark'),
                'startup' => array('title' => 'Startup', 'help' => 'Starts a stopped server'),
                'update' => array('title' => 'Update', 'help' => "Stops, updates, restarts Ark"),
                'maintenance' => array('title' => 'Maintain', 'help' => "Like update, but with warning messages in-game"),
                'restart' => array('title' => 'Restart', 'help' => "Stop and restart Ark"),
                'clearop' => array('title' => "Clear Op", 'help' => 'Clear the operation marker')
               );
    if ($cluster != '') {
      print "<div class='incluster'>\n";
      print "<a href='#'><h2>$cluster</h2></a>\n";
    } 
    foreach ($arks as $name => $ark) {
      if (
        (isset($ark['clusterid']) &&
        ($ark['clusterid'] == $cluster)) ||
        (!isset($ark['clusterid']) &&
        ($cluster == ''))
      ) {
        print "<div>\n";
        print "<h3><span>$name</span></h3>\n";
        print "<div id=\"status$name\"></div>\n";
        print "<script>";
        print "ajaxRunServerCommand('$name','status');";
        print "watchers['$name'] = setTimeout(ajaxRunServerCommand('$name','status'),15000);";
        print "</script>\n";
        print "<div>Commands: ";
        foreach ($cmds as $cmd => $info) {
          print "<a href=\"#\" onClick=\"javascript:ajaxRunServerCommand('$name','$cmd'); return 0;\" title=\"".$info['help']."\">".$info['title']."</a> ";
        }
        print "<a href=\"#\" onClick=\"\" title=\"send messages to servers\" class=\"disabled\">Message</a> ";
        print "<a href=\"#\" onClick=\"\" title=\"configure this server\" class=\"disabled\">Configure</a> ";
        print "</div>\n";
        print "</div>\n";
      }
    }
    if ($cluster != '') {
      print "</div>\n";
    }
  }
?>
<div id="messagebox" class="hiddenconfig" style="display: none;">
<h3>Send a message to your Ark Players:</h3>
<label for="arkmsg">Message text</label><input type="text" name="arkmsg" id="arkmsg"><br>
<label for="msgtocluster">Send to Cluster</label><input type="checkbox" name="msgtocluster" id="msgtocluster" value="q"><br>
<label for="msgtoall">Send to All</label><input type="checkbox" name="msgtoall" id="msgtoall" value=1><br>
<label for="setmotd">Set MOTD</label><input type="checkbox" name="setmotd" id="setmotd" value=1><br>
<button type="button">Send</button>
</div>
<div id="confbox" class="hiddenconfig" style="display: none;">
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
