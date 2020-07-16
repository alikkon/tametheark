#!/usr/bin/php
<?php
    $conf = parse_ini_file('../conf/conf.php');
    include('../www/functions.php');
    require('../www/SourceQuery/SourceQuery.class.php');

    $me = array_shift($argv);
    $cmd = array_shift($argv);
    $server = array_shift($argv);
    $parameters = $argv;
    $cluster = '';
    $cluster_cmd = '';
    $ark = '';
    print "$me\n";
    print "$cmd\n";
    print "$server\n";
    print_r($parameters);

    if (!isset($server) || !isset($cmd)) {
        print "missing parameters."; exit(1);
    }

    if (substr($cmd,0,7) == 'cluster') {
        // We're performing commands against a ccluster!
        $cmd_info = explode('_',$cmd);
        $cluster_cmd = $cmd_info[1];
        $cluster = $server;
        $server = '';
    }

    umask(0002);

    if ($cluster) {
        $all_arks = getAllConfs();
        foreach ($all_arks as $server => $ark) {
            $all_arks[$server]['rcon'] = setup_rcon($ark);
        }
        if (in_array($cluster_cmd,array('maintain', 'update'))) {
            if ($cluster_cmd == 'maintain') {
                $alive_arks = 0;
                foreach ($all_arks as $server => $ark) {
                    file_put_contents($ark['mypath'].'/'.$server.'/op',"command=restart");
                    $all_arks[$server]['server_status'] = getStatus($ark,$server,STYPE_CODE);
                    if ($all_arks[$server]['server_status'] != SRC_DEAD) {
                        $res = $ark['rcon']->Rcon( 'SetMessageOfTheDay "The server will be going down shortly for maintenance."' );
                        $alive_arks += 1;
                    }
                }
                if ($alive_arks) {
                    $i = $maintintervals;
                    while ($i > 0) {
                        foreach ($all_arks as $server => $ark) {
                            file_put_contents($ark['mypath'].'/'.$server.'/op',"command=restart\nstage=notification$i");
                            $res = $ark['rcon']->Rcon( "broadcast \"Maintenance begins in $i minutes\"" );
                        }
                        sleep(60);
                        $i--;
                    }
                    foreach ($all_arks as $server => $ark) {
                        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=maintenance\nstage=save");
                        saveWorld($ark);
                        sleep(10);
                    }
                    foreach ($all_arks as $server => $ark) {
                        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=maintenance\nstage=shutdown");
                        endArk($ark,$server);
                        if (file_exists($ark['mypath'].'/'.$server.'/pid')) { unlink($ark['mypath'].'/'.$server.'/pid'); }
                    }
                }
                foreach ($all_arks as $server => $ark) {
                    if (isset($rsync)) {
                        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=maintenance\nstage=backup");
                        exec("$rsync -avHCxl ".$ark['arkpath']."/ShooterGame/Saved/$server ".$ark['mypath'].'/'.$server."/Saved.bak");
                    }
                }
            }
            $unique_bins = 0;
            foreach ($all_arks as $server => $ark) {
                if ((!$lastarkpath) || ($lastarkpath != $ark['arkpath'])) {
                    $cluster_info = array('steampath' => $ark['steampath'],
                                          'arkpath' => $ark['arkpath'],
                                          'mypath' => $ark['mypath'],
                                          'name' => $ark['clusterid']);
                    #updateArkCluster( $cluster_info );
                    $lastarkpath = $ark['arkpath'];
                    $unique_bins += 1;
                }
            }
            if ($unique_bins > 1) {
                foreach ($all_arks as $server => $ark) {
                    file_put_contents($ark['mypath'].'/'.$server.'/op',"command=maintenance\nstage=patch");
                    updateArk( $ark, $server );
                }
            } else {
                updateArkCluster($cluster_info);
            }
            if ($cluster_cmd == 'maintain') {
                foreach ($all_arks as $server => $ark) {
                    if ($ark['server_status'] != SRC_DEAD) {
                        file_put_contents($ark['mypath'].'/'.$server.'/op',"command=maintenance\nstage=startup");
                        startArk($ark,$server);
                        $res = $ark['rcon']->Rcon( 'SetMessageOfTheDay "'.$ark['motd'].'"' );
                    }
                    unlink($ark['mypath'].'/'.$server.'/op');
                }
            }
            if ($unique_bins > 1) {
                foreach ($all_arks as $server => $ark) {
                    unlink($ark['mypath']."/".$server."/steam.out");
                }
            } else {
                unlink($cluster_info['mypath']."/".$cluster_info['name']."-steam.out");
            }
        } else {
            foreach ($all_arks as $server => $ark) {
                if (isset($ark['clusterid']) && ($ark['clusterid'] == $cluster)) {
                    print "Running $cluster_cmd against $server\n";
                    print_r($ark);
                    #runCmdOnServer($ark, $cluster_cmd, $server);
                }
            }
        }
    } else {
        $ark = getConf($server);
        $ark['rcon'] = setup_rcon($ark);
        runCmdOnServer($ark, $cmd, $server);
    }

    function setup_rcon($ark) {
        $rcon = new SourceQuery();
        try
        {
            $rcon->Connect( 'localhost', $ark['rconport'], 1, SourceQuery :: SOURCE );
            $rcon->SetRconPassword( $ark['adminpassword'] );
        }
        catch( Exception $e )
        {
        }

        return $rcon;
    }

    function runCmdOnServer($ark, $cmd, $server) {

        $cmds = array('save','start', 'shutdown','update','restart','maintenance','restart',
                      'cluster_save','cluster_start','cluster_shutdown','cluster_update','cluster_maintain','cluster_restart'
                      );

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
            if (isset($rsync)) {
                file_put_contents($ark['mypath'].'/'.$server.'/op',"command=update\nstage=backup");
                exec("$rsync -avHCxl ".$ark['arkpath']."/ShooterGame/Saved/$server ".$ark['mypath'].'/'.$server."/Saved.bak");
            }
            file_put_contents($ark['mypath'].'/'.$server.'/op',"command=update\nstage=patch");
            updateArk($ark,$server);
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
    }

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
        $opts = buildOpts($ark, 'opts');
        $flags = buildOpts($ark, 'flags');
        $cmd=(isset($nohup) ? $nohup : '').' '.(isset($nice) ? $nice : '')." ./ShooterGameServer ".($ark['map'] ? $ark['map'] : 'TheIsland')."?listen$opts?AltSaveDirectoryName=$server".
        ($ark['customdynamicconfigurl'] ? '?customdynamicconfigurl='.$ark['customdynamicconfigurl'].'/'.$server.' -UseDynamicConfig' : '').
        ($ark['clusterid'] ? ' -NoTransferFromFiltering -clusterid='.$ark['clusterid'] : '').
        $flags.
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
        copy($ark['arkpath'].'/version.txt',$ark['mypath'].'/'.$server.'/version.txt');
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

    function updateArkCluster( $cluster ) {
        exec($cluster['steampath'] . " +login anonymous +force_install_dir ".$cluster['arkpath']." +app_update 376030 +quit    > ".$cluster['mypath']."/".$cluster['name']."-steam.out 2>&1", $result, $exit);
    }
    
    // Take the list of options and build them into a command string //
    function buildOpts($ark, $opttype) {
        if ($opttype == 'opts') {
            $separator = "?";
        } else { $separator = " -"; }
        $opts = "";
        foreach ($ark[$opttype] as $k => $v) {
            if (!$v) {
                $opts .= "$separator$k";
            } elseif (!is_array($v)) {
                $opts .= "$separator$k=$v";
            } else {
                foreach ($v as $vv) {
                    $opts .= "$separator$k=$vv";
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
