<?php

return [
    'driver' => 'bcrypt',
    'bcrypt' => ['rounds' => env('BCRYPT_ROUNDS', 12), 'verify' => true, 'limit' => null],
    'argon' => ['memory' => 65536, 'threads' => 1, 'time' => 4, 'verify' => true],
    // Verification must not silently change hashes in the existing database.
    'rehash_on_login' => (bool) env('HASH_REHASH_ON_LOGIN', false),
];
