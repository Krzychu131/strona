<?php
/**
 * Skrypt do aktualizacji pełnych danych w data.json na serwerze
 * Uruchom raz aby zaktualizować wszystkie pola D1-D7, T, V, S1-S7
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dataFile = 'data.json';

// Pełne dane z wszystkimi polami - użyj rzeczywistych wartości D1-D7
$fullData = [
    'D1' => 281,  // Rzeczywiste wartości z czujników
    'D2' => 280,
    'D3' => 7,
    'D4' => 309,
    'D5' => 96,
    'D6' => 355,
    'D7' => -1,  // Domyślna wartość dla D7 (brak danych)
    'T' => 19.5,
    'V' => 3.412,
    'S1' => '',
    'S2' => '',
    'S3' => '',
    'S4' => '',
    'S5' => '',
    'S6' => '',
    'S7' => ''
];

// Wczytaj istniejące dane i zachowaj wartości S1-S7, T, V jeśli istnieją
if (file_exists($dataFile)) {
    $content = file_get_contents($dataFile);
    $existingData = json_decode($content, true);
    if ($existingData) {
        // Zachowaj istniejące wartości S1-S7, T, V
        foreach (['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'T', 'V'] as $key) {
            if (isset($existingData[$key]) && $existingData[$key] !== null && $existingData[$key] !== '') {
                $fullData[$key] = $existingData[$key];
            }
        }
        // Zachowaj istniejące wartości D1-D7 jeśli istnieją
        for ($i = 1; $i <= 7; $i++) {
            $dKey = 'D' . $i;
            if (isset($existingData[$dKey]) && $existingData[$dKey] !== null && $existingData[$dKey] !== '' && $existingData[$dKey] !== -1) {
                $fullData[$dKey] = $existingData[$dKey];
            }
        }
    }
}

// Zapisz pełne dane
$result = file_put_contents($dataFile, json_encode($fullData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($result !== false) {
    echo json_encode([
        'success' => true,
        'message' => 'Dane D1-D7 zostały zaktualizowane',
        'data' => $fullData
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Błąd zapisu danych'
    ]);
}
?>








