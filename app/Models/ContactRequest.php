<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ContactRequest extends Model{
    use HasFactory;
    protected $table = 'tbl_contact_requests';
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'interested_product',
        'message',
        'status',
        'accepted_policy',
    ];
}
