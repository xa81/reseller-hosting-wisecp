<?php
    defined("CORE_FOLDER") or exit("You can not get in here!");

    $LANG     = $module->lang;
    $options  = isset($options) ? $options : array();
    $config   = isset($options["config"]) ? $options["config"] : array();
    $ftp      = isset($options["ftp_info"]) ? $options["ftp_info"] : array();
    $domain   = isset($options["domain"]) ? $options["domain"] : '';
    $server   = isset($server) ? $server : array();
    $panelUrl = ($server["secure"] ? 'https' : 'http') . '://' . $server["ip"] . ':' . $server["port"];

    echo $LANG["domain"] . ": " . $domain . "\n";
    echo $LANG["login-panel"] . ": " . $panelUrl . "\n";
    echo $LANG["username"] . ": " . (isset($config["user"]) ? $config["user"] : '') . "\n";
    echo $LANG["password"] . ": " . (isset($config["password"]) ? $config["password"] : '') . "\n";
    echo $LANG["ftp-host"] . ": " . (isset($ftp["host"]) ? $ftp["host"] : '') . "\n";
    echo $LANG["ftp-port"] . ": " . (isset($ftp["port"]) ? $ftp["port"] : 21) . "\n";
