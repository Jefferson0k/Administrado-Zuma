<?php

namespace App\Enums;

enum MovementType: string{
    case PAYMENT = "payment";
    case DEPOSIT = "deposit";
    case WITHDRAW = "withdraw";
    case INVESTMENT = "investment";
    case TAX = "tax";
    case EXCHANGE_UP = "exchange_up";
    case EXCHANGE_DOWN = "exchange_down";
    case FIXED_RATE_DISBURSEMENT = "fixed_rate_disbursement";
    case FIXED_RATE_INTEREST_PAYMENT = "fixed_rate_interest_payment";
    case FIXED_RATE_CAPITAL_RETURN = "fixed_rate_capital_return";

    case MORTGAGE_DISBURSEMENT = "mortgage_disbursement";
    case MORTGAGE_INSTALLMENT_PAYMENT = "mortgage_installment_payment";
    case MORTGAGE_EARLY_PAYMENT = "mortgage_early_payment";
    
    case INVESTMENT_PAYMENT = "investment_payment";
    case INVESTMENT_REFUND = "investment_refund";
}
