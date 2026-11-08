<?php
include 'db.php';
include 'auth.php';

$action = $_GET['action'];
authenticate();

if ($action == 'get_consumption') {
    $sql = "SELECT id, name, date_served, halves_served, handles_served, jugs_served FROM kegs ORDER BY date_served DESC LIMIT 12";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $kegs = array();
    while($row = $result->fetch_assoc()) {
        $kegs[] = $row;
    }
    echo json_encode(array('kegs' => $kegs));
    $stmt->close();
} elseif ($action == 'update_consumption') {
    $data = json_decode(file_get_contents('php://input'), true);
    $kegId = $data['kegId'];
    $halvesServed = $data['halvesServed'];
    $handlesServed = $data['handlesServed'];
    $jugsServed = $data['jugsServed'];
    $dateCounted = date('Y-m-d');

    $sql = "UPDATE kegs SET halves_served=?, handles_served=?, jugs_served=?, date_counted=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiis", $halvesServed, $handlesServed, $jugsServed, $dateCounted, $kegId);

    if ($stmt->execute()) {
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => $stmt->error));
    }
    $stmt->close();
}
?>
