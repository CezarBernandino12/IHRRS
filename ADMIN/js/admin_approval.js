let offset = 10;

document.getElementById("loadMoreBtn").addEventListener("click", function () {
    fetch(`load_more_logs.php?offset=${offset}`)
        .then(response => response.text())
        .then(data => {
            if (data.trim() === "") {
                document.getElementById("loadMoreBtn").style.display = "none";
            } else {
                document.querySelector("#logTable tbody").innerHTML += data;
                offset += 10;
            }
        })
        .catch(error => console.error("Error loading logs:", error));
});

document.getElementById("userFilter").addEventListener("change", function () {
    document.getElementById("logFilterForm").submit();
});

function updateUser(userId, action) {
    $.post("user_action.php", { user_id: userId, action: action }, function (response) {
        alert(response);
        if (["approve", "reject", "delete"].includes(action)) {
            $("#row-" + userId).fadeOut();
        }
    });
}

function viewUser(userId) {
    fetch('get_user_info.php?user_id=' + userId)
    .then(response => response.json())
    .then(data => {
        // Update these lines to use the IDs that actually exist in your HTML
        document.getElementById('logUserFullName').innerText = data.full_name;
        document.getElementById('logUserName').innerText = data.username;
        document.getElementById('logUserStatus').innerText = data.status;
        document.getElementById('logUserBarangay').innerText = data.barangay;
        document.getElementById('logUserRole').innerText = data.role;
        
        // If you want to show additional info and have the elements for them:
        // document.getElementById('logUserAge').innerText = data.age;
        // document.getElementById('logUserContact').innerText = data.contact_number;
        // document.getElementById('logUserRegistration').innerText = data.registration_date;
        
        // Set action info to N/A since we don't have it from this call
        document.getElementById('logUserAction').innerText = 'N/A';
        document.getElementById('logUserTimestamp').innerText = data.registration_date || 'N/A';
        
        document.getElementById('userModal').style.display = 'block';
        document.getElementById('modalOverlay').style.display = 'block';
    })
    .catch(error => {
        console.error('Error fetching user data:', error);
        alert('Error retrieving user information');
    });
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}

// Make closeLogUserModal use closeModal to ensure consistency
function closeLogUserModal() {
    closeModal();
}
// Add event listeners for user links in the log table
document.addEventListener('DOMContentLoaded', function() {
    // Initial event listeners
    addUserLinkListeners();
    
    // Function to add event listeners to user links
    function addUserLinkListeners() {
        document.querySelectorAll('.user-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const userId = this.getAttribute('data-userid');
                const action = this.getAttribute('data-action');
                showLogUserModal(userId, action);
            });
        });
    }
    
    // After loading more logs, add event listeners to new elements
    document.getElementById("loadMoreBtn").addEventListener("click", function() {
        // Add a small delay to ensure the DOM is updated
        setTimeout(() => {
            addUserLinkListeners();
        }, 300);
    });
});

function showLogUserModal(userId, action) {
    // Fetch user information
    fetch('get_user_info.php?user_id=' + userId)
    .then(response => response.json())
    .then(data => {
        document.getElementById('logUserFullName').innerText = data.full_name;
        document.getElementById('logUserName').innerText = data.username;
        document.getElementById('logUserStatus').innerText = data.status;
        document.getElementById('logUserBarangay').innerText = data.barangay;
        document.getElementById('logUserRole').innerText = data.role;
        
        // Set action information
        document.getElementById('logUserAction').innerText = action;
        
        // Get timestamp from the server or you could pass it as a parameter
        // For now we'll use the current time
        const now = new Date();
        document.getElementById('logUserTimestamp').innerText = now.toLocaleString();
        
        // Display the modal
        document.getElementById('userModal').style.display = 'block';
        document.getElementById('modalOverlay').style.display = 'block';
    });
}

function closeLogUserModal() {
    document.getElementById('userModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}