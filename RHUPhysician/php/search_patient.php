<?php
require '../../php/db_connect.php';

if (isset($_POST['query'])) {
    $query = trim($_POST['query']);

$sql = "SELECT first_name, last_name, middle_name, sex, date_of_birth, patient_id
        FROM patients
        WHERE 
            CONCAT(first_name, ' ', last_name) LIKE :query
            OR CONCAT(last_name, ' ', first_name) LIKE :query
            OR first_name LIKE :query
            OR last_name LIKE :query

            -- fuzzy sound-alike matching
            OR SOUNDEX(first_name) = SOUNDEX(:sound)
            OR SOUNDEX(last_name)  = SOUNDEX(:sound)";
            
$stmt = $pdo->prepare($sql);

$searchTerm = "%$query%";

$stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
$stmt->bindParam(':sound', $query, PDO::PARAM_STR);

$stmt->execute();

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($results) { 
    foreach ($results as $row) {

        // Format date of birth
        $dob = date("F j, Y", strtotime($row['date_of_birth']));

        echo '<div class="result-info-section">
                <div class="search-result" onclick="selectPatient(' . $row['patient_id'] . ')">
                    <div style="font-size: 17px;"> 
                        <span class="icon">
                            <img src="../img/person_icon.png" alt="person icon" style="width:35px; height:30px; vertical-align:middle; margin-bottom: 8px; margin-right: 10px;">
                        </span>
                        <strong>' . htmlspecialchars($row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name']) . '</strong>
                    </div>
                    <div>' . htmlspecialchars($row['sex']) . '</div>
                    <div>' . $dob . '</div>
                </div>
              </div><br>';
    }

        
    } else {
        echo '<div class="no-results-container">
        <img src="../img/no.jpg" alt="No Results" class="no-results-img">
        <h2 class="no-results-title">SORRY!</h2>
        <p class="no-results-text">We Haven’t Found Any Document</p>
      </div>';

    }
    
}
?>
