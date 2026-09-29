<?php

namespace Tests\Architecture;

use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

/**
 * Bewaakt de harde grenzen uit de briefing (docs/03 en docs/05).
 */
class DomainBoundariesTest extends TestCase
{
    /**
     * Domeinen die nooit iets uit een ander domein mogen importeren.
     *
     * @var array<string, list<string>>
     */
    private const array FORBIDDEN_IMPORTS = [
        'Ranking' => ['Commerce', 'Marketing', 'Vouchers', 'Charities', 'Academy'],
        'Testing' => ['Participants', 'Commerce', 'Marketing', 'Vouchers', 'Charities', 'Academy'],
    ];

    #[Test]
    public function ranking_and_testing_never_import_commercial_or_identity_domains(): void
    {
        foreach (self::FORBIDDEN_IMPORTS as $domain => $forbidden) {
            $path = app_path("Domain/{$domain}");

            if (! File::isDirectory($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $contents = $file->getContents();

                foreach ($forbidden as $other) {
                    $this->assertStringNotContainsString(
                        "App\\Domain\\{$other}\\",
                        $contents,
                        "{$file->getRelativePathname()} in domein {$domain} verwijst naar domein {$other}.",
                    );
                }
            }
        }

        $this->assertTrue(true);
    }

    #[Test]
    public function testing_models_use_the_testing_connection_and_vault_models_the_vault_connection(): void
    {
        foreach (['Testing' => 'testing', 'Vault' => 'vault'] as $domain => $connection) {
            foreach ($this->modelClasses($domain) as $class) {
                $model = new $class;

                $this->assertSame($connection, $model->getConnectionName(), "{$class} moet op connectie '{$connection}' staan.");
            }
        }

        $this->assertTrue(true);
    }

    #[Test]
    public function every_domain_model_extends_the_domain_base_model(): void
    {
        $checked = 0;

        foreach (File::directories(app_path('Domain')) as $domainPath) {
            foreach ($this->modelClasses(basename($domainPath)) as $class) {
                $reflection = new ReflectionClass($class);

                if ($reflection->isSubclassOf(Pivot::class) || $reflection->isSubclassOf(Authenticatable::class)) {
                    continue;
                }

                $this->assertTrue($reflection->isSubclassOf(DomainModel::class), "{$class} moet DomainModel uitbreiden.");
                $checked++;
            }
        }

        $this->assertGreaterThan(0, $checked);
    }

    /**
     * @return list<class-string>
     */
    private function modelClasses(string $domain): array
    {
        $path = app_path("Domain/{$domain}/Models");

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::files($path))
            ->map(fn ($file) => "App\\Domain\\{$domain}\\Models\\".Str::before($file->getFilename(), '.php'))
            ->filter(fn (string $class) => class_exists($class))
            ->values()
            ->all();
    }
}
