<?php
    defined("CORE_FOLDER") or exit("You can not get in here!");

    $LANG    = $module->lang;
    $options = isset($options) ? $options : array();
    $config  = isset($options["config"]) ? $options["config"] : array();
    $domain  = isset($options["domain"]) ? $options["domain"] : '';
?>
<table class="table">
    <tr>
        <th><?php echo $LANG["domain"]; ?></th>
        <td><?php echo htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <tr>
        <th><?php echo $LANG["username"]; ?></th>
        <td><?php echo htmlspecialchars(isset($config["user"]) ? $config["user"] : '', ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
</table>
