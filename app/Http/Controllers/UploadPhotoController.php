<?php
session_start();
include 'config.php'; // your DB connection file

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $email = $_SESSION['email'];
    $targetDir = "uploads/";
    
    // Create uploads folder if not existing
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = uniqid() . "_" . basename($_FILES["profile_image"]["name"]);
    $targetFilePath = $targetDir . $fileName;

    // Check valid image
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
            // Save filename to database
            $update = $conn->prepare("UPDATE users SET profile_image=? WHERE email=?");
            $update->bind_param("ss", $targetFilePath, $email);
            $update->execute();

            // Update session
            $_SESSION['profile_image'] = $targetFilePath;
        }
    }
}

// Redirect back
header("Location: patient_page.php");
exit();
?>