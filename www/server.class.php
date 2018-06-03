<?php

class ark {

    public $arkname;
    public $gameiniopts = array();
    public $settingsiniopts = array();
    public $arkpath = '/home/steam/ArkServer';
    public $steampath = '/home/steam/steamcmd';
    public $servername;
    public $serverpassword;
    public $adminpassword;
    public $rconport = '3330';
    public $queryport = '27015';
    public $port = '7791';
    public $map = 'TheIsland';
    public $clusterid;
    public $clusterbinary = false;
    public $motd = 'This ark has been tamed.';
    public $isNew = false;

    function __construct( $service ) {
        # Store the name and confdir passed to us.
        $this->arkname = $service;
        if (substr($confdir,strlen($confdir),1) != '/') { $confdir = $confdir.'/'; }
        $this->confdir = $confdir
        # Validate the name of the ark.
        if (!preg_match('/^[a-zA-Z0-9-_]+$/',$service)) { throw new Exception('Ark conf name invalid.'); }
        # Check for file existence.
        if (file_exists($confdir.$service) else ) {
            # Load the file into array.
            $contents = file($service, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            # Go through each line and split them out.
            foreach ($contents as $line) {
                # Make sure the line is a key/value pair. Also make sure the line isn't commented.
                if (preg_match('/.+=.+/',$line) && !preg_match('/^#.*/',$line)) {
                    $kv = explode('=',$line);
                    $key = array_shift($kv);
                    $value = implode('=',$kv);
                    if (strtolower($key) == 'arkpath') {
                        $this->arkpath = $value;
                    } elseif (strtolower($key) == 'steampath') {
                        $this->steampath = $value;
                    } elseif (strtolower($key) == 'servername') {
                        $this->servername = $value;
                    } elseif (strtolower($key) == 'serverpassword') {
                        $this->serverpassword = $value;
                    } elseif (strtolower($key) == 'adminpassword') {
                        $this->adminpassword = $value;
                    } elseif (strtolower($key) == 'rconport') {
                        $this->rconport = $value;
                    } elseif (strtolower($key) == 'queryport') {
                        $this->queryport = $value;
                    } elseif (strtolower($key) == 'map') {
                        $this->map = $value;
                    } elseif (strtolower($key) == 'clusterid') {
                        $this->clusterid = $value;
                    } elseif (strtolower($key) == 'clusterbinary') {
                        $this->clusterbinary = $value;
                    } elseif (!strpos($key,':')) {
                        $sa = explode(':',$key);
                        $key = array_shift($sa);
                        $subkey = implode(':',$sa);
                        if ($key == 'opts') {
                            # Determine which array the option goes into and stuff it into the correct one.
                        }
                    }
                }
            }
        } else { $this->isNew = true; }
        return $this;
    }

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
            if (isset($rsync)) {
                file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=update\nstage=backup");
                exec("$rsync -avHCxl ".$ark['arkpath']."/ShooterGame/Saved/$server ".$ark['arkpath'].'/'.$server."/Saved.bak");
            }
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
            if (isset($rsync)) {
                file_put_contents($ark['arkpath'].'/'.$server.'/op',"command=maintenance\nstage=backup");
                exec("$rsync -avHCxl ".$ark['arkpath']."/ShooterGame/Saved/$server ".$ark['arkpath'].'/'.$server."/Saved.bak");
            }
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
        $cmd="$nohup $nice ./ShooterGameServer ".($ark['map'] ? $ark['map'] : 'TheIsland')."$opts?AltSaveDirectoryName=$server?listen".
        ($ark['clusterid'] ? ' -NoTransferFromFiltering -clusterid='.$ark['clusterid'] : '').
        " -server -servergamelog > ".$ark['arkpath']."/".$server."/out 2>&1 & echo $! > ".$ark['arkpath']."/".$server."/pid";
        file_put_contents($ark['arkpath'].'/'.$server.'/startCmd',$cmd);
        // Spawn process.
        exec($cmd);
        // Get PID - we'll use this to determine if the process is still running throughout startup.
        $pid = rtrim(file_get_contents($ark['arkpath'].'/'.$server.'/pid'));
        $res = 0;
        file_put_contents($ark['arkpath'].'/'.$server.'/op', "command=Startup\nstage=Waiting for server to become responsive...");
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
