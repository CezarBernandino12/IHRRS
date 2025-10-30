// Initialize variables for pagination
let currentOffset = 0;
const recordsPerPage = 10;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize flatpickr date pickers
    flatpickr(".flatpickr", {
        dateFormat: "Y-m-d",
        allowInput: true,
        altInput: true,
        altFormat: "F j, Y", 
        onChange: function(selectedDates, dateStr, instance) {
            // Auto-submit when date is changed
            setTimeout(() => {
                document.getElementById('logFilterForm').submit();
            }, 100);
        }
    });

    // Set up auto-submit for select filters
    document.querySelectorAll('.auto-submit').forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('logFilterForm').submit();
        });
    });

    // Initialize user link listeners
    addUserLinkListeners();
    
    // Initialize pagination buttons state
    updatePaginationButtons();
});

// Load more button event listener
document.getElementById("loadMoreBtn").addEventListener("click", function() {
    currentOffset += recordsPerPage;
    loadMoreLogs();
    updatePaginationButtons();
});

// Previous button event listener
document.getElementById("prevBtn").addEventListener("click", function() {
    currentOffset = Math.max(0, currentOffset - recordsPerPage);
    loadMoreLogs();
    updatePaginationButtons();
});

// Function to load logs based on current offset
function loadMoreLogs() {
    fetch(`load_more_logs.php?offset=${currentOffset}`)
        .then(response => response.text())
        .then(data => {
            if (data.trim() === "") {
                document.getElementById("loadMoreBtn").disabled = true;
            } else {
                document.querySelector("#logTable tbody").innerHTML = data;
                
                // Add event listeners to newly loaded elements
                setTimeout(() => {
                    addUserLinkListeners();
                }, 300);
            }
        })
        .catch(error => console.error("Error loading logs:", error));
}

// Update pagination buttons state
function updatePaginationButtons() {
    // Disable previous button if we're at the first page
    document.getElementById('prevBtn').disabled = currentOffset === 0;
}

// Handle form submission for filters
document.getElementById('filterButton').addEventListener('click', function(e) {
    e.preventDefault();
    currentOffset = 0; // Reset offset when applying new filters
    loadMoreLogs();
    updatePaginationButtons();
});

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

function updateUser(userId, action) {
    $.post("user_action.php", { user_id: userId, action: action }, function (response) {
        try {
            const res = JSON.parse(response);
            alert(res.message);

            if (res.success && ["approve", "reject", "delete"].includes(action)) {
                $("#row-" + userId).fadeOut();
            }
        } catch (e) {
            console.error("Error parsing response:", response);
            alert("An error occurred. Check the console for details.");
        }
    });
}

function showLogUserModal(userId, action) {
    // Fetch user information
    fetch('get_user_info.php?user_id=' + userId)
    .then(response => response.json())
    .then(data => {
        document.getElementById('logUserFullName').innerText = data.full_name;
        document.getElementById('logUserName').innerText = data.username;
        document.getElementById('logUserRole').innerText = data.role;
        
        // Set action information
        document.getElementById('logUserAction').innerText = action;
        
        // Get timestamp from the server or you could pass it as a parameter
        document.getElementById('logUserTimestamp').innerText = data.registration_date || new Date().toLocaleString();
        
        // Display the modal
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

// For compatibility, keep this function as an alias
function closeLogUserModal() {
    closeModal();
}

function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false; // Prevent the default link behavior
}

function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href = 'logout.php'; 
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeLogoutModal();
    }
};