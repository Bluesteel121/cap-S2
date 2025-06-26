<?php
require_once 'connect.php';

header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['type'])) {
    $type = $_GET['type'];

    if ($type === 'municipality' && isset($_GET['province'])) {
        $province = $_GET['province'];
        $sql = $conn->prepare("SELECT DISTINCT municipality FROM location WHERE province = ? ORDER BY municipality");
        $sql->bind_param("s", $province);
        $sql->execute();
        $result = $sql->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $response[] = ['municipality' => $row['municipality']];
            }
        }
        $sql->close();

    } elseif ($type === 'barangay' && isset($_GET['province']) && isset($_GET['municipality'])) {
        $province = $_GET['province'];
        $municipality = $_GET['municipality'];
        $sql = $conn->prepare("SELECT DISTINCT barangay FROM location WHERE province = ? AND municipality = ? ORDER BY barangay");
        $sql->bind_param("ss", $province, $municipality);
        $sql->execute();
        $result = $sql->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $response[] = ['barangay' => $row['barangay']];
            }
        }
        $sql->close();
    }
}

echo json_encode($response);

closeConnection();
?>