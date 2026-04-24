<?php
include 'config.php';

if (!isset($_GET['date']) || !isset($_GET['service_id'])) {
    echo json_encode([]);
    exit;
}

$date       = $_GET['date'];
$service_id = intval($_GET['service_id']);
$doctor_id  = isset($_GET['doctor_id'])  ? intval($_GET['doctor_id'])           : 0;
$preference = isset($_GET['preference']) ? strtoupper(trim($_GET['preference'])) : '';

// Full time slots split into AM and PM
$amTimes = ['08:00', '09:00', '10:00', '11:00'];
$pmTimes = ['12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];

// Filter by preference
if ($preference === 'AM') {
    $allTimes = $amTimes;
} elseif ($preference === 'PM') {
    $allTimes = $pmTimes;
} else {
    $allTimes = array_merge($amTimes, $pmTimes);
}

// Block slots where the selected doctor is already booked that day
if ($doctor_id > 0) {
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(appointment_time, '%H:%i') AS appointment_time
        FROM appointments
        WHERE appointment_date = ?
          AND doctor_id = ?
          AND status NOT IN ('cancelled')
    ");
    $stmt->bind_param("si", $date, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookedTimes = [];
    while ($row = $result->fetch_assoc()) {
        $bookedTimes[] = $row['appointment_time'];
    }

    $availableTimes = array_values(array_diff($allTimes, $bookedTimes));
} else {
    $availableTimes = $allTimes;
}

header('Content-Type: application/json');
echo json_encode($availableTimes);