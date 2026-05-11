<?php
require '../../php/db_connect.php';

require_once __DIR__ . '/session_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

if (isset($_POST['query'])) {
    $query = trim($_POST['query']);

    $sql = "SELECT first_name, last_name, middle_name, sex, date_of_birth, patient_id
    FROM patients
    WHERE 
        CONCAT(first_name, ' ', last_name) LIKE :query1
        OR CONCAT(last_name, ' ', first_name) LIKE :query2
        OR first_name LIKE :query3
        OR last_name LIKE :query4

        -- fuzzy sound-alike matching
        OR SOUNDEX(first_name) = SOUNDEX(:sound1)
        OR SOUNDEX(last_name)  = SOUNDEX(:sound2)";
        
    $stmt = $pdo->prepare($sql);

    $searchTerm = "%$query%";

    $stmt->bindParam(':query1', $searchTerm, PDO::PARAM_STR);
    $stmt->bindParam(':query2', $searchTerm, PDO::PARAM_STR);
    $stmt->bindParam(':query3', $searchTerm, PDO::PARAM_STR);
    $stmt->bindParam(':query4', $searchTerm, PDO::PARAM_STR);
    $stmt->bindParam(':sound1', $query, PDO::PARAM_STR);
    $stmt->bindParam(':sound2', $query, PDO::PARAM_STR);

    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) { 
        foreach ($results as $row) {
            $patientId = (int) $row['patient_id'];

            $fullName = trim(
                $row['last_name'] . ', ' .
                $row['first_name'] . ' ' .
                $row['middle_name']
            );

            $sex = trim($row['sex']);
            $sexLower = strtolower($sex);

            $sexClass = '';
            $sexIcon = 'bx-user';

            if ($sexLower === 'male' || $sexLower === 'm' || strpos($sexLower, 'male') === 0) {
                $sexClass = 'male';
                $sexIcon = 'bx-male-sign';
            } elseif ($sexLower === 'female' || $sexLower === 'f' || strpos($sexLower, 'female') === 0) {
                $sexClass = 'female';
                $sexIcon = 'bx-female-sign';
            }

            $dob = date("F j, Y", strtotime($row['date_of_birth']));

            echo '
                <div class="result-info-section">
                    <div class="search-result" onclick="selectPatient(' . $patientId . ')">
                        <span class="patient-name-cell">
                            <i class="bx bx-user patient-row-icon" aria-hidden="true"></i>
                            <span class="patient-name-text">' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '</span>
                        </span>

                        <span class="patient-sex-cell ' . $sexClass . '">
                            <i class="bx ' . $sexIcon . ' sex-icon ' . $sexClass . '" aria-hidden="true"></i>
                            <span class="patient-sex-text">' . htmlspecialchars($sex, ENT_QUOTES, 'UTF-8') . '</span>
                        </span>

                        <span class="patient-birthday-cell">' . htmlspecialchars($dob, ENT_QUOTES, 'UTF-8') . '</span>
                    </div>
                </div>
            ';
        }
    } else {
        echo '<div class="no-results-container">
            <img src="../img/no.jpg" alt="No Results" class="no-results-img">
            <h2 class="no-results-title">SORRY!</h2>
            <p class="no-results-text">We Haven\'t Found Any Document</p>
        </div>';
    }
}
?>