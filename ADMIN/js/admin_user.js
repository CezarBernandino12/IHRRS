document.getElementById('roleFilter').addEventListener('change', function () {
    this.form.submit();
});

function resetPassword(userId) {
    let newPassword = prompt("Enter a new password (at least 1 uppercase letter and 1 number):");

    if (!newPassword) {
        alert("Password cannot be empty!");
        return;
    }

    let passwordRegex = /^(?=.*[A-Z])(?=.*\d).{6,}$/;

    if (!passwordRegex.test(newPassword)) {
        alert("Password must contain at least 1 uppercase letter, 1 number, and be at least 6 characters long.");
        return;
    }

    fetch('reset_password.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'user_id=' + userId + '&new_password=' + encodeURIComponent(newPassword)
    })
    .then(response => response.json()) // Important: your PHP returns JSON!
    .then(data => {
        if (typeof data === 'string') {
            alert(data); // Success
            location.reload();
        } else if (data.error) {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Wrap all code in a DOMContentLoaded event listener to ensure the DOM is loaded
document.addEventListener("DOMContentLoaded", function() {
    // Set up filter functionality
    const toggleBtn = document.getElementById('toggleFilterBtn');
    const filterForm = document.getElementById('filterForm');
    const iconSpan = toggleBtn.querySelector('.icon');
    const labelSpan = toggleBtn.querySelector('.label');
    
    // Filter dropdown change event
    const roleFilter = document.getElementById('roleFilter');
    if (roleFilter) {
        roleFilter.addEventListener('change', function() {
            this.form.submit();
        });
    }

    // Set up filter toggle button display
    const isVisible = filterForm.style.display === 'flex';
    iconSpan.textContent = isVisible ? '×' : '+';
    labelSpan.textContent = isVisible ? 'Remove filter' : 'Add a filter';

    // Toggle filter display
    toggleBtn.addEventListener('click', function() {
        const isVisible = filterForm.style.display === 'flex';

        if (isVisible) {
            window.location.href = window.location.pathname;
        } else {
            filterForm.style.display = 'flex';
            iconSpan.textContent = '×';
            labelSpan.textContent = 'Remove filter';
        }
    });

    // Set up modal functionality
    const modal = document.getElementById("userModal");
    const overlay = document.getElementById("modalOverlay");
    
    // Add User Button - Critical fix here
    const addUserBtn = document.getElementById('addUserBtn');
    const addUserModal = document.getElementById('addUserModal');
    
    if (addUserBtn) {
        addUserBtn.addEventListener('click', function() {
            document.getElementById('addUserModal').style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        });
    }
    
    // Setup view buttons for user details
    setupViewButtons();
    
    
    // Set up password toggles
    setupPasswordToggles();
    
    // Add event to close modals when clicking outside
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
                closeAddUserModal();
            }
        });
    }
});

// Set up password visibility toggles
function setupPasswordToggles() {
    const passwordToggle = document.getElementById('passwordToggle');
    const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirmPassword');

    if (passwordToggle && passwordField) {
        passwordToggle.addEventListener('click', function() {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordToggle.classList.remove('bx-hide');
                passwordToggle.classList.add('bx-show');
            } else {
                passwordField.type = 'password';
                passwordToggle.classList.remove('bx-show');
                passwordToggle.classList.add('bx-hide');
            }
        });
    }

    if (confirmPasswordToggle && confirmPasswordField) {
        confirmPasswordToggle.addEventListener('click', function() {
            if (confirmPasswordField.type === 'password') {
                confirmPasswordField.type = 'text';
                confirmPasswordToggle.classList.remove('bx-hide');
                confirmPasswordToggle.classList.add('bx-show');
            } else {
                confirmPasswordField.type = 'password';
                confirmPasswordToggle.classList.remove('bx-show');
                confirmPasswordToggle.classList.add('bx-hide');
            }
        });
    }
}

// Event delegation for reset password buttons
    const userTableBody = document.getElementById('userTableBody');
    if (userTableBody) {
        userTableBody.addEventListener('click', function (e) {
            if (e.target.classList.contains('reset-password-btn')) {
                const userId = e.target.getAttribute('data-user-id');
                if (userId) {
                    resetPassword(userId);
                }
            }
        });
    }



// Set up view buttons on user cards
function setupViewButtons() {
    const viewButtons = document.querySelectorAll(".view-user-btn");

    viewButtons.forEach(button => {
        button.addEventListener("click", function() {
            const userData = JSON.parse(this.getAttribute("data-user"));

            // Always show
            document.getElementById("modalFullName").textContent = userData.full_name;
            document.getElementById("modalUsername").textContent = userData.username;
            document.getElementById("modalRole").textContent = userData.role;
            document.getElementById("modalStatus").textContent = userData.account_status;
            document.getElementById("modalAddress").textContent = userData.address ?? 'N/A';
            document.getElementById("modalAge").textContent = userData.age;
            document.getElementById("modalContact").textContent = userData.contact_number;
            document.getElementById("modalRegistrationDate").textContent = userData.registration_date ?? 'Unknown';

            // Conditionally show/hide Barangay
            const barangayRow = document.getElementById("modalBarangayRow");
            if (userData.barangay) {
                document.getElementById("modalBarangay").textContent = userData.barangay;
                barangayRow.style.display = "block";
            } else {
                barangayRow.style.display = "none";
            }

            document.getElementById("userModal").style.display = "block";
            document.getElementById("modalOverlay").style.display = "block";
        });
    });
}

// HTML Escape function for dynamic content
function htmlEscape(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

// Function to close user details modal
function closeUserModal() {
    const modal = document.getElementById("userModal");
    const overlay = document.getElementById("modalOverlay");
    if (modal) modal.style.display = "none";
    if (overlay) overlay.style.display = "none";
}

// Function to close add user modal
function closeAddUserModal() {
    const modal = document.getElementById("addUserModal");
    const overlay = document.getElementById("modalOverlay");
    const form = document.getElementById("addUserForm");
    
    if (modal) {
        modal.style.display = "none";
    }
    if (overlay) {
        overlay.style.display = "none";
    }
    if (form) {
        form.reset();
    }
}

// Function to handle password reset
function resetPassword(userId) {
    let newPassword = prompt("Enter a new password (at least 1 uppercase letter and 1 number):");

    if (!newPassword) {
        alert("Password reset cancelled.");
        return;
    }

    let passwordRegex = /^(?=.*[A-Z])(?=.*\d).{6,}$/;

    if (!passwordRegex.test(newPassword)) {
        alert("Password must contain at least 1 uppercase letter, 1 number, and be at least 6 characters long.");
        return;
    }

    fetch('reset_password.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'user_id=' + userId + '&new_password=' + encodeURIComponent(newPassword)
    })
    .then(response => response.json())
    .then(data => {
        if (typeof data === 'string') {
            alert(data); // Success
            location.reload();
        } else if (data.error) {
            alert('Error: ' + data.error);
        } else {
            alert("Password changed successfully");
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("An error occurred during password reset");
    });
}

// Function to save a new user
function saveNewUser() {
    const form = document.getElementById('addUserForm');
    const fullName = document.getElementById('fullName').value.trim();
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const role = document.getElementById('role').value;
    const licenseNumber = document.getElementById('licenseNumber') ? document.getElementById('licenseNumber').value.trim() : '';

    // Basic validation
    if (!fullName || !username || !password || !role) {
        alert('Please fill in all required fields (Full Name, Username, Password, and Role).');
        return;
    }

    // Password validation
    const passwordRegex = /^(?=.*[A-Z])(?=.*\d).{6,}$/;
    if (!passwordRegex.test(password)) {
        alert('Password must contain at least 1 uppercase letter, 1 number, and be at least 6 characters.');
        return;
    }

    // Confirm password match
    if (password !== confirmPassword) {
        alert('Passwords do not match.');
        return;
    }
    // If doctor role, ensure license number is provided
    if (role === 'doctor' && !licenseNumber) {
        alert('Please enter the physician license number.');
        return;
    }
 
    const formData = new FormData(form);

    fetch('add_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert(data);
        if (data.includes('successfully')) {
            closeAddUserModal();
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding user. Please try again.');
    });
}

// Function to toggle barangay field based on role
function toggleBarangayField() {
    const role = document.getElementById('role').value;
    const barangayGroup = document.getElementById('barangayGroup');
    const barangaySelect = document.getElementById('barangay');
    const rhuGroup = document.getElementById('rhu-group');
    const licenseGroup = document.getElementById('licenseGroup');
    const licenseInput = document.getElementById('licenseNumber');

    // Always show RHU dropdown
    rhuGroup.style.display = 'block';

    if (role === 'doctor') {
        // Hide Barangay
        barangayGroup.style.display = 'none';
        barangaySelect.removeAttribute('required');
        barangaySelect.value = ''; // Clear barangay

        // Show license field and make required
        if (licenseGroup) licenseGroup.style.display = 'block';
        if (licenseInput) licenseInput.setAttribute('required', '');
    } else {
        // Show Barangay for other roles
        barangayGroup.style.display = 'block';
        barangaySelect.setAttribute('required', '');

        // Hide license field
        if (licenseGroup) licenseGroup.style.display = 'none';
        if (licenseInput) licenseInput.removeAttribute('required');
    }
}


document.getElementById('contactNumber').addEventListener('input', function (e) {
    const input = e.target;
    const value = input.value;

    // Allow only numeric input
    input.value = value.replace(/\D/g, '');

    // Limit the input to 11 digits
    if (input.value.length > 11) {
        input.value = input.value.slice(0, 11);
    }
}); 

// Replace your previous load more code with this updated pagination code

// Global variables for pagination
let currentPage = 1;
let currentOffset = 0;
const pageSize = 10; // Number of users per page
let isLoading = false;
let totalUsers = parseInt(document.getElementById('totalUsers')?.textContent || '0');
let paginationHistory = []; // Store previous page states

document.addEventListener('DOMContentLoaded', function() {
    // Initialize pagination buttons
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    
    if (nextBtn) {
        nextBtn.addEventListener('click', loadNextPage);
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', loadPreviousPage);
    }
    
    // Initialize view buttons for the initial set of users
    initViewButtons();
    
    // Save initial page state
    saveCurrentPageState();
});

/**
 * Save current page state for history navigation
 */
function saveCurrentPageState() {
    const userRows = Array.from(document.querySelectorAll('#userTableBody tr')).map(row => row.outerHTML);
    
    paginationHistory[currentPage] = {
        html: userRows,
        offset: currentOffset,
        displayedCount: parseInt(document.getElementById('displayedUsers').textContent)
    };
}

/**
 * Load the next page of users
 */
function loadNextPage() {
    if (isLoading) return;
    
    currentPage++;
    currentOffset += pageSize;
    
    // Check if we already have this page in history
    if (paginationHistory[currentPage]) {
        displayPageFromHistory(currentPage);
        return;
    }
    
    // Otherwise, load from server
    loadUsersFromServer(currentOffset, true);
}

/**
 * Load the previous page of users
 */
function loadPreviousPage() {
    if (isLoading || currentPage <= 1) return;
    
    currentPage--;
    currentOffset = Math.max(0, currentOffset - pageSize);
    
    // We should always have previous pages in history
    displayPageFromHistory(currentPage);
}

/**
 * Display a page from history
 */
function displayPageFromHistory(pageNumber) {
    const pageData = paginationHistory[pageNumber];
    if (!pageData) return;
    
    const userTableBody = document.getElementById('userTableBody');
    userTableBody.innerHTML = pageData.html.join('');
    
    document.getElementById('startRecord').textContent = Math.max(1, pageData.offset + 1);
    document.getElementById('displayedUsers').textContent = Math.min(pageData.offset + pageData.html.length, totalUsers);
    
    updatePaginationControls();
    initViewButtons();
}

/**
 * Load users from server via AJAX
 */
function loadUsersFromServer(offset, isNext = true) {
    if (isLoading) return;
    isLoading = true;
    
    // Update button states
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    
    if (nextBtn) nextBtn.disabled = true;
    if (prevBtn) prevBtn.disabled = true;
    
    // Show loading indicator on next button
    if (nextBtn) {
        nextBtn.innerHTML = '<i class="bx bx-loader bx-spin"></i> Loading...';
    }
    
    // Get current search parameters
    const searchInput = document.querySelector('input[name="search"]');
    const roleFilter = document.getElementById('roleFilter');
    
    const search = searchInput ? searchInput.value : '';
    const role = roleFilter ? roleFilter.value : '';
    
    // Create AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `load_more_users.php?offset=${offset}&search=${encodeURIComponent(search)}&role=${encodeURIComponent(role)}`, true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    // Replace table content
                    const userTableBody = document.getElementById('userTableBody');
                    userTableBody.innerHTML = response.html;
                    
                    // Update counters
                    document.getElementById('startRecord').textContent = offset + 1;
                    document.getElementById('displayedUsers').textContent = Math.min(offset + response.count, response.total);
                    totalUsers = response.total;
                    document.getElementById('totalUsers').textContent = totalUsers;
                    
                    // Save this page to history
                    saveCurrentPageState();
                    
                    // Re-initialize view buttons
                    initViewButtons();
                } else {
                    console.error('Error loading users:', response.message);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
            }
            
            updatePaginationControls();
        }
        
        isLoading = false;
    };
    
    xhr.onerror = function() {
        console.error('Request failed');
        isLoading = false;
        updatePaginationControls();
    };
    
    xhr.send();
}

/**
 * Update pagination controls based on current state
 */
function updatePaginationControls() {
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    
    if (nextBtn) {
        nextBtn.disabled = currentOffset + pageSize >= totalUsers;
        nextBtn.innerHTML = 'Next <i class="bx bx-chevron-right"></i>';
    }
    
    if (prevBtn) {
        prevBtn.disabled = currentPage <= 1;
    }
}


function initViewButtons() {
    // Get all view buttons
    const viewButtons = document.querySelectorAll('.view-user-btn');
    
    // Add event listener to each button
    viewButtons.forEach(button => {
        button.removeEventListener('click', viewUserHandler); // Remove existing listener to prevent duplicates
        button.addEventListener('click', viewUserHandler);
    });
    
}

/**
 * Event handler for view user button clicks
 */
function viewUserHandler() {
    const userData = JSON.parse(this.getAttribute('data-user'));
    
    // Populate the modal with user data
    document.getElementById('modalFullName').textContent = userData.full_name;
    document.getElementById('modalUsername').textContent = userData.username;
    document.getElementById('modalRole').textContent = userData.role.charAt(0).toUpperCase() + userData.role.slice(1);
    document.getElementById('modalStatus').textContent = userData.account_status === 'active' ? 'Active' : 'Account Terminated';
    
    // Show/hide barangay row based on role
    const barangayRow = document.getElementById('modalBarangayRow');
    if (userData.role === 'bhw') {
        barangayRow.style.display = 'block';
        document.getElementById('modalBarangay').textContent = userData.barangay || 'N/A';
    } else {
        barangayRow.style.display = 'none';
    }
    
    document.getElementById('modalAddress').textContent = userData.address || 'N/A';
    document.getElementById('modalAge').textContent = userData.age || 'N/A';
    document.getElementById('modalContact').textContent = userData.contact_number || 'N/A';
    document.getElementById('modalRegistrationDate').textContent = userData.registration_date || 'N/A';
    
    // Show modal
    document.getElementById('modalOverlay').style.display = 'block';
    document.getElementById('userModal').style.display = 'block';
}

function showTerminateModal(userId) {
    document.getElementById('terminateUserId').value = userId;
    document.getElementById('terminateModal').style.display = 'block';
}

function closeTerminateModal() {
    document.getElementById('terminateModal').style.display = 'none';
}

window.onclick = function(event) {
    // Logout modal
    const logoutModal = document.getElementById('logoutModal');
    if (logoutModal && event.target == logoutModal) {
        closeModal();
    }

    // Terminate modal
    const terminateModal = document.getElementById('terminateModal');
    if (terminateModal && event.target == terminateModal) {
        closeTerminateModal();
    }

    // Add more modals here if needed
};

function showResetPasswordModal(userId) {
    document.getElementById('resetPasswordUserId').value = userId;
    document.getElementById('resetPasswordForm').reset();
    document.getElementById('resetPasswordError').style.display = 'none';
    document.getElementById('resetPasswordModal').style.display = 'block';
}

function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'none';
}

function closeResetSuccessModal() {
    document.getElementById('resetSuccessModal').style.display = 'none';
      location.reload();
}

// Password validation
document.getElementById('resetPasswordForm').onsubmit = function(e) {
    var newPass = document.getElementById('newPassword').value;
    var confirmPass = document.getElementById('confirmNewPassword').value;
    var errorDiv = document.getElementById('resetPasswordError');
    var regex = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

    if (!regex.test(newPass)) {
        errorDiv.textContent = "Password must be at least 8 characters, contain a number and a capital letter.";
        errorDiv.style.display = 'block';
        e.preventDefault();
        return false;
    }
    if (newPass !== confirmPass) {
        errorDiv.textContent = "Passwords do not match.";
        errorDiv.style.display = 'block';
        e.preventDefault();
        return false;
    }
    errorDiv.style.display = 'none';

    // Optional: AJAX submit to avoid page reload and show success modal
    e.preventDefault();
    var formData = new FormData(this);
  fetch('reset_password.php', {
    method: 'POST',
    body: formData
 })
.then(res => res.json())
.then(data => {
    if (data.success) {
        closeResetPasswordModal();
        document.getElementById('resetSuccessModal').style.display = 'block';
    } else {
        errorDiv.textContent = data.error || "An error occurred.";
        errorDiv.style.display = 'block';
    }
})
.catch(() => {
    errorDiv.textContent = "An error occurred.";
    errorDiv.style.display = 'block';
});
    return false;
};

document.getElementById('toggleNewPassword').onclick = function() {
  const input = document.getElementById('newPassword');
  this.classList.toggle('bx-show');
  this.classList.toggle('bx-hide');
  input.type = input.type === 'password' ? 'text' : 'password';
};

document.getElementById('toggleConfirmPassword').onclick = function() {
  const input = document.getElementById('confirmNewPassword');
  this.classList.toggle('bx-show');
  this.classList.toggle('bx-hide');
  input.type = input.type === 'password' ? 'text' : 'password';
};

function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false; // Prevent the default link behavior
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href = 'logout.php'; // Adjust path if needed
}

