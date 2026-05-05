<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;

trait AuthorizesRoleTableAccess
{
    protected function ensureTablePermission(string $table, string $action): void
    {
        $role = Auth::user()?->role;
        $actions = config("role_feature_matrix.roles.{$role}.tables.{$table}", []);

        if (!in_array($action, $actions, true)) {
            abort(403, 'Anda tidak memiliki izin untuk aksi ini.');
        }
    }
}
