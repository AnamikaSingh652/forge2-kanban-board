<?php

use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\BoardListController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

Route::apiResource('boards', BoardController::class);

Route::post('boards/{board}/lists', [BoardListController::class, 'store']);
Route::get('lists/{list}', [BoardListController::class, 'show']);
Route::patch('lists/{list}', [BoardListController::class, 'update']);
Route::delete('lists/{list}', [BoardListController::class, 'destroy']);
Route::post('boards/{board}/lists/reorder', [BoardListController::class, 'reorder']);

Route::get('lists/{list}/cards', [CardController::class, 'index']);
Route::post('lists/{list}/cards', [CardController::class, 'store']);
Route::get('cards/{card}', [CardController::class, 'show']);
Route::patch('cards/{card}', [CardController::class, 'update']);
Route::delete('cards/{card}', [CardController::class, 'destroy']);
Route::post('cards/{card}/move', [CardController::class, 'move']);
Route::post('lists/{list}/cards/reorder', [CardController::class, 'reorder']);

Route::get('boards/{board}/tags', [TagController::class, 'index']);
Route::post('boards/{board}/tags', [TagController::class, 'store']);
Route::get('tags/{tag}', [TagController::class, 'show']);
Route::patch('tags/{tag}', [TagController::class, 'update']);
Route::delete('tags/{tag}', [TagController::class, 'destroy']);
Route::post('cards/{card}/tags/{tag}', [CardController::class, 'attachTag']);
Route::delete('cards/{card}/tags/{tag}', [CardController::class, 'detachTag']);

Route::apiResource('members', MemberController::class);
Route::post('boards/{board}/members/{member}', [BoardController::class, 'attachMember']);
Route::delete('boards/{board}/members/{member}', [BoardController::class, 'detachMember']);
Route::post('cards/{card}/members/{member}', [CardController::class, 'attachMember']);
Route::delete('cards/{card}/members/{member}', [CardController::class, 'detachMember']);
