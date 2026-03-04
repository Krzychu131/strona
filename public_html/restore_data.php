<?php
/**
 * Skrypt do przywrócenia pełnych danych w data.json
 * Uruchom raz aby przywrócić wszystkie pola D1-D7, T, V, S1-S7
 */

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

// Wczytaj istniejące dane
if (file_exists($dataFile)) {
    $content = file_get_contents($dataFile);
    $existingData = json_decode($content, true);
    if ($existingData) {
        // Zachowaj istniejące wartości
        foreach ($existingData as $key => $value) {
            if (isset($fullData[$key])) {
                $fullData[$key] = $value;
            }
        }
    }
}

// Zapisz pełne dane
$result = file_put_contents($dataFile, json_encode($fullData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($result !== false) {
    echo json_encode([
        'success' => true,
        'message' => 'Dane zostały przywrócone',
        'data' => $fullData
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Błąd zapisu danych'
    ]);
}
?>

