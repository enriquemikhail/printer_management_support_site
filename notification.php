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

// Handle stock update for toners
if (isset($_POST['update_toner_stock'])) {
    $tonerType = $_POST['toner_type'];
    $newStock = $_POST['new_stock'];
    $updateTonerSql = "UPDATE toners SET stock = ? WHERE toner_type = ?";
    $stmt = $conn->prepare($updateTonerSql);
    $stmt->bind_param("is", $newStock, $tonerType);
    $stmt->execute();
    $stmt->close();
}

// Handle stock update for drums
if (isset($_POST['update_drum_stock'])) {
    $drumType = $_POST['drum_type'];
    $newStock = $_POST['new_stock'];
    $updateDrumSql = "UPDATE drums SET stock = ? WHERE drum_type = ?";
    $stmt = $conn->prepare($updateDrumSql);
    $stmt->bind_param("is", $newStock, $drumType);
    $stmt->execute();
    $stmt->close();
}

// Fetch all toners, prioritizing low stock
$tonerSql = "
    SELECT toner_type, stock, low_stock_threshold, (stock <= low_stock_threshold) AS low_stock_priority 
    FROM toners
    ORDER BY low_stock_priority DESC, stock ASC";
$tonerResult = $conn->query($tonerSql);

// Fetch all drums, prioritizing low stock
$drumSql = "
    SELECT drum_type, stock, low_stock_threshold, (stock <= low_stock_threshold) AS low_stock_priority 
    FROM drums
    ORDER BY low_stock_priority DESC, stock ASC";
$drumResult = $conn->query($drumSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .low-stock {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Stock Notifications</h2>
        <a href="admin_dashboard.php" class="btn btn-secondary mb-3">Return to Dashboard</a>
        
        <!-- Toner Stock Notifications -->
        <h4>Toner Stock</h4>
        <?php if ($tonerResult->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Toner Type</th>
                        <th>Current Stock</th>
                        <th>Low Stock Threshold</th>
                        <th>Update Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $tonerResult->fetch_assoc()): ?>
                        <tr class="<?php echo $row['stock'] <= $row['low_stock_threshold'] ? 'low-stock' : ''; ?>">
                            <td><?php echo htmlspecialchars($row['toner_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['stock']); ?></td>
                            <td><?php echo htmlspecialchars($row['low_stock_threshold']); ?></td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="toner_type" value="<?php echo htmlspecialchars($row['toner_type']); ?>">
                                    <input type="number" name="new_stock" class="form-control mr-2" placeholder="New Stock" required>
                                    <button type="submit" name="update_toner_stock" class="btn btn-primary">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-success">
                <p>No toner stock found.</p>
            </div>
        <?php endif; ?>
        
        <!-- Drum Stock Notifications -->
        <h4>Drum Stock</h4>
        <?php if ($drumResult->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Drum Type</th>
                        <th>Current Stock</th>
                        <th>Low Stock Threshold</th>
                        <th>Update Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $drumResult->fetch_assoc()): ?>
                        <tr class="<?php echo $row['stock'] <= $row['low_stock_threshold'] ? 'low-stock' : ''; ?>">
                            <td><?php echo htmlspecialchars($row['drum_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['stock']); ?></td>
                            <td><?php echo htmlspecialchars($row['low_stock_threshold']); ?></td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="drum_type" value="<?php echo htmlspecialchars($row['drum_type']); ?>">
                                    <input type="number" name="new_stock" class="form-control mr-2" placeholder="New Stock" required>
                                    <button type="submit" name="update_drum_stock" class="btn btn-primary">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-success">
                <p>No drum stock found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
