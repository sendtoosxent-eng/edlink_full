<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class PlatformTotpService
{
    public function __construct(private readonly Google2FA $totp = new Google2FA) {}

    public function generateSecret(): string { return $this->totp->generateSecretKey(32); }

    public function provisioningUri(string $email, string $secret): string
    {
        return $this->totp->getQRCodeUrl('Edlink Platform', $email, $secret);
    }

    public function qrDataUri(string $email, string $secret): string
    {
        $renderer = new ImageRenderer(new RendererStyle(260, 2), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString($this->provisioningUri($email, $secret));
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function verify(string $secret, string $code): bool
    {
        // Allow a small amount of clock drift between the server and the
        // administrator's authenticator without weakening the six-digit check.
        return preg_match('/^\d{6}$/', $code) === 1 && $this->totp->verifyKey($secret, $code, 2);
    }

    public function recoveryCodes(int $count = 10): array
    {
        return collect(range(1, $count))->map(fn () => strtoupper(implode('-', str_split(bin2hex(random_bytes(6)), 4))))->all();
    }
}
