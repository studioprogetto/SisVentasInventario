<?php

return [
    'name' => 'STUDIO & PROGETTO',
    'ruc' => '10756263157',
    'address' => 'Calles Las Gemas 626 Urb. Santa Inés',
    'district' => 'Trujillo - La Libertad',
    'phone' => '+51 939275406',
    'email' => 'ventas@studioyprogetto.com',
    'website' => 'https://studioyprogetto.com',
    'opening_hours' => [
        'weekdays' => 'Lun - Vie: 8:00 - 20:30',
        'weekend' => 'Sáb: 8:00 - 20:30',
    ],
    'return_policy_days' => 4,
    'pagination' => 12,
    'ticket_public_url' => env('TICKET_PUBLIC_URL'),
    'display_currency' => 'S/',
    'payment_methods' => ['efectivo', 'yape', 'plin', 'agora', 'transferencia', 'saldo', 'mixto'],
];
