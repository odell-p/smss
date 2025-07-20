<?php

// Define the new password you want to use.
$newPassword = 'pass@123';

// Use PHP's built-in function to create a secure hash.
// PASSWORD_BCRYPT is a strong and widely used hashing algorithm.
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

// Display the generated hash on the screen.
echo "<h1>Password Hash Generator</h1>";
echo "<p>Password to hash: <strong>" . htmlspecialchars($newPassword) . "</strong></p>";
echo "<p>Copy the hash below and use it in your SQL UPDATE statement:</p>";
echo "<pre style='background-color:#eee; border:1px solid #ccc; padding:10px; font-size:1.1em; word-wrap:break-word;'>" . htmlspecialchars($hashedPassword) . "</pre>";

?>