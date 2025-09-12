<?php

namespace App\Rules;

use App\Models\Company;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExistenceCompany implements ValidationRule
{
	/**
	 * Run the validation rule.
	 *
	 * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		$company = Company::select('id', 'name', 'document')->where('name', $value)->orWhere('document', $value)->first();
		if (!$company) {
			$fail('La Empresa no existe');
		}
	}
}
