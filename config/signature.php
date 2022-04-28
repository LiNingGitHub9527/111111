<?php

return [

    'client_ip' => env('CLIENT_IP', '172.31.21.131'),

    'pms_signature_api_key' => env('PMS_SIGNATURE_API_KEY', ''),

    'gb_signature_api_key' => env('GB_SIGNATURE_API_KEY', ''),

    'front_client_url' => env('FRONT_CLIENT_URL', ''),

    'pms_url' => env('PMS_URL', ''),

    'signatures' => [
        [
            'signatureSecret' => env('PMS_SIGNATURE_SECRET', 'PMSMTRkZjAyNTNiNDdlYmQ5OWE4NzNkN2FkZDk1NDQ0MmRhNDE3MjRiNDhkNWJjODYzNjY1OTdhOGFkNDM1YWFmNw=='),
            'signatureApiKey' => env('PMS_SIGNATURE_API_KEY', 'pms'),
            'timestampValidity' => 600
        ],
        [
            'signatureSecret' => env('GB_SIGNATURE_SECRET', 'GBY2E1YWNiYjdjNDQ3N2YwMjFlNzg5YmY3N2M2NzM1YzVlZjYxZmI0MzYwZjJlMDFmZmM5ZmZkMGVmOTczODcwZQ=='),
            'signatureApiKey' => env('GB_SIGNATURE_API_KEY', 'nocode'),
            'timestampValidity' => 600
        ]
    ]
];