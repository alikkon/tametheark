<?php
    header('Content-Type: text/plain');
    include_once('./worker.class.php');
    $server = substr($_SERVER['SCRIPT_URL'],strrpos($_SERVER['SCRIPT_URL'],'/')+1,strlen($_SERVER['SCRIPT_URL']));
    $ark = new ark_worker($server);
    $dynamicAllowedVars = array(
        'TamingSpeedMultiplier',
        'HarvestAmountMultiplier',
        'XPMultiplier',
        'MatingIntervalMultiplier',
        'BabyMatureSpeedMultiplier',
        'EggHatchSpeedMultiplier'
    );
    foreach ($ark->conf['opts'] as $option => $value) {
        if (in_array($option,$dynamicAllowedVars)) {
            print "$option=$value\n";
        }
    }
?>
