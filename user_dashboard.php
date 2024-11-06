<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "enriquemikhail_enriquemikhail", "$2RzW#tp.v%*uKB_", "enriquemikhail_printer_management_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the user's printer and toner details
$username = $_SESSION['username'];
$sql = "
    SELECT printers.model AS printer_name, toners.toner_type, drums.drum_type
    FROM users
    LEFT JOIN printers ON users.printer_id = printers.id
    LEFT JOIN toners ON printers.toner_id = toners.id
    LEFT JOIN drums ON printers.drum_id = drums.id
    WHERE users.username = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $printer_name = $user['printer_name'];
    $toner_type = $user['toner_type'];
    $drum_type = $user['drum_type'];
} else {
    $printer_name = "Not assigned";
    $toner_type = "Not assigned";
    $drum_type = "Not assigned";
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>Your printer details are as follows:</p>
        <a href="logout.php" class="btn btn-danger">Logout</a>

        <!-- Display printer and toner information -->
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Printer Information</h5>
                <p><strong>Printer Name:</strong> <?php echo htmlspecialchars($printer_name); ?></p>
                <p><strong>Toner Type:</strong> <?php echo htmlspecialchars($toner_type); ?></p>
                <p><strong>Drum Type:</strong> <?php echo htmlspecialchars($drum_type); ?></p>
            </div>
        </div>

        <!-- Request Support Button -->
        <form action="request_support.php" method="POST" class="mt-4">
            <input type="hidden" name="printer_name" value="<?php echo htmlspecialchars($printer_name); ?>">
            <input type="hidden" name="toner_type" value="<?php echo htmlspecialchars($toner_type); ?>">
            <button type="submit" class="btn btn-primary">Request Support</button>
        </form>
    </div>
</body>
</html>
