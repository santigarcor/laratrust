<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laratrust\Http\Controllers\PermissionsController;
use Laratrust\Http\Controllers\RolesAssignmentController;
use Laratrust\Http\Controllers\RolesController;

Route::resource('/permissions', PermissionsController::class, ['as' => 'laratrust'])
    ->only(['index', 'create', 'store', 'edit', 'update']);

Route::resource('/roles', RolesController::class, ['as' => 'laratrust']);

Route::resource('/roles-assignment', RolesAssignmentController::class, ['as' => 'laratrust'])
    ->only(['index', 'edit', 'update']);
