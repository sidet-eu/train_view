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
    $logFile = '/var/www/sideteu/projects/vlaciky/logs.txt';
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
    $count = 0;
    foreach ($data as $train) {
        // Extract each value into separate variables
        $stanicaZ = $train['StanicaZCislo'] ?? null;
        $stanicaDo = $train['StanicaDoCislo'] ?? null;
        if (($train['TypVlaku'] === 'Ex') && isset($train['CisloVlaku']) && (substr($train['CisloVlaku'], 0, 1) === '1' || substr($train['CisloVlaku'], 0, 1) === '2') ||in_array($train['CisloVlaku'], ['442', '443', '476', '477'])) {
            if(in_array($train['CisloVlaku'], ["1003", "1008", "1011", "1012", "1020", "1021", "1041", "1043", "1044", "1045", "1046", "1047", "1048", "1050", "1222", "1223"])) {
                $typVlaku = 'RJ';
                if (isset($train['Nazov'])) {
                    $train['Nazov'] = preg_replace('/Ex/', 'RJ', $train['Nazov'], 1);
                }
            } elseif(in_array($train['CisloVlaku'], ['442', '443', '476', '477'])) {
                $typVlaku = 'EN';
                if (isset($train['Nazov'])) {
                    $train['Nazov'] = preg_replace('/Ex/', 'EN', $train['Nazov'], 1);
                }
            } else {
                if (isset($train['Nazov'])) {
                    $train['Nazov'] = preg_replace('/Ex/', 'EC', $train['Nazov'], 1);
                }
                $typVlaku = 'EC';
            }
            $nazov = $train['Nazov'];
        } else {
            $typVlaku = $train['TypVlaku'];
            $nazov = $train['Nazov'];
        }
        $cisloVlaku = $train['CisloVlaku'] ?? null;
        $nazovVlaku = $train['NazovVlaku'] ?? null;
        if($typVlaku === 'RJ') {
            $nazovVlaku = "RegioJet";
        }
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
            $count++;
        } else {
            logMessage("Insert Error for train: " . json_encode($train) . " - Error: " . $stmt->error);
        }
    }

    $stmt->close();
    $mysqli->close();
    logMessage("Inserted $count records into the database.");
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