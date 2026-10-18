<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * Cart can't be priced (missing weight, no fare on the route, discount too large…).
 * Errors are keyed like request fields (items.0.weight_kg) so forms can show them inline.
 */
class CargoPricingException extends \RuntimeException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' ', array_values($errors)));
    }

    /**
     * Render as a standard 422 validation response.
     */
    public function toValidationException(): ValidationException
    {
        return ValidationException::withMessages($this->errors);
    }
}
