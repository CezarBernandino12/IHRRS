document.getElementById('consentCheckbox').addEventListener('change', function () {
    document.getElementById('continueBtn').disabled = !this.checked;
  });
  
  document.getElementById('continueBtn').addEventListener('click', function () {
    // Fill hidden fields
    document.getElementById('consentModal').style.display = 'none';
    document.getElementById('consentGiven').value = "1";
    document.getElementById('consentMethod').value = "verbal"; // change if needed
    document.getElementById('consentDate').value = new Date().toISOString(); // timestamp

  });
  
  document.getElementById('declineBtn').addEventListener('click', function () {

    window.location.href = "dashboard.html"; // or use history.back()
  });
  


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

    // Adjust if birthday hasn't occurred yet this year
    if (months < 0 || (months === 0 && days < 0)) {
        years--;
        months += 12; // Adjust months
    }

    let ageText = `Age: ${years} years old`;

    ageDisplay.textContent = ageText;
    ageInput.value = years;
}

function calculateAgeAndDisplaySections() { 
    const dob = document.getElementById("dob")?.value;
    const gender = document.getElementById("sex")?.value;
    const ageField = document.getElementById("age");
    

    
    if (dob) {
    const birthDate = new Date(dob);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    if (ageField) {
        ageField.value = age;
    }
    
    } else {
    if (ageField) {
        ageField.value = ""; 
    }
    }
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





























// Function to save form data (Initial Assessment + Referral)
/// Declare global variable for saved patient ID









// No button: Save Initial Assessment only
function noButton1ClickHandler() {
    modal1.style.display = 'none';
   
    const urlParams = new URLSearchParams(window.location.search);
const patientId = urlParams.get('patient_id');
console.log("Patient ID:", patientId);

// Set hidden input
document.getElementById("patient_id").value = patientId;
    
    if (patientId) {
        document.getElementById("patient_id").value = patientId;
    } else {
        alert("❌ Error: Missing patient ID in URL.");
        return;
    }

    // Now submit the form directly from the yesButton1 click
    const form = document.getElementById("individualRecordForm"); // Change selector if needed
    const formData = new FormData(form);

    // Append the patient ID again just in case
    formData.append("patient_id", patientId);

 
    fetch("php/saveInitialAssessment1.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text()) // <-- parse as text first
    .then(text => {
        console.log("📄 Raw response from server:", text);
    
        try {
            const data = JSON.parse(text); // attempt to parse JSON
            if (data.status === "success") {
                modal4.style.display = 'block';
                alert("✔ Visit summary saved successfully!");
            } else {
                modal5.style.display = 'block';
                alert("❌ Error saving visit: " + (data.message || "Unknown error."));
            }
        } catch (err) {
            alert("❌ Error parsing server response. Check console.");
            console.error("❌ JSON Parse Error:", err);
        }
    })
    .catch(error => {
        console.error("❌ Fetch error:", error);
        alert("❌ Network error. " + error.message);
    });
    
   
}


// Remove and re-add event listener to avoid duplication
noButton1.removeEventListener('click', noButton1ClickHandler);
noButton1.addEventListener('click', noButton1ClickHandler);




// Hide modal1 and show modal2 on yesButton1 click
yesButton1.addEventListener('click', function (event) { 
    event.preventDefault(); // Prevent default behavior

    modal1.style.display = 'none'; // Hide modal1
    modal2.style.display = 'block'; // Show modal2

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

    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get('patient_id');
    console.log("Patient ID:", patientId);

    if (!patientId) {
        alert("❌ Error: Missing patient ID in URL.");
        return;
    }

    // Set hidden inputs
    document.getElementById("patient_id").value = patientId;
    document.getElementById("referral_needed").value = "yes";

    // Correct selector: assuming it's an id
    const form = document.getElementById("individualRecordForm");
    const formData = new FormData(form);

    // Just in case
    formData.append("patient_id", patientId);
    formData.append("referral_needed", "yes");

    fetch("php/saveInitialAssessment1.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log("✅ Server response:", data);
        if (data.status === "success") {
            alert("✔ Visit summary saved successfully!");
        } else {
            alert("❌ Error saving visit: " + (data.message || "Unknown error."));
        }
    })
    .catch(error => {
        console.error("❌ Fetch error:", error);
        alert("❌ Network error. " + error.message);
    });
    let bhwId = document.getElementById('user_id')?.value;
    saveReferral(patientId, bhwId);
    modal3.style.display = 'block';
});



// Function to save referral
function saveReferral(patientId, bhwId) {
    let formData = new FormData();
    formData.append("patient_id", patientId);
    formData.append("user_id", bhwId);

    // Always include referral_date if available in the form
    let referralDateField = document.getElementById("referral_date");
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
closeBtn4.addEventListener("click", () => {
const urlParams = new URLSearchParams(window.location.search);
const patientId = urlParams.get('patient_id');

        // redirect with patient_id in URL
        window.location.href = `record.html?patient_id=${encodeURIComponent(patientId)}`;
  
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
