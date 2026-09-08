<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Action-specific permissions untuk PurchaseReturn workflow.
 *
 * Authorization per-action: approve / ship / complete tidak boleh melekat ke
 * permission update generik. Role yang saat ini memegang
 * `update_procurement::purchase::return` otomatis diberi ketiganya supaya akses
 * yang ada tidak patah — di masa depan bisa dipangkas per-role sesuai kebutuhan.
 *
 * Business rule tidak diubah di sini; hanya gerbang authorization digranularkan.
 */
return new class extends Migration
{
    private const ACTION_PERMS = [
        'approve_procurement::purchase::return',
        'ship_procurement::purchase::return',
        'complete_procurement::purchase::return',
    ];

    public function up(): void
    {
        $guard = 'web';

        // 1. Buat permission action (idempotent).
        foreach (self::ACTION_PERMS as $name) {
            Permission::findOrCreate($name, $guard);
        }

        // 2. Assign ke role yang sudah memegang update PR (preserve existing access).
        $updaters = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', 'update_procurement::purchase::return')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->pluck('roles.name');

        foreach ($updaters as $roleName) {
            $role = Role::whereName($roleName)->first();
            if ($role) {
                $role->givePermissionTo(self::ACTION_PERMS);
            }
        }
    }

    public function down(): void
    {
        foreach (self::ACTION_PERMS as $name) {
            Permission::whereName($name)->delete();
        }
    }
};