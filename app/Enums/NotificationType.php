<?php

namespace App\Enums;

enum NotificationType: string
{
    case DEFAULT = 'default';
    case PROCESS = 'process';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
