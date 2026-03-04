<?php
$data = file_get_contents("php://input");

if (!$data) {
    http_response_code(400);
    echo "NO DATA";
    exit;
}

json_decode($data);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo "INVALID JSON";
    exit;
}

file_put_contents("data.json", $data);
http_response_code(200);
echo "OK";
