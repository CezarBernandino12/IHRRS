 // THIS FILE IS FOR UPDATING BOTH PERSONAL AND VISIT INFORMATION



document.addEventListener("DOMContentLoaded", function () {

    let user_id = null;

fetch('php/getUserId.php')
  .then(response => response.json())
  .then(data => {
    if (data.user_id) {
        user_id = data.user_id;
    } else {
      console.error('User not logged in or session expired.');
    }
  })
  .catch(error => {
    console.error('Failed to fetch user ID:', error);
  });
  document.getElementById("user_id").value = user_id;



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
    
        const urlParams = new URLSearchParams(window.location.search);
        const visitId = urlParams.get('visit_id');
        console.log("Visit ID:", visitId);
    
        if (!visitId) {
            alert("Missing visit_id in URL.");
            return;
        }
        if (!user_id) {
            alert("User not logged in.");
            return;
        }
    
        document.getElementById("visitIdField").value = visitId;
        document.getElementById("user_id").value = user_id;
    
        const updatedData = {
            visit_id: visitId,
            visit_date: document.querySelector(".visit-date")?.value || "",
            patient_alert: document.querySelector(".patient-alert")?.value || "",
            chief_complaints: document.querySelector(".chief-complaints")?.value || "",
            blood_pressure: document.querySelector(".blood-pressure")?.value || "N/A",
            temperature: document.querySelector(".temperature")?.value || "N/A",
            weight: document.querySelector(".weight")?.value || "N/A",
            height: document.querySelector(".height")?.value || "N/A",
            chest_rate: document.querySelector(".chest-rate")?.value || "N/A",
            respiratory_rate: document.querySelector(".respiratory-rate")?.value || "N/A",
            remarks: document.querySelector(".remarks")?.value || "",
            diagnosis: document.querySelector(".diagnosis")?.value || "",
            instruction: document.querySelector(".instruction")?.value || "",
            medicine_name: Array.from(document.querySelectorAll(".medicine-given")).map(select => select.value || ""),
            quantity_dispensed: Array.from(document.querySelectorAll(".quantity-given")).map(input => input.value || ""),
            dispensed_by: user_id
        };
    
        console.log("Sending data:", updatedData);
    
        fetch("php/update_visit_info.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(updatedData)
        })
        .then(response => response.text())
        .then(responseText => {
            console.log("Raw response:", responseText);
            try {
                const result = JSON.parse(responseText);
                if (result.success) {
                    alert("Record updated successfully!");
                } else {
                    alert("Failed to update record: " + (result.error || "Unknown error"));
                }
            } catch (e) {
                console.error("Error parsing response JSON:", e);
                alert("Invalid response from server. See console for details.");
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
            alert("Network or server error. See console for details.");
        });
    });
    

    cancelBtn.addEventListener("click", () => {
        modal1.style.display = "none";
    });

    exitBtn.addEventListener("click", function () {
        modal5.style.display = "none";
        window.history.back();
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
      
      
      
              
        
           
      
 
      
         document.addEventListener("DOMContentLoaded", function () {  
          const urlParams = new URLSearchParams(window.location.search);
          const visit_id = urlParams.get("visit_id");
      
          if (visit_id) {
            fetch(`php/get_consultation_info.php?visit_id=${visit_id}`)
            .then(response => {
              if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`);
              }
              return response.text();
            })
            .then(responseText => {
              console.log("Raw response:", responseText);
          
              try {
                const data = JSON.parse(responseText);
                console.log("Received Data:", data);
          
                if (!data || data.error) {
                  console.error("Error:", data.error || "No data received.");
                  return;
                }
          
                // ✅ Populate Visit Info
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
          
                // ✅ Populate Consultation Info
                if (data.consultation) {
                  populateInput(".diagnosis", data.consultation.diagnosis);
                  populateInput(".instructions", data.consultation.instruction_prescription);
                  populateInput(".follow-up-date", data.consultation.follow_up_date, "date");
                }
          
                // ✅ Populate RHU Medicines
                const container = document.getElementById("medicine-container");
                const originalEntry = container.querySelector(".medicine-entry");
          
                // Clear all but one template
                container.innerHTML = "";
          
                if (Array.isArray(data.rhumedicine)) {
                  data.rhumedicine.forEach((med, index) => {
                    // Clone the template structure
                    const entry = originalEntry.cloneNode(true);
                    entry.style.display = "flex";
          
                    // Set medicine value
                    const select = entry.querySelector(".medicine-given");
                    if (select) {
                      select.value = med.medicine_name || "";
                    }
          
                    // Set quantity value
                    const qtyInput = entry.querySelector(".quantity-given");
                    if (qtyInput) {
                      qtyInput.value = med.quantity_dispensed || 0;
                    }
          
                    container.appendChild(entry);
                  });
                }
          
              } catch (e) {
                console.error("Error parsing JSON:", e);
                alert("Response was not valid JSON. See console for details.");
              }
            })
            .catch(error => {
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
      
      