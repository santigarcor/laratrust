<?php

namespace Laratrust\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laratrust\Helper;

trait LaratrustDynamicRelationsHandling
{

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param  string  $relationship
     * @param  mixed  $object
     * @param  mixed  $team
     * @return static
     */
    private function attachModel(string $relationship, $object, $team)
    {
        if (!Helper::isValidRelationship($relationship)) {
            throw new InvalidArgumentException();
        }

        $attributes = [];
        $objectType = Str::singular($relationship);

        $object = Helper::getIdFor($object, $objectType);

        if (Config::get('laratrust.teams.enabled')) {
            $team = Helper::getIdFor($team, 'team');

            if (
            $this->$relationship()
                ->wherePivot(Helper::teamForeignKey(), $team)
                ->wherePivot(Config::get("laratrust.foreign_keys.{$objectType}"), $object)
                ->count()
            ) {
                return $this;
            }

            $attributes[Helper::teamForeignKey()] = $team;
        }

        $this->$relationship()->attach(
            $object,
            $attributes
        );
        $this->flushCache();

        $this->fireLaratrustEvent("{$objectType}.attached", [$this, $object, $team]);

        return $this;
    }



    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param  string  $relationship
     * @param  mixed  $object
     * @param  mixed  $team
     * @return static
     */
    private function detachModel($relationship, $object, $team)
    {
        if (!Helper::isValidRelationship($relationship)) {
            throw new InvalidArgumentException;
        }

        $objectType = Str::singular($relationship);
        $relationshipQuery = $this->$relationship();

        if (Config::get('laratrust.teams.enabled')) {
            $relationshipQuery->wherePivot(
                Helper::teamForeignKey(),
                Helper::getIdFor($team, 'team')
            );
        }

        $object = Helper::getIdFor($object, $objectType);
        $relationshipQuery->detach($object);

        $this->flushCache();
        $this->fireLaratrustEvent("{$objectType}.detached", [$this, $object, $team]);

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's sync() method.
     *
     * @param  string  $relationship
     * @param  mixed  $objects
     * @param  mixed  $team
     * @param  boolean  $detaching
     * @return static
     */
    private function syncModels($relationship, $objects, $team, $detaching)
    {
        if (!Helper::isValidRelationship($relationship)) {
            throw new InvalidArgumentException;
        }

        $objectType = Str::singular($relationship);
        $mappedObjects = [];
        $useTeams = Config::get('laratrust.teams.enabled');
        $team = $useTeams ? Helper::getIdFor($team, 'team') : null;

        foreach ($objects as $object) {
            if ($useTeams && $team) {
                $mappedObjects[Helper::getIdFor($object, $objectType)] = [Helper::teamForeignKey() => $team];
            } else {
                $mappedObjects[] = Helper::getIdFor($object, $objectType);
            }
        }

        $relationshipToSync = $this->$relationship();

        if ($useTeams) {
            $relationshipToSync->wherePivot(Helper::teamForeignKey(), $team);
        }

        $result = $relationshipToSync->sync($mappedObjects, $detaching);

        $this->flushCache();
        $this->fireLaratrustEvent("{$objectType}.synced", [$this, $result, $team]);

        return $this;
    }



    /**
     * Flush the model's cache.
     *
     * @return bool
     */
    public function flushCache()
    {
        return Cache::forget($this->permissionsCacheKey());
    }

    /**
     * Flush the model's cache.
     *
     * @return string
     */
    public function permissionsCacheKey()
    {
        return 'laratrust_permissions_for_'.strtolower(class_basename($this)).'_'.$this->getKey();
    }

}
