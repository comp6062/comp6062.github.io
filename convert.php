<?php
// --- Begin authentication ---
$valid_username = 'admin';
$valid_password = 'Alex51611!';

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== $valid_username ||
    $_SERVER['PHP_AUTH_PW'] !== $valid_password) {
    header('WWW-Authenticate: Basic realm="Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access Denied';
    exit;
}
// --- End authentication ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Convert M3U to JSON</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background: url('https://youriptv.us/bg.jpg') no-repeat center center fixed;
      background-size: cover;
      font-family: Arial, sans-serif;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      flex-direction: column;
      text-align: center;
    }

    .container {
      background: rgba(0, 0, 0, 0.7);
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0,0,0,0.6);
    }

    input[type="file"],
    input[type="text"],
    input[type="submit"] {
      margin: 10px 0;
      padding: 10px;
      width: 100%;
      max-width: 300px;
      border-radius: 8px;
      border: none;
    }

    input[type="submit"] {
      background-color: #28a745;
      color: white;
      cursor: pointer;
    }

    a.download-link {
      margin-top: 20px;
      display: inline-block;
      padding: 10px 20px;
      background-color: #007bff;
      color: white;
      border-radius: 8px;
      text-decoration: none;
    }

    a.download-link:hover {
      background-color: #0056b3;
    }

    p.message {
      margin-top: 20px;
      font-weight: bold;
    }

    .home-button {
      margin-top: 30px;
      display: inline-block;
      padding: 10px 20px;
      background-color: #6c757d;
      color: white;
      border-radius: 8px;
      text-decoration: none;
    }

    .home-button:hover {
      background-color: #5a6268;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Upload M3U or Enter Remote Link</h2>
    <form action="convert.php" method="POST" enctype="multipart/form-data">
      <input type="file" name="m3ufile"><br>
      <input type="text" name="m3uurl" placeholder="Or paste M3U URL"><br>
      <input type="submit" name="submit" value="Convert and Save JSON">
    </form>

<?php
// --- Begin M3U Processing ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $output_path = __DIR__ . '/playlist.json';       // Save in the same directory
    $web_accessible_path = 'playlist.json';          // Relative URL for download link

    if (isset($_FILES['m3ufile']) && $_FILES['m3ufile']['error'] === 0) {
        $content = file_get_contents($_FILES['m3ufile']['tmp_name']);
    } elseif (!empty($_POST['m3uurl'])) {
        $url = filter_var($_POST['m3uurl'], FILTER_VALIDATE_URL);
        if (!$url) {
            echo "<p class='message' style='color:red;'>Invalid URL.</p>";
            exit;
        }
        $content = @file_get_contents($url);
        if ($content === false) {
            echo "<p class='message' style='color:red;'>Unable to fetch remote file.</p>";
            exit;
        }
    } else {
        echo "<p class='message' style='color:red;'>No M3U file or URL provided.</p>";
        exit;
    }

    $lines = explode("\n", $content);
    $json = [];
    $current = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#EXTINF:') === 0) {
            preg_match('/,(.*)/', $line, $matches);
            $current['name'] = $matches[1] ?? 'Unknown';
        } elseif (filter_var($line, FILTER_VALIDATE_URL)) {
            $current['url'] = $line;
            $json[] = $current;
            $current = [];
        }
    }

    $json_data = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    // Save a single copy in the same folder as this script
    $bytes_written = file_put_contents($output_path, $json_data, LOCK_EX);
    if ($bytes_written === false) {
        echo "<p class='message' style='color:red;'>Unable to save playlist.json. Check file/folder permissions.</p>";
        exit;
    }

    echo "<p class='message' style='color:lightgreen;'>Conversion successful. Saved " . count($json) . " entries.</p>";
    echo "<a class='download-link' href='$web_accessible_path?v=" . time() . "' download>Download playlist.json</a>";
}
// --- End M3U Processing ---
?>

    <a class="home-button" href="../index.html">Home</a>
  </div>
</body>
</html>
