<?php
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "database_name";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$action = $_GET['action'];

if ($action == 'get_styles') {
    $sql = "SELECT id, name FROM style";
    $result = $conn->query($sql);

    $styles = array();
    while($row = $result->fetch_assoc()) {
        $styles[] = $row;
    }
    echo json_encode(array('styles' => $styles));
} elseif ($action == 'search_styles') {
    $query = $_GET['query'];
    $sql = "SELECT id, name FROM style WHERE name LIKE '%$query%'";
    $result = $conn->query($sql);

    $styles = array();
    while($row = $result->fetch_assoc()) {
        $styles[] = $row;
    }
    echo json_encode(array('styles' => $styles));
} elseif ($action == 'save_style') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $name = $data['name'];

    if ($id) {
        $sql = "UPDATE style SET name='$name' WHERE id=$id";
    } else {
        $sql = "INSERT INTO style (name) VALUES ('$name')";
    }

    if ($conn->query($sql) === TRUE) {
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => $conn->error));
    }
} elseif ($action == 'get_style') {
    $id = $_GET['id'];
    $sql = "SELECT id, name FROM style WHERE id=$id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo json_encode(array('style' => $result->fetch_assoc()));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Style not found'));
    }
} elseif ($action == 'delete_style') {
    $id = $_GET['id'];
    $sqlCheck = "SELECT COUNT(*) as count FROM beverage WHERE style_id=$id";
    $resultCheck = $conn->query($sqlCheck);
    $rowCheck = $resultCheck->fetch_assoc();

    if ($rowCheck['count'] == 0) {
        $sql = "DELETE FROM style WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => $conn->error));
        }
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Cannot delete style, associated beverages exist'));
    }
}

// Breweries

if ($action == 'get_breweries') {
  $sql = "SELECT id, name, location FROM brewery";
  $result = $conn->query($sql);

  $breweries = array();
  while($row = $result->fetch_assoc()) {
      $breweries[] = $row;
  }
  echo json_encode(array('breweries' => $breweries));
} elseif ($action == 'search_breweries') {
  $query = $_GET['query'];
  $sql = "SELECT id, name, location FROM brewery WHERE name LIKE '%$query%' OR location LIKE '%$query%'";
  $result = $conn->query($sql);

  $breweries = array();
  while($row = $result->fetch_assoc()) {
      $breweries[] = $row;
  }
  echo json_encode(array('breweries' => $breweries));
} elseif ($action == 'save_brewery') {
  $data = json_decode(file_get_contents('php://input'), true);
  $id = $data['id'];
  $name = $data['name'];
  $location = $data['location'];

  if ($id) {
      $sql = "UPDATE brewery SET name='$name', location='$location' WHERE id=$id";
  } else {
      $sql = "INSERT INTO brewery (name, location) VALUES ('$name', '$location')";
  }

  if ($conn->query($sql) === TRUE) {
      echo json_encode(array('status' => 'success'));
  } else {
      echo json_encode(array('status' => 'error', 'message' => $conn->error));
  }
} elseif ($action == 'get_brewery') {
  $id = $_GET['id'];
  $sql = "SELECT id, name, location FROM brewery WHERE id=$id";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
      echo json_encode(array('brewery' => $result->fetch_assoc()));
  } else {
      echo json_encode(array('status' => 'error', 'message' => 'Brewery not found'));
  }
} elseif ($action == 'delete_brewery') {
  $id = $_GET['id'];
  $sqlCheck = "SELECT COUNT(*) as count FROM beverage WHERE brewery_id=$id";
  $resultCheck = $conn->query($sqlCheck);
  $rowCheck = $resultCheck->fetch_assoc();

  if ($rowCheck['count'] == 0) {
      $sql = "DELETE FROM brewery WHERE id=$id";
      if ($conn->query($sql) === TRUE) {
          echo json_encode(array('status' => 'success'));
      } else {
          echo json_encode(array('status' => 'error', 'message' => $conn->error));
      }
  } else {
      echo json_encode(array('status' => 'error', 'message' => 'Cannot delete brewery, associated beverages exist'));
  }
}

// kegs
// Add these new sections to the existing backend.php code

if ($action == 'get_kegs') {
  $sql = "SELECT id, name, volume, price FROM kegs";
  $result = $conn->query($sql);

  $kegs = array();
  while($row = $result->fetch_assoc()) {
      $kegs[] = $row;
  }
  echo json_encode(array('kegs' => $kegs));
} elseif ($action == 'search_kegs') {
  $query = $_GET['query'];
  $sql = "SELECT id, name, volume, price FROM kegs WHERE name LIKE '%$query%'";
  $result = $conn->query($sql);

  $kegs = array();
  while($row = $result->fetch_assoc()) {
      $kegs[] = $row;
  }
  echo json_encode(array('kegs' => $kegs));
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
      $sql = "UPDATE kegs SET name='$name', volume=$volume, beverage_id=$beverage_id, price=$price, half=$half, handle=$handle, jug=$jug WHERE id=$id";
  } else {
      $date_in = date('Y-m-d');
      $sql = "INSERT INTO kegs (name, volume, beverage_id, price, half, handle, jug, date_in) VALUES ('$name', $volume, $beverage_id, $price, $half, $handle, $jug, '$date_in')";
  }

  if ($conn->query($sql) === TRUE) {
      echo json_encode(array('status' => 'success'));
  } else {
      echo json_encode(array('status' => 'error', 'message' => $conn->error));
  }
} elseif ($action == 'get_keg') {
  $id = $_GET['id'];
  $sql = "SELECT id, name, volume, beverage_id, price, half, handle, jug FROM kegs WHERE id=$id";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
      echo json_encode(array('keg' => $result->fetch_assoc()));
  } else {
      echo json_encode(array('status' => 'error', 'message' => 'Keg not found'));
  }
}

// Taps
// Add these new sections to the existing backend.php code

if ($action == 'get_taps') {
  $sql = "SELECT id, name, in_service, keg_id FROM taps";
  $result = $conn->query($sql);

  $taps = array();
  while($row = $result->fetch_assoc()) {
      $taps[] = $row;
  }
  echo json_encode(array('taps' => $taps));
} elseif ($action == 'record_date_served') {
  $data = json_decode(file_get_contents('php://input'), true);
  $tapId = $data['tapId'];
  $dateServed = $data['dateServed'];

  $sql = "UPDATE kegs SET date_served='$dateServed' WHERE id=(SELECT keg_id FROM taps WHERE id=$tapId)";
  if ($conn->query($sql) === TRUE) {
      echo json_encode(array('status' => 'success'));
  } else {
      echo json_encode(array('status' => 'error', 'message' => $conn->error));
  }
} elseif ($action == 'mark_keg_empty') {
  $data = json_decode(file_get_contents('php://input'), true);
  $tapId = $data['tapId'];
  $dateToday = date('Y-m-d');

  $sql = "UPDATE kegs SET empty='$dateToday' WHERE id=(SELECT keg_id FROM taps WHERE id=$tapId)";
  if ($conn->query($sql) === TRUE) {
      $sql = "UPDATE taps SET keg_id=NULL, in_service=0 WHERE id=$tapId";
      if ($conn->query($sql) === TRUE) {
          echo json_encode(array('status' => 'success'));
      } else {
          echo json_encode(array('status' => 'error', 'message' => $conn->error));
      }
  } else {
      echo json_encode(array('status' => 'error', 'message' => $conn->error));
  }
}

// Consumption

if ($action == 'get_consumption') {
  $sql = "SELECT id, name, date_served, halves_served, handles_served, jugs_served FROM kegs ORDER BY date_served DESC LIMIT 12";
  $result = $conn->query($sql);

  $kegs = array();
  while($row = $result->fetch_assoc()) {
      $kegs[] = $row;
  }
  echo json_encode(array('kegs' => $kegs));
} elseif ($action == 'update_consumption') {
  $data = json_decode(file_get_contents('php://input'), true);
  $kegId = $data['kegId'];
  $halvesServed = $data['halvesServed'];
  $handlesServed = $data['handlesServed'];
  $jugsServed = $data['jugsServed'];
  $dateCounted = date('Y-m-d');

  $sql = "UPDATE kegs SET halves_served=$halvesServed, handles_served=$handlesServed, jugs_served=$jugsServed, date_counted='$dateCounted' WHERE id=$kegId";
  if ($conn->query($sql) === TRUE) {
      echo json_encode(array('status' => 'success'));
  } else {
      echo json_encode(array('status' => 'error', 'message' => $conn->error));
  }
}

// taplist:
if ($action == 'get_in_service_taps') {
  $sql = "
  SELECT 
      t.id, 
      b.badge, 
      br.name as brewery, 
      s.name as style, 
      b.abv, 
      k.half, 
      k.handle, 
      k.jug, 
      DATEDIFF(CURDATE(), k.date_served) as age 
  FROM 
      taps t 
      JOIN kegs k ON t.keg_id = k.id 
      JOIN beverage b ON k.beverage_id = b.id 
      JOIN brewery br ON b.brewery_id = br.id 
      JOIN style s ON b.style_id = s.id 
  WHERE 
      t.in_service = TRUE
  ";

  $result = $conn->query($sql);

  $taps = array();
  while($row = $result->fetch_assoc()) {
      $taps[] = $row;
  }
  echo json_encode(array('taps' => $taps));
}


$conn->close();
?>
