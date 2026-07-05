<?php

namespace Database\Seeders;

use App\Services\Api\PermissionSyncService;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionSyncService::class)->syncPermissions();
    }
}
