<?php

use Illuminate\Support\Facades\Route;

Route::get('/permissions', 'PermissionsController@index')
    ->name('laratrust.permissions.index');
Route::get('/permissions/{id}/edit', 'PermissionsController@edit')
    ->name('laratrust.permissions.edit');
Route::put('/permissions/{id}/', 'PermissionsController@update')
    ->name('laratrust.permissions.update');

Route::get('/roles', 'RolesController@index')
    ->name('laratrust.roles.index');
Route::get('/roles/create', 'RolesController@create')
    ->name('laratrust.roles.create');
Route::post('/roles', 'RolesController@store')
    ->name('laratrust.roles.store');
Route::get('/roles/{id}/edit', 'RolesController@edit')
    ->name('laratrust.roles.edit');
Route::put('/roles/{id}', 'RolesController@update')
    ->name('laratrust.roles.update');
