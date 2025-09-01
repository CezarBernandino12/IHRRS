document.addEventListener("DOMContentLoaded", function () {
    const urlParams = new URLSearchParams(window.location.search);
    const visit_id = urlParams.get("visit_id");

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

                // ✅ Visit Info
                if (data.visit) {
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
                    updateElement(".diagnosis", data.consultation.diagnosis);
                    updateElement(".instruction", data.consultation.instruction_prescription);
                    updateElement(".doctor", data.consultation.doctor_id);
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

                // ✅ Patient Info
                if (data.patient) {
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

    // ✅ Submit Button → Show Modal
    const submitBtn = document.getElementById("submitButton");
    if (submitBtn) {
        submitBtn.addEventListener("click", function (event) {
            event.preventDefault();
            const modal = document.getElementById("myModal");
            if (modal) modal.style.display = "block";
        });
    }

    // ✅ Cancel + Close modal
    ["cancelButton", "closeBtn"].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener("click", () => {
                const modal = document.getElementById("myModal");
                if (modal) modal.style.display = "none";
            });
        }
    });

    // ✅ Confirm Save → AJAX
    const confirmBtn = document.getElementById("confirmSave");
    if (confirmBtn) {
        confirmBtn.addEventListener("click", function () {
            const form = document.getElementById("individualRecordForm");
            if (!form) return;

            let formData = new FormData(form);
            confirmBtn.disabled = true;

            fetch("php/saveConsultation.php", {
                method: "POST",
                body: formData,
            })
                .then(response => response.text())
                .then(text => {
                    console.log("Raw Response:", text);
                    return JSON.parse(text);
                })
                .then(data => {
                    document.getElementById("myModal").style.display = "none";
                    showMessageModal(data.message || "Unknown response");
                })
                .catch(error => {
                    console.error("Error:", error);
                    showMessageModal("An error occurred while saving. ❌");
                })
                .finally(() => {
                    confirmBtn.disabled = false;
                });
        });
    }

    // ✅ Message Modal
    function showMessageModal(message) {
        const responseMsg = document.getElementById("responseMessage");
        const responseModal = document.getElementById("responseModal");
        if (responseMsg) responseMsg.textContent = message;
        if (responseModal) responseModal.style.display = "block";
    }

    const closeResponseBtn = document.getElementById("closeResponseModal");
    if (closeResponseBtn) {
        closeResponseBtn.addEventListener("click", function () {
            const responseModal = document.getElementById("responseModal");
            if (responseModal) responseModal.style.display = "none";
        });
    }
});
