function fixed(value, scale) {
    if (!new RegExp(`^\\d{1,15}(\\.\\d{0,${scale}})?$`).test(value)) return null;
    const [whole, fraction = ''] = value.split('.');
    return BigInt(whole + fraction.padEnd(scale, '0'));
}

export function itemCents(quantity, price) {
    const units = fixed(quantity, 3);
    const cents = fixed(price, 2);
    if (units === null || cents === null) return null;
    return (units * cents + 500n) / 1000n;
}

export function formatCents(cents) {
    return 'R$ ' + (cents / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + (cents % 100n).toString().padStart(2, '0');
}
