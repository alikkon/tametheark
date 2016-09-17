<?php
    define('STYPE_STATUS','1');
    define('STYPE_CODE','0');
    define('SRC_ALIVE','0');
    define('SRC_DEAD','1');
    define('SRC_DEGRADED','2');

    include('conf.php');
    include('functions.php');
    require('SourceQuery/SourceQuery.class.php');
    if (!isset($_POST['server']) || !isset($_POST['command'])) {
        print json_encode(array('error' => 'Malformed request: Missing parameter.')); exit;
    } else {
        $command = $_POST['command'];
        $server = $_POST['server'];
    }

    $ark = getConf($server);

    if (!$ark) { sendOutput(array('error' => 'Unable to load arkconf '.$server.'. Check your configuration and try again.')); exit; }
    if (!file_exists($ark['arkpath']."/$server")) {
        mkdir($ark['arkpath']."/$server");
    }

    if ($command == 'getconf') {
        print json_encode($ark);
        exit;
    }

    $ark['rcon'] = new SourceQuery();
    try
    {
        $ark['rcon']->Connect( 'localhost', $ark['rconport'], 1, SourceQuery :: SOURCE );
        $ark['rcon']->SetRconPassword( $ark['adminpassword'] );
    }
    catch( Exception $e )
    {
    }

    $cmds = array('save','startup', 'shutdown','update','restart','maintenance','restart','clearop');
    $status = array('error' => 'command not found');
    $inprocess = opCheck($ark, $server);
    if ((isset($status['success'])) && ($command != 'clearop')) {
        print json_encode($status);
        exit;
    }

    if ($inprocess) {
        if ($command == 'status') {
            $opin = file($ark['arkpath'].'/'.$server.'/op');
            foreach ($opin as $op) {
                rtrim($op);
                if (substr($op,0,7) == 'command') {
                    $currcmd = substr($op,8,strlen($op)-8);
                }
                if (substr($op,0,5) == 'stage') {
                    $currstage = substr($op,6,strlen($op)-6);
                }
            }
            if (file_exists($ark['arkpath']."/".$server."/steam.out")) {
                $extradata = file($ark['arkpath']."/".$server."/steam.out");
                sendOutput(array('success' => array(ucfirst($currcmd).' status: '.(isset($currstage) ? ucfirst($currstage) : '')." in progress...", $extradata), 'overridecommand' => 'status', 'overridetimer' => 3000));
            } else {
                sendOutput(array('success' => array(ucfirst($currcmd).' status: '.(isset($currstage) ? ucfirst($currstage) : '')." in progress..."), 'overridecommand' => 'status', 'overridetimer' => 3000));
            }
        } elseif ($command == 'clearop') {
            unlink($ark['arkpath'].'/'.$server.'/op');
            sendOutput(array('success' => array('Operation lock deleted.')));
        }
    } else {
        if ($command == 'status') {
            sendOutput(getStatus($ark,$server));
        } elseif ($command == 'clearop') {
            if ( file_exists($ark['arkpath'].'/'.$server.'/op') ) {
                unlink($ark['arkpath'].'/'.$server.'/op');
                sendOutput(array('success' => array('Operation lock deleted.')));
            } else {
                unlink($ark['arkpath'].'/'.$server.'/op');
                sendOutput(array('success' => array('No operation lock found')));
            }
        } elseif ($command == 'save') {
            sendOutput(saveWorld($ark));
        } elseif ($command == 'startup') {
            // Set op flag.
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=startup");
            // Notify about command status.
            sendOutput(array('success' => array('Sending Startup command, watch this space.'),'overridecommand' => 'status'));
            startArk($ark,$server);
            // Remove op flag.
            unlink($ark['arkpath'].'/'.$server.'/op');
        } elseif ($command == 'shutdown') {
            // Set op flag.
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=shutdown");
            // Notify about command status.
            sendOutput(array('success' => array('Shutdown has begun...'), 'overridecommand' => 'status'));
            endArk($ark,$server);
            // Remove op flag.
            unlink($ark['arkpath'].'/'.$server.'/op');
        } elseif ($command == 'restart') {
            // Set op flag
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=restart\nstage=save");
            // Notify about command status.
            sendOutput(array('success' => array('Beginning restart process'), 'overridecommand' => 'status'));
            // Status: Saving
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=restart\nstage=saving");
            saveWorld($ark);
            sleep(10);
            // Status: Shutting Down.
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=restart\nstage=shutdown");
            endArk($ark,$server);
            // Status: Starting Up.
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=restartstage\nstage=startup");
            startArk($ark,$server);
            // Remove op flag.
            unlink($ark['arkpath'].'/'.$server.'/op');
        } elseif ($command == 'update') {
            // Set op flag
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=restart");
            // Notify about command status.
            sendOutput(array('success' => array('Beginning update process...'), 'overridecommand' => 'status', 'overridetimer' => 3000));
            // Determine if we need to kill the process or not.
            $currstatus = getStatus($ark,$server,STYPE_CODE);
            if ($currstatus != SRC_DEAD) {
                // Status: Saving
                file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=update\nstage=saving");
                saveWorld($ark);
                sleep(10);
                // Exiting
                file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=update\nstage=shutdown");                
                endArk($ark,$server);
            }
            if (file_exists($ark['arkpath'].'/'.$server.'/pid')) { unlink($ark['arkpath'].'/'.$server.'/pid'); }
            // Backup data.
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=update\nstage=backup");
            exec("/bin/rsync -avHCxl ".$ark['arkpath']."/ShooterGame/Saved/$server ".$ark['arkpath'].'/'.$server."/Saved.bak");
            // Patch.
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=update\nstage=patch");
            updateArk($ark,$server);
            // Start back up.
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=update\nstage=startup");
            startArk($ark,$server);
            unlink($ark['arkpath']."/".$server."/steam.out");
            unlink($ark['arkpath'].'/'.$server.'/op');
        } elseif ($command == 'message') {
            $parameters = $_POST['parameters'];
            $res = $ark['rcon']->Rcon( "broadcast \"$parameters\"" );
            sendOutput(array('success' => array("Sent message: $parameters")));
        } elseif ($command == 'maintenance') {
            // Set op flag
           file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=restart");
            // Notify about command status.
            sendOutput(array('success' => array('Beginning update process...'), 'overridecommand' => 'status', 'overridetimer' => 3000));
            // Determine if we need to kill the process or not.
            $currstatus = getStatus($ark,$server,STYPE_CODE);
            if ($currstatus != SRC_DEAD) {
                // Send in-game notifications.
                $res = $ark['rcon']->Rcon( 'SetMessageOfTheDay "The server will be going down shortly for maintenance."' );
                $i = $maintintervals;
                while ($i > 0) {
                    file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=restart\nstage=notification$i");
                    $res = $ark['rcon']->Rcon( "broadcast \"Maintenance begins in $i minutes\"" );
                    sleep(60);
                    $i--;
                }
                // Save Ark.
                file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=maintenance\nstage=save");
                saveWorld($ark);
                sleep(10);
                // Kill Ark
                file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=maintenance\nstage=shutdown");
                endArk($ark,$server);
            }
            if (file_exists($ark['arkpath'].'/'.$server.'/pid')) { unlink($ark['arkpath'].'/'.$server.'/pid'); }
            // Backup data.
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=maintenance\nstage=backup");
            exec("/bin/rsync -avHCxl ".$ark['arkpath']."/ShooterGame/Saved/$server ".$ark['arkpath'].'/'.$server."/Saved.bak");
            // Patch.
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=maintenance\nstage=patch");
            updateArk($ark,$server);
            // Start back up.
            file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=maintenance\nstage=startup");
            startArk($ark,$server);
            $res = $ark['rcon']->Rcon( 'SetMessageOfTheDay "'.$ark['motd'].'"' );
            unlink($ark['arkpath']."/".$server."/steam.out");
            unlink($ark['arkpath'].'/'.$server.'/op');
        }
    }

    $ark['rcon']->Disconnect();

    // Check to see if we're in the middle of an operation.
    function opCheck($ark,$instance) {
        $path = $ark['arkpath'];
        if (!file_exists("$path/$instance")) {
            mkdir("$path/$instance");
        }
        if (file_exists("$path/$instance/op")) {
            $opin = file($path.'/'.$instance.'/op');
            $currop = '';
            $subop = '';
            foreach ($opin as $op) {
                $op = rtrim($op);
                if (substr($op,0,7) == 'command') {
                    $currop = substr($op,8,strlen($op)-8);
                }
                if (substr($op,0,5) == 'stage') {
                    $subop = substr($op,6,strlen($op)-6);
                }
            }
            return array('command' => $currop, 'stage' => $subop);
        } else {
            return 0;
        }
    }

    function startArk( $ark, $server ) {
        // Build options.
        chdir($ark['arkpath'].'/ShooterGame/Binaries/Linux');
        $opts = buildOpts($ark);
        // Spawn process.
        exec("$nohup $nice ./ShooterGameServer ".($ark['map'] ? $ark['map'] : 'TheIsland')."$opts?AltSaveDirectoryName=$server?listen".
        ($ark['clusterid'] ? ' -NoTransferFromFiltering -clusterid='.$ark['clusterid'] : '').
        " -server -log > ".$ark['arkpath']."/".$server."/out 2>&1 & echo $! > ".$ark['arkpath']."/".$server."/pid");
        // Get PID - we'll use this to determine if the process is still running throughout startup.
        $pid = rtrim(file_get_contents($ark['arkpath'].'/'.$server.'/pid'));
        $res = 0;
        while ($res == 0) {
            try {
                $ark['rcon'] = new SourceQuery();
                $ark['rcon']->Connect( 'localhost', $ark['rconport'], 1, SourceQuery :: SOURCE );
                $ark['rcon']->SetRconPassword( $ark['adminpassword'] );
                $res = $ark['rcon']->Rcon('listplayers');
                $ark['rcon']->Disconnect();
                $res = 1;
            } catch ( Exception $e ) {
                file_put_contents($ark['arkpath'].'/'.$server.'/op', "command=Startup\nstage=Waiting for server to become responsive...");
            }
            if (!posix_kill($pid, 0)) {
                file_put_contents($ark['arkpath'].'/'.$server.'/op', "command=There was an error starting Ark");
		exit;
            }
            sleep(10);
        }
	if (!$pid) {
            file_put_contents($ark['arkpath'].'/'.$server.'/op', "command=There was an error starting Ark. Check your nohup settings in conf.php");
            exit;
	}
    }

    function endArk ( $ark, $server ) {
        // Get current process PID and send kill.
        $pid = rtrim(file_get_contents($ark['arkpath'].'/'.$server.'/pid'));
        // Send kill command
        posix_kill($pid, 3);
        $stime=0;
        $running = true;
        // Wait for kill to complete.
        while ($running) {
            sleep(5);
            $stime += 5;
            // If we run too long, force kill.
            if ($stime >= 600) {
                posix_kill($pid,9);
                $running = false;
            }
            if (!posix_kill($pid,0)) {
                $running = false;
            }
        }
        unlink($ark['arkpath'].'/'.$server.'/pid');
    }
    
    function updateArk( $ark, $server ) {
        chdir($ark['steampath']);
        exec("./steamcmd.sh +login anonymous +force_install_dir ".$ark['arkpath']." +app_update 376030 +quit    > ".$ark['arkpath']."/".$server."/steam.out 2>&1", $result, $exit);
    }
    
    // Take the list of options and build them into a command string //
    function buildOpts($ark) {
        $opts = "";
        foreach ($ark['opts'] as $k => $v) {
            if (!is_array($v)) {
                $opts .= "?$k=$v";
            } else {
                foreach ($v as $vv) {
                    $opts .= "?$k=$vv";
                }
            }
        }
        foreach ($ark as $k => $v) {
            if (is_string($v)) {
                $opts = str_replace("[$k]",$v,$opts);
            }
        }
        $opts = str_replace(array('(',')','"','!'),array('\(','\)','\"','\!'),$opts);
        return $opts;
    }
    
    function sendOutput($status) {
        $tosend = json_encode($status);
        header('Connection: close');
        header("Content-Length: ".strlen($tosend));
        print $tosend;
        flush();
    }

    // Determine the current status of the Ark service in question //
    function getStatus( $ark, $server, $responsetype = STYPE_STATUS ) {
        try {
            $res = $ark['rcon']->Rcon('listplayers');
        } catch (Exception $e) { $res = '';}
        if ($res) {
            $status = array('success' => array('Ark is running', array($res)));
            $src = SRC_ALIVE;
        } else {
            if (file_exists($ark['arkpath'].'/'.$server.'/pid')) {
                $pid = rtrim(file_get_contents($ark['arkpath'].'/'.$server.'/pid'));
                if (posix_kill($pid, 0)) {
                    $status = array('error' => array('Ark is running but not responding.'));
                    $src = SRC_DEGRADED;
                } else {
                    $status = array('error' => array('Ark is not running.'));
                    $src = SRC_DEAD;
                }
            } else {
                $status = array('error' => array('Ark is not running.'));
                $src = SRC_DEAD;
            }
        }
        if ($responsetype == STYPE_STATUS) {
            return $status;
        } elseif ($responsetype == STYPE_CODE) {
            return $src;
        }
    }

    // Sends the save command - unfortunately, Ark doesn't actually give any feedback on whether or not this is successful.
    function saveWorld( $ark, $responsetype = STYPE_STATUS ) {
        try {
            $res = $ark['rcon']->Rcon('saveworld');
        } catch ( Exception $e ) {
        }
        $status = array('success' => array('Save command sent!'));
        $src = RC_SUCCESS;
	if ($responsetype == STYPE_STATUS) {
		return $status;
	} else {
	        return $src;
	}
    }

?>
