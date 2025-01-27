<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NurseRoleResource;
use App\Http\Resources\RoleCollection;
use App\Http\Resources\RoleResource;
use App\Models\User;
use App\Traits\HttpResponses;
use App\Traits\UserRoleCheck;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class PermissionController extends Controller
{
    use UserRoleCheck;
    use HttpResponses;
    public function createRole(Request $request)
    {
        //TODO: CHECK THIS for multi
        try {
            $user = auth()->user();
            if ($user->role === 'nurse') {
                return $this->error(null, 'Seuls les médecins sont autorisés à accéder.', 401);
            }
            setPermissionsTeamId($user);
            // Validate the incoming request
            $validated = $request->validate([
                'rolename' => 'required|string|unique:roles,name',
            ]);
            $existingRole = Role::where('name', $request->rolename)->where('team_id', $user->id)->first();

            if ($existingRole) {
                return $this->error(null, 'Le rôle existe déjà', 409);
            }
            // Create the role
            $role = Role::create([
                'name' => $validated['rolename'],
                'team_id' => $user->id,
                'guard_name' => 'sanctum', // Use the appropriate guard
            ]);
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            // Return a success response using the trait
            return $this->success($role, 'Role created successfully.', 201);
        } catch (\Throwable $th) {
            // Return an error response using the trait
            return $this->error(null, $th->getMessage(), 500);
        }
    }
    public function getUsersViaRoles()
    {
        $doctorId = $this->checkUserRole();

        try {
            $roles = Role::where('team_id', $doctorId)->where('name', '!=', 'doctor')
                ->with('users') // Include users for other roles
                ->get();
            return new RoleCollection($roles);
        } catch (\Throwable $th) {
            return $this->error(null, $th->getMessage(), 500);
        }
    }


    public function getRoles()
    {
        try {
            $doctorId = $this->checkUserRole();
            $authenticatedUser = auth()->user();
            if ($authenticatedUser->role === 'nurse') {
                return $this->error(null, 'Seuls les médecins sont autorisés à accéder.', 401);
            }
            $roles = Role::where('team_id', $doctorId)->where('name', '!=', 'doctor')->get();
            $rolesResource  = RoleResource::collection($roles);
            return $this->success($rolesResource, 'success', 201);
        } catch (\Throwable $th) {
            $this->error($th->getMessage(), 'error', 501);
        }
    }
    public function grantAccess(Request $request)
    {
        try {
            $user = auth()->user();
            if ($user->role === 'nurse') {
                return $this->error(null, 'Seuls les médecins sont autorisés à accéder.', 501);
            }
            setPermissionsTeamId($user);

            $nurse = User::where('id', $request->nurseid)->first();

            if (!$nurse) {
                return $this->error(null, "Aucune infirmière n'a été trouvée", 501);
            }

            $role = Role::where('name', $request->rolename)
                ->where('guard_name', 'sanctum')
                ->where('team_id', $user->id)
                ->first();
            if (!$role) {
                throw RoleDoesNotExist::named($request->rolename, 'sanctum');
            }
            $role->syncPermissions([]);
            $permissions = $request->permissions;
            $role->syncPermissions($permissions);

            $roles = $nurse->roles;
            foreach ($roles as $singlerole) {
                $nurse->removeRole($singlerole);
            }
            $nurse->assignRole($request->rolename);
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            return $this->success(null, "L'autorisation a été mise à jour avec succès.", 201);
        } catch (RoleDoesNotExist $exception) {

            return $this->error(null, $exception->getMessage(), 500);
        } catch (\Throwable $th) {
            return $this->error(null, $th->getMessage(), 500);
        }
    }
    public function userPermissions(Request $request)
    {
        try {

            $user = auth()->user();
            if ($user->role === 'nurse') {
                return $this->error(null, 'Seuls les médecins sont autorisés à accéder.', 501);
            }

            $role = Role::where('name', $request->rolename)
                ->where('guard_name', 'sanctum')
                ->where('team_id', $user->id)
                ->first();
            if (!$role) {
                throw RoleDoesNotExist::named($request->rolename, 'sanctum');
            }
            $permissions = $role->permissions->pluck('name')->toArray();
            return $this->success($permissions, 'success', 201);
        } catch (RoleDoesNotExist $exception) {

            return $this->error(null, $exception->getMessage(), 500);
        } catch (\Throwable $th) {
            return $this->error(null, $th->getMessage(), 500);
        }
    }
    public function RolesNursesList()
    {
        $doctorId = $this->checkUserRole();
        $authenticatedUserId = auth()->user();
        if ($authenticatedUserId->role === 'nurse') {
            return $this->error(null, 'Only doctors are allowed access!', 401);
        }
        $nurses = User::where('doctor_id', $doctorId)->where('role', 'nurse')->get();
        $data =  NurseRoleResource::collection($nurses);
        return $this->success($data, 'success', 200);
    }
    public function deleteRole($id)
    {
        try {
            $user = auth()->user();
            if ($user->role === 'nurse') {
                return $this->error(null, 'Seuls les médecins sont autorisés à accéder.', 501);
            }
            setPermissionsTeamId($user);

            $role = Role::where('id', $id)->where('team_id', $user->id)->first();
            $role->delete();
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            return $this->success(null, 'deleted success', 201);
        } catch (\Throwable $th) {
            return $this->error(null, $th->getMessage(), 500);
        }
    }
}
