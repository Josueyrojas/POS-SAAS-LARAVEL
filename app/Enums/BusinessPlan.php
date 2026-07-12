<?php

namespace App\Enums;

enum BusinessPlan: string
{
    case FREE = 'FREE';
    case BASIC = 'BASIC';
    case PRO = 'PRO';
    case ENTERPRISE = 'ENTERPRISE';

    public function label(): string
    {
        return ucfirst(strtolower($this->value));
    }
}
