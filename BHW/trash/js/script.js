document.addEventListener("DOMContentLoaded", function() {
    const minusBtn = document.querySelector('.btn-minus');
    const plusBtn = document.querySelector('.btn-plus');
    const quantityInput = document.getElementById('quantity_given');

    minusBtn.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        if (value > 1) {
            quantityInput.value = value - 1;
        }
    });

    plusBtn.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        quantityInput.value = value + 1;
    });
});

function calculateAge() {
    const dobInput = document.getElementById("dob");
    const ageDisplay = document.getElementById("age-display");
    const ageInput = document.getElementById("age-input"); // Hidden input field
    const dobValue = dobInput.value;

    if (!dobValue) {
        ageDisplay.textContent = "Age: ";
        ageInput.value = ""; // Clear hidden input if no date is selected
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

    if (days < 0) {
        const previousMonth = new Date(today.getFullYear(), today.getMonth(), 0);
        days += previousMonth.getDate();
        months--;
    }

    // Display the correct age format
    let ageText = `Age: ${years} years`;
    
    if (years === 0) {
        ageText = `Age: ${months} month/s`; // Show only months if under 1 year
    } else if (months > 0) {
        ageText += ` and ${months} month/s`;
    }

    // Update displayed age
    ageDisplay.textContent = ageText;

    // Store age value in hidden input for form submission
    ageInput.value = years; // You can change this to store the full age text if needed
}


//FOR SEARCHING PATIENT



// COUNTING VALUE
window.onload = function() {
    countToTarget(); 
    countProgressText(); 
};


const progressText = document.querySelector('.progress-text');
let currentValue = 1; 
const targetValue = 75;

function countToTarget() {
    if (currentValue <= targetValue) {
        progressText.textContent = currentValue;
        currentValue++;
        setTimeout(countToTarget, 20);
    }
}

function countProgressText() {
    const progressTextElements = document.querySelectorAll('.progress-text');
    progressTextElements.forEach(progressElement => {
        let currentProgress = 0; // Start from 0
        const targetProgress = parseInt(progressElement.textContent); 

        function count() {
            if (currentProgress <= targetProgress) {
                progressElement.textContent = currentProgress; 
                currentProgress++;
                setTimeout(count, 20); 
            }
        }

        count(); // Start counting for each progress text element
    });
}




//MODAL
var modal = document.getElementById("myModal");
var btn = document.getElementById("openModal");
var closeBtn = document.getElementById("closeBtn");
var noButton = document.getElementById("noButton");
var yesButton = document.getElementById("yesButton");

  btn.onclick = function() {
    modal.style.display = "block";
  }

  closeBtn.onclick = function() {
    modal.style.display = "none";
  }

  noButton.onclick = function() {
    modal.style.display = "none";
    window.location.href = "ITR.html"; 
  }

  yesButton.onclick = function() {
    modal.style.display = "none";
    window.location.href = "searchPatient.html"; 
  }
