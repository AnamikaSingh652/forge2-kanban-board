<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardList extends Model
{
    use HasFactory;

    protected $table = 'lists';

    protected $fillable = [
        'board_id',
        'name',
        'position',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'list_id')->orderBy('position');
    }
}
