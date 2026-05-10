<?php
require '../../php/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $query = trim($_POST['query']);

    if ($query === '') {
        echo "<div class='result-info-section'>Please enter a patient name.</div>";
        exit;
    }

    $sql = "
        SELECT 
            patient_id,
            first_name,
            last_name,
            middle_name,
            sex,
            date_of_birth
        FROM patients
        WHERE first_name LIKE :first_name
           OR last_name LIKE :last_name
           OR middle_name LIKE :middle_name
        ORDER BY last_name ASC, first_name ASC
        LIMIT 20
    ";

    $stmt = $pdo->prepare($sql);

    $searchTerm = "%{$query}%";

    $stmt->bindValue(':first_name', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':last_name', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':middle_name', $searchTerm, PDO::PARAM_STR);

    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        foreach ($results as $row) {
            $patientId = (int) $row['patient_id'];

            $lastName = $row['last_name'] ?? '';
            $firstName = $row['first_name'] ?? '';
            $middleName = $row['middle_name'] ?? '';

            $fullName = trim($lastName . ', ' . $firstName . ' ' . $middleName);

            $sex = $row['sex'] ?? '';
            $birthDate = $row['date_of_birth'] ?? '';

            echo '
                <div class="result-info-section">
                    <div class="search-result" onclick="selectPatient(' . $patientId . ')">
                        <span class="patient-name-cell">' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '</span>
                        <span class="patient-sex-cell">' . htmlspecialchars($sex, ENT_QUOTES, 'UTF-8') . '</span>
                        <span>' . htmlspecialchars($birthDate, ENT_QUOTES, 'UTF-8') . '</span>
                    </div>
                </div>
            ';
        }
    } else {
        echo "<div class='result-info-section'>No results found.</div>";
    }
}
?>