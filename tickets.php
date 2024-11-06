<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.html");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "enriquemikhail_enriquemikhail", "$2RzW#tp.v%*uKB_", "enriquemikhail_printer_management_db");

// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize message variable for feedback
$message = "";

// Handle status update
if (isset($_POST['update_status'])) {
    $ticket_id = $_POST['ticket_id'];
    $new_status = $_POST['status'];
    
    // Use prepared statement to update ticket status securely
    $updateSql = "UPDATE support_requests SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $new_status, $ticket_id);
    
    if ($stmt->execute()) {
        $message = "Ticket status updated successfully!";
    } else {
        $message = "Error updating status: " . $stmt->error;
    }
    $stmt->close();
}

// Handle ticket deletion
if (isset($_POST['delete_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    
    // Use prepared statement to delete the ticket securely
    $deleteSql = "DELETE FROM support_requests WHERE id = ?";
    $stmt = $conn->prepare($deleteSql);
    $stmt->bind_param("i", $ticket_id);
    
    if ($stmt->execute()) {
        $message = "Ticket deleted successfully!";
    } else {
        $message = "Error deleting ticket: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all support requests
$sql = "SELECT support_requests.id, users.username, support_requests.request_type, support_requests.description, 
               support_requests.status, support_requests.created_at 
        FROM support_requests 
        JOIN users ON support_requests.user_id = users.id 
        ORDER BY support_requests.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Support Requests</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Admin Dashboard - Support Requests</h2>
        <a href="admin_dashboard.php" class="btn btn-secondary">Return to Dashboard</a>
        <br><br>

        <!-- Display message if available -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Support Requests Table -->
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Request Type</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Date Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['request_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo date('m/d/Y h:i A', strtotime($row['created_at'] . ' -6 hours')); ?></td>
                            <td>
                                <!-- Status Update Form -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <select name="status" class="form-control mb-2">
                                        <option value="Not Resolved" <?php if ($row['status'] === 'Not Resolved') echo 'selected'; ?>>Not Resolved</option>
                                        <option value="Resolved" <?php if ($row['status'] === 'Resolved') echo 'selected'; ?>>Resolved</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                                </form>

                                <!-- Delete Form -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <button type="submit" name="delete_ticket" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this ticket?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No support requests found.</td>
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
