<?php

namespace App\Rules;

use App\Models\Investor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class LoginValidatePassword implements ValidationRule
{

    public function __construct(
        private readonly string $email
    ) {
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $investor = Investor::where('email',  $this->email)->first();
        if (!$investor || !Hash::check($value, $investor->password)) {
            $fail('La contraseña es incorrecta.');
        }
    }
}