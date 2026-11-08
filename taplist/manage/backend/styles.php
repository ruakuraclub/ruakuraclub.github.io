<?php
include 'db.php';
include 'auth.php';

$action = $_GET['action'];
authenticate();

if ($action == 'get_styles') {
    $sql = "SELECT id, name FROM style";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $styles = array();
    while($row = $result->fetch_assoc()) {
        $styles[] = $row;
    }
    echo json_encode(array('styles' => $styles));
    $stmt->close();
} elseif ($action == 'search_styles') {
    $query = '%' . $_GET['query'] . '%';
    $sql = "SELECT id, name FROM style WHERE name LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $query);
    $stmt->execute();
    $result = $stmt->get_result();

    $styles = array();
    while($row = $result->fetch_assoc()) {
        $styles[] = $row;
    }
    echo json_encode(array('styles' => $styles));
    $stmt->close();
} elseif ($action == 'save_style') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $name = $data['name'];

    if ($id) {
        $sql = "UPDATE style SET name=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $name, $id);
    } else {
        $sql = "INSERT INTO style (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $name);
    }

    if ($stmt->execute()) {
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => $stmt->error));
    }
    $stmt->close();
}
?>
