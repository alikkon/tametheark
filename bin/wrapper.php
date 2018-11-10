#!/usr/bin/php
<?php
    $conf = parse_ini_file('../conf/conf.php');
    include('../www/functions.php');
    require('../www/SourceQuery/SourceQuery.class.php');

    $me = array_shift($argv);
    $cmd = array_shift($argv);
    $server = array_shift($argv);
    $parameters = $argv;
    print "$me\n";
    print "$cmd\n";
    print "$server\n";
    print_r($parameters);

    if (!isset($server) || !isset($cmd)) {
        print "missing parameters."; exit(1);
    }

    $ark = getConf($server);

    $ark['rcon'] = new SourceQuery();
    try
    {
        $ark['rcon']->Connect( 'localhost', $ark['rconport'], 1, SourceQuery :: SOURCE );
        $ark['rcon']->SetRconPassword( $ark['adminpassword'] );
    }
    catch( Exception $e )
    {
    }

    umask(0002);

    $cmds = array('save','startup', 'shutdown','update','restart','maintenance','restart');

    if ($cmd == 'start') {
        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=startup");
        startArk($ark,$server);
        unlink($ark['mypath'].'/'.$server.'/op');
    } elseif ($cmd == 'stop') {
        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=shutdown");
        endArk($ark,$server);
        unlink($ark['mypath'].'/'.$server.'/op');
    } elseif ($cmd == 'restart') {
        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=restart\nstage=saving");
        saveWorld($ark);
        sleep(10);
        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=restart\nstage=shutdown");
        endArk($ark,$server);
        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=restartstage\nstage=startup");
        startArk($ark,$server);
        unlink($ark['mypath'].'/'.$server.'/op');
    } elseif ($cmd == 'update') {
        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=restart");
        $currstatus = getStatus($ark,$server,STYPE_CODE);
        if ($currstatus != SRC_DEAD) {
            file_put_contents($ark['mypath'].'/'.$server.'/op',"command=update\nstage=saving");
            saveWorld($ark);
            sleep(10);
            file_put_contents($ark['mypath'].'/'.$server.'/op',"command=update\nstage=shutdown");                
            endArk($ark,$server);
        }
        if (file_exists($ark['mypath'].'/'.$server.'/pid')) { unlink($ark['mypath'].'/'.$server.'/pid'); }
        if (isset($rsync)) {
            file_put_contents($ark['mypath'].'/'.$server.'/op',"command=update\nstage=backup");
            exec("$rsync -avHCxl ".$ark['arkpath']."/ShooterGame/Saved/$server ".$ark['mypath'].'/'.$server."/Saved.bak");
        }
        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=update\nstage=patch");
        updateArk($ark,$server);
        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=update\nstage=startup");
        startArk($ark,$server);
        unlink($ark['mypath']."/".$server."/steam.out");
        unlink($ark['mypath'].'/'.$server.'/op');
    } elseif ($cmd == 'maintain') {
        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=restart");
        $currstatus = getStatus($ark,$server,STYPE_CODE);
        if ($currstatus != SRC_DEAD) {
            $res = $ark['rcon']->Rcon( 'SetMessageOfTheDay "The server will be going down shortly for maintenance."' );
            $i = $maintintervals;
            while ($i > 0) {
                file_put_contents($ark['mypath'].'/'.$server.'/op',"command=restart\nstage=notification$i");
                $res = $ark['rcon']->Rcon( "broadcast \"Maintenance begins in $i minutes\"" );
                sleep(60);
                $i--;
            }
            file_put_contents($ark['mypath'].'/'.$server.'/op',"command=maintenance\nstage=save");
            saveWorld($ark);
            sleep(10);
            file_put_contents($ark['mypath'].'/'.$server.'/op',"command=maintenance\nstage=shutdown");
            endArk($ark,$server);
        }
        if (file_exists($ark['mypath'].'/'.$server.'/pid')) { unlink($ark['mypath'].'/'.$server.'/pid'); }
        if (isset($rsync)) {
            file_put_contents($ark['mypath'].'/'.$server.'/op',"command=maintenance\nstage=backup");
            exec("$rsync -avHCxl ".$ark['arkpath']."/ShooterGame/Saved/$server ".$ark['mypath'].'/'.$server."/Saved.bak");
        }
        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=maintenance\nstage=patch");
        updateArk($ark,$server);
        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=maintenance\nstage=startup");
        startArk($ark,$server);
        $res = $ark['rcon']->Rcon( 'SetMessageOfTheDay "'.$ark['motd'].'"' );
        unlink($ark['mypath']."/".$server."/steam.out");
        unlink($ark['mypath'].'/'.$server.'/op');
    } elseif ($cmd == 'checkpid') {
        $pid = rtrim(file_get_contents($ark['mypath'].'/'.$server.'/pid'));
        if (posix_kill($pid, 0)) {
            print 1;
        } else {
            print 0;
        }
    }

    $ark['rcon']->Disconnect();

    // Check to see if we're in the middle of an operation.
    function opCheck($ark,$instance) {
        $path = $ark['mypath'];
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
        $cmd=(isset($nohup) ? $nohup : '').' '.(isset($nice) ? $nice : '')." ./ShooterGameServer ".($ark['map'] ? $ark['map'] : 'TheIsland')."$opts?AltSaveDirectoryName=$server?listen".
        ($ark['clusterid'] ? ' -NoTransferFromFiltering -clusterid='.$ark['clusterid'] : '').
        " -server -servergamelog > ".$ark['mypath']."/".$server."/out 2>&1 & echo $! > ".$ark['mypath']."/".$server."/pid";
        file_put_contents($ark['mypath'].'/'.$server.'/startCmd',$cmd);
        // Spawn process.
        exec($cmd);
        // Get PID - we'll use this to determine if the process is still running throughout startup.
        $pid = rtrim(file_get_contents($ark['mypath'].'/'.$server.'/pid'));
        $res = 0;
        file_put_contents($ark['mypath'].'/'.$server.'/op', "command=Startup\nstage=Waiting for server to become responsive...");
        while ($res == 0) {
            try {
                $ark['rcon'] = new SourceQuery();
                $ark['rcon']->Connect( 'localhost', $ark['rconport'], 1, SourceQuery :: SOURCE );
                $ark['rcon']->SetRconPassword( $ark['adminpassword'] );
                $res = $ark['rcon']->Rcon('listplayers');
                $ark['rcon']->Disconnect();
                $res = 1;
            } catch ( Exception $e ) {
            }
            if (!posix_kill($pid, 0)) {
                file_put_contents($ark['mypath'].'/'.$server.'/op', "command=There was an error starting Ark");
		exit;
            }
            sleep(10);
        }
	if (!$pid) {
            file_put_contents($ark['mypath'].'/'.$server.'/op', "command=There was an error starting Ark. Check your nohup settings in conf.php");
            exit;
	}
    }

    function endArk ( $ark, $server ) {
        // Get current process PID and send kill.
        $pid = rtrim(file_get_contents($ark['mypath'].'/'.$server.'/pid'));
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
        unlink($ark['mypath'].'/'.$server.'/pid');
    }
    
    function updateArk( $ark, $server ) {
        exec($ark['steampath'] . " +login anonymous +force_install_dir ".$ark['arkpath']." +app_update 376030 +quit    > ".$ark['mypath']."/".$server."/steam.out 2>&1", $result, $exit);
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
    
    // Determine the current status of the Ark service in question //
    function getStatus( $ark, $server, $responsetype = STYPE_STATUS ) {
        try {
            $res = $ark['rcon']->Rcon('listplayers');
        } catch (Exception $e) { $res = '';}
        if ($res) {
            $status = array('success' => array('Ark is running', array($res)));
            $src = SRC_ALIVE;
        } else {
            if (file_exists($ark['mypath'].'/'.$server.'/pid')) {
                $pid = rtrim(file_get_contents($ark['mypath'].'/'.$server.'/pid'));
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
        } elseif ($resposnetype == STYPE_BOTH) {
            return array(STYPE_CODE => $src, STYPE_STATUS => $status);
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
