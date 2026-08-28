<?php
require __DIR__ . '/bootstrap.php';

$GLOBALS['dna_pass']   = 0;
$GLOBALS['dna_fail']   = 0;
$GLOBALS['dna_errors'] = array();
$GLOBALS['dna_test']   = '';

function test($name, callable $fn)
{
    $GLOBALS['dna_test'] = $name;
    try {
        $fn();
        $GLOBALS['dna_pass']++;
        echo ".";
    } catch (Exception $e) {
        $GLOBALS['dna_fail']++;
        $GLOBALS['dna_errors'][] = $name . "\n    " . $e->getMessage();
        echo "F";
    }
    if (($GLOBALS['dna_pass'] + $GLOBALS['dna_fail']) % 60 === 0) {
        echo "\n";
    }
}

function dna_fail_with($msg)
{
    throw new Exception($msg);
}

function assertSame($expected, $actual, $msg = '')
{
    if ($expected !== $actual) {
        dna_fail_with(($msg ? $msg . ' — ' : '') . 'beklenen ' . var_export($expected, true)
            . ', gelen ' . var_export($actual, true));
    }
}

function assertTrue($cond, $msg = '')
{
    if ($cond !== true) {
        dna_fail_with(($msg ? $msg . ' — ' : '') . 'true bekleniyordu, gelen ' . var_export($cond, true));
    }
}

function assertContains($needle, $haystack, $msg = '')
{
    if (strpos((string) $haystack, (string) $needle) === false) {
        dna_fail_with(($msg ? $msg . ' — ' : '') . var_export($needle, true)
            . ' bulunamadi. Gelen: ' . var_export($haystack, true));
    }
}

function assertThrows(callable $fn, $expectedMessageSubstring, $msg = '')
{
    try {
        $fn();
    } catch (Exception $e) {
        assertContains($expectedMessageSubstring, $e->getMessage(), $msg);
        return $e;
    }
    dna_fail_with(($msg ? $msg . ' — ' : '') . 'istisna bekleniyordu, firlatilmadi');
}

foreach (glob(__DIR__ . '/cases/*.php') as $case) {
    require $case;
}

echo "\n\n";
foreach ($GLOBALS['dna_errors'] as $err) {
    echo "FAIL: " . $err . "\n";
}
echo $GLOBALS['dna_pass'] . " gecti, " . $GLOBALS['dna_fail'] . " kaldi\n";
exit($GLOBALS['dna_fail'] > 0 ? 1 : 0);
