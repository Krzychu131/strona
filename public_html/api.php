<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Obsługa preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dataFile = 'data.json';

// Funkcja do odczytu danych
function readData($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return $data ? $data : [];
}

// Funkcja do zapisu danych
function saveData($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Pobierz wszystkie dane
            $data = readData($dataFile);
            
            // WAŻNE: Upewnij się, że zwracamy wszystkie pola, nawet jeśli są puste
            // To pomaga aplikacji mobilnej poprawnie wyświetlać wszystkie czujniki
            $allFields = ['D1', 'D2', 'D3', 'D4', 'D5', 'D6', 'D7', 'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'T', 'V', 'PH', 'GH', 'KH', 'NO2', 'NO3', 'CL2', 'R', 'G', 'B', 'Set'];
            $completeData = [];
            
            // Dodaj wszystkie pola z wartościami lub wartościami domyślnymi
            // WAŻNE: Upewniamy się, że wszystkie klucze są zawsze obecne w odpowiedzi
            // Używamy wartości -1 dla brakujących D1-D6, T, V (aplikacja rozpozna to jako "Brak danych")
            foreach ($allFields as $field) {
                if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '' && $data[$field] !== -1) {
                    $completeData[$field] = $data[$field];
                } else {
                    // Jeśli pole nie istnieje, dodaj -1 dla D1-D7, T, V, PH, GH, KH, NO2, NO3, CL2 (aplikacja pokaże "Brak danych")
                    // 0 dla R, G, B (domyślna wartość dla RGB)
                    // lub pusty string dla S1-S7, Set
                    if (strpos($field, 'D') === 0 || $field === 'T' || $field === 'V' || $field === 'PH' || $field === 'GH' || $field === 'KH' || $field === 'NO2' || $field === 'NO3' || $field === 'CL2') {
                        $completeData[$field] = -1; // Używamy -1 jako znacznika "brak danych"
                    } elseif ($field === 'R' || $field === 'G' || $field === 'B') {
                        $completeData[$field] = 0; // Domyślna wartość 0% dla RGB
                    } else {
                        $completeData[$field] = '';
                    }
                }
            }
            
            // Zwróć wszystkie dane - wszystkie klucze będą zawsze obecne
            echo json_encode($completeData, JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            // Aktualizacja pojedynczej wartości lub wielu wartości
            $input = file_get_contents("php://input");
            $postData = json_decode($input, true);
            
            if (!$postData) {
                // Spróbuj z $_POST jeśli JSON nie zadziałał
                $postData = $_POST;
            }
            
            if (empty($postData)) {
                http_response_code(400);
                echo json_encode(['error' => 'Brak danych do aktualizacji']);
                exit;
            }
            
            $currentData = readData($dataFile);
            
            // WAŻNE: Zachowaj istniejące wartości D1-D7 jeśli nie są w POST
            // (aby nie tracić danych z czujników gdy aktualizujemy tylko S1-S7, T, V)
            $preservedDValues = [];
            for ($i = 1; $i <= 7; $i++) {
                $dKey = 'D' . $i;
                if (isset($currentData[$dKey]) && !isset($postData[$dKey])) {
                    $preservedDValues[$dKey] = $currentData[$dKey];
                }
            }
            
            // Aktualizuj tylko przekazane wartości
            foreach ($postData as $key => $value) {
                // Walidacja kluczy (tylko dozwolone klucze)
                $allowedKeys = ['D1', 'D2', 'D3', 'D4', 'D5', 'D6', 'D7', 'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'T', 'V', 'PH', 'GH', 'KH', 'NO2', 'NO3', 'CL2', 'R', 'G', 'B', 'Set'];
                if (in_array($key, $allowedKeys)) {
                    if ($value === '' || $value === null) {
                        // Nie usuwaj D1-D7 nawet jeśli są puste - zachowaj istniejące wartości
                        // Nie usuwaj też pól kalibracji (PH, GH, KH, NO2, NO3, CL2)
                        // Nie usuwaj pól RGB i Set
                        if (strpos($key, 'D') !== 0 && !in_array($key, ['PH', 'GH', 'KH', 'NO2', 'NO3', 'CL2', 'R', 'G', 'B', 'Set'])) {
                            unset($currentData[$key]);
                        }
                    } else {
                        // Pola S1-S7 i Set są stringami, pozostałe są numeryczne
                        if (strpos($key, 'S') === 0 || $key === 'Set') {
                            $currentData[$key] = strval($value);
                        } else {
                            $numValue = is_numeric($value) ? floatval($value) : $value;
                            $currentData[$key] = $numValue;
                        }
                    }
                }
            }
            
            // Przywróć zachowane wartości D1-D7
            foreach ($preservedDValues as $key => $value) {
                $currentData[$key] = $value;
            }
            
            // WAŻNE: Jeśli akaryrym_fixed.py wysyła dane D1-D7, upewnij się że są zapisane
            // Sprawdź czy w POST są jakieś wartości D1-D7 - jeśli tak, zapisz je wszystkie
            $hasDValues = false;
            for ($i = 1; $i <= 7; $i++) {
                if (isset($postData['D' . $i])) {
                    $hasDValues = true;
                    break;
                }
            }
            
            // Jeśli są wartości D w POST, upewnij się że wszystkie są zapisane
            if ($hasDValues) {
                for ($i = 1; $i <= 7; $i++) {
                    $dKey = 'D' . $i;
                    if (isset($postData[$dKey]) && ($postData[$dKey] !== '' && $postData[$dKey] !== null)) {
                        $currentData[$dKey] = is_numeric($postData[$dKey]) ? floatval($postData[$dKey]) : $postData[$dKey];
                    }
                }
            }
            
            if (saveData($dataFile, $currentData)) {
                echo json_encode(['success' => true, 'data' => $currentData]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Błąd zapisu danych']);
            }
            break;

        case 'PUT':
            // Zastąp wszystkie dane (jak oryginalny update.php) - BEZ WALIDACJI
            // Pozwala Raspberry Pi zapisywać dane bezpośrednio, tak jak update.php
            $input = file_get_contents("php://input");
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Brak danych']);
                exit;
            }
            
            // Waliduj tylko czy to poprawny JSON (jak update.php)
            json_decode($input);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'INVALID JSON']);
                exit;
            }
            
            // Zapisz surowe dane JSON bezpośrednio (jak update.php) - BEZ WALIDACJI KLUCZY
            // To pozwala Raspberry Pi zapisywać dowolne dane, w tym D1-D7
            if (file_put_contents($dataFile, $input)) {
                http_response_code(200);
                // Zwróć JSON dla aplikacji mobilnej i strony HTML
                echo json_encode(['success' => true, 'message' => 'OK']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Błąd zapisu danych']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Nieobsługiwana metoda HTTP']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
}
?>
