<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "enriquemikhail_enriquemikhail", "$2RzW#tp.v%*uKB_", "enriquemikhail_printer_management_db");

// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch recent records for the message board

// 2. Fetch the 5 most recent support requests
$supportRequestsSql = "
    SELECT users.username, support_requests.request_type, support_requests.created_at 
    FROM support_requests
    JOIN users ON support_requests.user_id = users.id 
    ORDER BY support_requests.created_at DESC LIMIT 5";
$supportRequestsResult = $conn->query($supportRequestsSql);

// 3. Fetch low stock items
$lowStockSql = "
    SELECT toner_type, stock, low_stock_threshold 
    FROM toners 
    WHERE stock <= low_stock_threshold 
    ORDER BY stock ASC";
$lowStockResult = $conn->query($lowStockSql);

// Fetch users data with related printer, toner, and drum information
$sqlUsers = "
    SELECT users.id, users.username, users.role, 
           printers.model AS printer_name, toners.toner_type, drums.drum_type
    FROM users 
    LEFT JOIN printers ON users.printer_id = printers.id
    LEFT JOIN toners ON printers.toner_id = toners.id
    LEFT JOIN drums ON printers.drum_id = drums.id
";
$usersResult = $conn->query($sqlUsers);

// Fetch printers data with related location
$sqlPrinters = "
    SELECT printers.id, printers.model, locations.location_name, toners.toner_type, drums.drum_type, 
           printers.status, users.id AS user_id
    FROM printers
    LEFT JOIN locations ON printers.location_id = locations.id
    LEFT JOIN toners ON printers.toner_id = toners.id
    LEFT JOIN drums ON printers.drum_id = drums.id
    LEFT JOIN users ON printers.id = users.printer_id
";
$printersResult = $conn->query($sqlPrinters);

// Fetch support requests data with user information
$sqlSupportRequests = "
    SELECT support_requests.id, users.username, support_requests.request_type, support_requests.description, 
           support_requests.status, support_requests.created_at 
    FROM support_requests 
    JOIN users ON support_requests.user_id = users.id 
    ORDER BY support_requests.created_at DESC
";
$supportRequestsResult = $conn->query($sqlSupportRequests);

// Count unresolved tickets
$unresolvedTicketsSql = "SELECT COUNT(*) AS unresolved_count FROM support_requests WHERE status = 'Not Resolved'";
$unresolvedTicketsResult = $conn->query($unresolvedTicketsSql);
$unresolvedTicketsCount = ($unresolvedTicketsResult->num_rows > 0) ? $unresolvedTicketsResult->fetch_assoc()['unresolved_count'] : 0;

// Count low stock items
$lowStockSql = "SELECT COUNT(*) AS low_stock_count FROM toners WHERE stock <= low_stock_threshold";
$lowStockResult = $conn->query($lowStockSql);
$lowStockCount = ($lowStockResult->num_rows > 0) ? $lowStockResult->fetch_assoc()['low_stock_count'] : 0;

$lowStockDrumSql = "SELECT COUNT(*) AS low_stock_count FROM drums WHERE stock <= low_stock_threshold";
$lowStockDrumResult = $conn->query($lowStockDrumSql);
$lowStockDrumCount = ($lowStockDrumResult->num_rows > 0) ? $lowStockDrumResult->fetch_assoc()['low_stock_count'] : 0;

// Combine toner and drum low stock counts
$totalLowStockCount = $lowStockCount + $lowStockDrumCount;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .badge {
            background-color: #dc3545;
            color: white;
            font-size: 0.9em;
            border-radius: 12px;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mt-4 text-center">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p class="text-center">Here are your system stats:</p>
        <a href="logout.php" class="btn btn-danger">Logout</a>
        
        <!-- Navigation with Notification Bubbles -->
        <ul class="nav nav-tabs justify-content-center mb-4">
            <li class="nav-item">
                <a class="nav-link active" href="admin_dashboard.php">Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">Users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="tickets.php">
                    Tickets
                    <?php if ($unresolvedTicketsCount > 0): ?>
                        <span class="badge"><?php echo $unresolvedTicketsCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="printers.php">Printers</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="toners.php">Toners</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="drums.php">Drums</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="locations.php">Locations</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="notification.php">
                    Stock Notifications
                    <?php if ($totalLowStockCount > 0): ?>
                        <span class="badge"><?php echo $totalLowStockCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <!-- Support Requests Table -->
        <div class="mt-4">
            <h3 style="text-decoration: underline;">Tickets</h3>
            <a href="tickets.php" class="btn btn-primary mb-3">Update Status</a>
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Request ID</th>
                        <th>Username</th>
                        <th>Request Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($supportRequestsResult->num_rows > 0): ?>
                        <?php while ($row = $supportRequestsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['request_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo date('m/d/Y h:i A', strtotime($row['created_at'] . ' -6 hours')); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No support requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Users Table -->
        <div class="mt-4">
            <h3 style="text-decoration: underline;">Users List</h3>
            <a href="users.php" class="btn btn-primary mb-3">View or Edit Users</a>
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Printer Name</th>
                        <th>Toner Type</th>
                        <th>Drum Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $usersResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                        <td><?php echo htmlspecialchars($row['printer_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['toner_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['drum_type']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Printers Table -->
        <div class="mt-4">
            <h3 style="text-decoration: underline;">Printers List</h3>
            <a href="printers.php" class="btn btn-primary mb-3">View or Edit Printers</a>
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Model</th>
                        <th>Location</th>
                        <th>Toner Type</th>
                        <th>Drum Type</th>
                        <th>Status</th>
                        <th>User ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $printersResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['model']); ?></td>
                        <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['toner_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['drum_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
