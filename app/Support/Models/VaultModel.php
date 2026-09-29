<?php

namespace App\Support\Models;

/**
 * Basis voor de kluis: eigen connectie, eigen sleutel, alleen via VaultService te benaderen.
 */
abstract class VaultModel extends DomainModel
{
    protected $connection = 'vault';
}
