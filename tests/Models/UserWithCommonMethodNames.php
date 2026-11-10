<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use LogicException;

/**
 * A user model with methods named like common helpers. It uses the trait directly, like
 * applications do, so its own methods would replace the trait's ones with the same name.
 * Laratrust must never depend on them.
 */
#[Unguarded]
#[Table(name: 'users')]
class UserWithCommonMethodNames extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions;

    public function getMorphClass(): string
    {
        return User::class;
    }

    public function whereNameIs(): never
    {
        throw new LogicException('The application method was called.');
    }

    public function constrainToTeam(): never
    {
        throw new LogicException('The application method was called.');
    }

    public function attachModel(): never
    {
        throw new LogicException('The application method was called.');
    }

    public function detachModel(): never
    {
        throw new LogicException('The application method was called.');
    }

    public function syncModels(): never
    {
        throw new LogicException('The application method was called.');
    }

    public function isValidRelationship(): never
    {
        throw new LogicException('The application method was called.');
    }
}
