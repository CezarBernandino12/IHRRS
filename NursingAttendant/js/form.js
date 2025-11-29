document.addEventListener("DOMContentLoaded", function () {
    const urlParams = new URLSearchParams(window.location.search);
    const visit_id = urlParams.get("visit_id");

    $patient_id = document.getElementById("patient_id");
    if (visit_id) {
        const visitIdField = document.getElementById("visit_id");
        if (visitIdField) visitIdField.value = visit_id;

        fetch(`php/get_visit_info.php?visit_id=${visit_id}`)
            .then(response => response.json())
            .then(data => {
                console.log("Received Data:", data);

                if (!data || data.error) {
                    console.error("Error:", data.error || "No data received.");
                    return;
                }

                   // ✅ Patient Info
                if (data.patient) {
                    if ($patient_id) $patient_id.value = data.patient.patient_id || "";
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

                // ✅ Visit Info
                if (data.visit) {
                    document.getElementById("visit_id").value = data.visit.visit_id || "";
                    updateElement(".visit-date", data.visit.visit_date);
                    updateElement(".patient-alert", data.visit.patient_alert);
                    updateElement(".chief-complaints", data.visit.chief_complaints);
                    updateElement(".blood-pressure", data.visit.blood_pressure);
                    updateElement(".temperature", data.visit.temperature);
                    updateElement(".weight", data.visit.weight);
                    updateElement(".height", data.visit.height);
                    updateElement(".pulse-rate", data.visit.chest_rate);
                    updateElement(".respiratory-rate", data.visit.respiratory_rate);
                    updateElement(".remarks", data.visit.remarks);
                }

                // ✅ Consultation Info
                if (data.consultation) {
                    document.getElementById("diagnosis").value = data.consultation.diagnosis || "";
                    updateElement(".diagnosis_status", data.consultation.diagnosis_status);
                    updateElement(".instruction", data.consultation.instruction_prescription);
                    updateElement(".doctor", data.consultation.full_name);
                    updateElement(".licenseNo", data.consultation.license_number);
                    updateElement(".rhu", data.consultation.rhu);
                   
 
                    // store consultation_id in a hidden field
                    const consultationField = document.getElementById("consultation_id");
                    if (consultationField) {
                        consultationField.value = data.consultation.consultation_id;
                    }
                

                    // ✅ Resume Button
const resumeBtn = document.getElementById("resumeBtn");

if (data.consultation.diagnosis_status !== "Ongoing") {
        resumeBtn.disabled = true;
        resumeBtn.style.opacity = "0.6";   // make it look disabled
        resumeBtn.style.cursor = "not-allowed";

}
                      // ✅ Resume Button
const addMedCertBtn = document.getElementById("addMedCertBtn");

if (data.consultation.diagnosis_status !== "Ongoing") {
        addMedCertBtn.disabled = true;
        addMedCertBtn.style.opacity = "0.6";   // make it look disabled
        addMedCertBtn.style.cursor = "not-allowed";

}



if (data.consultation.diagnosis_status === "") {
        addMedCertBtn.disabled = false;
        addMedCertBtn.style.opacity = "1";   
        addMedCertBtn.style.cursor = "pointer";
             
                }
             

               
                
                }


                if (data.medicine || data.rhumedicine) {
               
// ✅ Medicines (BHS)
const medicineContainer = document.querySelector(".medicine-lists");
if (medicineContainer) {
    medicineContainer.innerHTML = "";
    if (data.medicine && data.medicine.length > 0) {
        data.medicine.forEach(med => {
          medicineContainer.innerHTML += `
    <div class="medicine-item" style="margin-bottom:8px; padding:6px; border-bottom:1px solid #ddd;">
        <p><strong>Medicine:</strong> ${med.medicine_name || "None"} &nbsp;&nbsp;&nbsp; 
           <strong>Quantity:</strong> ${med.quantity_dispensed || "0"}</p>
    </div>
`;

        });
    } else {
        medicineContainer.innerHTML = "<p>No medication recorded.</p>";
    }
}

// ✅ Medicines (RHU)
const medicineContainer2 = document.querySelector(".rhu-medicine-lists");
if (medicineContainer2) {
  medicineContainer2.innerHTML = "";
  if (data.rhumedicine && data.rhumedicine.length > 0) {
    data.rhumedicine.forEach(med => {
      medicineContainer2.innerHTML += `
        <div class="medicine-item" style="padding:2px 0; border-bottom:1px solid #ddd; font-family:'Trebuchet MS', sans-serif; font-weight:lighter; line-height:1.5;">
          <span style="display:block;"><strong>Medicine:</strong> ${med.medicine_name || "None"}</span>
          <span style="display:block;"><strong>Quantity:</strong> ${med.quantity_dispensed || "0"}</span>
          <span style="display:block;"><strong>Instruction:</strong> ${med.instruction || "None"}</span>
        </div>
      `;
    });
  } else {
    medicineContainer2.innerHTML = "<span>No medication recorded.</span>";
  }
}


const printMedicationElement = document.getElementById("printMedication");
if (printMedicationElement) {
    printMedicationElement.textContent = document.querySelector(".rhu-medicine-lists")?.textContent || "";
}


                }


             
            })
            .catch(error => console.error("Error fetching visit info:", error));


            



    }

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

    // ✅ Edit Record Button
    const editBtn = document.getElementById("editRecord");
    if (editBtn) {
        editBtn.onclick = () => {
            window.location.href = `editRecord.html?visit_id=${visit_id}`;
        };
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
                toggleBtn.textContent = "SHOW";
            }
        });
    }




    // ✅ Resume Button
    const resumeBtn = document.getElementById("resumeBtn");
    if (resumeBtn) {
        resumeBtn.addEventListener("click", function () {
            window.location.href = "resumeTreatment.html?visit_id=" + visit_id;
        });
    }

    // ✅ Add Med Cert Button
const addMedCertBtn = document.getElementById("addMedCertBtn");
if (addMedCertBtn) {
    addMedCertBtn.addEventListener("click", function () {
        const patientId = document.getElementById("patient_id")?.value;
        const urlParams = new URLSearchParams(window.location.search);
        const visitId = urlParams.get("visit_id"); // Get the visit_id from current page URL

        if (patientId && visitId) {
            window.location.href = `addMedCert.html?patient_id=${patientId}&visit_id=${visitId}`;
        } else {
            alert("Missing patient or visit information. Please try again.");
        }
    });
}


    // ✅ Load Lab File

const container = document.getElementById("lab-file-container");

if (visit_id) {
    fetch(`php/get_file.php?visit_id=${visit_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                const fileUrl = data.file;
                const fileExt = fileUrl.split('.').pop().toLowerCase();
                let content = "";

           
                   if (["jpg", "jpeg", "png", "gif"].includes(fileExt)) {
                    // Show actual image
                    content = `
                        <a href="${fileUrl}" target="_blank">
                            <img src="${fileUrl}" 
                                 alt="Lab Image" 
                                 style="width:40px; height:40px; object-fit:cover; vertical-align:middle; margin-right:8px; margin-left:8px;">
                            View Image
                        </a>
                    `;
                } else {
                    // Generic file preview for all non-image types
                    content = `
                        <a href="${fileUrl}" target="_blank">
                            <img src="css/img/file.png"
                                 alt="File" 
                                 style="width:40px; height:40px; vertical-align:middle; margin-right:8px; margin-left:8px;">
                            View File
                        </a>
                    `;
                }

                container.innerHTML = content;
            } else {
                container.textContent = "No file uploaded.";
            }
        })
        .catch(error => {
            console.error("Error fetching file:", error);
            container.textContent = "Error loading file.";
        });
} else {
    container.textContent = "No visit_id selected.";
}




});
