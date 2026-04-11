<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicketType extends Model
{
    protected $fillable = ['title'];

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }
}
