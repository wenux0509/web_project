<?php
session_start();
require_once 'config.php'; // Adjust the path if necessary

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload'])) {
    $target_dir = "files/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file already exists
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 500000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow only certain file formats
    if ($fileType != "jpg" && $fileType != "png" && $fileType != "jpeg"
        && $fileType != "gif") {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
        // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            // Insert file details into user_files table
            $filename = basename($_FILES["fileToUpload"]["name"]);
            $user_id = $_SESSION['id'];
            $filepath = $target_file;

            $sql = "INSERT INTO user_files (user_id, filename, filepath) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $user_id, $filename, $filepath);
            if ($stmt->execute()) {
                echo "The file " . htmlspecialchars($filename) . " has been uploaded.";
            } else {
                echo "Error uploading file.";
            }
            $stmt->close();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}

// Handle file deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $file_id = $_POST['file_id'];

    // Fetch filepath based on file_id and user_id
    $sql = "SELECT filepath FROM user_files WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $file_id, $_SESSION['id']);
    $stmt->execute();
    $stmt->bind_result($filepath);
    $stmt->fetch();
    $stmt->close();

    // Delete file if exists and belongs to the user
    if (isset($filepath)) {
        if (unlink($filepath)) {
            // Delete record from database
            $sql_delete = "DELETE FROM user_files WHERE id = ? AND user_id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("ii", $file_id, $_SESSION['id']);
            if ($stmt_delete->execute()) {
                echo "File deleted successfully.";
            } else {
                echo "Error deleting file.";
            }
            $stmt_delete->close();
        } else {
            echo "Error deleting file.";
        }
    } else {
        echo "File not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <link rel="stylesheet" href="css/bootstrap.css">
</head>

<body>
    <div class="container">
        <h2 class="mt-4">File Manager</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="fileToUpload">Select file to upload:</label>
                <input type="file" name="fileToUpload" id="fileToUpload" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" name="upload">Upload</button>
        </form>

        <h3 class="mt-4">Uploaded Files:</h3>
        <ul class="list-group">
            <?php
            // Fetch and display files uploaded by the user
            $sql_files = "SELECT id, filename FROM user_files WHERE user_id = ?";
            $stmt_files = $conn->prepare($sql_files);
            $stmt_files->bind_param("i", $_SESSION['id']);
            $stmt_files->execute();
            $stmt_files->bind_result($file_id, $filename);

            while ($stmt_files->fetch()) {
                echo '<li class="list-group-item">' . htmlspecialchars($filename) . ' 
                      <a href="files/' . htmlspecialchars($filename) . '" class="btn btn-sm btn-primary mx-2" download>Download</a>
                      <form action="delete.php" method="post" class="d-inline">
                          <input type="hidden" name="file_id" value="' . $file_id . '">
                          <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                      </form>
                      </li>';
            }

            $stmt_files->close();
            ?>
        </ul>

        <a href="logout.php" class="btn btn-danger mt-4">Logout</a>
    </div>
</body>

</html>
