<?php 
require '../../php/db_connect.php';

$consultation_id = $_GET['consultation_id'] ?? null;
if (!$consultation_id) die('Consultation ID missing.');

$typeParam = $_GET['type'] ?? 'all';
$types = explode(',', $typeParam);

try {
    $stmt = $pdo->prepare("
        SELECT 
            CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
            p.age,
            p.sex,
            p.address,
            c.consultation_date,
            c.instruction_prescription,
            m.medicine_name,
            m.instruction,
            m.dispensed_date,
            m.quantity_dispensed,
            u.full_name AS physician_name,
            u.license_number AS physician_license
        FROM rhu_consultations c
        JOIN patients p ON c.patient_id = p.patient_id
        LEFT JOIN rhu_medicine_dispensed m ON c.consultation_id = m.consultation_id
        LEFT JOIN users u ON m.dispensed_by = u.user_id
        WHERE c.consultation_id = :consultation_id
    ");
    $stmt->execute([':consultation_id' => $consultation_id]);
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$consultations) die("No record found.");

    $stmt2 = $pdo->prepare("
        SELECT 
            pr.medicine_name, 
            pr.quantity, 
            pr.instruction, 
            pr.date, 
            u.full_name AS physician_name, 
            u.license_number AS physician_license,
            u.rhu AS rhu
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
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: A4;
            margin: 6mm 8mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            width: 210mm;
            background: #fff;
        }

        /* ── Bond paper = two rows, each row has two columns ── */
        .bond-paper {
            width: 100%;
            height: 297mm; /* A4 height minus margins */
            display: flex;
            flex-direction: column;
        }

        /* Top half = Patient's Copy | Bottom half = RHU Copy */
        .half {
            width: 100%;
            height: 50%;
            display: flex;
            flex-direction: row;
            position: relative;
        }

        /* Dashed horizontal cut line between the two halves */
        .cut-line-h {
            width: 100%;
            border: none;
            border-top: 1.5px dashed #999;
            position: relative;
            margin: 0;
        }
        .cut-line-h::before {
            content: '✂ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -';
            position: absolute;
            top: -8px;
            left: 0;
            font-size: 9px;
            color: #aaa;
            letter-spacing: 1px;
            white-space: nowrap;
            overflow: hidden;
            width: 100%;
        }

        /* Dashed vertical cut line between instruction and prescription within a half */
        .cut-line-v {
            width: 1.5px;
            background: repeating-linear-gradient(
                to bottom,
                #aaa 0px, #aaa 6px,
                transparent 6px, transparent 12px
            );
            flex-shrink: 0;
        }

        /* Each individual slip */
        .slip {
            width: 50%;
            height: 100%;
            padding: 5mm 4mm;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        /* Copy label badge */
        .copy-label {
            display: inline-block;
            font-size: 8px;
            font-weight: bold;
            padding: 1px 5px;
            border-radius: 3px;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .rhu-label { background: #eee; }
        .patient-label { background: #fff; }

        /* Header */
        .slip-header {
            text-align: center;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
            margin-bottom: 3px;
        }
        .slip-header img.logo {
            height: 32px;
            width: auto;
        }
        .slip-header h3 {
            font-size: 9px;
            font-weight: bold;
            margin: 1px 0;
        }
        .slip-header p {
            font-size: 8px;
            margin: 0;
        }
        .rhu-name {
            font-size: 8px;
            margin-top: 1px;
        }

        /* Section title */
        .slip-title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin: 2px 0;
        }

        /* Info rows */
        .info-row {
            font-size: 9px;
            line-height: 1.4;
            margin: 1px 0;
        }
        .info-row .label { font-weight: bold; }

        /* Medicine table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            font-size: 8.5px;
        }
        th, td {
            border: 1px solid #444;
            padding: 2px 3px;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background-color: #f2f2f2;
            font-size: 8px;
        }

        /* Rx image */
        .rx-img {
            width: 30px;
            height: 30px;
            display: block;
            margin: 2px 0;
        }

        /* Signature block */
        .signature-block {
            margin-top: auto;
            padding-top: 6px;
        }
        .sig-line {
            border-top: 0.5px solid #000;
            width: 160px;
            margin-bottom: 2px;
        }
        .sig-name {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .sig-sub {
            font-size: 7.5px;
            font-style: italic;
            margin-top: -1px;
        }

        /* Remarks */
        .remarks {
            margin-top: 3px;
            font-size: 9px;
        }

        @media print {
            body { margin: 0; padding: 0; }
            .bond-paper { page-break-inside: avoid; }
        }
    </style>
</head>
<body onload="window.print()">

<?php
$rhuName       = $prescriptions[0]['rhu'] ?? ($consultations[0]['rhu'] ?? 'N/A');
$patientName   = htmlspecialchars($consultations[0]['patient_name']);
$address       = htmlspecialchars($consultations[0]['address']);
$age           = htmlspecialchars($consultations[0]['age']);
$sex           = htmlspecialchars($consultations[0]['sex']);
$consDate      = date("F j, Y", strtotime($consultations[0]['consultation_date']));
$remarks       = nl2br(htmlspecialchars($consultations[0]['instruction_prescription']));
$physicianDisp = htmlspecialchars($consultations[0]['physician_name'] ?? '');
$physicianPres = strtoupper(htmlspecialchars($prescriptions[0]['physician_name'] ?? ''));
$licenseNo     = htmlspecialchars($prescriptions[0]['physician_license'] ?? '');

function slipHeader($rhuName) { ?>
    <div class="slip-header">
        <img src="../../img/mho_logo.png" alt="RHU Logo" class="logo" />
        <h3>Republic of the Philippines</h3>
        <p>Province of Camarines Norte</p>
        <h3>Municipality of Daet</h3>
        <div class="rhu-name"><?= htmlspecialchars($rhuName) ?></div>
    </div>
<?php }
?>

<div class="bond-paper">

    <!-- ═══════════════════════════════════════════════ -->
    <!-- TOP HALF — PATIENT'S COPY                       -->
    <!-- ═══════════════════════════════════════════════ -->
    <div class="half">

        <?php if (in_array('instruction', $types) || in_array('all', $types)): ?>
        <!-- Instruction slip — Patient -->
        <div class="slip">
            <?php slipHeader($rhuName); ?>
            <span class="copy-label patient-label">Patient's Copy</span>
            <div class="slip-title">Instructions</div>

            <div class="info-row"><span class="label">Date:</span> <?= $consDate ?></div>
            <div class="info-row"><span class="label">Patient:</span> <?= $patientName ?></div>
            <div class="info-row"><span class="label">Address:</span> <?= $address ?></div>

            <table>
                <thead>
                    <tr>
                        <th>Medicine Given</th>
                        <th>Qty</th>
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
            </table>

            <div class="remarks"><span class="label">Remarks:</span><br><?= $remarks ?></div>

            <div class="signature-block">
                <div class="info-row"><span class="label">Physician:</span> <?= $physicianDisp ?></div>
            </div>
        </div>
        <div class="cut-line-v"></div>
        <?php endif; ?>

        <?php if (!empty($prescriptions) && (in_array('prescription', $types) || in_array('all', $types))): ?>
        <!-- Prescription slip — Patient -->
        <div class="slip">
            <?php slipHeader($rhuName); ?>
            <span class="copy-label patient-label">Patient's Copy</span>
            <div class="slip-title">Prescription</div>
            <img src="../../img/rx.png" alt="Rx" class="rx-img">

            <div class="info-row"><span class="label">Patient:</span> <?= $patientName ?></div>
            <div class="info-row"><span class="label">Address:</span> <?= $address ?></div>
            <div class="info-row"><span class="label">Age:</span> <?= $age ?> &nbsp;|&nbsp; <span class="label">Sex:</span> <?= $sex ?></div>
            <div class="info-row"><span class="label">Date:</span> <?= $consDate ?></div>

            <table>
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Qty</th>
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
            </table>

            <div class="signature-block">
                <div class="sig-line"></div>
                <div class="sig-name"><?= $physicianPres ?></div>
                <div class="sig-sub">Physician</div>
                <div class="sig-sub">License No.: <?= $licenseNo ?></div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /top half -->

    <!-- ─── Cut line ─── -->
    <hr class="cut-line-h">

    <!-- ═══════════════════════════════════════════════ -->
    <!-- BOTTOM HALF — RHU COPY                          -->
    <!-- ═══════════════════════════════════════════════ -->
    <div class="half">

        <?php if (in_array('instruction', $types) || in_array('all', $types)): ?>
        <!-- Instruction slip — RHU -->
        <div class="slip">
            <?php slipHeader($rhuName); ?>
            <span class="copy-label rhu-label">RHU Copy</span>
            <div class="slip-title">Instructions</div>

            <div class="info-row"><span class="label">Date:</span> <?= $consDate ?></div>
            <div class="info-row"><span class="label">Patient:</span> <?= $patientName ?></div>
            <div class="info-row"><span class="label">Address:</span> <?= $address ?></div>

            <table>
                <thead>
                    <tr>
                        <th>Medicine Given</th>
                        <th>Qty</th>
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
            </table>

            <div class="remarks"><span class="label">Remarks:</span><br><?= $remarks ?></div>

            <div class="signature-block">
                <div class="info-row"><span class="label">Physician:</span> <?= $physicianDisp ?></div>
            </div>
        </div>
        <div class="cut-line-v"></div>
        <?php endif; ?>

        <?php if (!empty($prescriptions) && (in_array('prescription', $types) || in_array('all', $types))): ?>
        <!-- Prescription slip — RHU -->
        <div class="slip">
            <?php slipHeader($rhuName); ?>
            <span class="copy-label rhu-label">RHU Copy</span>
            <div class="slip-title">Prescription</div>
            <img src="../../img/rx.png" alt="Rx" class="rx-img">

            <div class="info-row"><span class="label">Patient:</span> <?= $patientName ?></div>
            <div class="info-row"><span class="label">Address:</span> <?= $address ?></div>
            <div class="info-row"><span class="label">Age:</span> <?= $age ?> &nbsp;|&nbsp; <span class="label">Sex:</span> <?= $sex ?></div>
            <div class="info-row"><span class="label">Date:</span> <?= $consDate ?></div>

            <table>
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Qty</th>
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
            </table>

            <div class="signature-block">
                <div class="sig-line"></div>
                <div class="sig-name"><?= $physicianPres ?></div>
                <div class="sig-sub">Physician</div>
                <div class="sig-sub">License No.: <?= $licenseNo ?></div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /bottom half -->

</div><!-- /bond-paper -->

</body>
</html>