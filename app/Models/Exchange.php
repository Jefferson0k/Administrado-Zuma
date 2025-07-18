<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Money\Money;
use App\Helpers\MoneyConverter;

class Exchange extends Model{
    use HasFactory, HasUlids;
    protected $fillable = ['exchange_rate_sell', 'exchange_rate_buy', 'currency', 'status'];

}
