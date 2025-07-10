<?php
require_once 'connect.php';

header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['level'])) {
    $level = $_GET['level'];

    if ($level === 'province') {
        $sql = $conn->prepare("SELECT DISTINCT province FROM location ORDER BY province");
        $sql->execute();
        $result = $sql->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $response[] = ['id' => $row['province'], 'name' => $row['province']];
            }
        }
        $sql->close();

    } elseif ($level === 'municipality' && isset($_GET['parent_id'])) {
        $province = $_GET['parent_id'];
        $sql = $conn->prepare("SELECT DISTINCT municipality FROM location WHERE province = ? ORDER BY municipality");
        $sql->bind_param("s", $province);
        $sql->execute();
        $result = $sql->get_result();

        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {
                $response[] = ['id' => $row['municipality'], 'name' => $row['municipality']];
            }
        }
        $sql->close();

    } elseif ($level === 'barangay' && isset($_GET['parent_id'])) {
        // We need both province and municipality to get barangays
        // The parent_id in the JavaScript for barangays is the municipality ID (which is the municipality name)
        // We need to get the selected province from the province dropdown value

        // This part needs a slight adjustment in the frontend JavaScript
        // to pass both province and parent_id (municipality) when fetching barangays.
        // For now, let's assume the request includes both province and parent_id (municipality)
        if (isset($_GET['province']) && isset($_GET['parent_id'])) {
             $province = $_GET['province']; // Assuming province is also passed
             $municipality = $_GET['parent_id'];

        $sql = $conn->prepare("SELECT DISTINCT barangay FROM location WHERE province = ? AND municipality = ? ORDER BY barangay");
             $sql->bind_param("ss", $province, $municipality);
        $sql->execute();
        $result = $sql->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $response[] = ['id' => $row['barangay'], 'name' => $row['barangay']];
            }
        }
        $sql->close();
        }
    }
}

echo json_encode($response);

closeConnection();
?>