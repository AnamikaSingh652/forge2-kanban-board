<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardListController extends Controller
{
    public function show(BoardList $list): JsonResponse
    {
        return response()->json($list->load('board', 'cards.tags', 'cards.members'));
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $list = $board->lists()->create([
            'name' => $data['name'],
            'position' => $board->lists()->max('position') + 1,
        ]);

        return response()->json($list, 201);
    }

    public function update(Request $request, BoardList $list): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'min:1'],
        ]);

        $list->update($data);

        return response()->json($list);
    }

    public function destroy(BoardList $list): JsonResponse
    {
        $boardId = $list->board_id;
        $list->delete();
        $this->normalizePositions($boardId);

        return response()->json(null, 204);
    }

    public function reorder(Request $request, Board $board): JsonResponse
    {
        $data = $request->validate([
            'list_ids' => ['required', 'array'],
            'list_ids.*' => ['integer', 'exists:lists,id'],
        ]);

        DB::transaction(function () use ($board, $data): void {
            foreach ($data['list_ids'] as $index => $listId) {
                BoardList::query()
                    ->where('board_id', $board->id)
                    ->whereKey($listId)
                    ->update(['position' => $index + 1]);
            }
        });

        return response()->json($board->load('lists.cards'));
    }

    private function normalizePositions(int $boardId): void
    {
        BoardList::query()
            ->where('board_id', $boardId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(function (BoardList $list, int $index): void {
                $list->update(['position' => $index + 1]);
            });
    }
}
