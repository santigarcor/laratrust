<?php

namespace Laratrust\Contracts;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */

interface LaratrustTeamInterface
{
    /**
     * Morph by Many relationship between the team
     * and the one user by role models
     *
     * @param  string $relationship
     * @return Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function getMorphByUserRelation($relationship);
}
