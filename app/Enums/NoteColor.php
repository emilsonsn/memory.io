<?php

namespace App\Enums;

enum NoteColor: string
{
    case GRAY = 'gray';
    case RED = 'red';
    case ORANGE = 'orange';
    case YELLOW = 'yellow';
    case GREEN = 'green';
    case BLUE = 'blue';
    case PURPLE = 'purple';
    case PINK = 'pink';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $color): string => $color->value, self::cases());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $color): array => [
                'value' => $color->value,
                'label' => ucfirst($color->value),
            ],
            self::cases(),
        );
    }
}
