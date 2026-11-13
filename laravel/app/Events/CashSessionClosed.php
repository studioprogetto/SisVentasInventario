<?php

declare(strict_types=1);

namespace App\Events;

final readonly class CashSessionClosed
{
    public function __construct(public int $user, public int $turn) {}
}
