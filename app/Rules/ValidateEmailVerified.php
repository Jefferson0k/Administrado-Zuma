<?php

namespace App\Rules;

use App\Models\Investor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateEmailVerified implements ValidationRule
{
    public function __construct(
        private readonly string $document
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $investor = Investor::where('document', $this->document)->first();
        
        if ($investor && !$investor->hasVerifiedEmail()) {
            $fail('Tu cuenta no ha sido verificada. Revisa tu correo electrónico.');
        }
    }
}
