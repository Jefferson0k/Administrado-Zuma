<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class StateNotification extends Model
{
    use HasFactory,HasUlids;
    protected $table = 'state_notification';
    protected $fillable = [
        'investor_id',
        'status',
        'type'
        ];
}
