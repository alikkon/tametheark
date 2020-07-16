<?php

session_start();

function getAllConfs() {
  $conf = parse_ini_file('../conf/conf.php');
  $arkconfs = $conf['mypath'];
  $arks = array();
  $dh = opendir($arkconfs);
  while ($file = readdir($dh)) {
    if ((substr($file,0,1) != '.') && ($file != 'example') && is_dir($arkconfs.'/'.$file)) {
      $arks[$file] = getConf( $file );
    }
  }
  return $arks;
}

function getConf( $thisservice ) {
  $conf = parse_ini_file('../conf/conf.php');
  $arkconfs = $conf['mypath'];
  if (!preg_match('/^[a-zA-Z0-9-_]+$/',$thisservice)) { return '0'; }
  if (file_exists($arkconfs.'/'.$thisservice.'/conf')) {
    $ark = $conf;
    $contents = file($arkconfs.'/'.$thisservice.'/conf', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($contents as $line) {
      if (!preg_match('/^#.*/',$line)) {
        if (preg_match('/.+:.+/',$line)) {
          if (!isset($ark[$sa[0]])) {
            $ark[$sa[0]] = array();
          }
          $sa = explode(':',$line);
          if (preg_match('/.+=.+/',$line)) {
            $kv = explode('=',$sa[1],2);
            if (!isset($ark[$sa[0]][$sa[1]])) {
              $ark[$sa[0]][$kv[0]] = $kv[1];
            } elseif (is_array($ark[$sa[0]][$sa[1]])) {
              $ark[$sa[0]][$kv[0]][] = $kv[1];
            } else {
              $ark[$sa[0]][$kv[0]] = array($ark[$sa[0]][$kv[0]]);
              $ark[$sa[0]][$kv[0]][] = $kv[1];
            }
          } else {
            $ark[$sa[0]][$sa[1]] = null;
          }
        } elseif (preg_match('/.+=.+/',$line)) {
          $kv = explode('=',$line,2);
          $ark[$kv[0]] = $kv[1];
        }
      }
    }
    return $ark;
  }
}

function displayError($message) {
  $error = $message;
  include 'index.php';
  exit(0);
}

function doIncludes() {
  /**
   * Require the OpenID consumer code.
   */
  require_once "Auth/OpenID/Consumer.php";

  /**
   * Require the "file store" module, which we'll need to store
   * OpenID information.
   */
  require_once "Auth/OpenID/FileStore.php";

  /**
   * Require the Simple Registration extension API.
   */
  require_once "Auth/OpenID/SReg.php";

  /**
   * Require the PAPE extension module.
   */
  require_once "Auth/OpenID/PAPE.php";
}

doIncludes();

function &getStore() {
  /**
   * This is where the example will store its OpenID information.
   * You should change this path if you want the example store to be
   * created elsewhere.  After you're done playing with the example
   * script, you'll have to remove this directory manually.
   */
  $store_path = null;
  if (function_exists('sys_get_temp_dir')) {
    $store_path = sys_get_temp_dir();
  }
  else {
    if (strpos(PHP_OS, 'WIN') === 0) {
      $store_path = $_ENV['TMP'];
      if (!isset($store_path)) {
        $store_path = 'C:\Windows\Temp';
      }
    }
    else {
      $store_path = @$_ENV['TMPDIR'];
      if (!isset($store_path)) {
        $store_path = '/tmp';
      }
    }
  }
  $store_path .= DIRECTORY_SEPARATOR . '_php_consumer_test';

  if (!file_exists($store_path) &&
    !mkdir($store_path)) {
    print "Could not create the FileStore directory '$store_path'. ".
          " Please check the effective permissions.";
    exit(0);
  }
  $r = new Auth_OpenID_FileStore($store_path);

  return $r;
}

function &getConsumer() {
  /**
   * Create a consumer object using the store object created
   * earlier.
   */
  $store = getStore();
  $r = new Auth_OpenID_Consumer($store);
  return $r;
}

function getScheme() {
  $scheme = 'http';
  if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {
    $scheme .= 's';
  }
  return $scheme;
}

function getReturnTo() {
  return sprintf("%s://%s:%s%s/finish_auth.php",
                 getScheme(), $_SERVER['SERVER_NAME'],
                 $_SERVER['SERVER_PORT'],
                 dirname($_SERVER['PHP_SELF']));
}

function getTrustRoot() {
  return sprintf("%s://%s:%s%s/",
                 getScheme(), $_SERVER['SERVER_NAME'],
                 $_SERVER['SERVER_PORT'],
                 dirname($_SERVER['PHP_SELF']));
}

?>
