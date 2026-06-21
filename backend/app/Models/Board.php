<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function lists(): HasMany
    {
        return $this->hasMany(BoardList::class)->orderBy('position');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class)->orderBy('name');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class)->withTimestamps();
    }
}
