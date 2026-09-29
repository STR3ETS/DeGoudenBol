<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Alle drie de databases draaien in de testsuite in-memory; alleen connecties in deze lijst
     * houden hun tabellen tussen tests vast (RefreshDatabase bewaart hun PDO-verbinding).
     *
     * @var list<string>
     */
    protected array $connectionsToTransact = ['sqlite', 'testing', 'vault'];
}
