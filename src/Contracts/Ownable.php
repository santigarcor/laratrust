<?php

namespace Laratrust\Contracts;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */
interface Ownable
{
    /**
     * Gets the owner key value inside the model or object.
     *
     * @param  mixed  $owner
     * @return mixed
     */
    public function ownerKey($owner);
}
