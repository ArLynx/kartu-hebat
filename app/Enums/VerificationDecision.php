<?php

namespace App\Enums;

enum VerificationDecision: string
{
    case MS = 'MS';
    case TMS = 'TMS';

    public function label(): string
    {
        return match ($this) {
            self::MS => 'Memenuhi Syarat',
            self::TMS => 'Tidak Memenuhi Syarat',
        };
    }
}
