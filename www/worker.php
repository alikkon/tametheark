<?php
    define('STYPE_STATUS','1');
    define('STYPE_CODE','0');
    define('STYPE_BOTH','2');
    define('SRC_ALIVE','0');
    define('SRC_DEAD','1');
    define('SRC_DEGRADED','2');
    include_once('./worker.class.php');

    if (!isset($_POST['server']) || !isset($_POST['command'])) {
        print json_encode(array('error' => 'Malformed request: Missing parameter.')); exit;
    } else {
        $command = $_POST['command'];
        $server = $_POST['server'];
    }

    $ark = new ark_worker($server);
    if ($command == 'status') {
        $ark->sendOutput();
    } elseif ($command == 'clearop') {
        $ark->resetOp();
    } elseif ($command == 'startup') {
        $ark->start();
    } elseif ($command == 'shutdown') {
        $ark->stop();
    } elseif ($command == 'restart') {
        $ark->restart();
    } elseif ($command == 'update') {
        $ark->update();
    } elseif ($command == 'maintain') {
        $ark->update(True);
    } elseif ($command == 'save') {
        $ark->save();
    }
?>
