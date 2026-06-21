<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_id',
        'title',
        'description',
        'position',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(BoardList::class, 'list_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class)->withTimestamps();
    }
}
