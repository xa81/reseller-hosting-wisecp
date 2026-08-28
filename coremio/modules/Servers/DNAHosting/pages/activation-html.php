<?php
    defined("CORE_FOLDER") or exit("You can not get in here!");

    $LANG     = $module->lang;
    $options  = isset($options) ? $options : array();
    $config   = isset($options["config"]) ? $options["config"] : array();
    $ftp      = isset($options["ftp_info"]) ? $options["ftp_info"] : array();
    $domain   = isset($options["domain"]) ? $options["domain"] : '';
    $server   = isset($server) ? $server : array();
    $secure   = isset($server["secure"]) ? $server["secure"] : false;
    $ip       = isset($server["ip"]) ? $server["ip"] : '';
    $port     = isset($server["port"]) ? $server["port"] : 0;
    $panelUrl = ($secure ? 'https' : 'http') . '://' . $ip . ':' . $port;
    $esc      = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>
<table cellpadding="6" cellspacing="0" border="0">
    <tr><td><strong><?php echo $LANG["domain"]; ?></strong></td><td><?php echo $esc($domain); ?></td></tr>
    <tr><td><strong><?php echo $LANG["login-panel"]; ?></strong></td>
        <td><a href="<?php echo $esc($panelUrl); ?>"><?php echo $esc($panelUrl); ?></a></td></tr>
    <tr><td><strong><?php echo $LANG["username"]; ?></strong></td>
        <td><?php echo $esc(isset($config["user"]) ? $config["user"] : ''); ?></td></tr>
    <tr><td><strong><?php echo $LANG["password"]; ?></strong></td>
        <td><?php echo $esc(isset($config["password"]) ? $config["password"] : ''); ?></td></tr>
    <tr><td><strong><?php echo $LANG["ftp-host"]; ?></strong></td>
        <td><?php echo $esc(isset($ftp["host"]) ? $ftp["host"] : ''); ?></td></tr>
    <tr><td><strong><?php echo $LANG["ftp-port"]; ?></strong></td>
        <td><?php echo $esc(isset($ftp["port"]) ? $ftp["port"] : 21); ?></td></tr>
</table>
