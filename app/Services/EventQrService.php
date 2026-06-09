<?php

namespace App\Services;

use App\Models\Event;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Builds QR codes for an event.
 *
 * Generation is centralised here (not inlined in the controller) so it can
 * later target other permanent URLs — e.g. the physical polaroid product,
 * where the QR links to a specific submission's audio-playback page instead
 * of the guest submission app. Keep the encoded URL short and stable: never
 * encode volatile data (expiring tokens/query params) into a printed QR.
 */
class EventQrService
{
    /**
     * The guest submission URL encoded by the event's QR.
     * {GUEST_APP_BASE_URL}/{slug} — the slug never changes, so the QR stays
     * valid for the life of the event.
     */
    public function guestUrl(Event $event): string
    {
        $base = Config::get('guestbook.guest_app_base_url');

        return rtrim((string) $base, '/').'/'.$event->slug;
    }

    /**
     * Render the event's guest-URL QR in the given format ("svg" | "png").
     * SVG scales cleanly for large print; PNG is for tools needing a raster.
     */
    public function render(Event $event, string $format = 'svg', ?int $size = null): string
    {
        return $this->qrFor($this->guestUrl($event), $format, $size);
    }

    /**
     * Low-level: render a QR for any URL. Reusable for future submission-level
     * permanent URLs (polaroid product) without touching callers above.
     */
    public function qrFor(string $url, string $format = 'svg', ?int $size = null): string
    {
        $format = $format === 'png' ? 'png' : 'svg';
        $size ??= $format === 'png' ? 1000 : 300;

        // PNG: render with GD ourselves. simple-qrcode's PNG path needs the
        // imagick extension; rendering the Bacon matrix through GD keeps PNG
        // working on the (already-enabled) gd extension with no extra deps.
        if ($format === 'png') {
            return $this->pngViaGd($url, $size);
        }

        return (string) QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->generate($url);
    }

    /**
     * Rasterise a QR to PNG bytes using GD from the BaconQrCode matrix.
     *
     * @param  int  $size  target image width/height in px (with a 1-module quiet margin)
     */
    private function pngViaGd(string $url, int $size): string
    {
        if (! \function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('The gd extension is required to render PNG QR codes.');
        }

        $matrix = Encoder::encode($url, ErrorCorrectionLevel::M())->getMatrix();
        $modules = $matrix->getWidth();
        $margin = 1; // quiet zone in modules

        // Pick an integer module size so the QR stays crisp, then size the
        // canvas to an exact multiple (no fractional/blurred modules).
        $moduleSize = max(1, (int) floor($size / ($modules + 2 * $margin)));
        $dimension = ($modules + 2 * $margin) * $moduleSize;

        $image = imagecreatetruecolor($dimension, $dimension);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $dimension, $dimension, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $px = ($x + $margin) * $moduleSize;
                    $py = ($y + $margin) * $moduleSize;
                    imagefilledrectangle($image, $px, $py, $px + $moduleSize - 1, $py + $moduleSize - 1, $black);
                }
            }
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }
}
