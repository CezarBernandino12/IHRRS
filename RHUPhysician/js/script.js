document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all delete icons
    const deleteIcons = document.querySelectorAll('.delete-icon');
    
    deleteIcons.forEach(function(icon) {
        icon.addEventListener('click', function(event) {
            // Prevent the default behavior
            event.preventDefault();

            // Confirm if the user really wants to delete
            const isConfirmed = confirm("Are you sure you want to delete this referral?");
            if (isConfirmed) {
                // Get the row containing the delete icon and remove it
                const row = event.target.closest('tr');
                row.remove();
            }
        });
    });
});


// Function to handle the increase and decrease of the quantity
document.getElementById("increase").addEventListener("click", function() {
    let quantityInput = document.getElementById("quantity");
    let currentValue = parseInt(quantityInput.value);
    quantityInput.value = currentValue + 1; // Increment the quantity by 1
});

document.getElementById("decrease").addEventListener("click", function() {
    let quantityInput = document.getElementById("quantity");
    let currentValue = parseInt(quantityInput.value);
    if (currentValue > 1) {
        quantityInput.value = currentValue - 1; // Decrement the quantity by 1, but prevent going below 1
    }
});

// Ensure that if a user types a non-numeric value, the field resets to 1
document.getElementById("quantity").addEventListener("input", function() {
    let quantityInput = document.getElementById("quantity");
    let currentValue = parseInt(quantityInput.value);
    if (isNaN(currentValue) || currentValue < 1) {
        quantityInput.value = 1; // Set to 1 if the input is invalid or less than 1
    }
});

// Function to handle the Print button
document.querySelector(".print-btn").addEventListener("click", function() {
    // Hide the elements you don't want to print
    const sidebar = document.getElementById("sidebar");
    const navigation = document.querySelector("nav");
    
    sidebar.style.display = "none";  // Hide the sidebar
    navigation.style.display = "none";  // Hide the top navigation
    
    // Get the content of the form container
    const formContainer = document.querySelector(".container");

    // Create a temporary print window
    const printWindow = window.open('', '', 'height=800,width=800');
    
    // Add the form content to the print window
    printWindow.document.write('<html><head><title>Print Form</title>');
    printWindow.document.write('<style>');
    
    // Add your print-specific styles here
    printWindow.document.write(`
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .container-header h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .container-header p {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .form-group label {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .form-group p {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            font-size: 16px;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-btn {
            font-size: 18px;
            padding: 5px 10px;
            background-color: #ccc;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        button.print-btn {
            display: none;
        }
        .button-container {
            display: none;
        }
    `);

    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(formContainer.innerHTML);  // Add the content of the form to the print window
    printWindow.document.write('</body></html>');
    
    // Wait for the content to load in the print window before triggering the print dialog
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();

    // Restore the original page view after printing
    sidebar.style.display = "block";
    navigation.style.display = "block";
});

const submitButton = document.getElementById('submitButton');
const modal1 = document.getElementById('myModal');
const modal2 = document.getElementById('myModal2');  // Modal 2
const closeBtn1 = document.getElementById('closeBtn');
const closeBtn2 = document.getElementById('closeBtn3');  // Close button for Modal 2
const noButton1 = document.getElementById('noButton');
const yesButton1 = document.getElementById('yesButton');
const goBackButton = modal2.querySelector('.btn.yes');  // "Go Back" button in modal2

// Open modal1 when submitButton is clicked
submitButton.addEventListener('click', function (event) {
    event.preventDefault();
    modal1.style.display = 'block';  // Show modal1
});

// Close modal1 when closeBtn1 is clicked
closeBtn1.addEventListener('click', function () {
    modal1.style.display = 'none';  // Hide modal1
});

// Close modal1 when noButton1 is clicked
noButton1.addEventListener('click', function () {
    modal1.style.display = 'none';  // Hide modal1
});

// Open modal2 when yesButton1 is clicked
yesButton1.addEventListener('click', function () {
    modal1.style.display = 'none';  // Hide modal1
    modal2.style.display = 'block';  // Show modal2
});
 
// Close modal2 when closeBtn2 is clicked
closeBtn2.addEventListener('click', function () {
    modal2.style.display = 'none';  // Hide modal2
});

// Close modal2 when goBackButton is clicked
goBackButton.addEventListener('click', function () {
    modal2.style.display = 'none';  // Hide modal2
});

