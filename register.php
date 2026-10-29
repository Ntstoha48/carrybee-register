<?php

header('Content-Type: application/json; charset=utf-8');

$num = trim($_GET['num'] ?? '');
$msg = trim($_GET['msg'] ?? '');

// ভ্যালিডেশন
if ($num === '' || $msg === '') {
    echo json_encode([
        'status' => false,
        'error'  => 'Missing parameters. Required: num, msg'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ফোন নম্বর ভ্যালিডেশন (আপনার প্রয়োজন অনুযায়ী রেগেক্স বদলান)
if (!preg_match('/^\+?[0-9]{10,15}$/', $num)) {
    echo json_encode([
        'status' => false,
        'error'  => 'Invalid phone number format.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// মেসেজকে দুই ভাগে ভাগ করা
$msgLength  = mb_strlen($msg, 'UTF-8');
$halfLength = (int) ceil($msgLength / 2);

$namePart         = mb_substr($msg, 0, $halfLength, 'UTF-8');
$businessNamePart = mb_substr($msg, $halfLength, null, 'UTF-8');

$payload = [
    'name'          => $namePart,
    'phone_number'  => $num,
    'business_name' => $businessNamePart,
];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL            => 'https://api-merchant.carrybee.com/api/v2/merchant/register',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    // ⚠️ প্রোডাকশনে true করুন; শুধু লোকাল টেস্টে false রাখুন
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER     => [
        'User-Agent: Mozilla/5.0 (Linux; Android 12; V2027 Build/SP1A.210812.003) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.8059.43 Mobile Safari/537.36',
        'Accept: application/json',
        'Accept-Encoding: gzip, deflate, br, zstd',
        'Content-Type: application/json',
        'sec-ch-ua-platform: "Android"',
        'sec-ch-ua: "Android WebView";v="155", "Chromium";v="155", "Not(A:Brand";v="24"',
        'sec-ch-ua-mobile: ?1',
        'origin: https://merchant.carrybee.com',
        'x-requested-with: mark.via.gp',
        'sec-fetch-site: same-site',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: https://merchant.carrybee.com/',
        'accept-language: en-US,en;q=0.9',
        'priority: u=1, i',
    ],
]);

$response = curl_exec($curl);
$err      = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// PHP 8.0+ এ curl_close আর প্রয়োজন নেই (no-op), তবে নিরাপদ
if (PHP_VERSION_ID < 80000) {
    curl_close($curl);
}

if ($err) {
    echo json_encode([
        'status'    => false,
        'http_code' => $httpCode,
        'error'     => 'cURL Error: ' . $err
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$decoded = json_decode($response, true);

if (json_last_error() === JSON_ERROR_NONE) {
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode([
        'status'        => true,
        'http_code'     => $httpCode,
        'raw_response'  => $response
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
