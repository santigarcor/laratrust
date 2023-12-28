<?php

declare(strict_types=1);

namespace Laratrust\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface Permission
{
    /**
     * Many-to-Many relations with role model.
     */
    public function roles(): BelongsToMany;

    /**
     * Many-to-Many relations with group model.
     */
    public function groups(): BelongsToMany;

    /**
     * Morph by Many relationship between the permission and the one of the possible user models.
     */
    public function getMorphByUserRelation(string $relationship): MorphToMany;
}
