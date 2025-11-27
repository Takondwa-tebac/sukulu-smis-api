<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreRequest;
use App\Http\Requests\Users\UpdateRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::query()->latest()->paginate();

        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $user = User::create($request->validated());
        if ($request->hasFile('profile_photo')) {
            $user
                ->addMediaFromRequest('profile_photo')
                ->toMediaCollection('profile_photo');
        }

        return new UserResource($user);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, User $user)
    {
        $user->update($request->validated());

        $user->clearMediaCollection('profile_photo');
        
        if ($request->hasFile('profile_photo')) {
            $user
                ->addMediaFromRequest('profile_photo')
                ->toMediaCollection('profile_photo');
        }

        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
