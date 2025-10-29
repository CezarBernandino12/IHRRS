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
    .then(response => response.json())
    .then(data => {
        if (typeof data === 'string') {
            alert(data);
            location.reload();
        } else if (data.error) {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Wrap all code in a DOMContentLoaded event listener
document.addEventListener("DOMContentLoaded", function() {
    const toggleBtn = document.getElementById('toggleFilterBtn');
    const filterForm = document.getElementById('filterForm');
    const iconSpan = toggleBtn.querySelector('.icon');
    const labelSpan = toggleBtn.querySelector('.label');
    
    const roleFilter = document.getElementById('roleFilter');
    if (roleFilter) {
        roleFilter.addEventListener('change', function() {
            this.form.submit();
        });
    }

    const isVisible = filterForm.style.display === 'flex';
    iconSpan.textContent = isVisible ? '×' : '+';
    labelSpan.textContent = isVisible ? 'Remove filter' : 'Add a filter';

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

    const modal = document.getElementById("userModal");
    const overlay = document.getElementById("modalOverlay");
    
    const addUserBtn = document.getElementById('addUserBtn');
    const addUserModal = document.getElementById('addUserModal');
    
    if (addUserBtn) {
        addUserBtn.addEventListener('click', function() {
            document.getElementById('addUserModal').style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        });
    }
    
    setupViewButtons();
    setupPasswordToggles();
    
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
                closeAddUserModal();
            }
        });
    }

    // Initialize pagination
    initPagination();
});

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

function setupViewButtons() {
    const viewButtons = document.querySelectorAll(".view-user-btn");

    viewButtons.forEach(button => {
        button.addEventListener("click", function() {
            const userData = JSON.parse(this.getAttribute("data-user"));

            document.getElementById("modalFullName").textContent = userData.full_name.toUpperCase();
            document.getElementById("modalUsername").textContent = userData.username.toUpperCase();
            document.getElementById("modalRole").textContent = userData.role.toUpperCase();
            document.getElementById("modalStatus").textContent = userData.account_status;
            document.getElementById("modalAddress").textContent = userData.address ?? 'N/A';
            document.getElementById("modalAge").textContent = userData.age;
            document.getElementById("modalContact").textContent = userData.contact_number;
            document.getElementById("modalRegistrationDate").textContent = userData.registration_date ?? 'Unknown';

            const barangayRow = document.getElementById("modalBarangayRow");
            if (userData.barangay) {
                document.getElementById("modalBarangay").textContent = userData.barangay;
                barangayRow.style.display = "block";
            } else {
                barangayRow.style.display = "none";
            }

            const rhuRow = document.getElementById("modalRhuRow");
            if (userData.rhu) {
                document.getElementById("modalRhu").textContent = userData.rhu;
                rhuRow.style.display = "block";
            } else {
                rhuRow.style.display = "none";
            }

            document.getElementById("userModal").style.display = "block";
            document.getElementById("modalOverlay").style.display = "block";
        });
    });
}

function htmlEscape(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function closeUserModal() {
    const modal = document.getElementById("userModal");
    const overlay = document.getElementById("modalOverlay");
    if (modal) modal.style.display = "none";
    if (overlay) overlay.style.display = "none";
}

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
            alert(data);
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

function saveNewUser() {
    const form = document.getElementById('addUserForm');
    const fullName = document.getElementById('fullName').value.trim();
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const role = document.getElementById('role').value;
    const licenseNumber = document.getElementById('licenseNumber') ? document.getElementById('licenseNumber').value.trim() : '';

    if (!fullName || !username || !password || !role) {
        alert('Please fill in all required fields (Full Name, Username, Password, and Role).');
        return;
    }

    const passwordRegex = /^(?=.*[A-Z])(?=.*\d).{6,}$/;
    if (!passwordRegex.test(password)) {
        alert('Password must contain at least 1 uppercase letter, 1 number, and be at least 6 characters.');
        return;
    }

    if (password !== confirmPassword) {
        alert('Passwords do not match.');
        return;
    }

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

function toggleBarangayField() {
    const role = document.getElementById('role').value;
    const barangayGroup = document.getElementById('barangayGroup');
    const barangaySelect = document.getElementById('barangay');
    const rhuGroup = document.getElementById('rhu-group');
    const licenseGroup = document.getElementById('licenseGroup');
    const licenseInput = document.getElementById('licenseNumber');

    rhuGroup.style.display = 'block';

    if (role === 'doctor') {
        barangayGroup.style.display = 'none';
        barangaySelect.removeAttribute('required');
        barangaySelect.value = '';

        if (licenseGroup) licenseGroup.style.display = 'block';
        if (licenseInput) licenseInput.setAttribute('required', '');
    } else {
        barangayGroup.style.display = 'block';
        barangaySelect.setAttribute('required', '');

        if (licenseGroup) licenseGroup.style.display = 'none';
        if (licenseInput) licenseInput.removeAttribute('required');
    }
}

document.getElementById('contactNumber').addEventListener('input', function (e) {
    const input = e.target;
    const value = input.value;

    input.value = value.replace(/\D/g, '');

    if (input.value.length > 11) {
        input.value = input.value.slice(0, 11);
    }
}); 

// PAGINATION IMPLEMENTATION (Similar to activity_logs.js)
let currentOffset = 0;
const recordsPerPage = 10;

function initPagination() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            currentOffset = Math.max(0, currentOffset - recordsPerPage);
            loadUsers();
            updatePaginationButtons();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            currentOffset += recordsPerPage;
            loadUsers();
            updatePaginationButtons();
        });
    }

    updatePaginationButtons();
}

function loadUsers() {
    const searchInput = document.querySelector('input[name="search"]');
    const roleFilter = document.getElementById('roleFilter');
    
    const search = searchInput ? searchInput.value : '';
    const role = roleFilter ? roleFilter.value : '';

    fetch(`load_more_users.php?offset=${currentOffset}&search=${encodeURIComponent(search)}&role=${encodeURIComponent(role)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('userTableBody').innerHTML = data.html;
                
                // Update counters
                const start = currentOffset + 1;
                const end = Math.min(currentOffset + data.count, data.total);
                
                document.getElementById('startRecord').textContent = start;
                document.getElementById('displayedUsers').textContent = end;
                document.getElementById('totalUsers').textContent = data.total;
                
                // Re-initialize view buttons
                setupViewButtons();
                updatePaginationButtons();
            }
        })
        .catch(error => console.error("Error loading users:", error));
}

function updatePaginationButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const totalUsers = parseInt(document.getElementById('totalUsers').textContent);

    if (prevBtn) {
        prevBtn.disabled = currentOffset === 0;
        if (currentOffset === 0) {
            prevBtn.classList.add('hidden');
        } else {
            prevBtn.classList.remove('hidden');
        }
    }

    if (nextBtn) {
        const hasMore = (currentOffset + recordsPerPage) < totalUsers;
        nextBtn.disabled = !hasMore;
    }
}

function showTerminateModal(userId) {
    document.getElementById('terminateUserId').value = userId;
    document.getElementById('terminateModal').style.display = 'block';
}

function closeTerminateModal() {
    document.getElementById('terminateModal').style.display = 'none';
}

window.onclick = function(event) {
    const logoutModal = document.getElementById('logoutModal');
    if (logoutModal && event.target == logoutModal) {
        closeModal();
    }

    const terminateModal = document.getElementById('terminateModal');
    if (terminateModal && event.target == terminateModal) {
        closeTerminateModal();
    }
};

function showResetPasswordModal(userId) {
    document.getElementById('resetPasswordUserId').value = userId;
    document.getElementById('resetPasswordForm').reset();
    document.getElementById('resetPasswordError').style.display = 'none';

    // Check if this is a pending reset request
    const resetBtn = document.querySelector(`button[onclick="showResetPasswordModal(${userId})"]`);
    const isPendingReset = resetBtn && resetBtn.classList.contains('pending-reset');

    const modalTitle = document.getElementById('resetModalTitle');
    const pendingInfo = document.getElementById('pendingResetInfo');

    if (isPendingReset) {
        modalTitle.textContent = 'Process Password Reset Request';
        pendingInfo.style.display = 'block';

        // Fetch user contact number
        fetch(`get_user_info.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.contact_number) {
                    document.getElementById('userContactNumber').textContent = data.contact_number;
                }
            })
            .catch(error => console.error('Error fetching user info:', error));
    } else {
        modalTitle.textContent = 'Change User Password';
        pendingInfo.style.display = 'none';
    }

    document.getElementById('resetPasswordModal').style.display = 'block';
}

function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'none';
}

function closeResetSuccessModal() {
    document.getElementById('resetSuccessModal').style.display = 'none';
    location.reload();
}

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
    return false;
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href = 'logout.php';
}

document.addEventListener("DOMContentLoaded", function() {
    const rhuDropdown = document.getElementById("rhu");
    const barangayDropdown = document.getElementById("barangay");

    const barangaysByRHU = {
        "Rural Health Unit I": [
            "Barangay 2", "Barangay Pamoragon", "Barangay Mancruz", "Barangay Magang", "Barangay Calasgasan", "Barangay Bibirao", "Barangay Camambugan", "Barangay Alawihao", "Barangay Dogongan"
        ],
        "Rural Health Unit II": [
            "Barangay 1", "Barangay 6", "Barangay 7", "Barangay 8", "Barangay Gubat", "Barangay San Isidro", "Barangay Cobangbang", "Barangay Bagasbas", "Barangay Mambalite"
        ],
        "Rural Health Unit III": [
            "Barangay 3", "Barangay 4", "Barangay 5", "Barangay Awitan", "Barangay Gahonon", "Barangay Borabod", "Barangay Lag-On"
        ]
    };

    rhuDropdown.addEventListener("change", function() {
        const selectedRHU = rhuDropdown.value;
        const barangays = barangaysByRHU[selectedRHU] || [];

        // Clear existing options
        barangayDropdown.innerHTML = '<option value="" disabled selected>Select Barangay</option>';

        // Populate barangay dropdown
        barangays.forEach(barangay => {
            const option = document.createElement("option");
            option.value = barangay;
            option.textContent = barangay;
            barangayDropdown.appendChild(option);
        });
    });
});