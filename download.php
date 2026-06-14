<?php
$url = $_GET['url'] ?? '';
$name = $_GET['name'] ?? 'download';

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo 'Invalid URL.';
    exit;
}

$playlist_path = __DIR__ . '/playlist.json';
$playlist = json_decode(@file_get_contents($playlist_path), true);
$allowed = false;

if (is_array($playlist)) {
    foreach ($playlist as $item) {
        if (isset($item['url']) && $item['url'] === $url) {
            $allowed = true;
            if (isset($item['name']) && $item['name'] !== '') {
                $name = $item['name'];
            }
            break;
        }
    }
}

if (!$allowed) {
    http_response_code(403);
    echo 'URL not found in playlist.json.';
    exit;
}

$path = parse_url($url, PHP_URL_PATH);
$extension = pathinfo($path ?? '', PATHINFO_EXTENSION);
$filename = preg_replace('/[^A-Za-z0-9._ -]/', '_', $name);
$filename = trim($filename, ' ._');
if ($filename === '') {
    $filename = 'download';
}
if ($extension && !preg_match('/\.' . preg_quote($extension, '/') . '$/i', $filename)) {
    $filename .= '.' . $extension;
}

@set_time_limit(0);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache');

$stream = @fopen($url, 'rb');
if (!$stream) {
    http_response_code(502);
    echo 'Unable to open remote stream.';
    exit;
}

while (!feof($stream)) {
    echo fread($stream, 8192);
    flush();
}
fclose($stream);
exit;
?>
