<?php
/**
 * Database Connection File
 *
 * This script connects to the MySQL database using the MySQLi extension.
 * It sets the connection parameters, creates a connection object,
 * checks for connection errors, and sets the character set.
 *
 * The connection object `$conn` will be available to any script
 * that includes this file.
 */

// --- Database Credentials ---
// It's a good practice to store these in variables for easy management.
$servername = "localhost"; // Usually 'localhost' for local development
$username   = "root";
$password   = "Admin@1234";
$dbname     = "smss";


// --- Create a new connection to the MySQL database ---
$conn = new mysqli($servername, $username, $password, $dbname);


// --- Check the connection ---
// If the connect_error property is not null, it means an error occurred.
if ($conn->connect_error) {
    // The die() function is crucial for security. It stops the script immediately
    // and prevents it from continuing with a failed connection, which could
    // expose sensitive information or cause further errors.
    die("Connection Failed: " . $conn->connect_error);
}


// --- Set the character set to utf8mb4 ---
// This is a best practice to ensure proper handling of all characters,
// including special symbols and emojis, preventing encoding issues.
$conn->set_charset("utf8mb4");

// No need to close the connection here.
// PHP automatically closes the connection when the script finishes executing.
// The file that includes this one will use the active $conn variable.

?>