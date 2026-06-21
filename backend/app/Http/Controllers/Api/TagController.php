<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index(Board $board): JsonResponse
    {
        return response()->json($board->tags);
    }

    public function show(Tag $tag): JsonResponse
    {
        return response()->json($tag->load('board'));
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('tags')->where('board_id', $board->id)],
            'color' => ['required', 'string', 'max:32'],
        ]);

        return response()->json($board->tags()->create($data), 201);
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $data = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('tags')->where('board_id', $tag->board_id)->ignore($tag->id),
            ],
            'color' => ['sometimes', 'required', 'string', 'max:32'],
        ]);

        $tag->update($data);

        return response()->json($tag);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(null, 204);
    }
}
