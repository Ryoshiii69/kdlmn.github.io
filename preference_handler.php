<?php
// First part handles AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_preference') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Return JSON response
    header('Content-Type: application/json');

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit();
    }

    // Validate request
    if (!isset($_POST['preference']) || !isset($_POST['value'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "kadiliman";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    // Get values from request
    $preference = $_POST['preference'];
    $value = (int)($_POST['value'] === 'true' ? 1 : 0);
    $user_id = $_SESSION['user_id'];

    // Whitelist allowed preferences
    $allowed_preferences = ['login_alerts', 'password_changes']; // Add other preferences as needed
    if (!in_array($preference, $allowed_preferences)) {
        echo json_encode(['success' => false, 'message' => 'Invalid preference']);
        exit();
    }

    // Update the preference
    try {
        $sql = "UPDATE users SET $preference = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $value, $user_id);
        $result = $stmt->execute();
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Preference updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $conn->close();
    exit();
}

// If this file is not being accessed via AJAX, output the JavaScript part
if (!isset($_POST['action'])):
?>

<script>
// JavaScript part that handles the toggle interactions
document.addEventListener('DOMContentLoaded', function() {
    // Define preference toggles mapping (DOM ID to database field)
    const preferenceToggles = {
        'loginAlerts': 'login_alerts',
        'passwordChanges': 'password_changes'
        // Add more mappings as needed
    };
    
    // Add event listeners to all toggle switches
    for (const switchId in preferenceToggles) {
        const switchElement = document.getElementById(switchId);
        if (switchElement) {
            switchElement.addEventListener('change', function() {
                const dbField = preferenceToggles[this.id];
                const isChecked = this.checked;
                
                // Show loading indicator
                const originalLabel = this.nextElementSibling.innerText;
                this.nextElementSibling.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
                this.disabled = true;
                
                // Send AJAX request to update preference
                const formData = new FormData();
                formData.append('action', 'update_preference');
                formData.append('preference', dbField);
                formData.append('value', isChecked);
                
                fetch('preference_handler.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // Revert the switch if update failed
                        this.checked = !isChecked;
                        console.error('Failed to update preference:', data.message);
                        alert('Failed to update preference: ' + data.message);
                    }
                    // Reset label
                    this.nextElementSibling.innerText = originalLabel;
                })
                .catch(error => {
                    // Revert the switch if there was an error
                    this.checked = !isChecked;
                    console.error('Error updating preference:', error);
                    alert('Error updating preference. Please try again.');
                    this.nextElementSibling.innerText = originalLabel;
                })
                .finally(() => {
                    this.disabled = false;
                });
            });
        }
    }
});
</script>

<?php endif; ?>