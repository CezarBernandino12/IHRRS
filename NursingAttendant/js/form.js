
    
    document.addEventListener("DOMContentLoaded", function () {
        
   
        const urlParams = new URLSearchParams(window.location.search);
        const visit_id = urlParams.get("visit_id");
    
       
        if (visit_id) {
            document.getElementById("visit_id").value = visit_id;
            fetch(`php/get_visit_info.php?visit_id=${visit_id}`)
        .then(response => response.json())
        .then(data => {
            console.log("Received Data:", data); // ✅ Check data in console
            if (!data || data.error) {
                console.error("Error:", data.error || "No data received.");
                return;
                    }
    
                    // ✅ Populate Visit Information (if exists)
                    if (data.visit) {
                        updateElement(".visit-date", data.visit.visit_date);
                        updateElement(".patient-alert", data.visit.patient_alert);
                        updateElement(".chief-complaints", data.visit.chief_complaints);
                        updateElement(".blood-pressure", data.visit.blood_pressure);
                        updateElement(".temperature", data.visit.temperature);
                        updateElement(".weight", data.visit.weight);
                        updateElement(".height", data.visit.height);
                        updateElement(".pulse-rate", data.visit.pulse_rate);
                        updateElement(".respiratory-rate", data.visit.respiratory_rate);
                        updateElement(".remarks", data.visit.remarks);
                    }
                     // ✅ Populate Consultation Information (if exists)
                     if (data.consultation) {
                        updateElement(".diagnosis", data.consultation.diagnosis);
                        updateElement(".instruction", data.consultation.instruction_prescription);
                        updateElement(".doctor", data.consultation.doctor_id);
                       
                    }
    
    
                    // ✅ Populate Medicine Information
                    const medicineContainer = document.querySelector(".medicine-list");
                    if (medicineContainer) {
                        medicineContainer.innerHTML = ""; // Clear previous records
    
                        if (data.medicine && data.medicine.length > 0) {
                            data.medicine.forEach(med => {
                                const medHTML = `
                                    <div class="form-group">
                                        <label>Medicine:</label>
                                        <p>${med.medicine_name || "None"}</p>
                                    </div>
                                    <div class="form-group">
                                        <label>Quantity:</label>
                                        <p>${med.quantity_dispensed || "0"}</p>
                                    </div>
                                `;
                                medicineContainer.innerHTML += medHTML;
                            });
                        } else {
                            medicineContainer.innerHTML = "<p>No medication recorded.</p>";
                        }
                    }
                      // ✅ Populate RHU Medicine Information
                      const medicineContainer2 = document.querySelector(".rhu-medicine-list");
                      if (medicineContainer2) {
                          medicineContainer2.innerHTML = ""; // Clear previous records
      
                          if (data.rhumedicine && data.rhumedicine.length > 0) {
                              data.rhumedicine.forEach(med => {
                                  const medHTML = `
                                      <div class="form-group">
                                          <label>Medicine:</label>
                                          <p>${med.medicine_name || "None"}</p>
                                      </div>
                                      <div class="form-group">
                                          <label>Quantity:</label>
                                          <p>${med.quantity_dispensed || "0"}</p>
                                      </div>
                                  `;
                                  medicineContainer2.innerHTML += medHTML;
                              });
                          } else {
                              medicineContainer2.innerHTML = "<p>No medication recorded.</p>";
                          }
                      }
    
                    // ✅ Populate Patient Information (if exists)
                    if (data.patient) {
                       
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
                })
                .catch(error => console.error("Error fetching visit info:", error));
        }
    
        // ✅ Utility function to safely update text content
        function updateElement(selector, value) {
            const element = document.querySelector(selector);
            if (element) {
                element.textContent = value || "N/A";
            }
        }
        function calculateAge(dob) {
        if (!dob) return "N/A"; // Handle empty or invalid date
        const birthDate = new Date(dob);
        if (isNaN(birthDate)) return "N/A"; // Handle invalid date format
    
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        // Adjust age if birthday hasn't occurred yet this year
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
    
        return age >= 0 ? age : "N/A"; // Ensure age is not negative
    }
    document.getElementById('editRecord').onclick = () => {
        window.location.href = `editRecord.html?visit_id=${visit_id}`;
      };
  
   
    
    });

    

    document.getElementById("togglePersonalInfo").addEventListener("click", function () {
            const hiddenSection = document.getElementById("hiddenPersonalInfo");
            const toggleText = document.getElementById("togglePersonalInfo");
    
            if (hiddenSection.style.display === "none") {
                hiddenSection.style.display = "block";
                toggleText.textContent = "HIDE";
            } else {
                hiddenSection.style.display = "none";
                toggleText.textContent = "View All";
            }
        });
    



        document.getElementById("submitButton").addEventListener("click", function (event) {
            event.preventDefault(); // Stop form from submitting
            document.getElementById("myModal").style.display = "block"; // Show modal
        });
        
        
        // Close modal when clicking "Cancel" or "X"
        document.getElementById("cancelButton").addEventListener("click", function () {
            document.getElementById("myModal").style.display = "none";
        });
        document.getElementById("closeBtn").addEventListener("click", function () {
            document.getElementById("myModal").style.display = "none";
        });
        
        // Handle "Save" button click - Send form data via AJAX
        document.getElementById("confirmSave").addEventListener("click", function (event) {
        
        
            let formData = new FormData(document.getElementById("individualRecordForm")); // Collect form data
        
            // Disable button to prevent duplicate submissions
            document.getElementById("confirmSave").disabled = true; 
        
            fetch("php/saveConsultation.php", {
                method: "POST",
                body: formData,
            })
            .then(response => response.text())  // Get response as text first
            .then(text => {
                console.log("Raw Response:", text);  // Log response to debug
                return JSON.parse(text);  // Try parsing JSON manually
            })
            .then(data => {
                document.getElementById("myModal").style.display = "none"; // Hide confirmation modal
                showMessageModal(data.message || "Unknown response"); // Show message
            })
            .catch(error => {
                console.error("Error:", error);
                showMessageModal("An error occurred while saving. ❌");
            });
            
        });
        
        
        // Function to show message modal
        function showMessageModal(message) {
            document.getElementById("responseMessage").textContent = message;
            document.getElementById("responseModal").style.display = "block";
        }
        
        // Close response modal
        document.getElementById("closeResponseModal").addEventListener("click", function () {
            document.getElementById("responseModal").style.display = "none";
        });
        
