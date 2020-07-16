<?php
    define('STYPE_STATUS','1');
    define('STYPE_CODE','0');
    define('STYPE_BOTH','2');
    define('SRC_ALIVE','0');
    define('SRC_DEAD','1');
    define('SRC_DEGRADED','2');
    include_once('./cluster_worker.class.php');
    $conf = parse_ini_file('../conf/conf.php');

    if (isset($conf['steam_ids']) and ($conf['steam_ids'])) {
        if ((!$_SESSION['steam_id']) || (!in_array($_SESSION['steam_id'],$conf['steam_ids']))) {
            print json_encode(array('error' => 'Authentication expired. Reload this page.'));
            exit;
        }
    }
/*
    if (!isset($_POST['cluster']) || !isset($_POST['command'])) {
        print json_encode(array('error' => 'Malformed request: Missing parameter.')); exit;
    } else {
        $command = $_POST['command'];
        $cluster = $_POST['cluster'];
    }
*/

    $command = 'cluster_startup';
    $cluster = 'arktopolisclusterA';

    $arkcluster = new ark_cluster_worker($cluster);
    if ($command == 'cluster_status') {
        $arkcluster->sendOutput();
    } elseif ($command == 'cluster_clearop') {
        $arkcluster->resetOp();
    } elseif ($command == 'cluster_startup') {
        $arkcluster->start();
    } elseif ($command == 'cluster_shutdown') {
        $arkcluster->stop();
    } elseif ($command == 'cluster_restart') {
        $arkcluster->restart();
    } elseif ($command == 'cluster_update') {
        $arkcluster->update();
    } elseif ($command == 'cluster_maintain') {
        $arkcluster->update(True);
    } elseif ($command == 'cluster_save') {
        $arkcluster->save();
    }
?>
