<?php

namespace App\Services\Api;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Config;

class PermissionSyncService
{
    public function syncPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Config::get('permissions.permissions', []);

        foreach (Config::get('permissions.permissions', []) as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
            ]);
        }

        foreach (Config::get('permissions.roles', []) as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
            ]);

            if ($roleName === 'super-admin') {
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($permissions);
            }
        }
    }
}
