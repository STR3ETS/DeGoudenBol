<?php

namespace App\Support\Models;

/**
 * Basis voor alle modellen van de testketen: eigen connectie met alleen testnummers.
 */
abstract class TestingModel extends DomainModel
{
    protected $connection = 'testing';
}
