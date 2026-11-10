import { test } from 'node:test';
import assert from 'node:assert/strict';
import { itemCents, formatCents } from '../../resources/js/financeiro-money.js';

test('money uses decimal cents and half-up rounding per item', () => {
    assert.equal(itemCents('0.001', '5.00'), 1n);
    assert.equal(itemCents('0.001', '4.99'), 0n);
    assert.equal(itemCents('3.000', '0.10'), 30n);
    assert.equal(itemCents('2.500', '10.99'), 2748n);
});

test('large money amounts remain exact', () => {
    assert.equal(itemCents('1', '999999999999.99'), 99999999999999n);
    assert.equal(formatCents(99999999999999n), 'R$ 999.999.999.999,99');
});

test('incomplete or invalid amounts do not produce a total', () => {
    for (const value of ['', '-1', '1e10', '1,25', '1.1234']) assert.equal(itemCents(value, '1'), null);
    assert.equal(formatCents(0n), 'R$ 0,00');
});
