<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$conn = new mysqli("localhost", "enriquemikhail_enriquemikhail", "$2RzW#tp.v%*uKB_", "enriquemikhail_printer_management_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for CRUD actions
$id = $model = $toner_id = $drum_id = $location_id = $status = $user_id = "";
$edit_state = false;
$id = 0;

// Handle Create and Update actions
if (isset($_POST['save'])) {
    $model = $_POST['model'];
    $location_id = $_POST['location_id'];
    $toner_id = $_POST['toner_id'];
    $drum_id = $_POST['drum_id'];
    $status = $_POST['status'];
    $user_id = $_POST['user_id'];

    if ($_POST['id'] == 0) {
        // Insert new printer
        $sql = "INSERT INTO printers (model, location_id, toner_id, drum_id, status, user_id) VALUES ('$model', '$location_id', '$toner_id', '$drum_id', '$status', '$user_id')";
        if (!$conn->query($sql)) {
            die("Error inserting printer: " . $conn->error);
        }
    } else {
        // Update existing printer
        $id = $_POST['id'];
        $sql = "UPDATE printers SET model='$model', location_id='$location_id', toner_id='$toner_id', drum_id='$drum_id', status='$status', user_id='$user_id' WHERE id=$id";
        if (!$conn->query($sql)) {
            die("Error updating printer: " . $conn->error);
        }
    }
    header("Location: printers.php");
}

// Handle Delete action
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if (!$conn->query("DELETE FROM printers WHERE id=$id")) {
        die("Error deleting printer: " . $conn->error);
    }
    header("Location: printers.php");
}

// Handle Edit action - Populate form with existing data
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_state = true;
    $result = $conn->query("SELECT * FROM printers WHERE id=$id");
    if ($result && $row = $result->fetch_assoc()) {
        $id = $row['id'];
        $model = $row['model'];
        $location_id = $row['location_id'];
        $toner_id = $row['toner_id'];
        $drum_id = $row['drum_id'];
        $status = $row['status'];
        $user_id = $row['user_id'];
    } else {
        die("Error fetching printer details: " . $conn->error);
    }
}

// Fetch printer data with user, toner, and drum details
$sql = "
    SELECT printers.id, printers.model, printers.location_id, printers.status, printers.user_id,
           toners.toner_type, drums.drum_type, users.username AS user_name
    FROM printers
    LEFT JOIN toners ON printers.toner_id = toners.id
    LEFT JOIN drums ON printers.drum_id = drums.id
    LEFT JOIN users ON printers.user_id = users.id
";
$result = $conn->query($sql);

if (!$result) {
    die("Error executing query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printers</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Printer List</h2>
        <a href="admin_dashboard.php" class="btn btn-secondary">Return to Dashboard</a>
        <br><br>
   
        <!-- Form to Create or Update Printer -->
        <form method="POST" class="mb-4">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <div class="form-group">
                <label for="model">Model:</label>
                <input type="text" name="model" class="form-control" value="<?php echo $model; ?>" required>
            </div>
            <div class="form-group">
                <label for="location_id">Location ID:</label>
                <input type="number" name="location_id" class="form-control" value="<?php echo $location_id; ?>" required>
            </div>
            <div class="form-group">
                <label for="toner_id">Toner Type:</label>
                <input type="text" name="toner_id" class="form-control" value="<?php echo $toner_id; ?>" required>
            </div>
            <div class="form-group">
                <label for="drum_id">Drum Type:</label>
                <input type="text" name="drum_id" class="form-control" value="<?php echo $drum_id; ?>" required>
            </div>
            <div class="form-group">
                <label for="user_id">User ID:</label>
                <input type="text" name="user_id" class="form-control" value="<?php echo $user_id; ?>" required>
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" class="form-control" required>
                    <option value="Active" <?php echo $status == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo $status == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" name="save" class="btn btn-primary">
                <?php echo $edit_state ? 'Update Printer' : 'Add Printer'; ?>
            </button>
        </form>
      
        <!-- Printer List Table -->
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Model</th>
                    <th>Location</th>
                    <th>Toner Type</th>
                    <th>Drum Type</th>
                    <th>Status</th>
                    <th>User</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['model']; ?></td>
                            <td><?php echo $row['location_id']; ?></td>
                            <td><?php echo $row['toner_type']; ?></td>
                            <td><?php echo $row['drum_type']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo $row['user_name']; ?></td>
                            <td>
                                <a href="printers.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <br><br>
                                <a href="printers.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8">No printers found.</td></tr>
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
