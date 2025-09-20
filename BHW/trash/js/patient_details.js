
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

