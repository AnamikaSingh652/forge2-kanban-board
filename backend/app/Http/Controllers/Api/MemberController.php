<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Member::query()->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('members')],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
        ]);

        return response()->json(Member::create($data), 201);
    }

    public function show(Member $member): JsonResponse
    {
        return response()->json($member->load('boards', 'cards'));
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('members')->ignore($member->id)],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $member->update($data);

        return response()->json($member);
    }

    public function destroy(Member $member): JsonResponse
    {
        $member->delete();

        return response()->json(null, 204);
    }
}
