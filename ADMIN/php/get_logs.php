<?php
session_start();
require_once 'config.php';

// Fetch activity logs with filtering
$query = "SELECT logs.*, users.full_name AS performed_by_name,
          DATE_FORMAT(logs.timestamp, '%M %e, %Y %l:%i %p') AS formatted_timestamp
          FROM logs 
          JOIN users ON logs.performed_by = users.user_id 
          WHERE 1";

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;  // Records per page
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0; // Page offset
$params = []; // Initialize parameters array

// Filter by User
if (!empty($_GET['user'])) {
    $query .= " AND logs.performed_by = :user";
    $params[':user'] = $_GET['user'];
}

// Filter by Action Type
if (!empty($_GET['action'])) {
    $query .= " AND logs.action LIKE :action";
    $params[':action'] = '%' . $_GET['action'] . '%';
}

// Filter by Date Range
if (!empty($_GET['from_date'])) {
    $query .= " AND DATE(logs.timestamp) >= :from_date";
    $params[':from_date'] = $_GET['from_date'];
}

if (!empty($_GET['to_date'])) {
    $query .= " AND DATE(logs.timestamp) <= :to_date";
    $params[':to_date'] = $_GET['to_date'];
}

// Append LIMIT and OFFSET correctly
$query .= " ORDER BY logs.timestamp DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// Bind other parameters safely
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output table rows
foreach ($logs as $log): ?>
    <tr>
        <td><?= htmlspecialchars($log['action']) ?></td>
        <td><a href="#" class="user-link" data-userid="<?= $log['performed_by'] ?>" data-action="<?= htmlspecialchars($log['action']) ?>"><?= htmlspecialchars($log['performed_by_name']) ?></a></td>
        <td><?= htmlspecialchars($log['formatted_timestamp']) ?></td>
    </tr>
<?php endforeach;
?>