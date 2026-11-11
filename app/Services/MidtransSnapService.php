<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransSnapService
{
    public function __construct()
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createTransaction(array $params): object
    {
        if (! config('services.midtrans.server_key') || ! config('services.midtrans.client_key')) {
            throw new \RuntimeException('Kunci Midtrans belum dikonfigurasi.');
        }

        return Snap::createTransaction($params);
    }
}
