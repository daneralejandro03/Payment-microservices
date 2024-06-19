<?php

return [
    'public_key' => env('EPAYCO_PUBLIC_KEY'),
    'private_key' => env('EPAYCO_PRIVATE_KEY'),
    'test' => env('EPAYCO_TEST', true),
    'api_url' => env('EPAYCO_API_URL', 'https://apify.epayco.co')
];
