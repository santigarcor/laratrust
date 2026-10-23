<?php

declare(strict_types=1);

namespace Laratrust\Http\Controllers;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Laratrust\Support\ModelClasses;

class PermissionsController
{
    public function index(): ViewContract
    {
        return View::make('laratrust::panel.permissions.index', [
            'permissions' => ModelClasses::permission()::query()->simplePaginate(10),
        ]);
    }

    public function create(): ViewContract
    {
        return View::make('laratrust::panel.edit', [
            'model' => null,
            'permissions' => null,
            'type' => 'permission',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'unique:'.Config::get('laratrust.tables.permissions').',name'],
            'display_name' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        ModelClasses::permission()::query()->create($data);

        Session::flash('laratrust-success', 'Permission created successfully');

        return to_route('laratrust.permissions.index');
    }

    public function edit(string $id): ViewContract
    {
        return View::make('laratrust::panel.edit', [
            'model' => ModelClasses::permission()::query()->findOrFail($id),
            'type' => 'permission',
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $permission = ModelClasses::permission()::query()->findOrFail($id);

        $permission->update($request->validate([
            'display_name' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]));

        Session::flash('laratrust-success', 'Permission updated successfully');

        return to_route('laratrust.permissions.index');
    }
}
