<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supplier Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains default values for suppliers. These are primarily
    | used during seeding or when database values are not yet available.
    | Most operational config should be read from the 'suppliers' table.
    |
    */

    'suppliers' => [
        'sepehr' => [
            'name' => 'Sepehr Api Test',
            'base_url' => env('SUPPLIER_SEPEHR_BASE_URL', 'https://SepehrApiTest.ir/api/Partners/Flight/Availability/V16/SearchByRouteAndDate'),
            'poll_interval' => env('SUPPLIER_SEPEHR_POLL_INTERVAL', 20),
        ],
        'alibaba' => [
            'name' => 'Alibaba Charter',
            'base_url' => env('SUPPLIER_ALIBABA_BASE_URL', 'https://charter.alibaba.ir/api/Partners/Flight/Availability/V16/SearchByRouteAndDate'),
            'poll_interval' => env('SUPPLIER_ALIBABA_POLL_INTERVAL', 10),
        ],
        'sepidparvaz' => [
            'name' => 'Sepid Parvaz',
            'base_url' => env('SUPPLIER_SEPIDPARVAZ_BASE_URL', 'https://sepidparvaz.ir/api/Partners/Flight/Availability/V16/SearchByRouteAndDate'),
            'poll_interval' => env('SUPPLIER_SEPIDPARVAZ_POLL_INTERVAL', 15),
        ],
        'rahbal' => [
            'name' => 'Rahbal Sbook',
            'base_url' => env('SUPPLIER_RAHBAL_BASE_URL', 'https://sbook.rahbal.com/api/Partners/Flight/Availability/V16/SearchByRouteAndDate'),
            'poll_interval' => env('SUPPLIER_RAHBAL_POLL_INTERVAL', 30),
        ],
        'mehragin' => [
            'name' => 'Mehragin Seir',
            'base_url' => env('SUPPLIER_MEHRAGIN_BASE_URL', 'https://mehraginseir.ir/api/Partners/Flight/Availability/V16/SearchByRouteAndDate'),
            'poll_interval' => env('SUPPLIER_MEHRAGIN_POLL_INTERVAL', 25),
        ],
    ],

    'cache_ttl' => env('FLIGHT_CACHE_TTL_SECONDS', 300),
    'search_rate_limit' => env('SEARCH_RATE_LIMIT', 60),
];
