<?php

namespace App\Domain\Edition\Settings;

use App\Domain\Edition\Enums\CharityBasis;
use App\Domain\Edition\Enums\FinalistFallback;
use App\Domain\Edition\Enums\LogisticsMode;
use App\Domain\Edition\Enums\ParticipationModel;
use BackedEnum;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Alles wat per editie kan verschillen en dus geen code is.
 * Defaults volgen de adviezen uit de briefing (docs/07).
 */
final readonly class EditionSettings
{
    /**
     * @param  list<string>  $publicationDays
     * @param  list<string>  $tieBreakOrder  Criteriumcodes in beslisvolgorde bij gelijke stand.
     */
    public function __construct(
        public int $capacityPerProvince = 50,
        public int $provincialListLength = 10,
        public int $nationalListLength = 5,
        public int $maxFinalists = 12,
        public float $publishThreshold = 5.0,
        public int $minValidCards = 6,
        public int $maxSamplesPerSession = 8,
        public int $freshnessWindowMinutes = 180,
        public int $outlierDeviationPoints = 15,
        public int $roundingDecimals = 1,
        public array $publicationDays = ['tuesday', 'friday'],
        public string $publicationTime = '12:00',
        public ?string $quietPeriodFrom = null,
        public int $voucherCountPerWinner = 10,
        public int $voucherValueCents = 4500,
        public int $voucherMaxPerEmail = 1,
        public array $tieBreakOrder = ['smaak', 'structuur_luchtigheid', 'versheid', 'vulling_verhouding'],
        public FinalistFallback $finalistFallback = FinalistFallback::NextInLine,
        public LogisticsMode $logisticsMode = LogisticsMode::Delivery,
        public ParticipationModel $participationModel = ParticipationModel::B,
        public int $piecesPerEntry = 8,
        public string $productVariant = 'krenten_rozijnen',
        public bool $showPanelAfterEdition = true,
        public string $publicSubscoresScope = 'top10_final',
        public int $charityPercentage = 10,
        public CharityBasis $charityBasis = CharityBasis::Received,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Snake_case sleutels zoals opgeslagen in de database.
     */
    public static function fromArray(array $data): self
    {
        $arguments = [];

        foreach (self::parameters() as $name => ['enum' => $enum, 'nullable' => $nullable]) {
            $key = Str::snake($name);

            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if ($value === null && ! $nullable) {
                continue;
            }

            if ($enum !== null && $value !== null && ! $value instanceof BackedEnum) {
                $value = $enum::from($value);
            }

            $arguments[$name] = $value;
        }

        return new self(...$arguments);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        foreach (array_keys(self::parameters()) as $name) {
            $value = $this->{$name};
            $result[Str::snake($name)] = $value instanceof BackedEnum ? $value->value : $value;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $changes  Snake_case of camelCase sleutels.
     */
    public function with(array $changes): self
    {
        $normalized = [];

        foreach ($changes as $key => $value) {
            $normalized[Str::snake($key)] = $value;
        }

        return self::fromArray([...$this->toArray(), ...$normalized]);
    }

    /**
     * @return array<string, array{enum: class-string<BackedEnum>|null, nullable: bool}>
     */
    private static function parameters(): array
    {
        static $parameters = null;

        if ($parameters !== null) {
            return $parameters;
        }

        $parameters = [];

        foreach ((new ReflectionClass(self::class))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            $class = $type instanceof ReflectionNamedType && ! $type->isBuiltin() ? $type->getName() : null;

            $parameters[$parameter->getName()] = [
                'enum' => $class !== null && is_subclass_of($class, BackedEnum::class) ? $class : null,
                'nullable' => $type === null || $type->allowsNull(),
            ];
        }

        return $parameters;
    }
}
