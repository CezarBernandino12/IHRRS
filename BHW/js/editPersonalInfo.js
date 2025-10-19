document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const submitBtn = document.getElementById("submitButton");

    // Modals
    const modal1 = document.getElementById("myModal");
    const modal2 = document.getElementById("myModal2");
    const modal3 = document.getElementById("myModal3");

    // Buttons inside modals - with null checks
    const yesBtn = document.getElementById("yesButton");
    const cancelBtn = document.getElementById("cancelBtn");
    const closeBtn = document.getElementById("closeBtn");
    const closeBtn2 = document.getElementById("closeBtn2");
    const closeBtn3 = document.getElementById("closeBtn3");

    // Add event listeners only if elements exist
    if (closeBtn) {
        closeBtn.addEventListener("click", () => {
            if (modal1) modal1.style.display = "none";
        });
    }

    if (closeBtn2) {
        closeBtn2.addEventListener("click", () => {
            const urlParams = new URLSearchParams(window.location.search);
            const patientId = urlParams.get('patient_id');
            // Redirect to record.html with patient_id
            window.location.href = `record.html?patient_id=${patientId}`;
        });
    }

    if (closeBtn3) {
        closeBtn3.addEventListener("click", () => {
            if (modal3) modal3.style.display = "none";
        });
    }

    // Show modal1 on submit
    if (submitBtn) {
        submitBtn.addEventListener("click", function (e) {
            e.preventDefault();
            if (modal1) modal1.style.display = "block";
        });
    }

    // Modal1 button actions
    if (yesBtn) {
        yesBtn.addEventListener("click", () => {
            if (modal1) modal1.style.display = "none";

            const urlParams = new URLSearchParams(window.location.search);
            const patientId = urlParams.get('patient_id');
            console.log("Patient ID:", patientId);

            if (!patientId) {
                alert("Patient ID not found in URL");
                return;
            }

            // Set hidden input
            const patientIdField = document.getElementById("patientIdField");
            if (patientIdField) {
                patientIdField.value = patientId;
            }

            const updatedData = {
                patient_id: patientId,
                first_name: (document.querySelector(".patient-first-name")?.value || "").trim(),
                last_name: (document.querySelector(".patient-last-name")?.value || "").trim(),
                middle_name: (document.querySelector(".patient-middle-name")?.value || "").trim(),
                extension: (document.querySelector(".patient-extension")?.value || "").trim(),
                birthplace: (document.querySelector(".patient-birth-place")?.value || "").trim(),
                date_of_birth: (document.querySelector(".date-of-birth")?.value || "").trim(),
                address: (document.querySelector(".address")?.value || "").trim(),
                civil_status: (document.querySelector(".civil-status")?.value || "").trim(),
                contact_number: (document.querySelector(".contact-number")?.value || "").trim(),
                religion: (document.querySelector(".religion")?.value || "").trim(),
                occupation: (document.querySelector(".occupation")?.value || "").trim(),
                educational_attainment: (document.querySelector(".educational-attainment")?.value || "").trim(),
                birth_weight: (document.querySelector(".birth-weight")?.value || "").trim(),
                philhealth_member_no: (document.querySelector(".philhealth-member-no")?.value || "").trim(),
                category: (document.querySelector(".category")?.value || "").trim(),
                family_serial_no: (document.querySelector(".family-serial-no")?.value || "").trim(),
                sex: (document.querySelector(".sex")?.value || "").trim(),
                fourps_status: (document.querySelector(".fourps-status")?.value || "").trim()
            };

            // Debug: Check all fields
            console.log("=== DEBUGGING DATA ===");
            console.log("Sex field value:", document.querySelector(".sex")?.value);
            console.log("Sex field element:", document.querySelector(".sex"));
            console.log("All data being sent:", updatedData);
            console.log("=== END DEBUG ===");

            fetch("php/update_personal_info.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(updatedData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                console.log("Update Response:", result);
                if (result.success) {
                    if (modal2) modal2.style.display = "block";
                    // Don't auto-refresh - let user close modal first
                } else {
                    alert("Failed to update record: " + (result.error || "Ensure complete details."));
                    if (modal3) modal3.style.display = "block";
                }
            })
            .catch(error => {
                console.error("Error updating visit info:", error);
                alert("An error occurred while updating. Check console for details.");
                if (modal3) modal3.style.display = "block";
            });
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener("click", () => {
            if (modal1) modal1.style.display = "none";
        });
    }

    // Close modals when clicking outside of them
    window.addEventListener("click", function (e) {
        document.querySelectorAll(".modal").forEach(modal => {
            if (e.target === modal) {
                modal.style.display = "none";
                // If closing the success modal (modal2), redirect to record.html
                if (modal === modal2) {
                    const urlParams = new URLSearchParams(window.location.search);
                    const patientId = urlParams.get('patient_id');
                    window.location.href = `record.html?patient_id=${patientId}`;
                }
            }
        });
    });

    // Load patient data
    const urlParams = new URLSearchParams(window.location.search);
    const patient_id = urlParams.get("patient_id");

    if (patient_id) {
        fetch(`php/patient_details.php?patient_id=${patient_id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Received Data:", data);

                if (!data || data.error) {
                    console.error("Error:", data.error || "No data received.");
                    return;
                }

                // Populate Patient Information
                if (data.patient) {
                    populateInput(".patient-first-name", data.patient.first_name);
                    populateInput(".patient-last-name", data.patient.last_name);
                    populateInput(".patient-middle-name", data.patient.middle_name);
                    populateInput(".patient-extension", data.patient.extension);
                    populateInput(".patient-birth-place", data.patient.birthplace);
                    populateInput(".date-of-birth", data.patient.date_of_birth, "date");

                    const age = calculateAge(data.patient.date_of_birth);
                    populateInput(".age", age);

                    populateInput(".address", data.patient.address);
                    populateInput(".civil-status", data.patient.civil_status);
                    populateInput(".contact-number", data.patient.contact_number);
                    populateInput(".religion", data.patient.religion);
                    populateInput(".occupation", data.patient.occupation);
                    populateInput(".educational-attainment", data.patient.educational_attainment);
                    populateInput(".birth-weight", data.patient.birth_weight);
                    populateInput(".philhealth-member-no", data.patient.philhealth_member_no);
                    populateInput(".category", data.patient.category);
                    populateInput(".family-serial-no", data.patient.family_serial_no);
                    populateInput(".sex", data.patient.sex);
                    populateInput(".fourps-status", data.patient.fourps_status);
                }
            })
            .catch(error => {
                console.error("Error fetching visit info:", error);
                alert("An error occurred while fetching the visit info.");
            });
    }

    function populateInput(selector, value, type = "text") {
        const element = document.querySelector(selector);
        if (element) {
            if (element.tagName === "INPUT" || element.tagName === "TEXTAREA") {
                if (type === "date" && value) {
                    element.value = formatDate(value);
                } else {
                    element.value = value || "";
                }
            }
        }
    }

    function formatDate(dateString) {
        if (!dateString) return "";
        const date = new Date(dateString);
        return isNaN(date) ? "" : date.toISOString().split("T")[0];
    }

    function calculateAge(dob) {
        if (!dob) return "";
        const birthDate = new Date(dob);
        if (isNaN(birthDate)) return "";

        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        return age >= 0 ? age : "";
    }
});