<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePhoneVisibilityRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function updatePhoneVisibility(UpdatePhoneVisibilityRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'data' => [
                'phone' => $user->phone,
                'showPhoneToMembers' => $user->show_phone_to_members,
            ],
        ]);
    }
}
