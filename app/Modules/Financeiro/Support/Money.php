<?php

namespace App\Modules\Financeiro\Support;

class Money
{
    public static function itemTotal(string $quantity, string $unitPrice): string
    {
        return bcadd(bcmul($quantity, $unitPrice, 5), '0.005', 2);
    }

    public static function format(string $value): string
    {
        $number = bcadd($value, '0', 2);
        $negative = str_starts_with($number, '-');
        [$integer, $fraction] = explode('.', ltrim($number, '-'));

        return ($negative ? '-R$ ' : 'R$ ').preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integer).','.$fraction;
    }

    public static function quantity(string $value): string
    {
        [$integer, $fraction] = explode('.', bcadd($value, '0', 3));

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integer).','.$fraction;
    }
}
