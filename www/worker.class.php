<?php

    include('functions.php');
    require('SourceQuery/SourceQuery.class.php');

    class ark_worker {
        const SRC_ALIVE = 1;
        const SRC_DEAD = 0;
        const SRC_DEGRADED = -1;
        var $server;
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

        function __construct ($server) {
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
            $this->sendOutput();
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
            $this->sendOutput();
        }

        function stop() {
            $this->phprun(array('stop',$this->server));
            $this->waiting();
            $this->sendoutput();
        }
    
        function update($maintainence = False) {
            if ($maintainence) {
                $this->phprun(array('maintain',$this->server));
            } else {
                $this->phprun(array('update',$this->server));
            }
            $this->waiting();
            $this->sendoutput();
        }

        function restart() {
            $this->phprun(array('restart',$this->server));
            $this->waiting();
            $this->sendoutput();
        }

        function message() {
            try {
                $res = $this->rcon->Rcon('saveworld');
            } catch ( Exception $e ) {
                $this->status = 0;
                $this->message = 'Failed to send command over rcon';
            }
            $this->status = 1;
            $this->status = 'Saving the world!';
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
                } catch (Exception $e) { $res = '';}
                if ($res) {
                    $this->message = 'Ark is running';
                    $this->status = $this::SRC_ALIVE;
                } else {
                    if (file_exists($this->conf['arkpath'].'/'.$this->server.'/pid')) {
                        $pid = rtrim(file_get_contents($this->conf['arkpath'].'/'.$this->server.'/pid'));
                        if (posix_kill($pid, 0)) {
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
            return $this->status;
        }

        // Sends the save command - unfortunately, Ark doesn't actually give any feedback on whether or not this is successful.
        function saveWorld() {
            try {
                $res = $this->rcon->Rcon('saveworld');
            } catch ( Exception $e ) {
            }
            $status = array('success' => array('Save command sent!'));
            return 1;
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
                    exec( "$sudo ../bin/wrapper.php $execstring &");
                } else {
                    exec("../bin/wrapper.php $execstring &");
                }
                $this->message = 'Sent command: '.$parameters[0];
                $this->status = 1;
            } else {
                return false;
            }
        }

    }

?>
