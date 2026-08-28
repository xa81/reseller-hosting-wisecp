<?php
test('DNAHosting_Exception Exception turevidir', function () {
    $e = new DNAHosting_Exception('deneme');
    assertTrue($e instanceof Exception);
    assertSame('deneme', $e->getMessage());
});

test('FakeTransport kuyrugu sirayla dondurur', function () {
    $t = new DNAHosting_FakeTransport();
    $t->push(200, 'ilk')->push(500, 'ikinci');
    $a = $t('GET', 'http://x/1', array(), null, 30);
    $b = $t('POST', 'http://x/2', array('A: b'), 'govde', 30);
    assertSame('ilk', $a['body']);
    assertSame(500, $b['status']);
    assertSame(2, count($t->calls));
    assertSame('POST', $t->lastCall()['method']);
});
