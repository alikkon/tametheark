<?php

    include_once('functions.php');
    require_once('SourceQuery/SourceQuery.class.php');

    class ark_worker {
        const SRC_ALIVE = 1;
        const SRC_DEAD = 0;
        const SRC_DEGRADED = -1;
        var $server;
        var $version;
        var $message;
        var $extra;
        var $refresh = 15000;
        var $newcmd;
        var $status = 0;
        var $rcon;
        var $conf = array();
        var $pid;
        var $op;
        var $stage;
        var $players = array();
        var $ischild = False;

        function __construct ($server, $ischild = False) {
            $this->server = $server;
            $this->conf = getConf($server);
            if (!$this->conf) {
                $this->sendOutput(array('error' => 'Unable to load arkconf '.$server.'. Check your configuration and try again.'));
                return 0;
            }
            if (!file_exists($this->conf['mypath']."/".$this->server)) {
                $this->phprun(array("mkdir",$this->conf['mypath'].'/'.$this->server));
            }
            $this->rcon = new SourceQuery();
            try {
                $this->rcon->Connect('localhost', $this->conf['rconport'], 1, SourceQuery::SOURCE);
                $this->rcon->SetRconPassword($this->conf['adminpassword']);
            } catch( Exception $e ) { $this->rcon = False; }
            $this->opCheck();
            if (! $this->op) {
                $this->getStatus();
            }
            if ($ischild) { $this->ischild = $ischild; }
            return $this;
        }

        function __destruct () {
            if ($this->rcon) {
                $this->rcon->Disconnect();
            }
        }

        function waiting () {
            $this->refresh = 3000;
            $this->newcmd = 'status';
        }

        function resetOp () {
            $path = $this->conf['mypath'];
            $server = $this->server;
            if (file_exists("$path/$server/op")) {
                unlink("$path/$server/op");
                $this->waiting();
            }
            $this->opCheck();
            if (!$this->ischild) { $this->sendOutput(); }
        }

        function opCheck() {
            $path = $this->conf['mypath'];
            $server = $this->server;
            if (file_exists("$path/$server/op")) {
                $opin = file("$path/$server/op");
                $currop = '';
                $subop = '';
                foreach ($opin as $op) {
                    $op = rtrim($op);
                    if (substr($op,0,7) == 'command') {
                        $this->op = substr($op,8,strlen($op)-8);
                        $this->message = $this->op." status: ";
                    }
                    if (substr($op,0,5) == 'stage') {
                        $this->stage = substr($op,6,strlen($op)-6);
                        $this->message .= $this->stage;
                    }
                }
                $this->message .= " in progress";
                if (file_exists("$path/$server/steam.out")) {
                    $this->extra = "<br>\n<br>\n".preg_replace('/\n/',"<br>\n",file_get_contents("$path/$server/steam.out"));
                }
                return 1;
            } else {
                return 0;
            }
        }

        function start() {
            $this->phprun(array('start',$this->server));
            $this->waiting();
            if (!$this->ischild) { $this->sendOutput(); }
        }

        function stop() {
            $this->phprun(array('stop',$this->server));
            $this->waiting();
            if (!$this->ischild) { $this->sendOutput(); }
        }
    
        function update($maintainence = False) {
            if ($maintainence) {
                $this->phprun(array('maintain',$this->server));
            } else {
                $this->phprun(array('update',$this->server));
            }
            $this->waiting();
            if (!$this->ischild) { $this->sendOutput(); }
        }

        function restart() {
            $this->phprun(array('restart',$this->server));
            $this->waiting();
            if (!$this->ischild) { $this->sendOutput(); }
        }

        function save() {
            $this->saveWorld();
            $this->waiting();
            if (!$this->ischild) { $this->sendOutput(); }
        }

        function saveWorld() {
            try {
                $res = $this->rcon->Rcon('saveworld');
            } catch ( Exception $e ) {
                $this->status = 0;
                $this->message = 'Failed to send command over rcon';
            }
            $this->status = 1;
            $this->message = 'Saving the world!';
            return True;
        }
    
        // Determine the current status of the Ark service in question //
        function getStatus() {
            if (! $this->rcon) {
                $this->message = 'Ark is not running.';
                $this->status = $this::SRC_DEAD;
            } else {
                try {
                    $res = $this->rcon->Rcon('listplayers');
                    $this->players = $res;
                } catch (Exception $e) { $res = '';}
                if ($res) {
                    $this->message = 'Ark is running';
                    $this->status = $this::SRC_ALIVE;
                } else {
                    if (file_exists($this->conf['arkpath'].'/'.$this->server.'/pid')) {
                        $pid = rtrim(file_get_contents($this->conf['arkpath'].'/'.$this->server.'/pid'));
                        if (phprun_return(array('checkpid', $this->server)) == 1 ) {
                            $this->message = 'Ark is running but not responding.';
                            $this->status = $this::SRC_DEGRADED;
                        } else {
                            $this->message = 'Ark is not running.';
                            $this->status = $this::SRC_DEAD;
                        }
                    } else {
                        $this->message = 'Ark is not running.';
                        $this->status = $this::SRC_DEAD;
                    }
                }
            }
            if (file_exists($this->conf['mypath'].'/'.$this->server.'/version.txt')) {
                $this->version = file_get_contents($this->conf['mypath'].'/'.$this->server.'/version.txt');
            }
            return $this->status;
        }

        function sendOutput() {
            $message = array();
            $response = array();
            $response[] = $this->message;
            if ( $this->extra ) { $response[] = $this->extra; }
            if ($this->status > 0) {
                $message['success'] = $response;
            } else {
                $message['error'] = $response;
            }
            if ( $this->newcmd ) {
                $message['overridecommand'] = $this->newcmd;
                $message['newtimer'] = $this->refresh;
            }
            if ( $this->players ) {
                $message['players'] = $this->players;
            }
            if ( $this->version ) {
                $message['version'] = $this->version;
            }
            $tosend = json_encode($message);
            header('Connection: close');
            header("Content-Length: ".strlen($tosend));
            print $tosend;
            flush();
        }

        function phprun($parameters) {
            $validcommands = array('start', 'stop', 'restart', 'update', 'maintain');
            if (in_array($parameters[0], $validcommands)) {
                $execstring = '';
                foreach ($parameters as $p) {
                    if (!$execstring) {
                        $execstring = $p;
                    } else {
                        $execstring = "$execstring $p";
                    }
                }
                if (isset($this->conf['sudo'])) {
                    $sudo = $this->conf['sudo'];
                    exec( "$sudo ../bin/wrapper.php $execstring >/tmp/out.txt &");
                } else {
                    exec("../bin/wrapper.php $execstring &");
                }
                $this->message = 'Sent command: '.$parameters[0];
                $this->status = 1;
            } else {
                return false;
            }
        }

        function phprun_return($parameters) {
            $validcommands = array('checkpid');
            if (in_array($parameters[0], $validcommands)) {
                $execstring = '';
                foreach ($parameters as $p) {
                    if (!$execstring) {
                        $execstring = $p;
                    } else {
                        $execstring = "$execstring $p";
                    }
                }
                if (isset($this->conf['sudo'])) {
                    $sudo = $this->conf['sudo'];
                    exec( "$sudo ../bin/wrapper.php $execstring", $output);
                } else {
                    exec("../bin/wrapper.php $execstring", $output);
                }
                $this->message = 'Sent command: '.$parameters[0];
                $this->status = 1;
                return $output;
            } else {
                return false;
            }
        }

    }

?>
