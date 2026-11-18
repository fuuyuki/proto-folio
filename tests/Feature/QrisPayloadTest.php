<?php

use App\Support\QrisPayload;

test('a well-formed static qris payload is valid', function () {
    expect(QrisPayload::isValid(make_qris_payload()))->toBeTrue();
});

test('a payload with a broken checksum is rejected', function () {
    $payload = make_qris_payload();
    $broken = substr($payload, 0, -1).(str_ends_with($payload, '0') ? '1' : '0');

    expect(QrisPayload::isValid($broken))->toBeFalse()
        ->and(QrisPayload::forAmount($broken, '125000'))->toBeNull();
});

test('an amount is injected and the checksum is refreshed', function () {
    $dynamic = QrisPayload::forAmount(make_qris_payload(), '125000.00');

    expect($dynamic)->not->toBeNull()
        ->and(QrisPayload::isValid($dynamic))->toBeTrue()
        ->and($dynamic)->toContain('010212')
        ->and($dynamic)->toContain('5406125000');
});

test('an existing amount is replaced', function () {
    $dynamic = QrisPayload::forAmount(make_qris_payload('50000'), '125000');

    expect($dynamic)->not->toBeNull()
        ->and($dynamic)->toContain('5406125000')
        ->and($dynamic)->not->toContain('540550000');
});

test('empty, zero, and non-qris values are rejected', function () {
    expect(QrisPayload::forAmount(make_qris_payload(), '0'))->toBeNull()
        ->and(QrisPayload::forAmount(make_qris_payload(), ''))->toBeNull()
        ->and(QrisPayload::forAmount('not a qris payload', '125000'))->toBeNull();
});
