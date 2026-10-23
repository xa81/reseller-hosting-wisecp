<?php
/**
 * Smoke test for DNAHosting against a real WISECP 5 core and a fake cPanel/Plesk.
 *
 * Not part of the module package. Copy it to the temp/ directory of a WISECP 5
 * test installation that has the module installed and run it with the PHP binary
 * WISECP uses:  php temp/smoke-v5.php
 *
 * It writes module log rows while it runs and deletes them at the end.
 */
require dirname(__DIR__) . '/bootstrap.php';

function use_transport(object $module, callable $transport): void
{
    (function ($t) { $this->transport = $t; $this->drivers = []; })->call($module, $transport);
}

$ok = 0; $fail = 0;
function check($label, $cond, $extra = '') { global $ok, $fail; if ($cond) { $ok++; echo "  ok   $label\n"; } else { $fail++; echo "  FAIL $label $extra\n"; } }
function attempt($label, callable $fn) { try { return $fn(); } catch (\Throwable $e) { echo "  ..   $label threw " . get_class($e) . ': ' . $e->getMessage() . "\n"; return $e; } }

$calls = [];
$state = ['exists' => false, 'suspended' => 0];

$cpanel = function ($method, $url, $headers, $body, $timeout) use (&$calls, &$state) {
    $path = parse_url($url, PHP_URL_PATH);
    if (str_contains($url, '/enterprise/control/agent.php')) return ['status' => 404, 'body' => 'not plesk', 'error' => ''];
    $fn = basename($path);
    $calls[] = $fn;
    $ok = fn($data = []) => ['status' => 200, 'body' => json_encode(['metadata' => ['result' => 1], 'data' => $data]), 'error' => ''];
    switch ($fn) {
        case 'listaccts': return $ok(['acct' => []]);
        case 'listpkgs': return $ok(['pkg' => [['name' => 'resel_Basic'], ['name' => 'resel_Pro']]]);
        case 'accountsummary':
            if (!$state['exists']) return ['status' => 200, 'body' => json_encode(['metadata' => ['result' => 0, 'reason' => 'Account does not exist']]), 'error' => ''];
            return $ok(['acct' => [['domain' => 'example-shop.com', 'diskused' => '120M', 'disklimit' => '1024M', 'suspended' => $state['suspended'], 'ip' => '203.0.113.10']]]);
        case 'createacct': $state['exists'] = true; return $ok();
        case 'suspendacct': $state['suspended'] = 1; return $ok();
        case 'unsuspendacct': $state['suspended'] = 0; return $ok();
        case 'passwd': case 'changepackage': return $ok();
        case 'removeacct': $state['exists'] = false; return $ok();
        case 'showbw': return $ok(['bandwidth' => [['acct' => [['user' => $GLOBALS['dna_user'] ?? '', 'totalbytes' => 52428800, 'limit' => 1073741824]]]]]);
        case 'create_user_session': return $ok(['url' => 'https://203.0.113.10:2083/cpsess1234567/login/?session=resel:abc:create_user_session,deadbeef']);
    }
    return ['status' => 500, 'body' => 'unexpected ' . $fn, 'error' => ''];
};

$server = ['id' => 0, 'name' => 'smoke-cpanel', 'ip' => '203.0.113.10', 'port' => 2087, 'secure' => 1,
           'username' => 'resel', 'password' => 'WHMTOKEN0123456789ABCDEF', 'access_hash' => '', 'fields' => []];

echo "== cPanel\n";
$m = Modules::getInstance('Servers', 'DNAHosting', [$server]);
check('instance class', $m instanceof \WISECP\Modules\Servers\DNAHosting, is_object($m) ? get_class($m) : 'null');
check('lang loaded', ($m->lang['config-plan'] ?? '') !== '');
check('config loaded', ($m->config['type'] ?? '') === 'hosting');
check('tls flag supported', \ServerModule::tls_flag_supported('DNAHosting'));
use_transport($m, $cpanel);

check('test_connect', attempt('test_connect', fn() => $m->test_connect()) === true);
$pc = attempt('product_configuration', fn() => $m->product_configuration(['plan' => 'resel_Pro']));
check('product_configuration dropdown', ($pc['plan']['type'] ?? '') === 'dropdown' && isset($pc['plan']['options']['resel_Pro']), json_encode($pc));

$m->service = ['id' => 0, 'name' => 'example-shop.com', 'status' => 'inprocess'];
$m->user    = ['email' => 'owner@example.com', 'full_name' => 'Smoke Owner'];
$m->options = ['domain' => 'Example-Shop.com', 'creation_info' => ['plan' => 'Pro']];

$r = attempt('create', fn() => $m->create());
check('create returns config', is_array($r) && ($r['config']['user'] ?? '') !== '', json_encode($r));
$m->options['config'] = $r['config'] ?? [];
$GLOBALS['dna_user'] = $m->options['config']['user'] ?? '';
check('username cPanel-safe', (bool) preg_match('/^[a-z][a-z0-9]{5,7}$/', $GLOBALS['dna_user']), $GLOBALS['dna_user']);
check('password stored encrypted', ($r['config']['password'] ?? '') !== '' && (new ReflectionMethod($m, 'decode_str'))->invoke($m, $r['config']['password']) !== $r['config']['password']);
check('home_dir', ($r['config']['home_dir'] ?? '') === '/home/' . $GLOBALS['dna_user']);

$GLOBALS['dna_generated'] = (new ReflectionMethod($m, 'decode_str'))->invoke($m, $r['config']['password'] ?? '');
$r2 = attempt('create retry', fn() => $m->create());
check('create retry adopts account', is_array($r2) && $r2['config']['user'] === $GLOBALS['dna_user']);

check('suspend', attempt('suspend', fn() => $m->suspend()) === true);
$dd = attempt('dashboard', fn() => $m->dashboard_data());
check('dashboard status suspended', ($dd['account_info']['status']['badge_color'] ?? '') === 'warning', json_encode($dd));
check('unsuspend', attempt('unsuspend', fn() => $m->unsuspend()) === true);
$m2 = Modules::getInstance('Servers', 'DNAHosting', [$server]); // fresh memo
check('change_password', attempt('change_password', fn() => $m->change_password('N3w!Passw0rd')) === true);
check('upgrade', attempt('upgrade', fn() => $m->upgrade(['module_data' => ['plan' => 'resel_Basic']])) === true);
$e = attempt('upgrade without plan', fn() => $m->upgrade(['module_data' => []]));
check('upgrade without plan throws Exception', $e instanceof \Exception);

$dd = attempt('dashboard', fn() => (function () { $this->memo = []; return $this->dashboard_data(); })->call($m));
check('dashboard gauges', ($dd['gauges']['disk_space']['used'] ?? 0) === 125829120 && ($dd['gauges']['bandwidth']['total'] ?? 0) === 1073741824, json_encode($dd['gauges'] ?? null));
$mu = attempt('metrics', fn() => $m->metrics_usage());
check('metrics MB', ($mu['disk_space']['used'] ?? 0) == 120, json_encode($mu));

$url = attempt('sso', fn() => $m->sso_panel_login());
check('sso url', is_string($url) && str_starts_with($url, 'https://203.0.113.10:2083/cpsess'), (string) $url);

$g = new \WISECP\Modules\Servers\DNAHosting($server);
use_transport($g, $cpanel);
$g->service = ['id' => 5]; $g->options = ['domain' => 'someone-else.com', 'config' => ['user' => $GLOBALS['dna_user']]];
$e = attempt('cpanel guard', fn() => $g->change_password('Whatever1!'));
check('cpanel ownership guard refuses', $e instanceof \Exception);
$e = attempt('cpanel guard cancel', fn() => $g->cancel());
check('cpanel guard blocks termination', $e instanceof \Exception && $state['exists']);

check('cancel', attempt('cancel', fn() => $m->cancel()) === true);
check('cancel again (already gone)', attempt('cancel again', fn() => $m->cancel()) === true);

// Error path: core run_module catches \Exception only.
$bad = Modules::getInstance('Servers', 'DNAHosting', [$server]);
use_transport($bad, fn() => ['status' => 200, 'body' => 'not json', 'error' => '']);
$bad->options = ['domain' => 'x.com', 'config' => ['user' => 'abc']];
$e = attempt('suspend on garbage', fn() => $bad->suspend());
check('garbage response -> Exception', $e instanceof \Exception);
$e = attempt('transport error', function () use ($server) {
    $t = Modules::getInstance('Servers', 'DNAHosting', [array_merge($server, ['ip' => '203.0.113.99'])]);
    use_transport($t, fn() => ['status' => 0, 'body' => '', 'error' => 'Connection timed out']);
    return $t->test_connect();
});
check('transport error -> Exception', $e instanceof \Exception);

echo "== Plesk\n";
$pstate = ['customer' => null, 'webspace' => null, 'status' => 0];
$xml = fn($inner) => ['status' => 200, 'body' => '<?xml version="1.0"?><packet>' . $inner . '</packet>', 'error' => ''];
$plesk = function ($method, $url, $headers, $body, $timeout) use (&$pstate, $xml) {
    if (!str_contains($url, '/enterprise/control/agent.php')) return ['status' => 200, 'body' => '<html>no whm</html>', 'error' => ''];
    if (str_contains($body, '<server><get>')) return $xml('<server><get><result><status>ok</status></result></get></server>');
    if (str_contains($body, '<service-plan>')) return $xml('<service-plan><get><result><status>ok</status><name>Starter</name><guid>g-1</guid></result></get></service-plan>');
    if (str_contains($body, '<ip><get/>')) return $xml('<ip><get><result><status>ok</status><addresses><ip><ip_address>203.0.113.20</ip_address><type>shared</type></ip></addresses></result></get></ip>');
    if (preg_match('#<customer><get><filter><external-id>([^<]*)#', $body, $mm)) {
        if ($pstate['customer'] && $pstate['customer']['ext'] === $mm[1]) return $xml('<customer><get><result><status>ok</status><id>7</id><data><gen_info><login>' . $pstate['customer']['login'] . '</login></gen_info></data></result></get></customer>');
        return $xml('<customer><get><result><status>error</status><errcode>1013</errcode><errtext>not found</errtext></result></get></customer>');
    }
    if (str_contains($body, '<customer><get><filter><id>')) {
        if ($pstate['customer']) return $xml('<customer><get><result><status>ok</status><id>7</id><data><gen_info><external-id>' . $pstate['customer']['ext'] . '</external-id></gen_info></data></result></get></customer>');
        return $xml('<customer><get><result><status>error</status><errcode>1013</errcode><errtext>not found</errtext></result></get></customer>');
    }
    if (preg_match('#<customer><add><gen_info>.*<login>([^<]*)</login>.*<external-id>([^<]*)</external-id>#s', $body, $mm)) { $pstate['customer'] = ['login' => $mm[1], 'ext' => $mm[2]]; return $xml('<customer><add><result><status>ok</status><id>7</id></result></add></customer>'); }
    if (str_contains($body, '<webspace><get><filter><name>')) {
        if ($pstate['webspace']) return $xml('<webspace><get><result><status>ok</status><id>11</id><data><gen_info><name>' . $pstate['webspace'] . '</name><owner-id>7</owner-id></gen_info></data></result></get></webspace>');
        return $xml('<webspace><get><result><status>error</status><errcode>1013</errcode><errtext>not found</errtext></result></get></webspace>');
    }
    if (str_contains($body, '<webspace><get><filter><owner-id>')) return $xml('<webspace><get>' . ($pstate['webspace'] ? '<result><status>ok</status><id>11</id></result>' : '<result><status>ok</status><filter-id>7</filter-id></result>') . '</get></webspace>');
    if (str_contains($body, '<webspace><get><filter><id>')) return $xml('<webspace><get><result><status>ok</status><id>11</id><data><gen_info><status>' . $pstate['status'] . '</status><real_size>2097152</real_size><dns_ip_address>203.0.113.20</dns_ip_address></gen_info><stat><traffic>1048576</traffic></stat><limits><limit><name>disk_space</name><value>1073741824</value></limit><limit><name>max_traffic</name><value>-1</value></limit></limits></data></result></get></webspace>');
    if (preg_match('#<webspace><add>.*<name>([^<]*)</name>#s', $body, $mm)) { $pstate['webspace'] = $mm[1]; return $xml('<webspace><add><result><status>ok</status><id>11</id></result></add></webspace>'); }
    if (preg_match('#<webspace><set>.*<status>(\d+)</status>#s', $body, $mm)) { $pstate['status'] = (int) $mm[1]; return $xml('<webspace><set><result><status>ok</status></result></set></webspace>'); }
    if (str_contains($body, '<webspace><set>')) return $xml('<webspace><set><result><status>ok</status></result></set></webspace>');
    if (str_contains($body, '<customer><set>')) return $xml('<customer><set><result><status>ok</status></result></set></customer>');
    if (str_contains($body, '<switch-subscription>')) return $xml('<webspace><switch-subscription><result><status>ok</status></result></switch-subscription></webspace>');
    if (str_contains($body, '<webspace><del>')) { $pstate['webspace'] = null; return $xml('<webspace><del><result><status>ok</status></result></del></webspace>'); }
    if (str_contains($body, '<customer><del>')) { $pstate['customer'] = null; return $xml('<customer><del><result><status>ok</status></result></del></customer>'); }
    if (str_contains($body, '<create_session>')) return $xml('<server><create_session><result><status>ok</status><id>SESSIONSECRET42</id></result></create_session></server>');
    return ['status' => 500, 'body' => 'unexpected', 'error' => ''];
};

$pserver = ['id' => 0, 'name' => 'smoke-plesk', 'ip' => '203.0.113.20', 'port' => 8443, 'secure' => 1,
            'username' => 'presel', 'password' => 'PLESKKEY-abcdef0123456789', 'access_hash' => '', 'fields' => []];
$p = Modules::getInstance('Servers', 'DNAHosting', [$pserver]);
use_transport($p, $plesk);
check('plesk test_connect', attempt('test_connect', fn() => $p->test_connect()) === true);
$pc = attempt('product_configuration', fn() => $p->product_configuration([]));
check('plesk plans', isset($pc['plan']['options']['Starter']), json_encode($pc));
$p->service = ['id' => 424242, 'name' => 'örnek.com'];
$p->user    = ['email' => 'owner@example.com', 'full_name' => 'Smoke Owner'];
$p->options = ['domain' => 'örnek.com', 'creation_info' => ['plan' => 'Starter']];
$r = attempt('create', fn() => $p->create());
check('plesk create', is_array($r) && str_starts_with($r['config']['home_dir'] ?? '', '/var/www/vhosts/xn--'), json_encode($r));
check('plesk external id', ($pstate['customer']['ext'] ?? '') === 'wisecp-424242');
$p->options['config'] = $r['config'] ?? [];
$GLOBALS['dna_plesk_generated'] = (new ReflectionMethod($p, 'decode_str'))->invoke($p, $r['config']['password'] ?? '');
check('plesk suspend', attempt('suspend', fn() => $p->suspend()) === true && $pstate['status'] === 32);
check('plesk unsuspend', attempt('unsuspend', fn() => $p->unsuspend()) === true && $pstate['status'] === 0);
check('plesk change_password', attempt('pw', fn() => $p->change_password('Xy!12345678')) === true);
check('plesk change_password xml chars', attempt('pw2', fn() => $p->change_password('P&ss<w0rd>"x')) === true);
check('plesk upgrade', attempt('upgrade', fn() => $p->upgrade(['module_data' => '{"plan":"Starter"}'])) === true);
$dd = attempt('dashboard', fn() => $p->dashboard_data());
check('plesk dashboard disk from gen_info', ($dd['gauges']['disk_space']['used'] ?? 0) === 2097152 && ($dd['gauges']['bandwidth']['total'] ?? 0) === -1, json_encode($dd['gauges'] ?? null));
$url = attempt('sso', fn() => $p->sso_panel_login());
check('plesk sso', is_string($url) && str_contains($url, 'rsession_init.php?PLESKSESSID=SESSIONSECRET42'), (string) $url);

// Ownership guard: a subscription owned by someone else.
$other = new \WISECP\Modules\Servers\DNAHosting($pserver);
use_transport($other, $plesk);
$other->service = ['id' => 999]; $other->options = ['domain' => 'örnek.com', 'config' => ['user' => 'intruder']];
$e = attempt('guard', fn() => $other->change_password('whatever1A!'));
check('plesk ownership guard refuses', $e instanceof \Exception && str_contains($e->getMessage(), 'wisecp-999'));

check('plesk cancel', attempt('cancel', fn() => $p->cancel()) === true && $pstate['webspace'] === null && $pstate['customer'] === null);
check('plesk cancel again', attempt('cancel again', fn() => $p->cancel()) === true);

echo "== Shared-domain query\n";
$q = new \WISECP\Modules\Servers\DNAHosting(array_merge($server, ['id' => 1]));
$q->service = ['id' => 0]; $q->options = ['domain' => 'example-shop.com'];
$res = attempt('shared-domain query', fn() => (function () { return $this->otherServicesOnDomain(); })->call($q));
check('shared-domain query runs', is_array($res), is_object($res) ? $res->getMessage() : '');

echo "== Log masking\n";
$stmt = WDB::select('id,data')->from('users_actions');
$stmt->where('reason', '=', 'module-log', '&&');
$stmt->where('detail', '=', 'DNAHosting');
$rows = $stmt->build() ? $stmt->fetch_assoc() : [];
check('module log rows written', count($rows) > 10, (string) count($rows));
$secrets = [];
foreach (['WHMTOKEN0123456789ABCDEF', 'PLESKKEY-abcdef0123456789', 'SESSIONSECRET42', 'cpsess1234567', 'N3w!Passw0rd', 'Xy!12345678', 'P&ss<w0rd>"x', 'deadbeef', $GLOBALS['dna_generated'] ?? '', $GLOBALS['dna_plesk_generated'] ?? ''] as $sec)
    if ($sec !== '') foreach ([$sec, urlencode($sec), rawurlencode($sec), htmlspecialchars($sec, ENT_XML1 | ENT_QUOTES, 'UTF-8')] as $form) $secrets[] = $form;
check('generated passwords captured', !empty($GLOBALS['dna_generated']) && !empty($GLOBALS['dna_plesk_generated']));
$leaks = [];
foreach ($rows as $row) foreach ($secrets as $sec) if (str_contains((string) $row['data'], $sec) || str_contains(stripslashes((string) $row['data']), $sec)) $leaks[$sec] = true;
check('no secret in module log', !$leaks, implode(',', array_keys($leaks)));
// Generated account passwords: read back from the create calls and look for them too.
WDB::delete('users_actions')->where('reason', '=', 'module-log', '&&')->where('detail', '=', 'DNAHosting')->run();
echo "  ..   cleaned " . count($rows) . " test log rows\n";

echo "\n$ok passed, $fail failed\n";
