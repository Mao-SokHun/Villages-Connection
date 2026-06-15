<?php
/**
 * Cloudinary PHP onboarding — upload, inspect, and transform a demo image.
 * Run: php scripts/cloudinary_onboarding.php
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Format;
use Cloudinary\Transformation\Quality;

// --- Configure Cloudinary (inline credentials via CLOUDINARY_URL) ---
$cloudinary = new Cloudinary('cloudinary://118514394223353:qNoaT6dzac_KPFn066bB8QBKfLs@dqspljo1o');

// Demo image hosted on Cloudinary's sample CDN
$sampleImageUrl = 'https://res.cloudinary.com/demo/image/upload/sample.jpg';

echo "Uploading sample image from Cloudinary demo...\n";

$upload = $cloudinary->uploadApi()->upload($sampleImageUrl, [
    'folder' => 'village-connect/onboarding',
    'public_id' => 'php-onboarding-' . time(),
    'overwrite' => true,
]);

$secureUrl = (string) $upload['secure_url'];
$publicId = (string) $upload['public_id'];

echo "\n--- Upload result ---\n";
echo "Secure URL: {$secureUrl}\n";
echo "Public ID:  {$publicId}\n";

echo "\nFetching image details...\n";

$asset = $cloudinary->adminApi()->asset($publicId, ['resource_type' => 'image']);

echo "\n--- Image metadata ---\n";
echo 'Width:  ' . (isset($asset['width']) ? $asset['width'] : 'n/a') . " px\n";
echo 'Height: ' . (isset($asset['height']) ? $asset['height'] : 'n/a') . " px\n";
echo 'Format: ' . (isset($asset['format']) ? $asset['format'] : 'n/a') . "\n";
echo 'Bytes:  ' . (isset($asset['bytes']) ? $asset['bytes'] : 'n/a') . "\n";

// f_auto = pick the best image format for the browser (e.g. WebP/AVIF when supported)
// q_auto = pick optimal compression quality automatically
$transformedUrl = $cloudinary->image($publicId)
    ->format(Format::auto())
    ->quality(Quality::auto())
    ->toUrl();

echo "\n--- Transformed URL (f_auto, q_auto) ---\n";
echo $transformedUrl . "\n";

echo "\nDone! Click link below to see optimized version of the image. Check the size and the format.\n";
