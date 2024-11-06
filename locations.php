<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "enriquemikhail_enriquemikhail", "$2RzW#tp.v%*uKB_", "enriquemikhail_printer_management_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch locations and their associated printers
$sql = "
    SELECT locations.id AS location_id, locations.location_name, printers.model AS printer_name
    FROM locations
    LEFT JOIN printers ON locations.id = printers.location_id
    ORDER BY locations.location_name, printers.model
";
$result = $conn->query($sql);

// Organize results into an associative array
$locations = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $location_id = $row['location_id'];
        $location_name = $row['location_name'];
        $printer_name = $row['printer_name'];

        if (!isset($locations[$location_id])) {
            $locations[$location_id] = [
                'location_name' => $location_name,
                'printers' => []
            ];
        }
        
        // Add printer name to location's printers list, if it exists
        if ($printer_name) {
            $locations[$location_id]['printers'][] = $printer_name;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locations</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Locations and Printers List</h2>
        <a href="admin_dashboard.php" class="btn btn-secondary mb-3">Return to Dashboard</a>
        
        <!-- Locations Table -->
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Location ID</th>
                    <th>Location Name</th>
                    <th>Printers</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($locations) > 0): ?>
                    <?php foreach ($locations as $location_id => $location): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($location_id); ?></td>
                            <td><?php echo htmlspecialchars($location['location_name']); ?></td>
                            <td>
                                <?php if (count($location['printers']) > 0): ?>
                                    <ul>
                                        <?php foreach ($location['printers'] as $printer_name): ?>
                                            <li><?php echo htmlspecialchars($printer_name); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    No printers assigned
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center">No locations found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
