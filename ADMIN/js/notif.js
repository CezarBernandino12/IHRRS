document.addEventListener('DOMContentLoaded', function() {
    function checkNotifications() {
        // Use fetch API to check for new notifications
        fetch('notif.php?get_unread_count=1')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notification-badge');
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(error => console.error('Error checking notifications:', error));
    }
    
    // Check for notifications when page loads
    checkNotifications();
    
    // Check for new notifications every 60 seconds
    setInterval(checkNotifications, 60000);
});

function updateUser(userId, action) {
    if (confirm('Are you sure you want to ' + action + ' this user?')) {
        $.ajax({
            url: 'admin_approval.php',
            type: 'POST',
            data: {
                user_id: userId,
                action: action
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    // Remove the row from the table
                    $('#row-' + userId).fadeOut(500, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while processing your request.');
            }
        });
    }
}

function viewUser(userId) {
    // Implement view user functionality
    console.log('View user with ID: ' + userId);
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}

function confirmLogout() {
    return confirm('Are you sure you want to logout?');
}
