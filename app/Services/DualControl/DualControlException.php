<?php

namespace App\Services\DualControl;

use RuntimeException;

/**
 * A dual-control change that can't be submitted or applied, with a message meant for the person on screen.
 */
class DualControlException extends RuntimeException
{
}
