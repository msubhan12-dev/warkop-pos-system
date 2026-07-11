<?php
$url = "https://www.instagram.com/reel/C8q_3xMy3aF/";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
$html = curl_exec($ch);
curl_close($ch);

if (preg_match('/<meta property="og:video" content="([^"]+)"/', $html, $matches)) {
    echo "Video URL: " . $matches[1] . "\n";
} else {
    echo "Not found.\n";
}
