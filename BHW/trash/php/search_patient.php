<?php
require '../../php/db_connect.php';

if (isset($_POST['query'])) {
    $query = trim($_POST['query']);

    // Use a prepared statement correctly
    $sql = "SELECT first_name, last_name, middle_name, sex, date_of_birth, patient_id FROM patients WHERE first_name LIKE :query OR last_name LIKE :query";
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        foreach ($results as $row) {
            echo '<div class="result-info-section">
                    <div class="search-result" onclick="selectPatient(' . $row['patient_id'] . ')">
                        <div style="font-size: 17px;"><strong>' . htmlspecialchars($row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name']) . '</strong></div>
                        <div>' . htmlspecialchars($row['sex']) . '</div>
                        <div>' . htmlspecialchars($row['date_of_birth']) . '</div>
                    </div>
                  </div><br>';
        }
        
    } else {
        echo "<div class='result-info-section'>No results found.</div>";
    }
    
}
?>
