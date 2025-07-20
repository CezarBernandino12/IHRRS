<?php 
require '../../php/db_connect.php';

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$log_file = "../../logs/debug.log";

// Log raw input
$input = json_decode(file_get_contents('php://input'), true);
file_put_contents($log_file, "[RAW INPUT] " . print_r($input, true) . "\n", FILE_APPEND);

if (!$input || !isset($input['visit_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input: visit_id is missing.']);
    exit;
}

$visit_id = (int)$input['visit_id'];
$dispensed_by = isset($input['dispensed_by']) ? (int)$input['dispensed_by'] : null;

try {
    $pdo->beginTransaction();

    // 1. Update bhs_visits
    $stmt = $pdo->prepare("
        UPDATE bhs_visits SET 
            visit_date = :visit_date,
            patient_alert = :patient_alert,
            chief_complaints = :chief_complaints,
            blood_pressure = :blood_pressure,
            temperature = :temperature,
            weight = :weight,
            height = :height,
            chest_rate = :chest_rate,
            respiratory_rate = :respiratory_rate,
            remarks = :remarks
        WHERE visit_id = :visit_id
    ");
    $stmt->execute([
        'visit_date' => $input['visit_date'] ?? null,
        'patient_alert' => $input['patient_alert'] ?? null,
        'chief_complaints' => $input['chief_complaints'] ?? null,
        'blood_pressure' => $input['blood_pressure'] ?? 'N/A',
        'temperature' => $input['temperature'] ?? 'N/A',
        'weight' => $input['weight'] ?? 'N/A',
        'height' => $input['height'] ?? 'N/A',
        'chest_rate' => $input['chest_rate'] ?? 'N/A',
        'respiratory_rate' => $input['respiratory_rate'] ?? 'N/A',
        'remarks' => $input['remarks'] ?? null,
        'visit_id' => $visit_id
    ]);

    // 2. Get consultation_id
    $stmt = $pdo->prepare("SELECT consultation_id FROM rhu_consultations WHERE visit_id = :visit_id");
    $stmt->execute(['visit_id' => $visit_id]);
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$consultation) {
        throw new Exception("Consultation not found for visit_id $visit_id.");
    }

    $consultation_id = $consultation['consultation_id'];

    // 3. Update rhu_consultations
    $stmt = $pdo->prepare("
        UPDATE rhu_consultations SET
            diagnosis = :diagnosis,
            instruction_prescription = :instruction
        WHERE consultation_id = :consultation_id
    ");
    $stmt->execute([
        'diagnosis' => $input['diagnosis'] ?? null,
        'instruction' => $input['instruction'] ?? null,
        'consultation_id' => $consultation_id
    ]);

    // 4. Update rhu_medicine_dispensed
    $medicine_names = $input['medicine_name'] ?? [];
    $quantities = $input['quantity_dispensed'] ?? [];

    // Delete old
    $stmt = $pdo->prepare("DELETE FROM rhu_medicine_dispensed WHERE consultation_id = :consultation_id");
    $stmt->execute(['consultation_id' => $consultation_id]);

    // Add new if any
    if ($dispensed_by === null) {
        throw new Exception("dispensed_by (user_id) is missing or invalid.");
    }

    $stmt = $pdo->prepare("
        INSERT INTO rhu_medicine_dispensed (consultation_id, medicine_name, quantity_dispensed, dispensed_by)
        VALUES (:consultation_id, :medicine_name, :quantity_dispensed, :dispensed_by)
    ");

    foreach ($medicine_names as $i => $name) {
        $name = trim($name);
        $qty = $quantities[$i] ?? '';
        if ($name !== '') {
            $stmt->execute([
                'consultation_id' => $consultation_id,
                'medicine_name' => $name,
                'quantity_dispensed' => $qty,
               'dispensed_by' => $input['dispensed_by'] ?? null

            ]);
        }
    }

    $pdo->commit();

    ob_clean();
    echo json_encode(['success' => true]);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    file_put_contents($log_file, "[EXCEPTION] " . $e->getMessage() . "\n", FILE_APPEND);
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
?>
