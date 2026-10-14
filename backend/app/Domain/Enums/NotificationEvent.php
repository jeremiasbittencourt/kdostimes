<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum NotificationEvent: string
{
    case SESSION_OPENED = 'session_opened';
    case PRESENCE_DEADLINE_REMINDER = 'presence_deadline_reminder';
    case DRAW_PUBLISHED = 'draw_published';
    case VOTING_OPENED = 'voting_opened';
    case VOTING_DEADLINE_REMINDER = 'voting_deadline_reminder';
    case VOTING_CLOSED = 'voting_closed';
    case MEMBER_REMOVED = 'member_removed';

    public function isSilenceable(): bool
    {
        return $this !== self::MEMBER_REMOVED;
    }
}
