 // THIS FILE IS FOR UPDATING BOTH PERSONAL AND VISIT INFORMATION



document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const submitBtn = document.getElementById("submitButton");

    const referralInput = document.querySelector('input[name="referral_status"]');

    // Modals
    const modal1 = document.getElementById("myModal");
    const modal2 = document.getElementById("myModal2");
    const modal3 = document.getElementById("myModal3");
    const modal4 = document.getElementById("myModal4");
    const modal5 = document.getElementById("myModal5");

    // Buttons inside modals
    const yesBtn = document.getElementById("yesButton");
    const noBtn = document.getElementById("noButton");
    const cancelBtn = document.getElementById("cancelBtn");
    const yesBtn2 = document.getElementById("yesButton2");
    const noBtn2 = document.getElementById("noButton2");
    const viewDetailsBtn = document.getElementById("viewDetailsButton");
    const exitBtn = document.getElementById("exitButton");

    // Close buttons
    const closeBtns = document.querySelectorAll(".close-btn");

    // Show modal1 on submit
    submitBtn.addEventListener("click", function (e) {
        e.preventDefault(); // prevent form from submitting
        modal2.style.display = "block";
    });
 
    

    yesBtn2.addEventListener("click", () => {
        referralInput.value = "not_referred";
        modal2.style.display = "none";
        modal5.style.display = "block";
          // --- Your referral-saving logic starts here ---
        const urlParams = new URLSearchParams(window.location.search);
        const visitId = urlParams.get('visit_id');
        console.log("Visit ID:", visitId);
    
        // Set hidden input
        document.getElementById("visitIdField").value = visitId;
    
        const updatedData = {
            visit_id: document.getElementById("visitIdField").value,
            visit_date: document.querySelector(".visit-date")?.value || "",
            patient_alert: document.querySelector(".patient-alert")?.value || "",
            chief_complaints: document.querySelector(".chief-complaints")?.value || "",
            blood_pressure: document.querySelector(".blood-pressure")?.value || "N/A",
            temperature: document.querySelector(".temperature")?.value || "N/A",
            weight: document.querySelector(".weight")?.value || "N/A",
            height: document.querySelector(".height")?.value || "N/A",
            pulse_rate: document.querySelector(".pulse-rate")?.value || "N/A",
            respiratory_rate: document.querySelector(".respiratory-rate")?.value || "N/A",
            remarks: document.querySelector(".remarks")?.value || "",
            first_name: document.querySelector(".patient-first-name")?.value || "",
            last_name: document.querySelector(".patient-last-name")?.value || "",
            middle_name: document.querySelector(".patient-middle-name")?.value || "",
            extension: document.querySelector(".patient-extension")?.value || "",
            birthplace: document.querySelector(".patient-birth-place")?.value || "",
            date_of_birth: document.querySelector(".date-of-birth")?.value || "",
            address: document.querySelector(".address")?.value || "",
            civil_status: document.querySelector(".civil-status")?.value || "",
            contact_number: document.querySelector(".contact-number")?.value || "",
            religion: document.querySelector(".religion")?.value || "",
            occupation: document.querySelector(".occupation")?.value || "",
            birth_weight: document.querySelector(".birth-weight")?.value || "",
            educational_attainment: document.querySelector(".educational-attainment")?.value || "",
            philhealth_member_no: document.querySelector(".philhealth-member-no")?.value || "",
            category: document.querySelector(".category")?.value || "",
            family_serial_no: document.querySelector(".family-serial-no")?.value || "",
            sex: document.querySelector(".sex")?.value || "",
            fourps_status: document.querySelector(".fourps-status")?.value || ""
        };
    
        console.log("Sending data:", updatedData);
    
        fetch("php/update_visit_info.php", {
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
      
      
      
              
        
           
      
              document.getElementById("togglePersonalInfo").addEventListener("click", function () {
              const hiddenSection = document.getElementById("hiddenPersonalInfo");
              const toggleText = document.getElementById("togglePersonalInfo");
      
              if (hiddenSection.style.display === "none") {
                  hiddenSection.style.display = "block";
                  toggleText.textContent = "View Less";
              } else {
                  hiddenSection.style.display = "none";
                  toggleText.textContent = "View All";
              }
          });
      
         document.addEventListener("DOMContentLoaded", function () {  
          const urlParams = new URLSearchParams(window.location.search);
          const visit_id = urlParams.get("visit_id");
      
          if (visit_id) {
          fetch(`php/get_visit_info.php?visit_id=${visit_id}`)
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
      
                      // ✅ Populate Visit Information
                      if (data.visit) {
                          populateInput(".visit-date", data.visit.visit_date, "date");
                          populateInput(".patient-alert", data.visit.patient_alert);
                          populateInput(".chief-complaints", data.visit.chief_complaints);
                          populateInput(".blood-pressure", data.visit.blood_pressure);
                          populateInput(".temperature", data.visit.temperature);
                          populateInput(".weight", data.visit.weight);
                          populateInput(".height", data.visit.height);
                          populateInput(".pulse-rate", data.visit.pulse_rate);
                          populateInput(".respiratory-rate", data.visit.respiratory_rate);
                          populateInput(".remarks", data.visit.remarks);
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
      
      