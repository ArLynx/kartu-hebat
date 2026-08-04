<?php

namespace App\Enums;

enum VerificationDecision: string
{
    case MS = 'MS';
    case BTL = 'BTL';
    case TMS = 'TMS';

    public function label(): string
    {
        return match ($this) {
            self::MS => 'Memenuhi Syarat',
            self::BTL => 'Butuh Perbaikan',
            self::TMS => 'Tidak Memenuhi Syarat',
        };
    }
}
