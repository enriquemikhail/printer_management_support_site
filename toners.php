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

// Initialize variables for CRUD actions
$toner_type = $stock = $low_stock_threshold = "";
$edit_state = false;
$id = 0;

// Handle Create and Update actions
if (isset($_POST['save'])) {
    $toner_type = $_POST['toner_type'];
    $stock = $_POST['stock'];
    $low_stock_threshold = $_POST['low_stock_threshold'];
    $id = $_POST['id'];

    if ($id == 0) {
        // Insert new toner
        $stmt = $conn->prepare("INSERT INTO toners (toner_type, stock, low_stock_threshold) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $toner_type, $stock, $low_stock_threshold);
        $stmt->execute();
        $stmt->close();
    } else {
        // Update existing toner
        $stmt = $conn->prepare("UPDATE toners SET toner_type = ?, stock = ?, low_stock_threshold = ? WHERE id = ?");
        $stmt->bind_param("siii", $toner_type, $stock, $low_stock_threshold, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: toners.php");
    exit();
}

// Handle Delete action
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM toners WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: toners.php");
    exit();
}

// Handle Edit action - Populate form with existing data
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_state = true;
    $stmt = $conn->prepare("SELECT * FROM toners WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $toner_type = $row['toner_type'];
        $stock = $row['stock'];
        $low_stock_threshold = $row['low_stock_threshold'];
    }
    $stmt->close();
}

// Fetch all toner data
$sql = "SELECT * FROM toners";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toners</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Toner List</h2>
        </div>
        <a href="admin_dashboard.php" class="btn btn-secondary mb-3">Return to Dashboard</a>

        <!-- Form to Create or Update Toner -->
        <form method="POST" class="mb-4">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
            <div class="form-group">
                <label for="toner_type">Toner Type:</label>
                <input type="text" name="toner_type" class="form-control" value="<?php echo htmlspecialchars($toner_type); ?>" required>
            </div>
            <div class="form-group">
                <label for="stock">Stock:</label>
                <input type="number" name="stock" class="form-control" value="<?php echo htmlspecialchars($stock); ?>" required>
            </div>
            <div class="form-group">
                <label for="low_stock_threshold">Low Stock Threshold:</label>
                <input type="number" name="low_stock_threshold" class="form-control" value="<?php echo htmlspecialchars($low_stock_threshold); ?>" required>
            </div>
            <button type="submit" name="save" class="btn btn-primary">
                <?php echo $edit_state ? 'Update Toner' : 'Add Toner'; ?>
            </button>
        </form>

        <!-- Toner List Table -->
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Toner Type</th>
                    <th>Stock</th>
                    <th>Low Stock Threshold</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['toner_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['stock']); ?></td>
                    <td><?php echo htmlspecialchars($row['low_stock_threshold']); ?></td>
                    <td>
                        <a href="toners.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="toners.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
