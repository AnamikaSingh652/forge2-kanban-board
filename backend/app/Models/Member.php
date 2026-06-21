<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'avatar_url',
    ];

    public function boards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class)->withTimestamps();
    }

    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(Card::class)->withTimestamps();
    }
}
