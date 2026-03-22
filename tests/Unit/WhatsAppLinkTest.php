<?php

namespace Tests\Unit;

use App\Support\WhatsAppLink;
use Tests\TestCase;

class WhatsAppLinkTest extends TestCase
{
    public function test_normalizes_indonesian_local_number(): void
    {
        $this->assertSame('6281234567890', WhatsAppLink::normalizeToWaDigits('081234567890'));
    }

    public function test_normalizes_without_leading_zero(): void
    {
        $this->assertSame('08127012804', WhatsAppLink::normalizeToWaDigits('08127012804'));
    }

    public function test_build_welcome_url_contains_wa_me_and_text(): void
    {
        $url = WhatsAppLink::buildWelcomeUrl('08127012804');
        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/6281260352471?text=', $url);
        $this->assertStringContainsString(rawurlencode(WhatsAppLink::welcomeMessage()), $url);
    }
}
