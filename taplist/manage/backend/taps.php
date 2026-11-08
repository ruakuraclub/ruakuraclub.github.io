<?php
include 'db.php';
include 'auth.php';

$action = $_GET['action'];
authenticate();

if ($action == 'get_taps') {
    $sql = "SELECT id, name, in_service, keg_id FROM taps";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $taps = array();
    while($row = $result->fetch_assoc()) {
        $taps[] = $row;
    }
    echo json_encode(array('taps' => $taps));
    $stmt->close();
} elseif ($action == 'record_date_served') {
    $data = json_decode(file_get_contents('php://input'), true);
    $tapId = $data['tapId'];
    $dateServed = $data['dateServed'];

    $sql = "UPDATE kegs SET date_served=? WHERE id=(SELECT keg_id FROM taps WHERE id=?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $dateServed, $tapId);

    if ($stmt->execute()) {
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => $stmt->error));
    }
    $stmt->close();
} elseif ($action == 'mark_keg_empty') {
    $data = json_decode(file_get_contents('php://input'), true);
    $tapId = $data['tapId'];
    $dateToday = date('Y-m-d');

    $sql = "UPDATE kegs SET empty=? WHERE id=(SELECT keg_id FROM taps WHERE id=?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $dateToday, $tapId);

    if ($stmt->execute()) {
        $sql = "UPDATE taps SET keg_id=NULL, in_service=0 WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $tapId);
        if ($stmt->execute()) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => $stmt->error));
        }
    } else {
        echo json_encode(array('status' => 'error', 'message' => $stmt->error));
    }
    $stmt->close();
}
?>
