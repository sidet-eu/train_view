<?php
require_once 'config.php';
function getInitialCookie() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://mapa.zsr.sk/index.aspx");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    if ($response === false) {
        die("cURL Error: " . curl_error($ch));
    }
    curl_close($ch);
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
    $cookies = [];
    foreach ($matches[1] as $cookie) {
        $cookies[] = $cookie;
    }
    return implode('; ', $cookies);
}

function fetchData($cookie) {
    $url = "https://mapa.zsr.sk/api/action";
    $postData = [
        'action' => 'gtall',
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/x-www-form-urlencoded",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
        "Cookie: " . $cookie
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        die("cURL Error: " . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

function logMessage($message) {
    $logFile = 'logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

function saveToDatabase($data) {
    global $mysqli;

    if (!$mysqli->query("TRUNCATE TABLE train_data")) {
        logMessage("Truncate Error: " . $mysqli->error);
        die("Truncate Error: " . $mysqli->error);
    }

    $sql = "INSERT INTO train_data (
                StanicaZCislo, StanicaDoCislo, Nazov, TypVlaku, CisloVlaku, NazovVlaku, 
                Popis, Meska, Dopravca, InfoZoStanice, MeskaText
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        logMessage("Prepare Error: " . $mysqli->error);
        die("Prepare Error: " . $mysqli->error);
    }

    foreach ($data as $train) {
        // Extract each value into separate variables
        $stanicaZ = $train['StanicaZCislo'] ?? null;
        $stanicaDo = $train['StanicaDoCislo'] ?? null;
        $nazov = $train['Nazov'] ?? null;
        $typVlaku = $train['TypVlaku'] ?? null;
        $cisloVlaku = $train['CisloVlaku'] ?? null;
        $nazovVlaku = $train['NazovVlaku'] ?? null;
        $popis = $train['Popis'] ?? null;
        $meska = $train['Meska'] ?? null;
        $dopravca = $train['Dopravca'] ?? null;
        $infoZoStanice = $train['InfoZoStanice'] ?? null;
        $meskaText = $train['MeskaText'] ?? null;
    
        $stmt->bind_param(
            "iisssssssss",
            $stanicaZ,
            $stanicaDo,
            $nazov,
            $typVlaku,
            $cisloVlaku,
            $nazovVlaku,
            $popis,
            $meska,
            $dopravca,
            $infoZoStanice,
            $meskaText
        );

        if ($stmt->execute()) {
            logMessage("Insert OK for train: " . json_encode($train));
        } else {
            logMessage("Insert Error for train: " . json_encode($train) . " - Error: " . $stmt->error);
        }
    }

    $stmt->close();
    $mysqli->close();

    echo "Data saved to the database successfully.\n";
}


function removeTrasaData(&$data) {
    foreach ($data as &$train) {
        if (isset($train['Trasa'])) {
            unset($train['Trasa']);
        }
    }
}

$cookie = getInitialCookie();
$data = fetchData($cookie);
removeTrasaData($data);
saveToDatabase($data);