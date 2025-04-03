<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const CACHE_KEY = 'exchange_rate_usd_eur';
    private const CACHE_TTL = 3600; // 1 hour
    private const API_URL = 'https://open.er-api.com/v6/latest/USD';
    private const DEFAULT_RATE = 0.85;

    public function getExchangeRate(): float
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            try {
                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => self::API_URL,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                curl_close($curl);

                if ($err || $httpCode !== 200) {
                    Log::warning('Exchange rate API error', [
                        'error' => $err,
                        'http_code' => $httpCode
                    ]);
                    return self::DEFAULT_RATE;
                }

                $data = json_decode($response, true);
                return $data['rates']['EUR'] ?? self::DEFAULT_RATE;
            } catch (\Exception $e) {
                Log::error('Exchange rate API error: ' . $e->getMessage());
                return self::DEFAULT_RATE;
            }
        });
    }
} 