document.addEventListener('DOMContentLoaded', function() {
    
    // Only add delete icon listeners if delete icons exist
    const deleteIcons = document.querySelectorAll('.delete-icon');
    if (deleteIcons.length > 0) {
        deleteIcons.forEach(function(icon) {
            icon.addEventListener('click', function(event) {
                event.preventDefault();
                const isConfirmed = confirm("Are you sure you want to delete this referral?");
                if (isConfirmed) {
                    const row = event.target.closest('tr');
                    if (row) row.remove();
                }
            });
        });
    }

    // Only add quantity controls if they exist on this page
    const increaseBtn = document.getElementById("increase");
    const decreaseBtn = document.getElementById("decrease");
    const quantityInput = document.getElementById("quantity");

    if (increaseBtn) {
        increaseBtn.addEventListener("click", function() {
            if (quantityInput) {
                let currentValue = parseInt(quantityInput.value) || 0;
                quantityInput.value = currentValue + 1;
            }
        });
    }

    if (decreaseBtn) {
        decreaseBtn.addEventListener("click", function() {
            if (quantityInput) {
                let currentValue = parseInt(quantityInput.value) || 0;
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                }
            }
        });
    }

    if (quantityInput) {
        quantityInput.addEventListener("input", function() {
            let currentValue = parseInt(quantityInput.value);
            if (isNaN(currentValue) || currentValue < 1) {
                quantityInput.value = 1;
            }
        });
    }

    // Only add print functionality if print button exists
    const printBtn = document.querySelector(".print-btn");
    if (printBtn) {
        printBtn.addEventListener("click", function() {
            const sidebar = document.getElementById("sidebar");
            const navigation = document.querySelector("nav");
            
            if (sidebar) sidebar.style.display = "none";
            if (navigation) navigation.style.display = "none";
            
            const formContainer = document.querySelector(".container");
            if (!formContainer) {
                alert("Form container not found");
                return;
            }

            const printWindow = window.open('', '', 'height=800,width=800');
            printWindow.document.write('<html><head><title>Print Form</title>');
            printWindow.document.write('<style>');
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
            printWindow.document.write(formContainer.innerHTML);
            printWindow.document.write('</body></html>');
            
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();

            if (sidebar) sidebar.style.display = "block";
            if (navigation) navigation.style.display = "block";
        });
    }

    // Modal handling - only if modals exist on this page
    const submitButton = document.getElementById('submitButton');
    const modal1 = document.getElementById('myModal');
    const modal2 = document.getElementById('myModal2');
    const closeBtn1 = document.getElementById('closeBtn');
    const closeBtn2 = document.getElementById('closeBtn3');
    const noButton1 = document.getElementById('noButton');
    const yesButton1 = document.getElementById('yesButton');

    if (submitButton && modal1) {
        submitButton.addEventListener('click', function (event) {
            event.preventDefault();
            modal1.style.display = 'block';
        });
    }

    if (closeBtn1 && modal1) {
        closeBtn1.addEventListener('click', function () {
            modal1.style.display = 'none';
        });
    }

    if (noButton1 && modal1) {
        noButton1.addEventListener('click', function () {
            modal1.style.display = 'none';
        });
    }

    if (yesButton1 && modal1) {
        yesButton1.addEventListener('click', function () {
            modal1.style.display = 'none';
        });
    }

    if (closeBtn2 && modal2) {
        closeBtn2.addEventListener('click', function () {
            modal2.style.display = 'none';
        });
    }

    if (modal2) {
        const goBackButton = modal2.querySelector('.btn.yes');
        if (goBackButton) {
            goBackButton.addEventListener('click', function () {
                modal2.style.display = 'none';
            });
        }
    }
});