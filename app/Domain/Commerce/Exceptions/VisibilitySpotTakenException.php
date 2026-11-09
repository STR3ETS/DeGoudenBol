<?php

namespace App\Domain\Commerce\Exceptions;

use RuntimeException;

/**
 * Iemand anders was net eerder: deze zichtbaarheidsplek is bezet.
 */
class VisibilitySpotTakenException extends RuntimeException {}
