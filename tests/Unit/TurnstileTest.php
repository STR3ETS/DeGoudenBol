<?php

namespace Tests\Unit;

use App\Support\Turnstile;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    #[Test]
    public function the_check_is_skipped_without_keys_and_verified_with_them(): void
    {
        config()->set('services.turnstile.site_key', null);
        config()->set('services.turnstile.secret', null);

        $turnstile = new Turnstile;
        $this->assertFalse($turnstile->enabled());
        $this->assertTrue($turnstile->verify(null));

        config()->set('services.turnstile.site_key', 'site');
        config()->set('services.turnstile.secret', 'secret');

        Http::fake([
            'challenges.cloudflare.com/*' => Http::sequence()
                ->push(['success' => true])
                ->push(['success' => false, 'error-codes' => ['invalid-input-response']]),
        ]);

        $this->assertTrue($turnstile->enabled());
        $this->assertFalse($turnstile->verify(null), 'Zonder token nooit geldig.');
        $this->assertTrue($turnstile->verify('goed-token', '127.0.0.1'));
        $this->assertFalse($turnstile->verify('fout-token', '127.0.0.1'));

        Http::assertSent(fn ($request) => $request['secret'] === 'secret' && $request['response'] === 'goed-token');
    }
}
