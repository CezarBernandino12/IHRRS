
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
    modal.style.display = "block";

    savedPatientId = patientId; // Store globally for button handlers

    document.getElementById("useExistingBtn").onclick = function() {
        console.log("✅ Using existing patient ID:", savedPatientId);

        let formData = new FormData(document.getElementById('individualRecordForm'));
        formData.append("existing_patient_id", savedPatientId);

        fetch('php/saveInitialAssessment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log("🔹 Server Response:", text);

            try {
                let data = JSON.parse(text);
                if (data.status === "success") {
                    console.log("✅ Data successfully saved under patient ID:", savedPatientId);
                    alert("Patient record saved successfully.");
                    modal.style.display = "none"; // Hide modal only if successful
                } else {
                    console.error("❌ Error saving record:", data.message);
                    alert("Error saving record: " + (data.message || "Unknown error"));
                }
            } catch (error) {
                console.error("❌ JSON Parsing Error:", error);
                alert("Invalid response from server.");
            }
        })
        .catch(error => {
            console.error("❌ Fetch Error:", error);
            alert("Network error. Please try again.");
        });
    };

    document.getElementById("addNewBtn").onclick = function() {
        console.log("🆕 Adding new patient...");
        
        let formData = new FormData(document.getElementById("individualRecordForm"));
        formData.append("is_new_patient", "true");
    
        fetch('php/saveInitialAssessment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log("🔹 Server Response:", text);
    
            try {
                let data = JSON.parse(text);
                if (data.status === "success") {
                    console.log("✅ New patient successfully added with ID:", data.patient_id);
                    
                    savedPatientId = data.patient_id;  // ✅ Store the new patient ID
                    
                    alert("New patient record saved successfully.");
                    modal.style.display = "none";  // Hide modal only if successful
                    
                } else {
                    console.error("❌ Error saving new patient:", data.message);
                    alert("Error saving record: " + (data.message || "Unknown error"));
                }
            } catch (error) {
                console.error("❌ JSON Parsing Error:", error);
                alert("Invalid response from server.");
            }
        })
        .catch(error => {
            console.error("❌ Fetch Error:", error);
            alert("Network error. Please try again.");
        });
    };
    

    document.getElementById("cancelBtn").onclick = function() {
        console.log("❌ Cancelled.");
        modal.style.display = "none";
    };
}

// Function to save form data (Initial Assessment + Referral)
/// Declare global variable for saved patient ID


// Function to save form data (Initial Assessment + Referral)
function saveFormData(referralNeeded, callback) {
    let formData = new FormData(document.getElementById('individualRecordForm'));
    formData.append("referralNeeded", referralNeeded);

    if (referralNeeded === "yes") {  // Ensure referralNeeded is "yes" before checking bhw_id
        let bhwElement = document.getElementById('user_id');
        let bhwId = bhwElement ? bhwElement.value : "";  // Handle null case

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
            
            if (referralNeeded === "yes") {
                modal2.style.display = 'block'; // Show confirmation modal for referral
            } else {
                modal4.style.display = 'block'; // Show success modal for saving without referral
            }

            if (typeof callback === "function") callback();
        } else {
            alert('❌ Error: ' + (data.message || "Unknown error."));
        }
    })
    .catch(error => {
        console.error("❌ Fetch Error:", error);
        alert("Network error. Please check your connection.");
    });
}






// No button: Save Initial Assessment only
function noButton1ClickHandler() {
    modal1.style.display = 'none';
    let isNewPatient = document.getElementById("addNewBtn").clicked;  // Detect if "Add New" was clicked
    let existingPatientId = savedPatientId || null;

    saveFormData("no", isNewPatient, existingPatientId, function () {
        modal4.style.display = 'block';
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
    saveFormData("yes", function () {
        // Referral step happens later in modal2
    });
});


// Close modal2
closeBtn2.addEventListener('click', function () {
    modal2.style.display = 'none';
});

// No button in modal2: Proceed without referral
noButton2.addEventListener('click', function () {
    modal2.style.display = 'none';
    modal4.style.display = 'block';
});

// Yes button in modal2: Confirm referral
yesButton2.addEventListener('click', function () {
    modal2.style.display = 'none';

    if (!savedPatientId) {
        console.error("❌ Error: No patient ID available.");
        alert("Error: No patient ID available.");
        return;
    }

    let bhwId = document.getElementById('user_id')?.value;
    if (!bhwId) {
        console.error("❌ Error: No BHW ID provided.");
        alert("Error: No BHW ID provided.");
        return;
    }

    console.log("📤 Saving Referral for:", { savedPatientId, bhwId });

    saveReferral(savedPatientId, bhwId);
    modal3.style.display = 'block';
});




// Function to save referral
function saveReferral(patientId, bhwId) {
    let formData = new FormData();
    formData.append("patient_id", patientId);
    formData.append("user_id", bhwId);

    fetch('php/saveReferral.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            localStorage.setItem("referral_id", data.referral_id);
            console.log("📌 Referral saved successfully:", data.referral_id);
        } else {
            console.error("❌ Error saving referral:", data.message);
            alert("Error: " + (data.message || "Unknown error"));
        }
    })
    .catch(error => {
        console.error("❌ Fetch Error:", error);
        alert("Network error. Please try again.");
    });
}





// Close modal3
closeBtn3.addEventListener('click', function () {
    modal3.style.display = 'none';
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
    window.history.back();
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

// Close modals if user clicks outside
window.addEventListener('click', function (event) {
    [modal1, modal2, modal3, modal4, modal5].forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
