<?php

namespace App\Enums;

enum MovementType: string
{
    case PAYMENT = "payment";
    case DEPOSIT = "deposit";
    case WITHDRAW = "withdraw";
    case INVESTMENT = "investment";
    case TAX = "tax";
    case EXCHANGE_UP = "exchange_up";
    case EXCHANGE_DOWN = "exchange_down";
}
