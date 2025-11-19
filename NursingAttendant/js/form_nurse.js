function calculateAge() {
    const dobInput = document.getElementById("dob");
    const ageDisplay = document.getElementById("age-display");
    const ageInput = document.getElementById("age-input");
    if (!dobInput) return;
    const dobValue = dobInput.value;

    if (!dobValue) {
        if (ageDisplay) ageDisplay.textContent = "Age: ";
        if (ageInput) ageInput.value = "";
        return;
    }

    const dob = new Date(dobValue);
    const today = new Date();

    let years = today.getFullYear() - dob.getFullYear();
    let months = today.getMonth() - dob.getMonth();
    let days = today.getDate() - dob.getDate();

    if (days < 0) {
        months--;
        days += new Date(today.getFullYear(), today.getMonth(), 0).getDate();
    }

    if (months < 0) {
        years--;
        months += 12;
    }

    let ageText = "";

    if (years === 0) {
        ageText = `Age: ${months} month${months !== 1 ? 's' : ''} old`;
        if (ageInput) ageInput.value = `0.${months}`;
    } else {
        ageText = `Age: ${years} year${years !== 1 ? 's' : ''} old`;
        if (ageInput) ageInput.value = years;
    }

    if (ageDisplay) ageDisplay.textContent = ageText;
}

document.addEventListener("DOMContentLoaded", () => {

    /// helper utilities
    // Safe element getter
    const $ = (id) => document.getElementById(id);

    // Safe event binder: no-op if element is null
    const on = (el, evt, fn) => { if (el) el.addEventListener(evt, fn); };

    // Debug helper
    const dbg = (...args) => { console.log(...args); };

    /// Declare global variable for saved patient ID
    let savedPatientId = null;

    // Grab elements (may be null if not present on the page)
    const submitButton = $('submitButton');
    const modal1 = $('myModal');
    const closeBtn1 = $('closeBtn');
    const svButton = $('saveButton');
    const yesButton1 = $('yesButton');
    const noButton1 = $('noButton');

    const modal4 = $('myModal4');
    const closeBtn4 = $('closeBtn4');
    const cancelButton = $('cancelButton'); // patientExistsModal's cancel
    const proceedButton = $('proceedButton');

    const modal5 = $('myModal5');
    const closeBtn5 = $('closeBtn5');
    const exitButton = $('exitButton');

    const modal2 = $('myModal2');
    const closeBtn2 = $('closeBtn2');
    const noButton2 = $('noButton2');
    const yesButton2 = $('yesButton2');

    const modal3 = $('myModal3');
    const closeBtn3 = $('closeBtn3');
    const diagButton = $('diagButton');
    const patientExistsModal = $('patientExistsModal');
    const closeBtn6 = $('closeBtn6'); // X on patientExistsModal
    const doneButton = $('doneButton');

    const errorModal = $('errorModal');
    const errorCloseBtn = $('errorCloseBtn');

    dbg("Elements:", {
        submitButton, modal1, closeBtn1, svButton, yesButton1,
        modal2, modal3, modal4, modal5, diagButton, patientExistsModal, closeBtn6
    });

    // Show first modal when submit button is clicked
    on(submitButton, 'click', function (event) {
        if (event && typeof event.preventDefault === 'function') event.preventDefault();
        if (modal1) modal1.style.display = 'block';
    });

    // Close first modal
    on(closeBtn1, 'click', function () {
        if (modal1) modal1.style.display = 'none';
    });
 on(errorCloseBtn, 'click', function () {
        errorModal.style.display = 'none';
    });
    on(doneButton, 'click', function () {
        if (modal3) modal1.style.display = 'none';
        try {
             window.location.href = `pending.html`;

         } catch (e) {}
    });
 
    

// No button: Save Initial Assessment only
function noButton1ClickHandler() {
    if (modal1) modal1.style.display = 'none';
    let isNewPatient = document.getElementById("addNewBtn").clicked;  // Detect if "Add New" was clicked
    let existingPatientId = savedPatientId || null;
    
    saveFormData("no", isNewPatient, existingPatientId, function () {
        modal4.style.display = 'block';
    });
}
       // Remove and re-add event listener to avoid duplication
       noButton1.removeEventListener('click', noButton1ClickHandler);
       noButton1.addEventListener('click', noButton1ClickHandler);

    // Function to show the modal when a patient exists
    function showPatientExistsModal(patientId) {
        let modal = $('patientExistsModal');
        let prevModal = $('myModal2');
        if (prevModal) prevModal.style.display = 'none'; // Hide previous modal if
        if (!modal) {
            console.warn("patientExistsModal not found in DOM");
            return;
        }
        modal.style.display = "block";

        savedPatientId = patientId; // Store globally for button handlers

        const useExistingBtn = $('useExistingBtn');
        const addNewBtn = $('addNewBtn');
        const cancelBtn = $('cancelButton'); // correct id for cancel inside patientExistsModal

        on(useExistingBtn, 'click', function () {
            dbg("✅ Using existing patient ID:", savedPatientId);

            let formElem = $('individualRecordForm');
            if (!formElem) {
                alert("Form not found.");
                return;
            }
            let formData = new FormData(formElem);
            formData.append("existing_patient_id", savedPatientId);

            fetch('php/saveInitialAssessment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                dbg("🔹 Server Response (useExisting):", text);

                try {
                    let data = JSON.parse(text);
                    if (data.status === "success") {
                        dbg("✅ Data successfully saved under patient ID:", savedPatientId);
                        alert("Patient record saved successfully.");
                        modal.style.display = "none";
                        modal4.style.display = "block"; 
                    } else {
                        errorModal.style.display = "block";
                        dbg("❌ Error saving record:", data.message);
                        alert("Error saving record: " + (data.message || "Unknown error"));
                    }
                } catch (error) {
                    errorModal.style.display = "block";
                    dbg("❌ JSON Parsing Error (useExisting):", error);
                    alert("Invalid response from server.");
                }
            })
            .catch(error => {
                errorModal.style.display = "block";
                dbg("❌ Fetch Error (useExisting):", error);
                alert("Network error. Please try again.");
            });
        });

        on(addNewBtn, 'click', function () {
            dbg("🆕 Adding new patient...");

            let formElem = $('individualRecordForm');
            if (!formElem) {
                alert("Form not found.");
                return;
            }
            let formData = new FormData(formElem);
            formData.append("is_new_patient", "true");

            fetch('php/saveInitialAssessment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                dbg("🔹 Server Response (addNew):", text);

                try {
                    let data = JSON.parse(text);
                    if (data.status === "success") {
                        dbg("✅ New patient successfully added with ID:", data.patient_id);
                        savedPatientId = data.patient_id;  // Store the new patient ID
                        alert("New patient record saved successfully.");
                        modal.style.display = "none";
                         modal4.style.display = "block";  // Hide modal only if successful
                    } else {
                        errorModal.style.display = "block";
                        dbg("❌ Error saving new patient:", data.message);
                        alert("Error saving record: " + (data.message || "Unknown error"));
                    }
                } catch (error) {
                    errorModal.style.display = "block";
                    dbg("❌ JSON Parsing Error (addNew):", error);
                    alert("Invalid response from server.");
                }
            })
            .catch(error => {
                dbg("❌ Fetch Error (addNew):", error);
                alert("Network error. Please try again.");
            });
        });

        on(cancelBtn, 'click', function (e) {
            if (e && typeof e.preventDefault === 'function') e.preventDefault();
            dbg("❌ Patient-exists modal cancelled.");
            modal.style.display = "none";
        });
    }

// Function to save form data (Initial Assessment + Referral)
function saveFormData(referralNeeded, callback) {
    const formElem = document.getElementById('individualRecordForm');
    if (!formElem) {
        alert("Form not found.");
        return;
    }

    let formData = new FormData(formElem);
    formData.append("referralNeeded", referralNeeded);

    // ---------------------------------------------
    // Add referral fields ONLY if referral is needed
    // ---------------------------------------------
    if (referralNeeded === "yes") {
        let bhwElem = document.getElementById('user_id');
        let bhwId = bhwElem ? bhwElem.value : "";

        if (!bhwId) {
            alert("Error: No user_id (BHW ID) provided.");
            console.error("❌ Missing BHW ID.");
            return;
        }

        formData.append("user_id", bhwId);
        formData.append("referral_status", "pending");
    }

    fetch("php/saveInitialAssessment.php", {
        method: "POST",
        body: formData
    })
    .then(async (response) => {
        let raw = await response.text();

        // Log raw response for debugging
        console.log("📥 Raw server response:", raw);

        // Try to parse JSON safely
        let json;
        try {
            json = JSON.parse(raw);
        } catch (e) {
            console.error("❌ Invalid JSON received:", raw);
            alert("⚠ Server returned invalid JSON. Check PHP output in console.");
            return null;
        }

        return json;
    })
    .then((data) => {
        if (!data) return;

        console.log("🔹 Parsed Response:", data);

        // -------------------------------------------------
        // CASE 1 — Duplicate Patient
        // -------------------------------------------------
        if (data.status === "duplicate") {
            if (data.patient_id) {
                showPatientExistsModal(data.patient_id);
            } else {
                alert("Duplicate detected, but patient_id missing in response.");
            }
            return;
        }

        // -------------------------------------------------
        // CASE 2 — SUCCESS
        // -------------------------------------------------
        if (data.status === "success") {
            savedPatientId = data.patient_id;
            console.log("✅ Saved patient_id:", savedPatientId);

            // Save visit_id for referral later
            if (data.visit_id) {
                localStorage.setItem("visit_id", data.visit_id);
                console.log("💾 Saved visit_id:", data.visit_id);
            } else {
                console.warn("⚠️ No visit_id returned by backend.");
                if (window.modal4) modal4.style.display = "block";
            }

            // Callback function (ex: run saveReferral)
            if (typeof callback === "function") callback();
            return;
        }

        // -------------------------------------------------
        // CASE 3 — ERROR
        // -------------------------------------------------
        if (data.status === "error") {
            console.error("❌ PHP Error:", data.message);

            let details = data.error_details ? "\n\nDETAILS:\n" + data.error_details : "";
            alert("❌ ERROR: " + data.message + details);

            if (window.errorModal) errorModal.style.display = "block";
            return;
        }

        // -------------------------------------------------
        // CASE 4 — Unexpected response
        // -------------------------------------------------
        console.warn("⚠ Unknown response format:", data);
        alert("Unexpected server response. Check console output.");
    })
    .catch((error) => {
        console.error("❌ Fetch Error:", error);
        alert("Network error. Please check your connection.");
        if (window.errorModal) errorModal.style.display = "block";
    });
}


 // No button: Save Initial Assessment only
    function svButtonClickHandler(event) {
        if (event && typeof event.preventDefault === 'function') event.preventDefault();
        if (modal1) modal1.style.display = 'none';
        if (modal2) modal2.style.display = 'block';
  /* 
        // Use savedPatientId if set; the server flow handles duplicates/new patient modal
        saveFormData("no", function () {
            if (modal2) modal2.style.display = 'block';
        });*/
    } 
      // Attach save button listener safely
    if (svButton) {
        // ensure no duplicate listeners: remove if present then add
        try { svButton.removeEventListener('click', svButtonClickHandler); } catch (e) {}
        svButton.addEventListener('click', svButtonClickHandler);
    } else {
        dbg("saveButton not found in DOM");
    }

 
    // Yes button: Save Initial Assessment and Referral
    on(yesButton1, 'click', function (event) {
        if (event && typeof event.preventDefault === 'function') event.preventDefault();
        if (modal1) modal1.style.display = 'none';
        if (modal2) modal2.style.display = 'block';
        saveFormData("yes", function () {
            // Referral step happens later in modal2
        });
    });

    // Close modal2
    on(closeBtn2, 'click', function () {
        if (modal2) modal2.style.display = 'none';
    });

    // No button in modal2: Proceed without referral
    on(noButton2, 'click', function () {
        if (modal2) modal2.style.display = 'none';
        if (modal1) modal.style.display = 'block';
    });

    // Yes button in modal2: Confirm referral
    on(yesButton2, 'click', function (event) {
    if (event && typeof event.preventDefault === 'function') event.preventDefault();

    if (modal1) modal1.style.display = 'none';
  

    // Save patient form data first
    saveFormData("no", function () {
        if (!savedPatientId) {
            console.error("❌ Error: No patient ID available.");
            alert("Error: No patient ID available.");
            return;
        }

        let bhwId = $('user_id') ? $('user_id').value : null;
        if (!bhwId) {
            console.error("❌ Error: No BHW ID provided.");
            alert("Error: No BHW ID provided.");
            return;
        }

  // ✅ Declare visitId BEFORE using it
    let visitId = localStorage.getItem("visit_id");
    if (!visitId) {
        console.error("❌ Error: No visit ID found in localStorage.");
        alert("Error: Missing visit ID.");
        return;
    }

    dbg("📤 Saving Referral for:", { savedPatientId, bhwId, visitId });

    // Save referral
    saveReferral(savedPatientId, bhwId, visitId);


        // Move to next modal after successful referral
        if (modal2) modal2.style.display = 'none';
        if (modal4) modal4.style.display = 'none';
       
    });
});

   // Attach save button listener safely
    if (yesButton2) {
        // ensure no duplicate listeners: remove if present then add
        try { yesButton2.removeEventListener('click', svButtonClickHandler); } catch (e) {}
        yesButton2.addEventListener('click', svButtonClickHandler);
    } else {
        dbg("saveButton not found in DOM");
    }

   // Function to save referral
// Function to save referral
function saveReferral(patientId, bhwId, visitId) {
    let formData = new FormData();
    formData.append("patient_id", patientId);
    formData.append("user_id", bhwId);
    formData.append("visit_id", visitId);

    fetch('php/saveReferral.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        let text = await response.text();

        console.log("📥 Raw Referral Response:", text);

        // Attempt to parse JSON safely
        let json;
        try {
            json = JSON.parse(text);
        } catch (e) {
            console.error("❌ Failed to parse JSON:", text);
            alert("⚠ Server returned invalid JSON. Check console for details.");
            return null;
        }

        return json;
    })
    .then(data => {
        if (!data) return;

        // ------------------------------------------------
        // Show PHP warnings/notices (very important)
        // ------------------------------------------------
        if (data.error_details) {
            console.warn("⚠️ PHP Warnings/Notices from saveReferral.php:\n", data.error_details);
        }

        // ------------------------------------------------
        // SUCCESS
        // ------------------------------------------------
        if (data.status === "success") {

            console.log("📌 Referral saved successfully!");
            modal3.style.display = 'block';

            if (data.referral_id) {
                console.log("📌 Referral ID:", data.referral_id);
                localStorage.setItem("referral_id", data.referral_id);
            } else {
                console.warn("⚠ Referral saved but no referral_id returned.");
            }

            if (window.modal3) modal3.style.display = 'block';
            return;
        }

        // ------------------------------------------------
        // ERROR
        // ------------------------------------------------
        if (data.status === "error") {
            let message = data.message || "Unknown error";

            if (data.error_details) {
                message += "\n\nDetails:\n" + data.error_details;
            }

            console.error("❌ Error saving referral:", message);

            if (window.errorModal) errorModal.style.display = "block";
            alert(message);

            return;
        }

        // ------------------------------------------------
        // Unexpected response
        // ------------------------------------------------
        console.warn("⚠️ Unexpected response format:", data);
        alert("Unexpected server response. See console.");
    })
    .catch(error => {
        console.error("❌ Fetch Error (saveReferral):", error);
        alert("Network error. Please try again.");
        if (window.errorModal) errorModal.style.display = "block";
    });
}


    // Close modal3
    on(closeBtn3, 'click', function () {
        if (modal3) modal3.style.display = 'none';
    });

    // diagButton (redirect to UpdateVisitInfo) - defensive and prevents default if inside a form
    on(diagButton, 'click', function (e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();

        // hide modals safely
        if (modal2) modal2.style.display = 'none';
        if (modal3) modal3.style.display = 'none';
        if (modal4) modal4.style.display = 'none';
        if (modal5) modal5.style.display = 'none';
        if (patientExistsModal) patientExistsModal.style.display = 'none';

        let visitId = localStorage.getItem('visit_id');

        if (!visitId) {
            alert("❌ Error: No visit ID found. Please submit a referral first.");
            return;
        }

        dbg("🔹 Redirecting to: UpdateVisitInfo.html?visit_id=" + encodeURIComponent(visitId));
        window.location.href = `UpdateVisitInfo.html?visit_id=${encodeURIComponent(visitId)}`;
    });

// Close modal4 (X)
on(closeBtn4, 'click', function () {
    if (modal4) modal4.style.display = 'none';
    if (savedPatientId) {
        window.location.href = `record.html?patient_id=${encodeURIComponent(savedPatientId)}`;
    } else {
        console.warn("⚠️ No patient ID found, staying on page.");
    }
});

    // Cancel button in patientExistsModal
    on(cancelButton, 'click', function (e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        if (patientExistsModal) patientExistsModal.style.display = 'none';
    });

    // Close patientExistsModal via X (closeBtn6)
    on(closeBtn6, 'click', function () {
        if (patientExistsModal) patientExistsModal.style.display = 'none';
    });

    // Proceed button in modal4
    on(proceedButton, 'click', function () {
        if (modal4) modal4.style.display = 'none';
        if (modal5) modal5.style.display = 'block';
    });

    // Close modal5
    on(closeBtn5, 'click', function () {
        if (modal5) modal5.style.display = 'none';
    });

    // Exit button in modal5
    on(exitButton, 'click', function () {
        if (modal5) modal5.style.display = 'none';
    });

    // Close modals if user clicks outside (added patientExistsModal too)
    window.addEventListener('click', function (event) {
        [modal1, modal2, modal3, modal4, modal5, patientExistsModal].forEach(modal => {
            if (modal && event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    dbg("✅ Event bindings completed.");
});
