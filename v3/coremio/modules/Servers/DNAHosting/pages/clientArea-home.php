<?php
    defined("CORE_FOLDER") or exit("You can not get in here!");
    $LANG     = isset($LANG) ? $LANG : array();
    $username = isset($username) ? $username : '';
    $domain   = isset($domain) ? $domain : '';
    $server   = isset($server) ? $server : array();
?>
<div class="dnahosting-panel">
    <table class="table">
        <tr>
            <th><?php echo $LANG['domain']; ?></th>
            <td><?php echo htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th><?php echo $LANG['username']; ?></th>
            <td><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th><?php echo $LANG['ftp-host']; ?></th>
            <td>ftp.<?php echo htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th><?php echo $LANG['ftp-port']; ?></th>
            <td>21</td>
        </tr>
    </table>
</div>
