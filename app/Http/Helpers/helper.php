<?php

function getTrx()
{
    return strtoupper('TRX' . uniqid() . bin2hex(random_bytes(4)));
}


function showAmount($amount, $currencyFormat = true, $symbol = '৳')
{
    if ($currencyFormat) {
        return $symbol . ' ' . number_format($amount, 2);
    }
    return number_format($amount, 2);
}


if (!function_exists('getPaginate')) {
    function getPaginate()
    {
        return 20;
    }
}
