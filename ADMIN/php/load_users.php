<?php
require 'config.php';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

// Query to fetch users with pagination
$query = "SELECT u.*, 
                 COALESCE(fpr.status, 'none') AS reset_status 
          FROM users u 
          LEFT JOIN forgot_password_requests fpr 
          ON u.user_id = fpr.user_id AND fpr.status = 'pending'
          WHERE (u.full_name LIKE :search OR u.username LIKE :search)
          AND u.status = 'approved'";

$params = [':search' => "%$search%"];

if (!empty($roleFilter)) {
    $query .= " AND role = :role";
    $params[':role'] = $roleFilter;
}

$query .= " ORDER BY u.registration_date DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val);
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate HTML for the user rows
foreach ($users as $user) {
    $formattedDate = date("F j, Y g:i A", strtotime($user['registration_date']));
    echo "<tr>
            <td>" . htmlspecialchars($user['full_name']) . "</td>
            <td>" . htmlspecialchars($user['username']) . "</td>
            <td>" . ucfirst(htmlspecialchars($user['role'])) . "</td>
            <td class='status-cell'>" . 
                ($user['account_status'] === 'active' 
                    ? "<span class='status-indicator active'>Active</span>" 
                    : "<span class='status-indicator inactive'>Account Terminated</span>") . 
            "</td>
            <td>
                <button class='view-user-btn' data-user='" . json_encode($user) . "'>View</button>
                " . ($user['account_status'] === 'active' ? "
                <form method='POST' action='terminated_user.php'>
                    <input type='hidden' name='user_id' value='" . $user['user_id'] . "'>
                    <button type='submit' class='deactivate-btn'>Terminate</button>
                </form>
                <button class='reset-password-btn'>" . 
                    ($user['reset_status'] === 'pending' ? 'Pending Reset' : 'Reset Password') . 
                "</button>" : "") . "
            </td>
          </tr>";
}
?>