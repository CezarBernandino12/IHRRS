document.addEventListener("DOMContentLoaded", function () {
    const urlParams = new URLSearchParams(window.location.search);
    const visit_id = urlParams.get("visit_id");

    $patient_id = document.getElementById("patient_id");
    if (visit_id) {
        const visitIdField = document.getElementById("visit_id");
        if (visitIdField) visitIdField.value = visit_id;

        fetch(`php/get_visit_info.php?visit_id=${visit_id}`)
            .then(response => response.json())
            .then(data => {
                console.log("Received Data:", data);

                if (!data || data.error) {
                    console.error("Error:", data.error || "No data received.");
                    return;
                }

                   // ✅ Patient Info
                if (data.patient) {
                    if ($patient_id) $patient_id.value = data.patient.patient_id || "";
                    updateElement(".patient-first-name", data.patient.first_name);
                    updateElement(".patient-last-name", data.patient.last_name);
                    updateElement(".patient-middle-name", data.patient.middle_name);
                    updateElement(".patient-extension", data.patient.extension);
                    updateElement(".patient-birth-place", data.patient.birthplace);
                    updateElement(".date-of-birth", data.patient.date_of_birth);
                    const age = calculateAge(data.patient.date_of_birth);
                    updateElement(".age", age);
                    updateElement(".address", data.patient.address);
                    updateElement(".civil-status", data.patient.civil_status);
                    updateElement(".contact-number", data.patient.contact_number);
                    updateElement(".religion", data.patient.religion);
                    updateElement(".occupation", data.patient.occupation);
                    updateElement(".birth-weight", data.patient.birth_weight);
                    updateElement(".educational-attainment", data.patient.educational_attainment);
                    updateElement(".philhealth-member-no", data.patient.philhealth_member_no);
                    updateElement(".category", data.patient.category);
                    updateElement(".family-serial-no", data.patient.family_serial_no);
                    updateElement(".sex", data.patient.sex);
                    updateElement(".fourps-status", data.patient.fourps_status);
                                
                }

                // ✅ Visit Info
                if (data.visit) {
                    document.getElementById("visit_id").value = data.visit.visit_id || "";
                    updateElement(".visit-date", data.visit.visit_date);
                    updateElement(".patient-alert", data.visit.patient_alert);
                    updateElement(".chief-complaints", data.visit.chief_complaints);
                    updateElement(".blood-pressure", data.visit.blood_pressure);
                    updateElement(".temperature", data.visit.temperature);
                    updateElement(".weight", data.visit.weight);
                    updateElement(".height", data.visit.height);
                    updateElement(".pulse-rate", data.visit.pulse_rate);
                    updateElement(".respiratory-rate", data.visit.respiratory_rate);
                    updateElement(".remarks", data.visit.remarks);
                }

                // ✅ Consultation Info
                if (data.consultation) {
                    updateElement(".diagnosis", data.consultation.diagnosis || "N/A");
                    document.getElementById("diagnosis").value = data.consultation.diagnosis || "";
                    updateElement(".diagnosis_status", data.consultation.diagnosis_status);
                    updateElement(".instruction", data.consultation.instruction_prescription);
                    updateElement(".doctor", data.consultation.full_name);

                    // store consultation_id in a hidden field
                    const consultationField = document.getElementById("consultation_id");
                    if (consultationField) {
                        consultationField.value = data.consultation.consultation_id;
                    }

                    // 🔹 fetch lab file dynamically
                    loadLabFile(data.consultation.consultation_id);
                }

                // ✅ Medicines (Patient)
                const medicineContainer = document.querySelector(".medicine-list");
                if (medicineContainer) {
                    medicineContainer.innerHTML = "";
                    if (data.medicine && data.medicine.length > 0) {
                        data.medicine.forEach(med => {
                            medicineContainer.innerHTML += `
                                <div class="form-group">
                                    <label>Medicine:</label>
                                    <p>${med.medicine_name || "None"}</p>
                                </div>
                                <div class="form-group">
                                    <label>Quantity:</label>
                                    <p>${med.quantity_dispensed || "0"}</p>
                                </div>
                            `;
                        });
                    } else {
                        medicineContainer.innerHTML = "<p>No medication recorded.</p>";
                    }
                }

                // ✅ Medicines (RHU)
                const medicineContainer2 = document.querySelector(".rhu-medicine-list");
                if (medicineContainer2) {
                    medicineContainer2.innerHTML = "";
                    if (data.rhumedicine && data.rhumedicine.length > 0) {
                        data.rhumedicine.forEach(med => {
                            medicineContainer2.innerHTML += `
                                <div class="form-group">
                                    <label>Medicine:</label>
                                    <p>${med.medicine_name || "None"}</p>
                                </div>
                                <div class="form-group">
                                    <label>Quantity:</label>
                                    <p>${med.quantity_dispensed || "0"}</p>
                                </div>
                            `;
                        });
                    } else {
                        medicineContainer2.innerHTML = "<p>No medication recorded.</p>";
                    }
                }

             
            })
            .catch(error => console.error("Error fetching visit info:", error));
    }

    // ✅ Utility: Update element text
    function updateElement(selector, value) {
        const element = document.querySelector(selector);
        if (element) element.textContent = value || "N/A";
    }

    // ✅ Utility: Age Calculation
    function calculateAge(dob) {
        if (!dob) return "N/A";
        const birthDate = new Date(dob);
        if (isNaN(birthDate)) return "N/A";

        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age >= 0 ? age : "N/A";
    }

    // ✅ Edit Record Button
    const editBtn = document.getElementById("editRecord");
    if (editBtn) {
        editBtn.onclick = () => {
            window.location.href = `editRecord.html?visit_id=${visit_id}`;
        };
    }

    // ✅ Toggle Personal Info
    const toggleBtn = document.getElementById("togglePersonalInfo");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", function () {
            const hiddenSection = document.getElementById("hiddenPersonalInfo");
            if (!hiddenSection) return;

            if (hiddenSection.style.display === "none" || hiddenSection.style.display === "") {
                hiddenSection.style.display = "block";
                toggleBtn.textContent = "HIDE";
            } else {
                hiddenSection.style.display = "none";
                toggleBtn.textContent = "View All";
            }
        });
    }

    // ✅ Resume Button
    const resumeBtn = document.getElementById("resumeBtn");
    if (resumeBtn) {
        resumeBtn.addEventListener("click", function () {
            window.location.href = "resumeTreatment.html?visit_id=" + visit_id;
        });
    }

    // ✅ Load Lab File


const container = document.getElementById("lab-file-container");

if (visit_id) {
    fetch(`php/get_file.php?visit_id=${visit_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                const fileUrl = data.file;
                const fileExt = fileUrl.split('.').pop().toLowerCase();
                let content = "";

                if (["jpg", "jpeg", "png", "gif"].includes(fileExt)) {
                    // Show actual image
                    content = `
                        <a href="${fileUrl}" target="_blank">
                            <img src="${fileUrl}" 
                                 alt="Lab File" 
                                 style="width:50px; height:50px; object-fit:cover; vertical-align:middle; margin-right:8px;">
                            View File
                        </a>
                    `;
                } else if (fileExt === "pdf") {
                    // Show PDF icon
                    content = `
                        <a href="${fileUrl}" target="_blank">
                            <img src="icons/pdf-icon.png" 
                                 alt="PDF File" 
                                 style="width:50px; height:50px; vertical-align:middle; margin-right:8px;">
                            View PDF
                        </a>
                    `;
                } else if (["doc", "docx"].includes(fileExt)) {
                    // Word docs
                    content = `
                        <a href="${fileUrl}" target="_blank">
                            <img src="icons/word-icon.png" 
                                 alt="Word File" 
                                 style="width:50px; height:50px; vertical-align:middle; margin-right:8px;">
                            View Word Document
                        </a>
                    `;
                } else if (["xls", "xlsx"].includes(fileExt)) {
                    // Excel docs
                    content = `
                        <a href="${fileUrl}" target="_blank">
                            <img src="icons/excel-icon.png" 
                                 alt="Excel File" 
                                 style="width:50px; height:50px; vertical-align:middle; margin-right:8px;">
                            View Excel File
                        </a>
                    `;
                } else {
                    // Other file types
                    content = `
                        <a href="${fileUrl}" target="_blank">
                            <img src="icons/file-icon.png" 
                                 alt="File" 
                                 style="width:50px; height:50px; vertical-align:middle; margin-right:8px;">
                            Download File
                        </a>
                    `;
                }

                container.innerHTML = content;
            } else {
                container.textContent = "No file uploaded.";
            }
        })
        .catch(error => {
            console.error("Error fetching file:", error);
            container.textContent = "Error loading file.";
        });
} else {
    container.textContent = "No visit_id selected.";
}
});
