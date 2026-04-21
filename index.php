<!DOCTYPE html>
<html>
<head>
    <title>Web Gallz</title>
    <link href="styles.css" type="text/css" rel="stylesheet">
    
</head>
<body>
<?php
echo "<h1>Welcome Web Gallz</h1>";
$servername = "localhost";
$username = "root";
$password = "";
$database = "Art_Gallz";

// Connect to MySQL Database Server Using mysqli
// Object Oriented way
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
            //Print a message and terminate the current script
        die("Connection failed: " . $conn->connect_error);
}

?>
</body>
</html>