<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$conn = new mysqli("localhost", "enriquemikhail_enriquemikhail", "$2RzW#tp.v%*uKB_", "enriquemikhail_printer_management_db");


// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the current user’s ID
$userResult = $conn->query("SELECT id FROM users WHERE username='" . $_SESSION['username'] . "'");
$userRow = $userResult->fetch_assoc();
$current_user_id = $userRow['id'];

// Initialize success message variable
$requestSent = false;

// Handle support request creation
if (isset($_POST['request_support'])) {
    $message = $_POST['message'];
    $description = $_POST['description'] ?? null; // Optional description field

    // Prepare and bind SQL statement
    $stmt = $conn->prepare("INSERT INTO support_requests (user_id, request_type, description, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $current_user_id, $message, $description);

    // Execute and check if the request was successfully saved
    if ($stmt->execute()) {
        $requestSent = true;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Support</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Request Support</h2>
        <a href="user_dashboard.php" class="btn btn-secondary">Return to Dashboard</a>
        <br><br>

        <!-- Display success message if request was sent -->
        <?php if ($requestSent): ?>
            <div class="alert alert-success">
                Your request has been sent successfully.
            </div>
        <?php endif; ?>

        <!-- Support Request Form with Dropdown and Description Field -->
        <form method="POST" class="mb-4">
            <div class="form-group">
                <label for="message">Select Support Request Type:</label>
                <select name="message" class="form-control" required>
                    <option value="Printer issue">Printer issue</option>
                    <option value="Toner Replacement">Toner Replacement</option>
                    <option value="Drum Replacement">Drum Replacement</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Describe the Issue (optional):</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Add details here if necessary..."></textarea>
            </div>
            <button type="submit" name="request_support" class="btn btn-primary">Submit Support Request</button>
        </form>
    </div>
</body>
</html>
