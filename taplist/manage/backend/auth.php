<?php
function verify_jwt_token($token) {
    $url = 'https://mysite.dev/wp-json/jwt-auth/v1/token/validate';
    $headers = array(
        'Authorization: Bearer ' . $token
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpcode === 200;
}

function authenticate() {
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        echo json_encode(array('status' => 'error', 'message' => 'No authorization header found'));
        exit;
    }

    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
    list($bearer, $token) = explode(' ', $auth_header);

    if ($bearer !== 'Bearer' || !verify_jwt_token($token)) {
        echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        exit;
    }
}
?>
