<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pesan sambutan (link wa.me/?text=...)
    |--------------------------------------------------------------------------
    */
    'welcome_message' => env('WHATSAPP_WELCOME_MESSAGE', 'Hai, Selamat datang di Dapur pak slam.'),

    /*
    |--------------------------------------------------------------------------
    | Nomor bisnis (untuk petunjuk admin)
    |--------------------------------------------------------------------------
    | Pesan dikirim dari akun WhatsApp yang sedang login di perangkat/browser Anda.
    | Pastikan itu nomor bisnis ini jika ingin konsisten dengan branding.
    */
    'business_number' => env('WHATSAPP_BUSINESS_NUMBER', '081260352471'),

];
