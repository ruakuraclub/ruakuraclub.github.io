<?php
include 'db.php';
include 'auth.php';

$action = $_GET['action'];
authenticate();

if ($action == 'get_kegs') {
    $sql = "SELECT id, name, volume, price FROM kegs";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $kegs = array();
    while($row = $result->fetch_assoc()) {
        $kegs[] = $row;
    }
    echo json_encode(array('kegs' => $kegs));
    $stmt->close();
} elseif ($action == 'search_kegs') {
    $query = '%' . $_GET['query'] . '%';
    $sql = "SELECT id, name, volume, price FROM kegs WHERE name LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $query);
    $stmt->execute();
    $result = $stmt->get_result();

    $kegs = array();
    while($row = $result->fetch_assoc()) {
        $kegs[] = $row;
    }
    echo json_encode(array('kegs' => $kegs));
    $stmt->close();
} elseif ($action == 'save_keg') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $name = $data['name'];
    $volume = $data['volume'];
    $beverage_id = $data['beverage_id'];
    $price = $data['price'];
    $half = $data['half'];
    $handle = $data['handle'];
    $jug = $data['jug'];

    if ($id) {
        $sql = "UPDATE kegs SET name=?, volume=?, beverage_id=?, price=?, half=?, handle=?, jug=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiiiiii", $name, $volume, $beverage_id, $price, $half, $handle, $jug, $id);
    } else {
        $date_in = date('Y-m-d');
        $sql = "INSERT INTO kegs (name, volume, beverage_id, price, half, handle, jug, date_in) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiiiiis", $name, $volume, $beverage_id, $price, $half, $handle, $jug, $date_in);
    }

    if ($stmt->execute()) {
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => $stmt->error));
    }
    $stmt->close();
} elseif ($action == 'get_keg') {
    $id = $_GET['id'];
    $sql = "SELECT id, name, volume, beverage_id, price, half, handle, jug FROM kegs WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(array('keg' => $result->fetch_assoc()));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Keg not found'));
    }
    $stmt->close();
}
?>
