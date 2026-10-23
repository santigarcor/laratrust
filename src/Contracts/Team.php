<?php

declare(strict_types=1);

namespace Laratrust\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface Team
{
    /**
     * Morph by Many relationship between the role and the one of the possible user models.
     *
     * @return MorphToMany<covariant Model, covariant Model>
     */
    public function getMorphByUserRelation(string $relationship): MorphToMany;
}
