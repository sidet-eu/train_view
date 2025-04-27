<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';
function toTitleCase($string) {
    $string = mb_strtolower($string, 'UTF-8');
    
    $words = explode(' ', $string);
    
    foreach ($words as &$word) {
        if (mb_strlen($word, 'UTF-8') > 0) {
            $firstLetter = mb_substr($word, 0, 1, 'UTF-8');
            $restOfWord = mb_substr($word, 1, null, 'UTF-8');
            $word = mb_strtoupper($firstLetter, 'UTF-8') . $restOfWord;
        }
    }
    
    return implode(' ', $words);
}

function getRoute($train_number) {
    $json = file_get_contents('routes.json');
    $data = json_decode($json, true);
    $route_number = null;
    foreach ($data as $route) {
        if (in_array($train_number, $route['trains'])) {
            $route_number = $route['route'];
            break;
        }
    }
    return $route_number;
}
function removeCapitalWord($input) {
    $result = preg_replace('/\b[A-ZŔŇŤÝÁÍÉĽŠČŽ]+\b/u', '', $input);
    $result = preg_replace('/\s+/', ' ', $result);
    return trim($result);
}
function removeNumber($input) {
    $result = preg_replace('/\b\d+\b/', '', $input);
    $result = preg_replace('/\s+/', ' ', $result);
    return trim($result);
}
function getForeignStation($trainInfo,$pos) {
    $stationsJson = file_get_contents('stations.json');
    if ($stationsJson === FALSE) {
        die('Error fetching the stations JSON file');
    }
    $stationsData = json_decode($stationsJson, true);
    if ($stationsData === null) {
        die('Error decoding the JSON data');
    }
    
    foreach ($stationsData['stations'] as $station) {
        if (isset($station['id']) && isset($stid) && $station['id'] === $stid) {
            return $station['name'] . ' ' . $stid;
        }
    }
    
    $matches = [];
    if (preg_match('/^[A-Z\s]+\s+(.*)\s*->\s*(.*)$/', $trainInfo, $matches) === 1) {
        $town1 = isset($matches[1]) ? trim($matches[1]) : '';
        $town2 = isset($matches[2]) ? trim($matches[2]) : '';
        if ($pos == 1) {
            $town1 = removeCapitalWord(removeNumber($town1));
            return $town1;
        } elseif ($pos == 2) {
            $town2 = removeCapitalWord(removeNumber($town2));
            return $town2;
        }
    } else {
        return "Zahraničie";
    }
}
function getStationName($station_number, $train_name, $pos) {
    $station_number = substr($station_number, 0, -2);
    $json = file_get_contents('stations.json');
    $data = json_decode($json, true);
    $station_name = null;

    if (isset($data['stations'])) {
        foreach ($data['stations'] as $station) {
            if ($station['id'] == $station_number) {
                $station_name = $station['name'];
                break;
            }
        }
    }

    if ($station_name === null) {
        $station_name = getForeignStation($train_name, $pos);
    }
    
    return $station_name;
}
// load table "train_data"; 
// col: "CisloVlaku" = train_number, "Typ" = type, "Nazov" = train_name, "Meska" = delay, "InfoZoStanice" = current, "StanicaZCislo" = from, "StanicaDoCislo" = to, "Dopravca" = carrier, "Trasa" = line
$search_param = '';
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $_SESSION['search_query'] = $_GET['search'];
    $search_param = $_GET['search'];
} elseif(!isset($_SESSION['search_query'])) {
    unset($_SESSION['search_query']);
    $search_param = "";
}

$train_numbers = array_filter(array_map('trim', explode(',', $search_param)));
$trainData = [];

if (!empty($train_numbers)) {
    foreach ($train_numbers as $train_number) {
        $sql = "SELECT CisloVlaku, TypVlaku, Nazov, NazovVlaku, Meska, InfoZoStanice, StanicaZCislo, StanicaDoCislo, Dopravca FROM train_data WHERE CisloVlaku = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $train_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $trainData[] = [
                    "type" => $row['TypVlaku'],
                    "train_number" => $row['CisloVlaku'],
                    "train_name" => toTitleCase($row['NazovVlaku']),
                    "delay" => $row['Meska'],
                    "from" => getStationName($row['StanicaZCislo'], $row['Nazov'], 1),
                    "to" => getStationName($row['StanicaDoCislo'], $row['Nazov'], 2),
                    "carrier" => $row['Dopravca'],
                    "current" => $row['InfoZoStanice'],
                    "line" => getRoute($row['CisloVlaku']) 
                ];
            }
        } else {
            $sql = "SELECT CisloVlaku, TypVlaku, Nazov, NazovVlaku, Meska, InfoZoStanice, StanicaZCislo, StanicaDoCislo, Dopravca FROM train_data WHERE CisloVlaku LIKE ?";
            $search_pattern = $train_number . '%';
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('s', $search_pattern);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $trainData[] = [
                        "type" => $row['TypVlaku'],
                        "train_number" => $row['CisloVlaku'],
                        "train_name" => toTitleCase($row['NazovVlaku']),
                        "delay" => $row['Meska'],
                        "from" => getStationName($row['StanicaZCislo'], $row['Nazov'], 1),
                        "to" => getStationName($row['StanicaDoCislo'], $row['Nazov'], 2),
                        "carrier" => $row['Dopravca'],
                        "current" => $row['InfoZoStanice'],
                        "line" => getRoute($row['CisloVlaku']) 
                    ];
                }
            } else {
                $trainData[] = ["error" => "No train data found for train number: $train_number"];
            }
        }
    }
} else {
    $sql = "SELECT CisloVlaku, TypVlaku, Nazov, NazovVlaku, Meska, InfoZoStanice, StanicaZCislo, StanicaDoCislo, Dopravca FROM train_data";
    $result = $mysqli->query($sql);
    $trainData = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $trainData[] = [
            "type" => $row['TypVlaku'],
            "train_number" => $row['CisloVlaku'],
            "train_name" => toTitleCase($row['NazovVlaku']),
            "delay" => $row['Meska'],
            "from" => getStationName($row['StanicaZCislo'], $row['Nazov'], 1),
            "to" => getStationName($row['StanicaDoCislo'], $row['Nazov'], 2),
            "carrier" => $row['Dopravca'],
            "current" => $row['InfoZoStanice'],
            "line" => getRoute($row['CisloVlaku'])
            ];
        }
    }
}

echo json_encode($trainData);
?>