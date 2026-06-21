<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\BoardList;
use App\Models\Member;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CardController extends Controller
{
    public function index(BoardList $list): JsonResponse
    {
        return response()->json($list->cards()->with(['tags', 'members'])->get());
    }

    public function store(Request $request, BoardList $list): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ]);

        $card = $list->cards()->create([
            ...$data,
            'position' => $list->cards()->max('position') + 1,
        ]);

        return response()->json($card->load(['tags', 'members']), 201);
    }

    public function show(Card $card): JsonResponse
    {
        return response()->json($card->load(['list.board', 'tags', 'members']));
    }

    public function update(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ]);

        $card->update($data);

        return response()->json($card->load(['tags', 'members']));
    }

    public function destroy(Card $card): JsonResponse
    {
        $listId = $card->list_id;

        $card->delete();
        $this->normalizePositions($listId);

        return response()->json(null, 204);
    }

    public function move(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'list_id' => ['required', 'integer', 'exists:lists,id'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $targetList = BoardList::findOrFail($data['list_id']);
        $sourceListId = $card->list_id;

        if ($card->list->board_id !== $targetList->board_id) {
            throw ValidationException::withMessages([
                'list_id' => 'Cards can only move between lists on the same board.',
            ]);
        }

        DB::transaction(function () use ($card, $sourceListId, $targetList, $data): void {
            $card->update([
                'list_id' => $targetList->id,
                'position' => 0,
            ]);

            if ($sourceListId !== $targetList->id) {
                $this->normalizePositions($sourceListId);
            }

            $targetCards = Card::query()
                ->where('list_id', $targetList->id)
                ->whereKeyNot($card->id)
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->values();

            $insertAt = min($data['position'] - 1, $targetCards->count());
            $orderedCards = $targetCards
                ->take($insertAt)
                ->push($card)
                ->merge($targetCards->slice($insertAt));

            $this->applyPositions($orderedCards);
        });

        return response()->json($card->fresh()->load(['tags', 'members']));
    }

    public function reorder(Request $request, BoardList $list): JsonResponse
    {
        $data = $request->validate([
            'card_ids' => ['required', 'array'],
            'card_ids.*' => ['integer', 'exists:cards,id'],
        ]);

        DB::transaction(function () use ($list, $data): void {
            foreach ($data['card_ids'] as $index => $cardId) {
                Card::query()
                    ->where('list_id', $list->id)
                    ->whereKey($cardId)
                    ->update(['position' => $index + 1]);
            }
        });

        return response()->json($list->load('cards.tags', 'cards.members'));
    }

    public function attachTag(Card $card, Tag $tag): JsonResponse
    {
        $this->ensureTagBelongsToCardBoard($card, $tag);

        $card->tags()->syncWithoutDetaching($tag->id);

        return response()->json($card->load(['tags', 'members']));
    }

    public function detachTag(Card $card, Tag $tag): JsonResponse
    {
        $card->tags()->detach($tag->id);

        return response()->json($card->load(['tags', 'members']));
    }

    public function attachMember(Card $card, Member $member): JsonResponse
    {
        $board = $card->list->board;

        if (! $board->members()->whereKey($member->id)->exists()) {
            throw ValidationException::withMessages([
                'member_id' => 'Member must belong to the board before assignment.',
            ]);
        }

        $card->members()->syncWithoutDetaching($member->id);

        return response()->json($card->load(['tags', 'members']));
    }

    public function detachMember(Card $card, Member $member): JsonResponse
    {
        $card->members()->detach($member->id);

        return response()->json($card->load(['tags', 'members']));
    }

    private function normalizePositions(int $listId): void
    {
        $cards = Card::query()
            ->where('list_id', $listId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $this->applyPositions($cards);
    }

    private function applyPositions($cards): void
    {
        $cards->values()->each(function (Card $card, int $index): void {
            $card->update(['position' => $index + 1]);
        });
    }

    private function ensureTagBelongsToCardBoard(Card $card, Tag $tag): void
    {
        if ($card->list->board_id !== $tag->board_id) {
            throw ValidationException::withMessages([
                'tag_id' => 'Tag must belong to the same board as the card.',
            ]);
        }
    }
}
