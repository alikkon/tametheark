<?php

    include_once('functions.php');
    require_once('SourceQuery/SourceQuery.class.php');
    include_once('./worker.class.php');

    class ark_cluster_worker {
        const SRC_ALIVE = 1;
        const SRC_DEAD = 0;
        const SRC_DEGRADED = -1;
        var $cluster;
        var $version;
        var $message;
        var $path;
        var $extra;
        var $op;
        var $arks = array();

        function __construct ($cluster) {
            $this->cluster = $cluster;
            $arks = getAllConfs();
            foreach ($arks as $server => $ark) {
                if ($ark['clusterid'] == $cluster) {
                    $this->arks[$server] = new ark_worker($server);
                    $this->path = $ark['arkpath'];
                }
            }
            $this->opCheck();
            return $this;
        }

        function resetOp () {
            foreach ($this->arks as $server => $ark) {
                $result = $ark->resetOp();
            }
        }

        function opCheck() {
            $cluster = $this->cluster;
            $oplist = array();
            foreach ($this->arks as $server => $ark) {
                $ark->opCheck();
                if ( $ark->op ) {
                    $oplist[] = $ark->op;
                }
            }
            if ($oplist) {
                $this->op = 'active';
                $this->message = "One or more servers in this cluster are currently processing commands.";
            }
            if (file_exists("$this->path/$cluster-steam.out")) {
                 $this->extra = "<br>\n<br>\n".preg_replace('/\n/',"<br>\n",file_get_contents("$this->path/$cluster-steam.out"));
            }

        }

        function start() {
            $this->phprun(array('cluster_start',$this->cluster));
            $this->sendOutput();
        }

        function stop() {
            $this->phprun(array('cluster_stop',$this->cluster));
            $this->sendoutput();
        }
    
        function update($maintainence = False) {
            if ($maintainence) {
                $this->phprun(array('cluster_maintain',$this->cluster));
            } else {
                $this->phprun(array('cluster_update',$this->cluster));
            }
            $this->sendoutput();
        }

        function restart() {
            $this->phprun(array('cluster_restart',$this->cluster));
            $this->sendoutput();
        }

        function save() {
            $this->saveWorld();
            $this->sendoutput();
        }

        function saveWorld() {
            foreach ($this->arks as $server => $ark) {
                $result = $ark->saveWorld();
            }
        }
    
        function sendOutput() {
            $message = array();
            $response = array();
            $response[] = $this->message;
            if ( $this->extra ) { $response[] = $this->extra; }
            $message['success'] = $response;
            $tosend = json_encode($message);
            header('Connection: close');
            header("Content-Length: ".strlen($tosend));
            print $tosend;
            flush();
        }

        function phprun($parameters) {
            $validcommands = array('cluster_start', 'cluster_stop', 'cluster_restart', 'cluster_update', 'cluster_maintain');
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
    }

?>
