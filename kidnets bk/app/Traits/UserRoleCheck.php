<?php

namespace App\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

trait UserRoleCheck
{
    /**
     * Check the user's role and optionally validate their permissions.
     *
     * @param string|array|null $permissions Permission(s) to check (optional).
     * @return int|JsonResponse|null
     */
    protected function checkUserRole(string|array|null $permissions = null)
    {
        $user = Auth::user();

        if (!$user) {
            return null; // Handle unauthenticated user case.
        }

        // Optional permission check
        if ($permissions) {
            $permissionResult = $this->checkPermission($permissions);
            if ($permissionResult instanceof JsonResponse) {
                return $permissionResult; // Return the error response if permission fails.
            }
        }

        // Determine doctor ID based on role
        return $user->role === 'nurse' ? $user->doctor_id : $user->id;
    }

    /**
     * Check if the user has the given permission(s).
     *
     * @param string|array $permissions Permission(s) to check.
     * @return JsonResponse|null
     */
    protected function checkPermission(string|array $permissions): ?JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized action.'], Response::HTTP_FORBIDDEN);
        }

        // Set permissions team ID (assuming you have this helper function).
        setPermissionsTeamId($user);

        // Handle single permission or multiple permissions
        $permissions = (array) $permissions; // Ensure it's an array
        foreach ($permissions as $permission) {
            if (!$user->can($permission)) {
                return response()->json(['error' => "Unauthorized action for permission: $permission."], Response::HTTP_FORBIDDEN);
            }
        }

        return null; // Return null if all permissions are satisfied.
    }
}
