<?php

namespace Laratrust\Traits;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;


trait LaratrustHasAssignedPermissionsTrait
{
	use LaratrustHasEvents;

	/**
	 * Boots the role model and attaches event listener to
	 * remove the many-to-many records when trying to delete.
	 * Will NOT delete any records if the role model uses soft deletes.
	 *
	 * @return void|bool
	 */
	public static function bootLaratrustHasAssignedPermissionsTrait()
	{
		$flushCache = function ($model) {
			$model->flushCache();
		};

		// If the model doesn't use SoftDeletes.
		if (method_exists(static::class, 'restored')) {
			static::restored($flushCache);
		}

		static::deleted($flushCache);
		static::saved($flushCache);

		static::deleting(function ($model) {
			if (method_exists($model, 'bootSoftDeletes') && !$model->forceDeleting) {
				return;
			}
			$model->syncPermissions();
		});
	}
	/*
	 *
	 * - - -  R E L A T I O N S H I P S  - - -
	 *
	 */
	/**
	 * Get the Permissions for the .
	 */
	public function permissions()
	{
		return $this->morphToMany(Config::get('laratrust.models.permission'), 'model',
			Config::get('laratrust.tables.permission_models'));
	}


	/*
	 *
	 * - - -   M E T H O D S  - - -
	 *
	 */

	/**
	 * Tries to return all the cached permissions of the model.
	 * If it can't bring the permissions from the cache,
	 * it brings them back from the DB.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function cachedPermissions()
	{
		if (!Config::get('laratrust.use_cache')) {
			return $this->permissions()->get();
		}
		$cacheKey = $this->getCacheKey();

		return Cache::remember($cacheKey, Config::get('cache.ttl', 60), function () {
			return $this->permissions()->get()->toArray();
		});
	}


	/**
	 *
	 * @return bool
	 */
	public function hasAssignedPermissions()
	{
		return (bool)$this->cachedPermissions();
	}


	/**
	 * Checks if the model has a permission by its name.
	 *
	 * @param  string|array $permission Permission name or array of permission names.
	 *
	 * @return bool
	 */
	public function hasAccessPermission($permission)
	{
		foreach ($this->cachedPermissions() as $perm) {
			if (str_is($permission, $perm['name'])) {
				return true;
			}
		}
		return false;
	}


	/**
	 * @param  string
	 *
	 * @return array
	 */
	public function getAccessPermissions($columnName = 'name')
	{
		$perms = $this->cachedPermissions();
		if ($perms instanceof Collection) {
			return $this->permissions()->get()->pluck($columnName)->toArray();
		}

		return array_pluck($perms, $columnName);

	}

	/**
	 * Attach Permissions to model
	 *
	 * @param  mixed $permissions
	 *
	 * @return  mixed
	 */
	public function attachPermissions($permissions = [])
	{
		$perms = $this->prepareData($permissions);
		foreach ($perms as $perm) {
			$this->attachPermission($perm);
		}
		$this->flushCache();
		return $this;
	}

	/**
	 * Attach permission to current role.
	 *
	 * @param  object|array $permission
	 * @return void
	 */
	private function attachPermission($permission)
	{
		$permission = Helper::getIdFor($permission, 'permission');
		$this->permissions()->attach($permission);
		$this->fireLaratrustEvent("permission.attached", [$this, $permission]);
	}

	/**
	 *  Sync Permissions to model
	 *
	 * @param  mixed $permissions
	 *
	 * @return  $this
	 */
	public function syncPermissions($permissions = [])
	{
		$perms = $this->prepareData($permissions);
		$mappedPermissions=[];
		foreach ($perms as $perm) {
			$mappedPermissions[] = Helper::getIdFor($perm, 'permission');
		}
		$changes = $this->permissions()->sync($mappedPermissions);
		$this->flushCache();
		$this->fireLaratrustEvent("permission.synced", [$this, $changes]);

		return $this;
	}

	/**
	 * Delete attached Permissions
	 *
	 * @param  mixed $permissions
	 *
	 * @return  $this
	 */
	public function detachPermissions($permissions = [])
	{
		$perms = $this->prepareData($permissions);

		if (empty($perms)) {
			$perms = $this->permissions()->get();
		}
		foreach ($perms as $perm) {
			$this->detachPermission($perm);
		}
		$this->flushCache();

		return $this;
	}

	/**
	 * Detach permission from current role.
	 *
	 * @param  object|array  $permission
	 * @return void
	 */
	private function detachPermission($permission)
	{
		$permission = Helper::getIdFor($permission, 'permission');
		$this->permissions()->detach($permission);
		$this->fireLaratrustEvent("permission.detached", [$this, $permission]);

	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  mixed
	 *
	 * @return  array
	 */
	private function prepareData($argument)
	{	
		return (array) $argument;
	}


	/**
	 * Flush the role's cache.
	 *
	 * @return void
	 */
	public function flushCache()
	{
		Cache::forget($this->getCacheKey());
	}

	protected function getCacheKey()
	{
		return 'laratrust_permissions_for_model_' . class_basename(get_class($this)) . '_' . $this->getKey();
	}
}
