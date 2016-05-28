<?php

namespace Santigarcor\Laratrust;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Santigarcor\Laratrust
 */

use Santigarcor\Laratrust\Contracts\LaratrustPermissionInterface;
use Santigarcor\Laratrust\Traits\LaratrustPermissionTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class LaratrustPermission extends Model implements LaratrustPermissionInterface
{
    use LaratrustPermissionTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Creates a new instance of the model.
     *
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = Config::get('laratrust.permissions_table');
    }
}
