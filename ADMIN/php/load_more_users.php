<?php
require 'config.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get parameters from request
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = 10; // Number of users to fetch
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

// Query to count total approved users matching the filter
$countQuery = "SELECT COUNT(*) as total FROM users u
               WHERE (u.full_name LIKE :search OR u.username LIKE :search)
               AND u.status = 'approved'";

$countParams = [':search' => "%$search%"];

if (!empty($roleFilter)) {
    $countQuery .= " AND role = :role";
    $countParams[':role'] = $roleFilter;
}

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($countParams);
$totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Query to get the next batch of approved users
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
$params[':limit'] = $limit;
$params[':offset'] = $offset;

$stmt = $pdo->prepare($query);
// PDO needs to explicitly identify integer parameters for LIMIT and OFFSET
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => &$val) {
    if ($key != ':limit' && $key != ':offset') {
        $stmt->bindParam($key, $val);
    }
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build HTML for the new rows
$html = '';
foreach ($users as $user) {
    $formattedDate = date("F j, Y g:i A", strtotime($user['registration_date']));
    
    $html .= '<tr>
        <td>' . htmlspecialchars($user['full_name']) . '</td>
        <td>' . htmlspecialchars($user['username']) . '</td>
        <td>' . ucfirst(htmlspecialchars($user['role'])) . '</td>
        <td class="status-cell">';
    
    if ($user['account_status'] === 'active') {
        $html .= '<span class="status-indicator active">Active</span>';
    } else {
        $html .= '<span class="status-indicator inactive">Account Terminated</span>';
    }
    
    $html .= '</td>
        <td style="display: flex; gap: 5px;">
            <button class="view-user-btn" 
                data-user=\'' . json_encode([
                    'user_id' => $user['user_id'],
                    'full_name' => $user['full_name'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'account_status' => $user['account_status'],
                    'barangay' => $user['barangay'],
                    'address' => $user['address'] ?? 'N/A',
                    'age' => $user['age'],
                    'contact_number' => $user['contact_number'],
                    'registration_date' => $formattedDate
                ]) . '\'>View</button>';
    
    if ($user['account_status'] === 'active') {
        $html .= '<form method="POST" action="terminated_user.php">
                <input type="hidden" name="user_id" value="' . $user['user_id'] . '">
                <button type="submit" class="deactivate-btn" onclick="return confirm(\'Are you sure you want to terminate the account of this user?\')">Terminated</button>
            </form>
            
            <button class="reset-password-btn ' . (($user['reset_status'] == 'pending') ? 'pending-reset' : '') . '"
                onclick="resetPassword(' . $user['user_id'] . ')">
                ' . (($user['reset_status'] == 'pending') ? 'Pending Reset' : 'Change Password') . '
            </button>';
    }
    
    $html .= '</td>
    </tr>';
}

// Return JSON response
echo json_encode([
    'success' => true,
    'html' => $html,
    'count' => count($users),
    'total' => $totalUsers,
    'offset' => $offset
]);
?>