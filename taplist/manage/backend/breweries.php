<?php
include 'db.php';
include 'auth.php';

$action = $_GET['action'];
authenticate();

if ($action == 'get_breweries') {
    $sql = "SELECT id, name, location FROM brewery";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $breweries = array();
    while($row = $result->fetch_assoc()) {
        $breweries[] = $row;
    }
    echo json_encode(array('breweries' => $breweries));
    $stmt->close();
} elseif ($action == 'search_breweries') {
    $query = '%' . $_GET['query'] . '%';
    $sql = "SELECT id, name, location FROM brewery WHERE name LIKE ? OR location LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();

    $breweries = array();
    while($row = $result->fetch_assoc()) {
        $breweries[] = $row;
    }
    echo json_encode(array('breweries' => $breweries));
    $stmt->close();
} elseif ($action == 'save_brewery') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $name = $data['name'];
    $location = $data['location'];

    if ($id) {
        $sql = "UPDATE brewery SET name=?, location=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $location, $id);
    } else {
        $sql = "INSERT INTO brewery (name, location) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $name, $location);
    }

    if ($stmt->execute()) {
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => $stmt->error));
    }
    $stmt->close();
}
?>
