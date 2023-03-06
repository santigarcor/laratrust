<?php

namespace Laratrust\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface Team
{
    /**
     * Morph by Many relationship between the role and the one of the possible user models.
     */
    public function getMorphByUserRelation(string $relationship): MorphToMany;
}
