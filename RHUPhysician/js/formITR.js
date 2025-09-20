document.addEventListener("DOMContentLoaded", function () {
    const urlParams = new URLSearchParams(window.location.search);
    const patient_id = urlParams.get("patient_id");

    

    if (!patient_id) {
        console.error("No patient_id found in the URL.");
        return;
    } 

    const $patient_id = document.getElementById("patient_id");
    if ($patient_id) $patient_id.value = patient_id;

    fetch(`php/patient_details_only.php?patient_id=${patient_id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Received Data:", data);

            if (!data || data.error) {
                console.error("Error:", data.error || "No data received.");
                return;
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
        .catch(error => console.error("Error fetching patient info:", error));

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

const closeBtn = document.getElementById("closeBtn");
const cancelBtn = document.getElementById("cancelButton");

closeBtn.addEventListener("click", function() {
    document.getElementById("myModal").style.display = "none"; 
});

cancelBtn.addEventListener("click", function() {
    document.getElementById("myModal").style.display = "none"; 
});

   
});

