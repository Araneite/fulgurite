<?php

namespace App\Services\Users;

use Illuminate\Support\Facades\DB;

class UserRoleService
{
	public function assignRole($user, $role) {
        $pivot = DB::table('fg_user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->first();

        if ($pivot) {
            DB::table('fg_user_roles')
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->update([
                    "deleted_at"=> null,
                    "updated_at"=> now(),
                ]);
        } else {
            DB::table('fg_user_roles')
                ->insert([
                    "user_id" => $user->id,
                    "role_id" => $role->id,
                    "created_at"=> now(),
                    "updated_at"=> now(),
                    "deleted_at"=> null,
                ]);
        }
    }

    public function removeRole($user, $role) {
        DB::table('fg_user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->update([
                "deleted_at"=> now(),
                "updated_at"=> now(),
            ]);
    }

    public function hardRemoveRole($user, $role) {
        DB::table('fg_user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->delete();
    }

    public function restoreRole($user, $role) {
        DB::table('fg_user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->update([
                "deleted_at"=> null,
                "updated_at"=> now(),
            ]);
    }
}
