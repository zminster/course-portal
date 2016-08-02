<?php
	$servername = "localhost";
	$username = "course-portal";
	$password = "testpass";
	$dbname = "course_portal";

	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbname);

	// Check connection
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}
?>