<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Board::query()->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        return response()->json(Board::create($data), 201);
    }

    public function show(Board $board): JsonResponse
    {
        return response()->json($board->load([
            'lists.cards.tags',
            'lists.cards.members',
            'tags',
            'members',
        ]));
    }

    public function update(Request $request, Board $board): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $board->update($data);

        return response()->json($board);
    }

    public function destroy(Board $board): JsonResponse
    {
        $board->delete();

        return response()->json(null, 204);
    }

    public function attachMember(Board $board, Member $member): JsonResponse
    {
        $board->members()->syncWithoutDetaching($member->id);

        return response()->json($board->load('members'));
    }

    public function detachMember(Board $board, Member $member): JsonResponse
    {
        $board->members()->detach($member->id);

        return response()->json($board->load('members'));
    }
}
