<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum SupportTicketStatusEnum: string
{
    use InvokableCases, Values, Names;

    case OPEN            = 'open';
    case IN_PROGRESS     = 'in_progress';
    case REOPEN          = 'reopen';
    case PENDING_REVIEW  = 'pending_review';
    case RESOLVED        = 'resolved';
    case CLOSED          = 'closed';

    public function label(): string
    {
        return match($this) {
            self::OPEN           => 'Open',
            self::IN_PROGRESS    => 'In Progress',
            self::REOPEN         => 'Re-opened',
            self::PENDING_REVIEW => 'Pending Review',
            self::RESOLVED       => 'Resolved',
            self::CLOSED         => 'Closed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::OPEN           => 'bg-blue-100 text-blue-800',
            self::IN_PROGRESS    => 'bg-yellow-100 text-yellow-800',
            self::REOPEN         => 'bg-orange-100 text-orange-800',
            self::PENDING_REVIEW => 'bg-purple-100 text-purple-800',
            self::RESOLVED       => 'bg-green-100 text-green-800',
            self::CLOSED         => 'bg-gray-100 text-gray-600',
        };
    }
}
