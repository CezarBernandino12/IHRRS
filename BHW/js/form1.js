const _consentCheckbox = document.getElementById('consentCheckbox');
const _continueBtn = document.getElementById('continueBtn');
const _declineBtn = document.getElementById('declineBtn');

if (_consentCheckbox && _continueBtn) {
  _consentCheckbox.addEventListener('change', function () {
    _continueBtn.disabled = !this.checked;
  });

  _continueBtn.addEventListener('click', function () {
    document.getElementById('consentModal').style.display = 'none';
    document.getElementById('consentGiven').value = "1";
    document.getElementById('consentMethod').value = "verbal";
    document.getElementById('consentDate').value = new Date().toISOString();
  });
}

if (_declineBtn) {
  _declineBtn.addEventListener('click', function () {
    window.location.href = "dashboard";
  });
}
  


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

    
    if (months < 0 || (months === 0 && days < 0)) {
        years--;
        months += 12; 
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



submitButton.addEventListener('click', function (event) {
    event.preventDefault();
    modal1.style.display = 'block';
});

closeBtn1.addEventListener('click', function () {
    modal1.style.display = 'none';
});




function noButton1ClickHandler() {
    modal1.style.display = 'none';
   
    const urlParams = new URLSearchParams(window.location.search);
const patientId = urlParams.get('patient_id');
console.log("Patient ID:", patientId);

document.getElementById("patient_id").value = patientId;
    
    if (patientId) {
        document.getElementById("patient_id").value = patientId;
    } else {
        alert("❌ Error: Missing patient ID in URL.");
        return;
    }

    const form = document.getElementById("individualRecordForm"); 
    const formData = new FormData(form);

    formData.append("patient_id", patientId);

 
    fetch("php/saveInitialAssessment1.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text()) 
    .then(text => {
        console.log("📄 Raw response from server:", text);
    
        try {
            const data = JSON.parse(text); 
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

noButton1.removeEventListener('click', noButton1ClickHandler);
noButton1.addEventListener('click', noButton1ClickHandler);



yesButton1.addEventListener('click', function (event) { 
    event.preventDefault(); 

    modal1.style.display = 'none'; 
    modal2.style.display = 'block'; 

});

closeBtn2.addEventListener('click', function () {
    modal2.style.display = 'none';
});

noButton2.addEventListener('click', function () {
    modal2.style.display = 'none';
    modal4.style.display = 'block';
});

yesButton2.addEventListener('click', function () {
    modal2.style.display = 'none';

    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get('patient_id');
    console.log("Patient ID:", patientId);

    if (!patientId) {
        alert("❌ Error: Missing patient ID in URL.");
        return;
    }

    document.getElementById("patient_id").value = patientId;
    document.getElementById("referral_needed").value = "yes";

    const form = document.getElementById("individualRecordForm");
    const formData = new FormData(form);

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

            let bhwId = document.getElementById('user_id')?.value;
            let visitId = data.visit_id;

            if (!visitId) {
                console.error("❌ Missing visit_id in response.");
                alert("Error: Missing visit ID from server.");
                return;
            }

            console.log("📋 Passing to saveReferral:", { patientId, bhwId, visitId });
            saveReferral(patientId, bhwId, visitId);

            modal3.style.display = 'block';

        } else {
            alert("❌ Error saving visit: " + (data.message || "Unknown error."));
        }
    })
    .catch(error => {
        console.error("❌ Fetch error:", error);
        alert("❌ Network error. " + error.message);
    });
});


function saveReferral(patientId, bhwId, visitId) {
    let formData = new FormData();
    formData.append("patient_id", patientId);
    formData.append("user_id", bhwId);
    formData.append("visit_id", visitId); 

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
                modal3.style.display = "block";
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




closeBtn3.addEventListener('click', function () {
    modal3.style.display = 'none';
});


if (viewDetailsButton) {
    viewDetailsButton.addEventListener('click', function () {
        let referralId = localStorage.getItem('referral_id') || new URLSearchParams(window.location.search).get('referral_id'); 

        if (!referralId) {
            alert("❌ Error: No referral ID found. Please submit a referral first.");
            return;
        }

        console.log("🔹 Redirecting to: details.html?referral_id=" + encodeURIComponent(referralId));
        window.location.href = `details?referral_id=${encodeURIComponent(referralId)}`;
    });
} else {
    console.warn("⚠️ Warning: viewDetailsButton not found in the DOM.");
}

if (closeBtn4) closeBtn4.addEventListener("click", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get('patient_id');
    window.location.href = `record?patient_id=${encodeURIComponent(patientId)}`;
});

if (cancelButton) cancelButton.addEventListener('click', function () {
    modal4.style.display = 'none';
});

if (proceedButton) proceedButton.addEventListener('click', function () {
    modal4.style.display = 'none';
    modal5.style.display = 'block';
});

if (closeBtn5) closeBtn5.addEventListener('click', function () {
    modal5.style.display = 'none';
});

if (exitButton) exitButton.addEventListener('click', function () {
    modal5.style.display = 'none';
});

window.addEventListener('click', function (event) {
    [modal1, modal2, modal3, modal4, modal5].forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
