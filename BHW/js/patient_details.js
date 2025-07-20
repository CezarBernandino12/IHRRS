
document.addEventListener("DOMContentLoaded", function() {
    if (typeof patientData !== "undefined") {
        document.querySelector(".last-name").textContent = patientData.last_name;
        document.querySelector(".first-name").textContent = patientData.first_name;
        document.querySelector(".middle-name").textContent = patientData.middle_name;
        document.querySelector(".id-number").textContent = patientData.patient_id;

        document.querySelectorAll(".detail")[0].querySelector(".underline").textContent = patientData.address;
        document.querySelectorAll(".detail")[1].querySelector(".underline").textContent = calculateAge(patientData.date_of_birth);
        document.querySelectorAll(".detail")[2].querySelector(".underline").textContent = patientData.sex;
        document.querySelectorAll(".detail")[3].querySelector(".underline").textContent = patientData.date_of_birth;
        document.querySelectorAll(".detail")[4].querySelector(".underline").textContent = patientData.contact_number;
        document.querySelectorAll(".detail")[5].querySelector(".underline").textContent = patientData.family_serial_no;
        document.querySelectorAll(".detail")[6].querySelector(".underline").textContent = patientData.civil_status;
        document.querySelectorAll(".detail")[7].querySelector(".underline").textContent = patientData.birthplace;
        document.querySelectorAll(".detail")[8].querySelector(".underline").textContent = patientData.educational_attainment;
        document.querySelectorAll(".detail")[9].querySelector(".underline").textContent = patientData.occupation;
        document.querySelectorAll(".detail")[10].querySelector(".underline").textContent = patientData.religion;
        document.querySelectorAll(".detail")[11].querySelector(".underline").textContent = patientData.philhealth_member_no;
        document.querySelectorAll(".detail")[12].querySelector(".underline").textContent = patientData['fourps_status'];
    }
});

// Function to calculate age from date of birth
function calculateAge(dob) {
    let birthDate = new Date(dob);
    let today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    let monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age + " years old";
}

/* function calculateAge() {
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
*/