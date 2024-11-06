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

// Variable to store edit user details
$editUser = [
    'id' => '',
    'username' => '',
    'password' => '', // Optional: usually, you don't want to autofill passwords
    'role' => '',
    'printer_id' => ''
];

// If editing, fetch user data
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT id, username, role, printer_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editUser = $result->fetch_assoc();
    $stmt->close();
}

// Handle new user creation or update
if (isset($_POST['create']) || isset($_POST['update'])) {
    $username = $_POST['username'];
    $password = $_POST['password']; // No hashing
    $role = $_POST['role'];
    $printer_id = $_POST['printer_id']; // Assign printer by ID
    
    if (isset($_POST['create'])) {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, printer_id) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            echo "Error preparing statement: " . $conn->error;
        }
        $stmt->bind_param("sssi", $username, $password, $role, $printer_id);
    } elseif (isset($_POST['update'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ?, printer_id = ? WHERE id = ?");
        if (!$stmt) {
            echo "Error preparing statement: " . $conn->error;
        }
        $stmt->bind_param("sssii", $username, $password, $role, $printer_id, $id);
    }
    
    $stmt->execute();
    $stmt->close();
    header("Location: users.php");
    exit();
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if (!$stmt) {
        echo "Error preparing statement: " . $conn->error;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: users.php");
    exit();
}

// Fetch all users with associated printer, toner, and drum details
$sql = "
    SELECT users.id, users.username, users.role, printers.model AS printer_name, toners.toner_type, drums.drum_type
    FROM users
    LEFT JOIN printers ON users.printer_id = printers.id
    LEFT JOIN toners ON printers.toner_id = toners.id
    LEFT JOIN drums ON printers.drum_id = drums.id
";
$result = $conn->query($sql);

// Fetch all available printers for assigning to users
$printers = $conn->query("SELECT id, model FROM printers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Users List</h2>
        <a href="admin_dashboard.php" class="btn btn-secondary">Return to Dashboard</a>
        <br><br>

        <!-- Form to Add or Update User -->
        <form method="POST" class="mb-4">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editUser['id']); ?>">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Username" value="<?php echo htmlspecialchars($editUser['username']); ?>" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" <?php echo isset($_GET['edit']) ? '' : 'required'; ?>>
            </div>
            <div class="form-group">
                <input type="text" name="role" class="form-control" placeholder="Role" value="<?php echo htmlspecialchars($editUser['role']); ?>" required>
            </div>
            <div class="form-group">
                <label for="printer_id">Assign Printer:</label>
                <select name="printer_id" class="form-control" required>
                    <option value="">Select Printer</option>
                    <?php while ($printer = $printers->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($printer['id']); ?>" <?php echo $editUser['printer_id'] == $printer['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($printer['model']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="<?php echo isset($_GET['edit']) ? 'update' : 'create'; ?>" class="btn btn-primary">
                <?php echo isset($_GET['edit']) ? 'Update User' : 'Add User'; ?>
            </button>
        </form>

        <!-- Users Table -->
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Printer Name</th>
                    <th>Toner Type</th>
                    <th>Drum Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                    <td><?php echo htmlspecialchars($row['printer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['toner_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['drum_type']); ?></td>
                    <td>
                        <a href="users.php?edit=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="users.php?delete=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
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
