<?php
namespace Laratrust\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * User: liuchunhua
 * Datetime: 2018-04-28 16:16
 * Copyright: camel
 */
class LaratrustPermissionRule extends Model
{
    /**
     * Creates a new instance of the model.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = Config::get('laratrust.tables.permission_rule');
    }
}