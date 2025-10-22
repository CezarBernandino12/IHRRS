<?php 
require '../../php/db_connect.php';

$consultation_id = $_GET['consultation_id'] ?? null;
if (!$consultation_id) die('Consultation ID missing.');

$typeParam = $_GET['type'] ?? 'all';
$types = explode(',', $typeParam);

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
        u.full_name AS physician_name,
        u.license_number AS physician_license,
        u.rhu,
        m.instruction,
        m.dispensed_date,
        m.quantity_dispensed
    FROM rhu_consultations c
    JOIN patients p ON c.patient_id = p.patient_id
    LEFT JOIN rhu_medicine_dispensed m ON c.consultation_id = m.consultation_id
    LEFT JOIN users u ON m.dispensed_by = u.user_id
    WHERE c.consultation_id = :consultation_id
");

    $stmt->execute([':consultation_id' => $consultation_id]);
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$consultations) die("No record found.");


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

    $stmt2->execute([':consultation_id' => $consultation_id]);
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
   @page {
    size: A6; /* default print size */
    margin: 5mm;
}

body {
    font-family: Arial, sans-serif;
    font-size: 11px; /* optional, slightly smaller for A6 */
    margin: 0;
    padding: 0;
    width: 105mm;  /* A6 width */
    height: 148mm; /* A6 height */
    box-sizing: border-box;
}

.quarter-page {
    width: 100%;   /* fill body */
    height: 100%;
    padding: 6mm;
    box-sizing: border-box;
    position: relative;
    border: 1px solid #ccc; /* remove if you don't want border in print */
    overflow: hidden;
    page-break-after: always;
}


    h2, h3 {
        text-align: center;
        margin: 4px 0;
        font-size: 12px;
    }

    .info {
        margin-bottom: 4px;
        line-height: 1.3;
    }

    .label {
        font-weight: bold;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 5px;
        font-size: 12px;
    }

    th, td {
        border: 1px solid #333;
        padding: 3px;
        text-align: left;
        vertical-align: top;
        word-wrap: break-word;
    }

    th {
        background-color: #f2f2f2;
    }

    img.print-logo {
        height: 25px;
        width: auto;
    }

    img[alt="Rx"] {
        width: 35px;
        height: 35px;
    }
     .signature-line {
            border-top: 0.5px solid #000;
            width: 200px;
            margin-left: 0;
            margin-bottom: 5px;
        }

   @media print {
    body {
        margin: 0;
        padding: 0;
        width: 105mm;
        height: 148mm;
    }
    .quarter-page {
        border: none; /* remove border when printing */
        page-break-inside: avoid;
    }
}
</style>


</head>
<body onload="window.print()">

<!-- ===== HEADER TEMPLATE ===== -->
<?php
function headerSection($consultation) { ?>
    <div class="header" style="text-align: center;">
          <img src="../../img/RHUlogo.png" alt="RHU Logo" class="print-logo" style="height: 50px; width: auto;" />
        <h3>Republic of the Philippines</h3>
        <p style="margin-top: -5px;">Province of Camarines Norte</p>
        <h3 style="margin-top: -10px;">Municipality of Daet</h3>
        <div class="info" style="margin-top: -2px;">
           <span class="label"></span> <?= htmlspecialchars($consultation['rhu'] ?? 'N/A') ?>
        </div>
        <br>
    </div>
<?php } ?>

<!-- ===== INSTRUCTION COPY (IF ALLOWED) ===== -->
<?php if (in_array('instruction', $types) || in_array('all', $types)): ?>


    <div class="quarter-page">
    <?php headerSection($consultations[0]); ?>
    <div class="section">

        <h3>Instructions (Patient's Copy)</h3><br><br>
        <div class="info">
    <span class="label">Consultation Date:</span> 
    <?= date("F j, Y", strtotime($consultations[0]['consultation_date'])) ?>
</div>
<br>
        <div class="info"><span class="label">Patient:</span> <?= htmlspecialchars($consultations[0]['patient_name']) ?></div>
        <div class="info"><span class="label">Address:</span> <?= htmlspecialchars($consultations[0]['address']) ?></div>

       
        
        
         <table>
            <thead>
                <tr>
                    <th>Given Medicine</th>
                    <th>Quantity</th>
                    <th>Instruction</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($consultations as $cons): ?>
                    <tr>
                        <td><?= htmlspecialchars($cons['medicine_name']) ?></td>
                        <td><?= htmlspecialchars($cons['quantity_dispensed']) ?></td>
                        <td><?= htmlspecialchars($cons['instruction']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table><br><br>
        
                    <div class="info"><span class="label">Remarks:</span><br> <?= nl2br(htmlspecialchars($consultations[0]['instruction_prescription'])) ?></div>
        <br><br>
         <div class="info"><span class="label">Physician:</span> <?= htmlspecialchars($consultations[0]['physician_name']) ?></div>
        <div class="info"><span class="label">License No.:</span> <?= htmlspecialchars($consultations[0]['physician_license']) ?></div>
    </div> </div>
<?php endif; ?>


<?php if (in_array('instruction', $types) && in_array('prescription', $types)): ?>
    <div style="page-break-before: always;"></div>
<?php endif; ?>


<!-- ===== PRESCRIPTION COPY (IF ALLOWED) ===== -->
<?php if (!empty($prescriptions) && (in_array('prescription', $types) || in_array('all', $types))): ?>
 
     <div class="quarter-page">
    <?php headerSection($consultations[0]); ?>
    <div class="section">

   
        <h2>Prescription</h2><br>
        <img src="../../img/rx.png" alt="Rx" style="width:45px;height:45px;"><br><br>

        <div class="info"><span class="label">Patient:</span> <?= htmlspecialchars($consultations[0]['patient_name']) ?></div>
        <div class="info"><span class="label">Address:</span> <?= htmlspecialchars($consultations[0]['address']) ?></div>
        <div class="info"><span class="label">Age:</span> <?= htmlspecialchars($consultations[0]['age']) ?> |
        <span class="label">Sex:</span> <?= htmlspecialchars($consultations[0]['sex']) ?></div>
       <div class="info">
    <span class="label">Consultation Date:</span> 
    <?= date("F j, Y", strtotime($consultations[0]['consultation_date'])) ?>
</div> <br>


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

             <div class="signature">
             <div class="signature-title">Signature:</div> <br> <br> <br>
            <div class="signature-line"></div>
           <div class="info">
    <span class="label"></span> 
    <strong><?= strtoupper(htmlspecialchars($prescriptions[0]['physician_name'])) ?></strong>
</div>
             <div class="info"><span class="label" style="font-weight: 500; font-size: 7pt; margin-top: -5px;"><i>Physician</i></div>
            <div class="info" style="font-weight: 500; font-size: 7pt; margin-top: -5px;">License No.: <?= htmlspecialchars($prescriptions[0]['physician_license']) ?></div>
        </div> <br>

        
        
    </div>  </div>
<?php endif; ?>

</body>
</html>
 