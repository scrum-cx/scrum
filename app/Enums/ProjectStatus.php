<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planning = 'planning';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::Active => 'Active',
            self::OnHold => 'On hold',
            self::Completed => 'Completed',
        };
    }

    /** Tailwind classes for the status badge. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Planning => 'bg-gray-100 text-gray-700',
            self::Active => 'bg-green-100 text-green-800',
            self::OnHold => 'bg-yellow-100 text-yellow-800',
            self::Completed => 'bg-blue-100 text-blue-800',
        };
    }
}
