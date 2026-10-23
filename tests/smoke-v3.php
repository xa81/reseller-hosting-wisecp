<?php
/**
 * Smoke test for the WISECP 3 package (v3/) against a real WISECP 3 core and a fake
 * cPanel/Plesk. Not part of any package.
 *
 * Copy it to the temp/ directory of a WISECP 3 test installation that has the module
 * installed and run it with WISECP's PHP:  php temp/smoke-v3.php
 * WISECP 3 has no CLI bootstrap, so the script loads index.php for a missing route and
 * runs its checks once the core is up. Module log rows it writes are deleted at the end.
 */
$_SERVER['SERVER_ADDR']    = '127.0.0.1';
$_SERVER['DOCUMENT_ROOT']  = dirname(__DIR__);
$_SERVER['PHP_SELF']       = '/index.php';
$_SERVER['REQUEST_URI']    = '/__dna_smoke_v3';
$_SERVER['HTTP_HOST']      = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
chdir(dirname(__DIR__));

register_shutdown_function(function () {
    while (ob_get_level()) ob_end_clean();
    dna_smoke_v3();
});
ob_start();
include dirname(__DIR__) . '/index.php';

/** Replaces cURL inside the module with a fake panel; the module has no public hook for it. */
function dna_use_transport($module, callable $transport)
{
    $set = function ($t) { $this->transport = $t; $this->panel = null; $this->drivers = array(); };
    Closure::bind($set, $module, get_class($module))($transport);
}

function dna_smoke_v3()
{
    $ok = 0; $fail = 0;
    $check = function ($label, $cond, $extra = '') use (&$ok, &$fail) {
        if ($cond) { $ok++; echo "  ok   $label\n"; } else { $fail++; echo "  FAIL $label $extra\n"; }
    };

    Config::set('options', ['save-module-log' => true], true);
    if (!class_exists('DNAHosting_Module')) include MODULE_DIR . 'Servers' . DS . 'DNAHosting' . DS . 'DNAHosting.php';

    $state = ['exists' => false, 'suspended' => 0, 'domain' => 'example-shop.com'];
    $calls = [];
    $cpanel = function ($method, $url, $headers, $body, $timeout) use (&$state, &$calls) {
        if (strpos($url, '/enterprise/control/agent.php') !== false) return ['status' => 404, 'body' => 'not plesk', 'error' => ''];
        $fn = basename(parse_url($url, PHP_URL_PATH));
        $calls[] = [$fn, $url];
        $ok = function ($data = []) { return ['status' => 200, 'body' => json_encode(['metadata' => ['result' => 1], 'data' => $data]), 'error' => '']; };
        switch ($fn) {
            case 'listaccts': return $ok(['acct' => []]);
            case 'listpkgs': return $ok(['pkg' => [['name' => 'resel_Basic'], ['name' => 'resel_Pro']]]);
            case 'accountsummary':
                if (!$state['exists']) return ['status' => 200, 'body' => json_encode(['metadata' => ['result' => 0, 'reason' => 'Account does not exist']]), 'error' => ''];
                return $ok(['acct' => [['domain' => $state['domain'], 'diskused' => '120M', 'disklimit' => '1024M', 'suspended' => $state['suspended']]]]);
            case 'createacct': $state['exists'] = true; return $ok();
            case 'suspendacct': $state['suspended'] = 1; return $ok();
            case 'unsuspendacct': $state['suspended'] = 0; return $ok();
            case 'passwd': case 'changepackage': return $ok();
            case 'removeacct': $state['exists'] = false; return $ok();
            case 'showbw': return $ok(['bandwidth' => [['acct' => [['user' => $GLOBALS['dna_user'], 'totalbytes' => 52428800, 'limit' => 1073741824]]]]]);
            case 'create_user_session': return $ok(['url' => 'https://203.0.113.10:2083/cpsess7654321/login/?session=resel:abc:create_user_session,deadbeef']);
            case 'nostatus': return ['status' => 200, 'body' => json_encode(['data' => []]), 'error' => ''];
        }
        return ['status' => 500, 'body' => 'unexpected ' . $fn, 'error' => ''];
    };

    $server = ['id' => 0, 'name' => 'panel.example.com', 'ip' => '203.0.113.10', 'port' => 2087, 'secure' => 1,
               'username' => 'resel', 'password' => 'WHMTOKEN0123456789ABCDEF', 'access_hash' => ''];

    echo "== cPanel\n";
    $m = new DNAHosting_Module($server, []);
    dna_use_transport($m, $cpanel);
    $check('lang loaded into translator', DNAHosting_Support::t('error-no-session') !== 'error-no-session');
    $check('host is the server name', DNAHosting_Support::host($server) === 'panel.example.com');
    $check('tls verified for host name', DNAHosting_Support::verifiesTls($server));
    $check('tls not claimed for bare ip', !DNAHosting_Support::verifiesTls(array_merge($server, ['name' => 'My server'])));
    $check('testConnect', $m->testConnect() === true, (string) $m->error);
    $check('connects to the host name', strpos($calls[0][1] ?? '', 'https://panel.example.com:2087/') === 0, $calls[0][1] ?? '');
    $plans = $m->getPlans();
    $check('getPlans', is_array($plans) && count($plans) === 2, (string) $m->error);

    $m->order   = ['id' => 0];
    $m->user    = ['email' => 'owner@example.com', 'full_name' => 'Smoke Owner'];
    $m->options = ['domain' => 'Example-Shop.com', 'creation_info' => ['plan' => 'Pro']];
    $r = $m->createAccount('Example-Shop.com', $m->options);
    $check('createAccount', is_array($r) && !empty($r['username']), (string) $m->error);
    $GLOBALS['dna_user'] = $r['username'] ?? '';
    $GLOBALS['dna_pw']   = $r['password'] ?? '';
    $m->config['user'] = $GLOBALS['dna_user'];

    $check('suspend', $m->suspend() === true, (string) $m->error);
    $check('unsuspend', $m->unsuspend() === true, (string) $m->error);
    $check('changePassword', $m->changePassword('', 'P&ss<w0rd>"x') === true, (string) $m->error);
    $check('change_plan', $m->change_plan('resel_Basic') === true, (string) $m->error);
    $disk = $m->getDisk();
    $check('getDisk', is_array($disk) && $disk['used'] === 125829120, json_encode($disk));
    $bw = $m->getBandwidth();
    $check('getBandwidth', is_array($bw) && $bw['limit'] === 1073741824, json_encode($bw));
    $showbw = array_values(array_filter($calls, function ($c) { return $c[0] === 'showbw'; }));
    $check('showbw filtered to the account', $showbw && strpos($showbw[0][1], 'searchtype=user') !== false, $showbw[0][1] ?? '');
    $url = $m->driver()->createSession($GLOBALS['dna_user'], 'cpaneld', '');
    $check('session url', is_string($url) && strpos($url, 'cpsess7654321') !== false);

    // Ownership guard: the account serves another domain.
    $state['domain'] = 'someone-else.com';
    $g = new DNAHosting_Module($server, ['config' => ['user' => $GLOBALS['dna_user']], 'domain' => 'example-shop.com']);
    dna_use_transport($g, $cpanel);
    $check('cpanel guard refuses password change', $g->changePassword('', 'Whatever1!') === false && strpos((string) $g->error, 'someone-else.com') !== false, (string) $g->error);
    $check('cpanel guard refuses termination', $g->removeAccount() === false && $state['exists'], (string) $g->error);
    $state['domain'] = 'example-shop.com';

    $check('removeAccount', $m->removeAccount() === true && !$state['exists'], (string) $m->error);
    $check('removeAccount again (already gone)', $m->removeAccount() === true, (string) $m->error);

    // A WHM reply without a status block is not a success.
    $n = new DNAHosting_Module($server, []);
    dna_use_transport($n, $cpanel);
    try { $n->driver()->call('nostatus'); $check('missing status is an error', false); }
    catch (Throwable $e) { $check('missing status is an error', true); }

    echo "== Plesk\n";
    $p = ['customer' => null, 'webspace' => null, 'status' => 0, 'user_ip' => ''];
    $xml = function ($inner) { return ['status' => 200, 'body' => '<?xml version="1.0"?><packet>' . $inner . '</packet>', 'error' => '']; };
    $plesk = function ($method, $url, $headers, $body, $timeout) use (&$p, $xml) {
        if (strpos($url, '/enterprise/control/agent.php') === false) return ['status' => 200, 'body' => '<html>no whm</html>', 'error' => ''];
        if (strpos($body, '<server><get>') !== false) return $xml('<server><get><result><status>ok</status></result></get></server>');
        if (strpos($body, '<service-plan>') !== false) return $xml('<service-plan><get><result><status>ok</status><name>Starter</name><guid>g-1</guid></result></get></service-plan>');
        if (strpos($body, '<ip><get/>') !== false) return $xml('<ip><get><result><status>ok</status><addresses><ip><ip_address>203.0.113.20</ip_address><type>shared</type></ip></addresses></result></get></ip>');
        if (preg_match('#<customer><get><filter><external-id>([^<]*)#', $body, $mm)) {
            if ($p['customer'] && $p['customer']['ext'] === $mm[1]) return $xml('<customer><get><result><status>ok</status><id>7</id><data><gen_info><login>' . $p['customer']['login'] . '</login></gen_info></data></result></get></customer>');
            return $xml('<customer><get><result><status>error</status><errcode>1013</errcode><errtext>not found</errtext></result></get></customer>');
        }
        if (strpos($body, '<customer><get><filter><id>') !== false) {
            if ($p['customer']) return $xml('<customer><get><result><status>ok</status><id>7</id><data><gen_info><external-id>' . $p['customer']['ext'] . '</external-id></gen_info></data></result></get></customer>');
            return $xml('<customer><get><result><status>error</status><errcode>1013</errcode><errtext>not found</errtext></result></get></customer>');
        }
        if (preg_match('#<customer><add><gen_info>.*<login>([^<]*)</login>.*<external-id>([^<]*)</external-id>#s', $body, $mm)) { $p['customer'] = ['login' => $mm[1], 'ext' => $mm[2]]; return $xml('<customer><add><result><status>ok</status><id>7</id></result></add></customer>'); }
        if (strpos($body, '<webspace><get><filter><name>') !== false) {
            if ($p['webspace']) return $xml('<webspace><get><result><status>ok</status><id>11</id><data><gen_info><name>' . $p['webspace'] . '</name><owner-id>7</owner-id></gen_info></data></result></get></webspace>');
            return $xml('<webspace><get><result><status>error</status><errcode>1013</errcode><errtext>not found</errtext></result></get></webspace>');
        }
        if (strpos($body, '<webspace><get><filter><owner-id>') !== false) return $xml('<webspace><get>' . ($p['webspace'] ? '<result><status>ok</status><id>11</id></result>' : '<result><status>ok</status><filter-id>7</filter-id></result>') . '</get></webspace>');
        if (strpos($body, '<webspace><get><filter><id>') !== false) return $xml('<webspace><get><result><status>ok</status><id>11</id><data><gen_info><status>' . $p['status'] . '</status><real_size>2097152</real_size></gen_info><stat><traffic>1048576</traffic></stat><limits><limit><name>disk_space</name><value>1073741824</value></limit></limits></data></result></get></webspace>');
        if (preg_match('#<webspace><add>.*<name>([^<]*)</name>#s', $body, $mm)) { $p['webspace'] = $mm[1]; return $xml('<webspace><add><result><status>ok</status><id>11</id></result></add></webspace>'); }
        if (preg_match('#<webspace><set>.*<status>(\d+)</status>#s', $body, $mm)) { $p['status'] = (int) $mm[1]; return $xml('<webspace><set><result><status>ok</status></result></set></webspace>'); }
        if (strpos($body, '<webspace><set>') !== false) return $xml('<webspace><set><result><status>ok</status></result></set></webspace>');
        if (strpos($body, '<customer><set>') !== false) return $xml('<customer><set><result><status>ok</status></result></set></customer>');
        if (strpos($body, '<switch-subscription>') !== false) return $xml('<webspace><switch-subscription><result><status>ok</status></result></switch-subscription></webspace>');
        if (strpos($body, '<webspace><del>') !== false) { $p['webspace'] = null; return $xml('<webspace><del><result><status>ok</status></result></del></webspace>'); }
        if (strpos($body, '<customer><del>') !== false) { $p['customer'] = null; return $xml('<customer><del><result><status>ok</status></result></del></customer>'); }
        if (preg_match('#<user_ip>([^<]*)</user_ip>#', $body, $mm)) { $p['user_ip'] = $mm[1]; return $xml('<server><create_session><result><status>ok</status><id>SESSIONSECRET42</id></result></create_session></server>'); }
        return ['status' => 500, 'body' => 'unexpected', 'error' => ''];
    };
    $pserver = ['id' => 0, 'name' => 'plesk.example.com', 'ip' => '203.0.113.20', 'port' => 8443, 'secure' => 1,
                'username' => 'presel', 'password' => 'PLESKKEY-abcdef0123456789', 'access_hash' => ''];
    $pl = new DNAHosting_Module($pserver, []);
    dna_use_transport($pl, $plesk);
    $check('plesk testConnect', $pl->testConnect() === true, (string) $pl->error);
    $pl->order   = ['id' => 424242];
    $pl->user    = ['email' => 'owner@example.com', 'full_name' => 'Smoke Owner'];
    $pl->options = ['domain' => 'örnek.com', 'creation_info' => ['plan' => 'Starter']];
    $r = $pl->createAccount('xn--rnek-4qa.com', $pl->options);
    $check('plesk createAccount', is_array($r) && !empty($r['username']), (string) $pl->error);
    $GLOBALS['dna_plesk_pw'] = $r['password'] ?? '';
    $pl->config['user'] = $r['username'] ?? '';
    $check('plesk suspend', $pl->suspend() === true && $p['status'] === 32, (string) $pl->error);
    $check('plesk unsuspend', $pl->unsuspend() === true && $p['status'] === 0, (string) $pl->error);
    $check('plesk changePassword', $pl->changePassword('', 'Xy!12345678') === true, (string) $pl->error);
    $disk = $pl->getDisk();
    $check('plesk disk from gen_info', is_array($disk) && $disk['used'] === 2097152, json_encode($disk));
    $sso = $pl->driver()->createSession($pl->config['user'], '198.51.100.7');
    $check('plesk session uses host name', strpos($sso, 'https://plesk.example.com:8443/') === 0, $sso);
    $check('plesk user_ip base64', $p['user_ip'] === base64_encode('198.51.100.7'), $p['user_ip']);
    $o = new DNAHosting_Module($pserver, ['config' => ['user' => 'intruder'], 'domain' => 'örnek.com']);
    $o->order = ['id' => 999];
    dna_use_transport($o, $plesk);
    $check('plesk ownership guard refuses', $o->changePassword('', 'whatever1A!') === false && strpos((string) $o->error, 'wisecp-999') !== false, (string) $o->error);
    $check('plesk removeAccount deletes customer too', $pl->removeAccount() === true && $p['webspace'] === null && $p['customer'] === null, (string) $pl->error);

    echo "== Log masking\n";
    $stmt = Models::$init->db->select('id,data')->from('users_actions');
    $stmt->where('reason', '=', 'module-log', '&&');
    $stmt->where('detail', '=', 'DNAHosting');
    $rows = $stmt->build() ? $stmt->fetch_assoc() : [];
    $check('module log rows written', count($rows) > 10, (string) count($rows));
    $leaks = [];
    foreach (['WHMTOKEN0123456789ABCDEF', 'PLESKKEY-abcdef0123456789', 'SESSIONSECRET42', 'cpsess7654321', 'deadbeef', 'P&ss<w0rd>"x', 'Xy!12345678', $GLOBALS['dna_pw'], $GLOBALS['dna_plesk_pw']] as $sec) {
        if ($sec === '') continue;
        foreach ([$sec, urlencode($sec), rawurlencode($sec), htmlspecialchars($sec, ENT_XML1 | ENT_QUOTES, 'UTF-8')] as $form)
            foreach ($rows as $row)
                if (strpos((string) $row['data'], $form) !== false || strpos(stripslashes((string) $row['data']), $form) !== false) $leaks[$form] = true;
    }
    $check('no secret in module log', !$leaks, implode(',', array_keys($leaks)));
    $del = Models::$init->db->delete('users_actions');
    $del->where('reason', '=', 'module-log', '&&');
    $del->where('detail', '=', 'DNAHosting');
    $del->run();
    echo "  ..   cleaned " . count($rows) . " test log rows\n";

    echo "\n$ok passed, $fail failed\n";
}
