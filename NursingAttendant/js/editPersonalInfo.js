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

            // Get sex value from radio buttons
            const sexRadio = document.querySelector('input[name="sex"]:checked');
            const sexValue = sexRadio ? sexRadio.value : "";

            // Get 4ps value from radio buttons
            const fourpsRadio = document.querySelector('input[name="fourps_status"]:checked');
            const fourpsValue = fourpsRadio ? fourpsRadio.value : "";

            const updatedData = {
                patient_id: patientId,
                first_name: (document.querySelector(".patient-first-name")?.value || "").trim(),
                last_name: (document.querySelector(".patient-last-name")?.value || "").trim(),
                middle_name: (document.querySelector(".patient-middle-name")?.value || "").trim(),
                extension: (document.querySelector(".patient-extension")?.value || "").trim(),
                birthplace: (document.querySelector(".patient-birth-place")?.value || "").trim(),
                date_of_birth: (document.querySelector(".date-of-birth")?.value || "").trim(),
                address: (document.querySelector("#permanent_address_combined")?.value || "").trim(),
                civil_status: (document.querySelector(".civil-status")?.value || "").trim(),
                contact_number: (document.querySelector(".contact-number")?.value || "").trim(),
                religion: (document.querySelector(".religion")?.value || "").trim(),
                occupation: (document.querySelector(".occupation")?.value || "").trim(),
                educational_attainment: (document.querySelector(".educational-attainment")?.value || "").trim(),
                birth_weight: (document.querySelector(".birth-weight")?.value || "").trim(),
                philhealth_member_no: (document.querySelector(".philhealth-member-no")?.value || "").trim(),
                category: (document.querySelector(".category")?.value || "").trim(),
                family_serial_no: (document.querySelector(".family-serial-no")?.value || "").trim(),
                sex: sexValue,
                fourps_status: fourpsValue
            };

            console.log("=== DEBUGGING DATA ===");
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

                    // Populate address components
                    populateAddressFromFull(data.patient.address);

                    populateSelect(".civil-status", data.patient.civil_status);
                    populateInput(".contact-number", data.patient.contact_number);
                    populateSelect(".religion", data.patient.religion);
                    populateInput(".occupation", data.patient.occupation);
                    populateSelect(".educational-attainment", data.patient.educational_attainment);
                    populateInput(".birth-weight", data.patient.birth_weight);
                    populateInput(".philhealth-member-no", data.patient.philhealth_member_no);
                    populateSelect(".category", data.patient.category);
                    populateInput(".family-serial-no", data.patient.family_serial_no);
                    
                    // Set radio buttons
                    if (data.patient.sex) {
                        const sexRadio = document.querySelector(`input[name="sex"][value="${data.patient.sex}"]`);
                        if (sexRadio) sexRadio.checked = true;
                    }

                    if (data.patient.fourps_status) {
                        const fourpsRadio = document.querySelector(`input[name="fourps_status"][value="${data.patient.fourps_status}"]`);
                        if (fourpsRadio) fourpsRadio.checked = true;
                    }
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

    function populateSelect(selector, value) {
        const element = document.querySelector(selector);
        if (element) {
            const exists = Array.from(element.options).some(opt => opt.value === value);
            if (exists) {
                element.value = value;
            } else if (value) {
                // Add as new option if it doesn't exist
                const newOption = new Option(value, value, true, true);
                element.appendChild(newOption);
                element.value = value;
            }
        }
    }

    function populateAddressFromFull(fullAddress) {
        if (!fullAddress) return;

        // Parse the full address (format: "Purok/Street, Barangay, City, Province, Region")
        const parts = fullAddress.split(",").map(p => p.trim());
        
        // Set the street value
        if (parts.length >= 1) {
            const streetInput = document.getElementById("street");
            if (streetInput) {
                streetInput.value = parts[0];
                // Trigger input event to update the hidden field
                streetInput.dispatchEvent(new Event('input'));
            }
        }

        // Set the hidden field directly as well
        const hiddenField = document.getElementById("permanent_address_combined");
        if (hiddenField) {
            hiddenField.value = fullAddress;
        }

        // Ensure the address is composed when street is changed
        const streetEl = document.getElementById("street");
        if (streetEl) {
            streetEl.addEventListener("input", function() {
                const hiddenFull = document.getElementById("permanent_address_combined");
                if (hiddenFull) {
                    const r = document.getElementById("region")?.options[document.getElementById("region")?.selectedIndex]?.text || "";
                    const p = document.getElementById("province")?.options[document.getElementById("province")?.selectedIndex]?.text || "";
                    const c = document.getElementById("city")?.options[document.getElementById("city")?.selectedIndex]?.text || "";
                    const b = document.getElementById("barangay")?.options[document.getElementById("barangay")?.selectedIndex]?.text || "";
                    const s = (this.value || "").trim();
                    hiddenFull.value = [s, b, c, p, r].filter(Boolean).join(", ");
                }
            });
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
