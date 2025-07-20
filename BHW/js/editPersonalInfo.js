
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const submitBtn = document.getElementById("submitButton");
    const referralInput = document.querySelector('input[name="referral_status"]');

    // Modals
    const modal1 = document.getElementById("myModal");
    const modal2 = document.getElementById("myModal2");


    // Buttons inside modals
    const yesBtn = document.getElementById("yesButton");

    const cancelBtn = document.getElementById("cancelBtn");

    const exitBtn = document.getElementById("exitButton");

    // Close buttons
    const closeBtns = document.querySelectorAll(".close-btn");
    closeBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            btn.closest(".modal").style.display = "none";
        });
    });
    // Show modal1 on submit
    submitBtn.addEventListener("click", function (e) {
        e.preventDefault(); // prevent form from submitting
        modal1.style.display = "block";
    });

    // Modal1 button actions
    yesBtn.addEventListener("click", () => {
     
        modal1.style.display = "none";
        modal2.style.display = "block";

          // --- Your referral-saving logic starts here ---
        const urlParams = new URLSearchParams(window.location.search);
        const patientId = urlParams.get('patient_id');
        console.log("Patient ID:", patientId);
    
        // Set hidden input
        document.getElementById("patientIdField").value = patientId;
    
        const updatedData = {
            patient_id: document.getElementById("patientIdField").value,
            first_name: document.querySelector(".patient-first-name")?.value || "",
            last_name: document.querySelector(".patient-last-name")?.value || "",
            middle_name: document.querySelector(".patient-middle-name")?.value || "",
            extension: document.querySelector(".patient-extension")?.value || "",
            birthplace: document.querySelector(".patient-bh-placeirt")?.value || "",
            date_of_birth: document.querySelector(".date-of-birth")?.value || "",
            address: document.querySelector(".address")?.value || "",
            civil_status: document.querySelector(".civil-status")?.value || "",
            contact_number: document.querySelector(".contact-number")?.value || "",
            religion: document.querySelector(".religion")?.value || "",
            occupation: document.querySelector(".occupation")?.value || "",
            educational_attainment: document.querySelector(".educational-attainment")?.value || "",
            birth_weight: document.querySelector(".birth-weight")?.value || "",
            philhealth_member_no: document.querySelector(".philhealth-member-no")?.value || "",
            category: document.querySelector(".category")?.value || "",
            family_serial_no: document.querySelector(".family-serial-no")?.value || "",
            sex: document.querySelector(".sex")?.value || "",
            fourps_status: document.querySelector(".fourps-status")?.value || ""
        };
    
        console.log("Sending data:", updatedData);
    
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
            return response.text();
        })
        .then(responseText => {
            try {
                const result = JSON.parse(responseText);
                console.log("Update Response:", result);
                if (result.success) {
                    alert("Record updated successfully!");
                } else {
                    alert("Failed to update record: " + (result.message || "Unknown error"));
                }
            } catch (e) {
                console.error("Error parsing JSON:", e);
                alert("Response was not valid JSON. See console for details.");
            }
        })
        .catch(error => {
            console.error("Error updating visit info:", error);
            alert("An error occurred while updating. Check console for details.");
        });

    });

    

    cancelBtn.addEventListener("click", () => {
        modal1.style.display = "none";
    });

  
    

    noBtn2.addEventListener("click", () => {
        modal2.style.display = "none";

    });

    // Modal3 View Details
    viewDetailsBtn.addEventListener("click", () => {
        modal3.style.display = "none";
        // Redirect or show details (customize as needed)
        alert("Viewing Details...");
    });

    // Modal5 Exit
    exitBtn.addEventListener("click", () => {
        modal5.style.display = "none";
        // You can reset the form or redirect here
        form.reset();
    });

    // Close buttons for all modals
    closeBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            btn.closest(".modal").style.display = "none";
        });
    });

    // Optional: Close modals when clicking outside of them
    window.addEventListener("click", function (e) {
        document.querySelectorAll(".modal").forEach(modal => {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });
    });
});


    
    
    
    
    
    
    
    
    
    
    
    // Function to get query parameter from URL
     function getQueryParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
      }
      
      
      
              
        
      
      
          document.addEventListener("DOMContentLoaded", function() {
            // Get patient_id from the URL
            const urlParams = new URLSearchParams(window.location.search);
            const patient_id = urlParams.get("patient_id");

          if (patient_id) {
          fetch(`php/patient_details.php?patient_id=${patient_id}`)
              .then(response => {
                  // Check if the response is OK (status 200-299)
                  if (!response.ok) {
                      throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`);
                  }
                  return response.text(); // Get raw response as text first
              })
              .then(responseText => {
                  console.log("Raw response:", responseText); // Log raw response to debug
      
                  try {
                      // Try parsing the response as JSON
                      const data = JSON.parse(responseText);
                      console.log("Received Data:", data); // ✅ Debugging
      
                      if (!data || data.error) {
                          console.error("Error:", data.error || "No data received.");
                          return;
                      }
      
      
                      // ✅ Populate Patient Information
                      if (data.patient) {
                          populateInput(".patient-first-name", data.patient.first_name);
                          populateInput(".patient-last-name", data.patient.last_name);
                          populateInput(".patient-middle-name", data.patient.middle_name);
                          populateInput(".patient-extension", data.patient.extension);
                          populateInput(".patient-birth-place", data.patient.birthplace);
                          populateInput(".date-of-birth", data.patient.date_of_birth, "date");
      
                          // Calculate age
                          const age = calculateAge(data.patient.date_of_birth);
                          populateInput(".age", age);
      
                          populateInput(".address", data.patient.address);
                          populateInput(".civil-status", data.patient.civil_status);
                          populateInput(".contact-number", data.patient.contact_number);
                          populateInput(".religion", data.patient.religion);
                          populateInput(".occupation", data.patient.occupation);
                          populateInput(".educational-attainment", data.patient.educational_attainment);
                          populateInput(".birth-weight", data.patient.birth_weight);
                          populateInput(".philhealth-member-no", data.patient.philhealth_member_no);
                          populateInput(".category", data.patient.category);
                          populateInput(".family-serial-no", data.patient.family_serial_no);
                          populateInput(".sex", data.patient.sex);
                          populateInput(".fourps-status", data.patient.fourps_status);
                      }
                  } catch (e) {
                      // Error parsing JSON or unexpected response structure
                      console.error("Error parsing JSON:", e);
                      alert("Response was not valid JSON. See console for details.");
                  }
              })
              .catch(error => {
                  // Catch network errors or other unexpected issues
                  console.error("Error fetching visit info:", error);
                  alert("An error occurred while fetching the visit info. See console for details.");
              });
      }
      
      
          // ✅ Function to update input values
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
      
          function formatDate(dateString) {
              if (!dateString) return "";
              const date = new Date(dateString);
              return isNaN(date) ? "" : date.toISOString().split("T")[0];
          }
      
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
      
        // ✅ Save Button Functionality

      
      
      });
      
      