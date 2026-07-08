<?php

namespace App\Http\Controllers\web\UserManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
class UserController extends Controller
{
    /**
     * Display a listing of the users with optional filtering by minimum diagnosis number.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->has('min_diagnosis'), function ($query) use ($request) {
                $query->where('diagnose_num', '>=', $request->min_diagnosis);
            })
            ->get();

        return response()->json($users);
    }
    /**
     * Toggle the status of a user (active/inactive) based on the provided user ID.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(int $id)
    {
      $user = User::findOrFail($id);
    
      $user->status = !$user->status;
      $user->save();

     return response()->json([
        'message' => 'Status updated successfully',
        'new_status' => $user->status
    ]);
    }
}
