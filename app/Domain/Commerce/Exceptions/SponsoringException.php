<?php

namespace App\Domain\Commerce\Exceptions;

use RuntimeException;

/**
 * Domeinfout in de sponsoring met een boodschap voor de gebruiker (dubbele verkoop, verkeerde fase).
 */
class SponsoringException extends RuntimeException {}
