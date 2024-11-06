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
$id = 0;
$drum_type = $stock = $low_stock_threshold = "";
$edit_state = false;

// Handle Create and Update actions
if (isset($_POST['save'])) {
    $drum_type = $_POST['drum_type'];
    $stock = $_POST['stock'];
    $low_stock_threshold = $_POST['low_stock_threshold'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0; // Ensure ID is an integer

    if ($id == 0) {
        // Insert new drum unit
        $stmt = $conn->prepare("INSERT INTO drums (drum_type, stock, low_stock_threshold) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sii", $drum_type, $stock, $low_stock_threshold);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "<p style='color:red;'>Error preparing statement: " . $conn->error . "</p>";
        }
    } else {
        // Update existing drum unit
        $stmt = $conn->prepare("UPDATE drums SET drum_type = ?, stock = ?, low_stock_threshold = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("siii", $drum_type, $stock, $low_stock_threshold, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "<p style='color:red;'>Error preparing statement: " . $conn->error . "</p>";
        }
    }

    header("Location: drums.php");
    exit();
}

// Handle Delete action
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM drums WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: drums.php");
    exit();
}

// Handle Edit action - Populate form with existing data
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_state = true;
    $stmt = $conn->prepare("SELECT * FROM drums WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $drum_type = $row['drum_type'];
            $stock = $row['stock'];
            $low_stock_threshold = $row['low_stock_threshold'];
        }
        $stmt->close();
    }
}

// Fetch all drum units for display
$sql = "SELECT * FROM drums";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drum Units</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Drum Units List</h2>
        <a href="admin_dashboard.php" class="btn btn-secondary mb-3">Return to Dashboard</a>

        <!-- Form to Create or Update Drum Unit -->
        <form method="POST" class="mb-4">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
            <div class="form-group">
                <label for="drum_type">Drum Type:</label>
                <input type="text" name="drum_type" class="form-control" value="<?php echo htmlspecialchars($drum_type); ?>" required>
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
                <?php echo $edit_state ? 'Update Drum Unit' : 'Add Drum Unit'; ?>
            </button>
        </form>

        <!-- Drum Units Table -->
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Drum Type</th>
                    <th>Stock</th>
                    <th>Low Stock Threshold</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['drum_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['stock']); ?></td>
                    <td><?php echo htmlspecialchars($row['low_stock_threshold']); ?></td>
                    <td>
                        <a href="drums.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="drums.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
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
