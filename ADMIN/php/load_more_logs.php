<?php
require 'config.php';

$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
$limit = 10;

$query = "SELECT logs.*, users.full_name AS performed_by_name,
          DATE_FORMAT(logs.timestamp, '%Y-%m-%d %h:%i %p') AS formatted_timestamp
          FROM logs 
          JOIN users ON logs.performed_by = users.user_id 
          ORDER BY logs.timestamp DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($logs as $log) {
  echo "<tr>
          <td>" . htmlspecialchars($log['action']) . "</td>
          <td><a href='#' class='user-link' data-userid='" . $log['performed_by'] . "' data-action='" . htmlspecialchars($log['action']) . "'>" . htmlspecialchars($log['performed_by_name']) . "</a></td>
          <td>" . htmlspecialchars($log['formatted_timestamp']) . "</td>
        </tr>";
}?>
