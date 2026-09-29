<?php

namespace App\Domain\Testing\Exceptions;

use RuntimeException;

/**
 * De kaart is niet aangenomen: verkeerde panelist, sessie gesloten, monster niet meer vers of ongeldige scores.
 */
class ScorecardRejectedException extends RuntimeException {}
