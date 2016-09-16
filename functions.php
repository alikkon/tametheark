<?php

function getAllConfs() {
  $arkconfs = './arkconfs/';
  $arks = array();
  $dh = opendir($arkconfs);
  while ($file = readdir($dh)) {
    if (substr($file,0,1) != '.') {
      $arks[$file] = getConf( $file );
    }
  }
  return $arks;
}

function getConf( $thisservice ) {
  $arkconfs = './arkconfs/';
  if (!preg_match('/^[a-zA-Z0-9]+$/',$thisservice)) { return '0'; }
  if (file_exists($arkconfs.$thisservice)) {
    $ark = array();
    $contents = file($arkconfs.$thisservice, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($contents as $line) {
      if (preg_match('/.+=.+/',$line) && !preg_match('/^#.*/',$line)) {
        $kv = explode('=',$line,2);
        if (!strpos($kv[0],':')) {
          $ark[$kv[0]] = $kv[1];
        } else {
          $sa = explode(':',$kv[0]);
          if (!isset($ark[$sa[0]])) {
            $ark[$sa[0]] = array();
          }
          if (!isset($ark[$sa[0]][$sa[1]])) {
            $ark[$sa[0]][$sa[1]] = $kv[1];
          } elseif (is_array($ark[$sa[0]][$sa[1]])) {
            $ark[$sa[0]][$sa[1]][] = $kv[1];
          } else {
            $ark[$sa[0]][$sa[1]] = array($ark[$sa[0]][$sa[1]]);
            $ark[$sa[0]][$sa[1]][] = $kv[1];
          }
        }
      }
    }
    return $ark;
  }
}

?>
