<?php
// Database configuration example
// Copy this file to config.php and update with your local development credentials
// For Google Cloud Platform deployment, credentials are stored in Secret Manager

// Detect if running on Google App Engine
$onGCP = (getenv('GAE_ENV') !== false);

if ($onGCP) {
    // Google App Engine - retrieve credentials from Secret Manager
    // This provides secure credential management without hardcoding values
    
    /**
     * Retrieve a secret from Google Secret Manager
     * @param string $secretName The name of the secret to retrieve
     * @return string|null The secret value or null on failure
     */
    function getSecret($secretName) {
        // Validate inputs
        $projectId = getenv('GOOGLE_CLOUD_PROJECT');
        if (empty($projectId)) {
            error_log("GOOGLE_CLOUD_PROJECT environment variable is not set");
            return null;
        }
        
        // Validate secret name to prevent injection attacks
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $secretName)) {
            error_log("Invalid secret name format: {$secretName}");
            return null;
        }
        
        // Get access token from metadata server
        $url = "http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Metadata-Flavor: Google'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 second connection timeout
        $tokenResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($tokenResponse === false || !empty($curlError)) {
            error_log("Failed to get access token from metadata server: " . $curlError);
            return null;
        }
        
        // Parse JSON and validate structure
        $tokenData = json_decode($tokenResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($tokenData['access_token'])) {
            error_log("Failed to parse access token response: " . json_last_error_msg());
            return null;
        }
        
        $token = $tokenData['access_token'];
        
        // Access the secret
        $secretUrl = "https://secretmanager.googleapis.com/v1/projects/{$projectId}/secrets/{$secretName}/versions/latest:access";
        $ch = curl_init($secretUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 second connection timeout
        $secretResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($secretResponse === false || !empty($curlError)) {
            error_log("Failed to retrieve secret {$secretName}: cURL error: " . $curlError);
            return null;
        }
        
        if ($httpCode === 200) {
            $data = json_decode($secretResponse, true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['payload']['data'])) {
                error_log("Failed to parse secret {$secretName} response: " . json_last_error_msg());
                return null;
            }
            return base64_decode($data['payload']['data']);
        } else {
            // Don't log full response body as it may contain sensitive info
            error_log("Failed to retrieve secret {$secretName}: HTTP {$httpCode}");
            return null;
        }
    }
    
    // Retrieve credentials from Secret Manager
    // Secrets should be created during deployment:
    // - db-username: Database username
    // - db-password: Database password  
    // - db-connection-name: Cloud SQL connection name
    $dbUsername = getSecret('db-username');
    $dbPassword = getSecret('db-password');
    $connectionName = getSecret('db-connection-name');
    
    // Fallback to environment variables if Secret Manager fails
    if (!$dbUsername || !$dbPassword || !$connectionName) {
        error_log("Secret Manager retrieval failed, falling back to environment variables");
        $dbUsername = getenv('DB_USERNAME') ?: 'pardus_app_user';
        $dbPassword = getenv('DB_PASSWORD');
        $connectionName = getenv('CLOUD_SQL_CONNECTION_NAME') ?: 'YOUR_CONNECTION_NAME';
    }
    
    $socketDir = getenv('DB_SOCKET_DIR') ?: '/cloudsql';
    
    define('DB_SOCKET', $socketDir . '/' . $connectionName);
    define('DB_USERNAME', $dbUsername);
    define('DB_PASSWORD', $dbPassword);
    define('DB_NAME', 'pardus_combat_data');
} else {
    // Local development settings
    // Update these values for your local MySQL installation
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'your_database_username');
    define('DB_PASSWORD', 'your_database_password');
    define('DB_NAME', 'your_database_name');
}

// Function to create database connection
function getDatabaseConnection() {
    global $onGCP;
    
    if ($onGCP) {
        // Google App Engine - connect via Unix socket
        $conn = new mysqli(null, DB_USERNAME, DB_PASSWORD, DB_NAME, null, DB_SOCKET);
    } else {
        // Standard connection for local development
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    }
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Connection failed. Please try again later.");
    }
    
    return $conn;
}
?>
