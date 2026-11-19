<?php

namespace App\Enums;

/**
 * Site-wide role of a user.
 * Admin: platform administrator; can see and manage every team and project.
 * Member: everyone else (default for new sign-ups). What a member can do inside a team
 * depends on their TeamRole in that team.
 */
enum Role: string
{
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
