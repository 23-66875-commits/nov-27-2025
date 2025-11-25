<?php
// --- CONFIGURATION ---
$servername = "localhost";
$username = "root";
$password = ""; // Use your actual MySQL password here!
$dbname = "batcave_db";

// Set JSON header
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// Get the raw POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['client_name']) || !isset($data['booking_date']) || !isset($data['total_fee'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// 1. Connect to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // If connection fails, return a clear error
    echo json_encode(["status" => "error", "message" => "Database connection error: " . $conn->connect_error]);
    exit;
}

// 2. Prepare the INSERT statement
// FIX: Explicitly list all relevant columns to match the database structure. 
// We rely on 'submission_timestamp' using its DEFAULT CURRENT_TIMESTAMP value.
$sql = "INSERT INTO bookings (client_name, client_email, booking_type, booking_date, start_time, end_time, total_fee, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

// Bind parameters and sanitize input
$client_name = trim($data['client_name']);
$client_email = trim($data['client_email'] ?? '');
$booking_type = trim($data['booking_type'] ?? 'Study Room');
$booking_date = trim($data['booking_date']);
$start_time = trim($data['start_time'] ?? '00:00:00');
$end_time = trim($data['end_time'] ?? '00:00:00');
$total_fee = floatval($data['total_fee']);
$status = 'Pending'; // Always start as Pending
$notes = trim($data['notes'] ?? '');

$stmt->bind_param("ssssssdss", $client_name, $client_email, $booking_type, $booking_date, $start_time, $end_time, $total_fee, $status, $notes);

if ($stmt->execute()) {
    $booking_id = $conn->insert_id;
    // Success response with the new ID
    echo json_encode([
        "status" => "success",
        "message" => "Booking saved successfully.",
        "dbStatus" => "Pending",
        "bookingId" => $booking_id
    ]);
} else {
    // Error during execution
    echo json_encode(["status" => "error", "message" => "Error executing statement: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>