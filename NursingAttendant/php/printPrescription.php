<?php 
require '../../php/db_connect.php';

$consultation_id = $_GET['consultation_id'] ?? null;
if (!$consultation_id) die('Consultation ID missing.');

$type = $_GET['type'] ?? 'all'; // "instruction", "prescription", or "all"

try {
    // ===== FETCH CONSULTATION + PATIENT INFO =====
    $stmt = $pdo->prepare("
       SELECT 
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        p.age,
        p.sex,
        p.address,
        c.consultation_date,
        c.instruction_prescription,
        m.medicine_name,
        m.dispensed_by,
        m.dispensed_date,
        m.quantity_dispensed
    FROM rhu_consultations c
    JOIN patients p ON c.patient_id = p.patient_id
    LEFT JOIN rhu_medicine_dispensed m ON c.consultation_id = m.consultation_id
    WHERE c.consultation_id = :consultation_id
    ");
    $stmt->execute(['consultation_id' => $consultation_id]);
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$consultation) die("No record found.");

    // ===== FETCH PRESCRIPTION DATA =====
  $stmt2 = $pdo->prepare("
    SELECT 
        pr.medicine_name, 
        pr.quantity, 
        pr.instruction, 
        pr.date, 
        u.full_name AS physician_name, 
        u.license_number AS physician_license
    FROM prescription pr
    LEFT JOIN users u ON pr.physician = u.user_id
    WHERE pr.consultation_id = :consultation_id
");

    $stmt2->execute(['consultation_id' => $consultation_id]);
    $prescriptions = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Consultation Record</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        h2, h3 { text-align: center; margin-bottom: 10px; }
        .info { margin-bottom: 10px; }
        .label { font-weight: bold; }
        .section { margin-bottom: 50px; page-break-after: always; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body onload="window.print()">

<!-- ===== HEADER TEMPLATE ===== -->
<?php function headerSection() { ?>
    <div style="text-align: center;">
        <h3>Republic of the Philippines</h3>
        <p style="margin-top: -5px;">Province of Camarines Norte</p>
        <h3 style="margin-top: -5px;">Municipality of Daet</h3>
        <h2 style="margin-top: -5px;">Rural Health Unit</h2>
        <br><br>
    </div>
<?php } ?>

<!-- ===== INSTRUCTION COPY (IF ALLOWED) ===== -->
<?php if ($type === 'instruction' || $type === 'all'): ?>
    <?php headerSection(); ?>
    <div class="section">
        <h3>Instructions (Patient's Copy)</h3><br><br>
        <div class="info"><span class="label">Consultation Date:</span> <?= htmlspecialchars($consultation['consultation_date']) ?></div><br>
        <div class="info"><span class="label">Patient:</span> <?= htmlspecialchars($consultation['patient_name']) ?></div>
        <div class="info"><span class="label">Address:</span> <?= htmlspecialchars($consultation['address']) ?></div>

        <div style="padding: 20px; border: 1px solid #000;">
            <div class="info"><span class="label">Medicine Given:</span><br> <?= nl2br(htmlspecialchars($consultation['medicine_name'])) ?></div>
            <div class="info"><span class="label">Quantity Given:</span><br> <?= nl2br(htmlspecialchars($consultation['quantity_dispensed'])) ?></div>
            <div class="info"><span class="label">Remarks/Instructions:</span><br> <?= nl2br(htmlspecialchars($consultation['instruction_prescription'])) ?></div>
        </div><br><br>
        <div class="info"><span class="label">Given by:</span><br> <?= nl2br(htmlspecialchars($consultation['dispensed_by'])) ?></div>
    </div>
<?php endif; ?>

<!-- ===== PRESCRIPTION COPY (IF ALLOWED) ===== -->
<?php if (!empty($prescriptions) && ($type === 'prescription' || $type === 'all')): ?>
    <?php headerSection(); ?>
    <div class="section">
        <h2>Prescription</h2><br>
        <img src="../../img/rx.png" alt="Rx" style="width:70px;height:70px;"><br><br>

        <div class="info"><span class="label">Patient:</span> <?= htmlspecialchars($consultation['patient_name']) ?></div>
        <div class="info"><span class="label">Address:</span> <?= htmlspecialchars($consultation['address']) ?></div>
        <div class="info"><span class="label">Age:</span> <?= htmlspecialchars($consultation['age']) ?> |
        <span class="label">Sex:</span> <?= htmlspecialchars($consultation['sex']) ?></div>
        <div class="info"><span class="label">Consultation Date:</span> <?= htmlspecialchars($consultation['consultation_date']) ?></div>

        <h3>Prescribed Medicines:</h3>
        <table>
            <thead>
                <tr>
                    <th>Medicine Name</th>
                    <th>Quantity</th>
                    <th>Instruction</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prescriptions as $pres): ?>
                    <tr>
                        <td><?= htmlspecialchars($pres['medicine_name']) ?></td>
                        <td><?= htmlspecialchars($pres['quantity']) ?></td>
                        <td><?= htmlspecialchars($pres['instruction']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table><br><br>

        <div class="info"><span class="label">Physician:</span> <?= htmlspecialchars($prescriptions[0]['physician_name']) ?></div>
        <div class="info"><span class="label">License No.:</span> <?= htmlspecialchars($prescriptions[0]['physician_license']) ?></div>
    </div>
<?php endif; ?>

</body>
</html>
 