// ...existing code...
function calculateAge() {
        const dobInput = document.getElementById("dob");
        const ageDisplay = document.getElementById("age-display");
        const ageInput = document.getElementById("age-input"); 
        const dobValue = dobInput.value;
    
        if (!dobValue) {
            ageDisplay.textContent = "Age: ";
            ageInput.value = ""; 
            return;
        }
    
        const dob = new Date(dobValue);
        const today = new Date();
    
        let years = today.getFullYear() - dob.getFullYear();
        let months = today.getMonth() - dob.getMonth();
        let days = today.getDate() - dob.getDate();
    
        if (days < 0) {
            months--;
            days += new Date(today.getFullYear(), today.getMonth(), 0).getDate(); // Days in previous month
        }
    
        if (months < 0) {
            years--;
            months += 12;
        }
    
        let ageText = "";
    
        if (years === 0) {
            ageText = `Age: ${months} month${months !== 1 ? 's' : ''} old`;
            ageInput.value = `0.${months}`; // e.g., 0.6 for 6 months
        } else {
            ageText = `Age: ${years} year${years !== 1 ? 's' : ''} old`;
            ageInput.value = years;
        }
    
        ageDisplay.textContent = ageText;
    }
    
// ...existing code...

/// Declare global variable for saved patient ID
let savedPatientId = null;

const submitButton = document.getElementById('submitButton');
const modal1 = document.getElementById('myModal');
const closeBtn1 = document.getElementById('closeBtn');
const noButton1 = document.getElementById('noButton');
const yesButton1 = document.getElementById('yesButton');

const modal4 = document.getElementById('myModal4');
const closeBtn4 = document.getElementById('closeBtn4');
const cancelButton = document.getElementById('cancelButton');
const proceedButton = document.getElementById('proceedButton');

const modal5 = document.getElementById('myModal5');
const closeBtn5 = document.getElementById('closeBtn5');
const exitButton = document.getElementById('exitButton');

const modal2 = document.getElementById('myModal2');
const closeBtn2 = document.getElementById('closeBtn2');
const noButton2 = document.getElementById('noButton2');
const yesButton2 = document.getElementById('yesButton2');

const modal3 = document.getElementById('myModal3');
const closeBtn3 = document.getElementById('closeBtn3');
const viewDetailsButton = document.getElementById('viewDetailsButton');

errorModal = document.getElementById('errorModal');
const closeBtnError = document.getElementById('errorCloseBtn');

// Helper: get BHW/user id — prefer element value, then localStorage, then URL param
function getBhwId() {
    const el = document.getElementById('user_id');
    if (el) {
        // element might be input or hidden field
        if (typeof el.value !== 'undefined' && el.value !== '') return el.value;
        if (typeof el.textContent !== 'undefined' && el.textContent.trim() !== '') return el.textContent.trim();
    }
    const fromStorage = localStorage.getItem('user_id');
    if (fromStorage) return fromStorage;
    const fromUrl = new URLSearchParams(window.location.search).get('user_id');
    if (fromUrl) return fromUrl;
    return null;
}

const checkToday = document.getElementById("setToday");
const checkTomorrow = document.getElementById("setTomorrow");
const referralDateInput = document.getElementById("referral_date");

// Disable Yes button initially
if (yesButton2) {
    yesButton2.disabled = true;
    yesButton2.style.opacity = "0.5";
}

// Function to handle checkbox selection
function handleCheckboxChange(selectedCheckbox) {
    // Only allow one checkbox to be checked
    if (selectedCheckbox === checkToday && checkToday.checked) {
        checkTomorrow.checked = false;
    } else if (selectedCheckbox === checkTomorrow && checkTomorrow.checked) {
        checkToday.checked = false;
    }

    // Enable Yes button only if a checkbox is checked
    if (checkToday.checked || checkTomorrow.checked) {
        yesButton2.disabled = false;
        yesButton2.style.opacity = "1";
    } else {
        yesButton2.disabled = true;
        yesButton2.style.opacity = "0.5";
    }

    // Set referral_date input
    let date = new Date();
    if (checkTomorrow.checked) {
        date.setDate(date.getDate() + 1); // tomorrow
    }
    // Format date as yyyy-mm-dd
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, "0");
    const dd = String(date.getDate()).padStart(2, "0");
    referralDateInput.value = `${yyyy}-${mm}-${dd}`;
}

// Event listeners
if (checkToday) checkToday.addEventListener("change", () => handleCheckboxChange(checkToday));
if (checkTomorrow) checkTomorrow.addEventListener("change", () => handleCheckboxChange(checkTomorrow));

// ...existing code...

// Show first modal when submit button is clicked
submitButton.addEventListener('click', function (event) {
    event.preventDefault();
    modal1.style.display = 'block';
});

// Close first modal
closeBtn1.addEventListener('click', function () {
    modal1.style.display = 'none';
}); 


// Function to show the modal when a patient exists
function showPatientExistsModal(patientId) {
    let modal = document.getElementById("patientExistsModal");

    // Hide other modals if open
    if (modal1.style.display === "block") modal1.style.display = "none";
   

    modal.style.display = "block";
    savedPatientId = patientId; // Store globally

    // Use Existing button
    document.getElementById("useExistingBtn").onclick = function () {
        console.log("✅ Using existing patient ID:", savedPatientId);

        let formData = new FormData(document.getElementById("individualRecordForm"));
        formData.append("existing_patient_id", savedPatientId);

        fetch("php/saveInitialAssessment.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log("🔹 Server Response:", text);

            try {
                let data = JSON.parse(text);
                if (data.status === "success") {
                    console.log("✅ Data saved under patient ID:", savedPatientId);
                    localStorage.setItem("patient_id", savedPatientId);

                    // If we came from referral modal, also save referral
                    if (modal2 && modal2.style.display === "block") {
                        let bhwId = getBhwId();
                        if (bhwId) {
                            const visitId = data.visit_id || null;
                            saveReferral(savedPatientId, visitId, bhwId);
                        }
                    }

                    modal.style.display = "none";
                    modal4.style.display = "block";

                     
                } else {
                    errorModal.style.display = "block";
                    console.error("❌ Error saving record:", data.message);
                }
            } catch (error) {
                errorModal.style.display = "block";
                console.error("❌ JSON Parsing Error:", error);
            }
        })
        .catch(error => {
            errorModal.style.display = "block";
            console.error("❌ Fetch Error:", error);
        });
    };

    // Add New button
    document.getElementById("addNewBtn").onclick = function () {
        console.log("🆕 Adding new patient...");

        let formData = new FormData(document.getElementById("individualRecordForm"));
        formData.append("is_new_patient", "true");

        fetch("php/saveInitialAssessment.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log("🔹 Server Response:", text);

            try {
                let data = JSON.parse(text);
                if (data.status === "success") {
                    savedPatientId = data.patient_id;
                    const visitId = data.visit_id || null;
                    console.log("✅ New patient added with ID:", savedPatientId);
                    localStorage.setItem("patient_id", savedPatientId);

                    // If we came from referral modal, also save referral
                    if (modal2 && modal2.style.display === "block") {
                        let bhwId = getBhwId();
                        if (bhwId) saveReferral(savedPatientId, visitId, bhwId);
                    }

                    modal.style.display = "none";
                    modal4.style.display = "block";
   // Record added
                } else {
                    errorModal.style.display = "block";
                    console.error("❌ Error saving new patient:", data.message);
                }
            } catch (error) {
                errorModal.style.display = "block";
                console.error("❌ JSON Parsing Error:", error);
            }
        })
        .catch(error => {
            errorModal.style.display = "block";
            console.error("❌ Fetch Error:", error);
        });
    };

    // Cancel button
    document.getElementById("cancelBtn").onclick = function () {
        console.log("❌ Cancelled.");
        modal.style.display = "none";
    };
}

// Function to save form data (Initial Assessment + Referral)
// ...existing code...
function saveFormData(referralNeeded, callback) {
    let formData = new FormData(document.getElementById('individualRecordForm'));
    formData.append("referralNeeded", referralNeeded);

    if (referralNeeded === "yes") {  // Ensure referralNeeded is "yes" before checking bhw_id
        const bhwId = getBhwId();

        if (!bhwId) {
            console.error("❌ Error: No BHW ID provided.");
            alert("Error: No BHW ID provided.");
            return;
        }

        formData.append("user_id", bhwId);
        formData.append("referral_status", "pending");
    }
 
    fetch('php/saveInitialAssessment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log("🔹 Server Response:", data);

        if (data.status === 'duplicate' && data.patient_id) {
            showPatientExistsModal(data.patient_id);

        } else if (data.status === 'success') {
            savedPatientId = data.patient_id;  
            console.log("✅ Saved Patient ID:", savedPatientId);
            localStorage.setItem("patient_id", savedPatientId); // Store patient ID in localStorage
            if (modal2 && modal2.style.display === "block") modal2.style.display = "none";
            
            if (referralNeeded === "yes") {
                if (modal2) modal2.style.display = 'block'; // Show confirmation modal for referral
            } else {
                if (modal4) modal4.style.display = 'block'; // Show success modal for saving without referral
            }

            // ✅ FIXED: pass `data` to the callback so the next function gets visit_id and patient_id
            if (typeof callback === "function") callback(data);

        } else {
            alert('❌ Error: ' + (data.message || "Unknown error."));
            if (errorModal) errorModal.style.display = 'block';
            if (modal2) modal2.style.display = 'none';
        }
    })
    .catch(error => {
        console.error("❌ Fetch Error:", error);
        alert("Network error. Please check your connection.");
    });
}

// ...existing code...

// No button: Save Initial Assessment only
function noButton1ClickHandler() {
    modal1.style.display = 'none';

    // Save without referral; callback shows success modal
    saveFormData("no", function () {
        if (modal4) modal4.style.display = 'block';
    });
}


// Remove and re-add event listener to avoid duplication
noButton1.removeEventListener('click', noButton1ClickHandler);
noButton1.addEventListener('click', noButton1ClickHandler);

// Yes button: Save Initial Assessment and Referral
yesButton1.addEventListener('click', function (event) { 
    event.preventDefault(); // Prevent default behavior
    modal1.style.display = 'none'; // Hide modal1
    modal2.style.display = 'block';
  
});


// Close modal2
closeBtn2.addEventListener('click', function () {
    modal2.style.display = 'none';
});

// Cancel button in modal1 (first confirmation modal)
document.getElementById('cancelBtn').addEventListener('click', function () {
    modal1.style.display = 'none';
});

// No button in modal2: Proceed without referral
noButton2.addEventListener('click', function () {
    modal2.style.display = 'none';
});

// ✅ FIXED: Yes button in modal2 - Save form with referral (NO separate saveReferral call)
yesButton2.addEventListener('click', function () {

    // Save the form with referral included
    saveFormData("yes", function (responseData) {
        // ✅ Expecting { status: "success", patient_id: ..., visit_id: ..., referral_id: ... }
        if (!responseData || responseData.status !== "success") {
            console.error("❌ Error: Failed to save form or missing response data.");
            alert("Error: Failed to save form or missing response data.");
            return;
        }

        const savedPatientId = responseData.patient_id;
        const visitId = responseData.visit_id;
        const referralId = responseData.referral_id; // ✅ Referral already created in PHP

        if (!savedPatientId) {
            console.error("❌ Error: No patient ID available for referral.");
            alert("Error: No patient ID available for referral.");
            return;
        }

        if (!visitId) {
            console.error("❌ Error: No visit ID available for referral.");
            alert("Error: No visit ID available for referral.");
            return;
        }

        if (!referralId) {
            console.error("❌ Error: No referral ID returned from server.");
            alert("Error: No referral ID returned from server.");
            return;
        }

        console.log("✅ Referral saved successfully:", { savedPatientId, visitId, referralId });
        
        // Store referral ID for printing
        localStorage.setItem("referral_id", referralId);

        // Close modal2 and show success modal
        if (modal2) modal2.style.display = 'none';
        if (modal3) modal3.style.display = 'block'; // Show referral saved modal    
    });
});

//Close button and Cancel button for Patient Exists Modal
const patientExistsModal = document.getElementById('patientExistsModal');
const closeBtnPatientExists = document.querySelector('#patientExistsModal .close-btn');
const cancelBtnPatientExists = document.getElementById('cancelBtnPatientExists');

if (closeBtnPatientExists) {
    closeBtnPatientExists.addEventListener('click', function() {
        patientExistsModal.style.display = 'none';
        console.log("❌ Patient exists modal closed via X button.");
    });
}

if (cancelBtnPatientExists) {
    cancelBtnPatientExists.addEventListener('click', function() {
        patientExistsModal.style.display = 'none';
        console.log("❌ Patient exists modal cancelled.");
    });
}

// ✅ Function to save referral
function saveReferral(patientId, visitId, bhwId) {
    const formData = new FormData();
    formData.append("patient_id", patientId);
    // visitId may be null if not provided by server; still append if present
    if (typeof visitId !== 'undefined' && visitId !== null) {
        formData.append("visit_id", visitId); // ✅ Include visit ID
    }
    formData.append("user_id", bhwId);

    const referralDateField = document.getElementById("referral_date");
    if (referralDateField) {
        formData.append("referral_date", referralDateField.value);
    }

    fetch('php/saveReferral.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            localStorage.setItem("referral_id", data.referral_id);
            console.log("📌 Referral saved successfully:", data.referral_id);

            if (typeof modal4 !== "undefined" && modal4.style.display === "block") {
                modal4.style.display = "none";
            }
            if (typeof modal3 !== "undefined") {
                modal3.style.display = "block"; // Show referral saved modal
            }
        } else {
            console.error("❌ Error saving referral:", data.message);
            alert("Error: " + (data.message || "Unknown error"));
            if (typeof errorModal !== "undefined") errorModal.style.display = "block";
        }
    })
    .catch(error => {
        console.error("❌ Fetch Error:", error);
        alert("Network error. Please try again.");
        if (typeof errorModal !== "undefined") errorModal.style.display = "block";
    });
}

// ...existing code...

// Close modal3
closeBtn3.addEventListener('click', function () {
 modal3.style.display = "none"; // Redirect to ITR.html
});

// View Details button functionality
if (viewDetailsButton) {
    viewDetailsButton.addEventListener('click', function () {
        let referralId = localStorage.getItem('referral_id') || new URLSearchParams(window.location.search).get('referral_id'); 

        if (!referralId) {
            alert("❌ Error: No referral ID found. Please submit a referral first.");
            return;
        }

        console.log("🔹 Redirecting to: details.html?referral_id=" + encodeURIComponent(referralId));
        window.location.href = `details.html?referral_id=${encodeURIComponent(referralId)}`;
    });
} else {
    console.warn("⚠️ Warning: viewDetailsButton not found in the DOM.");
}

// Close modal4
closeBtn4.addEventListener('click', function () {
    modal4.style.display = 'none';
    window.location.href = `record.html?patient_id=${localStorage.getItem('patient_id')}`; // Redirect to patient records
  
});

// Cancel button in modal4
cancelButton.addEventListener('click', function () {
    modal4.style.display = 'none';
});

// Proceed button in modal4
proceedButton.addEventListener('click', function () {
    modal4.style.display = 'none';
    modal5.style.display = 'block';
});

// Close modal5
closeBtn5.addEventListener('click', function () {
    modal5.style.display = 'none';
});

// Exit button in modal5
exitButton.addEventListener('click', function () {
    modal5.style.display = 'none';
});

closeBtnError.addEventListener('click', function () {
    errorModal.style.display = 'none';
});

// Close modals if user clicks outside
window.addEventListener('click', function (event) {
    [modal1, modal2, modal3, modal4, modal5].forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
// ...existing code...