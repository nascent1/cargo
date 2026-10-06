<?php

namespace App\Exceptions;

/**
 * A payment action the officer can fix (wrong number, channel unavailable, duplicate reference…).
 * Controllers render the message as a 422.
 */
class CargoPaymentException extends \RuntimeException
{
}
