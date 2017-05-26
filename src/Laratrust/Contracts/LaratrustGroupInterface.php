<?php

namespace Laratrust\Contracts;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */

interface LaratrustGroupInterface
{
    /**
     * Morph by Many relationship between the group
     * and the one user by role models
     *
     * @param  string $relationship
     * @return Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function getMorphByUserRelation($relationship);
}
