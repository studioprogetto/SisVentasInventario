<?php

return [
    'mysqldump' => env('MYSQLDUMP_BINARY', 'C:/xampp/mysql/bin/mysqldump.exe'),
    'backup_on_close' => (bool) env('BACKUP_ON_CLOSE', true),
];
