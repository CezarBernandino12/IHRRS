<?php
session_start();
require 'config.php';

$userId = $_SESSION['user_id'];

// Fetch current data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'];
    $contact = $_POST['contact_number'];
    $age = $_POST['age'];
    $barangay = $_POST['barangay'];

    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, contact_number = ?, age = ?, barangay = ? WHERE user_id = ?");
    $stmt->execute([$fullName, $contact, $age, $barangay, $userId]);

    header("Location: profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/Profile.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Personal Information</title>
</head>
<body>

    <!-- Sidebar Section -->
    <section id="sidebar">
        <a href="#" class="brand">
            <img src="../../img/logo.png" alt="RHULogo" class="logo">
            <span class="text">Hello Admin</span>
        </a>
        <ul class="side-menu top">
            <li>
                <a href="admin_dashboard2.php">
                    <i class="bx bxs-dashboard"></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="admin_approval.php">
                    <i class="bx bxs-user"></i>
                    <span class="text">Approval & Logs</span>
                </a>
            </li>
            <li>
                <a href="admin_user.php">
                    <i class="bx bxs-notepad"></i>
                    <span class="text">User management</span>
                </a>
            </li>
            <li>
                <a href="admin_reports.php">
                    <i class="bx bxs-report"></i>
                    <span class="text">Reports</span>
                </a>
            </li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="../../role.html" class="logout" onclick="return confirmLogout()">
                    <i class="bx bxs-log-out-circle"></i>
                    <span class="text">Logout</span>
                </a>
            </li>
        </ul>
    </section> 

    <!-- Content Section -->
    <section id="content">
        <nav>
            <form action="#"></form>

            <a href="profile.php" class="profile">
                <img src="../../img/profile.jpg" alt="Profile Picture">
            </a>
        </nav>
        <main>
            <div class="container">
                <h2>  <i class="bx bxs-edit-alt"></i> Edit Profile</h2>
                <form method="POST" class="card">
                    <div class="row">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($user['contact_number']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($user['age']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Barangay</label>
                            <select name="barangay" class="form-select" required>
                                <?php
                                $barangays = ['Barangay 1', 'Barangay 6', 'Barangay 7', 'Barangay 8', 'Gubat', 'San Isidro', 'Cobangbang', 'Bagasbas', 'Manbalite'];
                                foreach ($barangays as $brgy) {
                                    $selected = $user['barangay'] == $brgy ? 'selected' : '';
                                    echo "<option value='$brgy' $selected>$brgy</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">Save Changes</button>
                    <a href="profile.php" class="btn btn-secondary cancel-btn">Cancel</a>
                </form>
            </div>
        </main>
    </section>
</body>
</html>
