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

	function get_class_pds($conn) {
		$res = $conn->query("SELECT * FROM class");
		$classes = array();

		if ($res->num_rows > 0) {
			while ($class_pd = $res->fetch_assoc()) {
				$classes[] = $class_pd["class_pd"];
			}
			return $classes;
		} else{
			die("<span style=\"color:red;\"><b>
				You must <a href=\"add_class.php\">add a class</a>.</b></span>");
		}
	}

	function get_asgn_types($conn) {
		$res = $conn->query("SELECT * FROM assignment_type");
		$types = array();

		if ($res->num_rows > 0) {
			while ($type = $res->fetch_assoc()) {
				$types[] = $type;
			}
			return $types;
		} else{
			die("<span style=\"color:red;\"><b>
				You must <a href=\"add_asgn_type.php\">add an assignment type</a>.</b></span>");
		}
	}

	function get_all_assignments($conn) {
		$res = $conn->query("SELECT * FROM assignment");
		$asgns = array();

		if ($res->num_rows > 0) {
			while ($asgn = $res->fetch_assoc()) {
				$asgns[$asgn["asgn_id"]] = $asgn;
			}
			return $asgns;
		} else{
			die("<span style=\"color:red;\"><b>
				You must <a href=\"add_asgn.php\">add an assignment</a>.</b></span>");
		}
	}

	function get_all_assignment_data($conn, $asgn_id) {
		$res = $conn->query("SELECT * FROM assignment_meta WHERE asgn_id = ".$asgn_id);
		$asgns = array();

		if ($res->num_rows > 0) {
			while ($asgn = $res->fetch_assoc()) {
				$asgns[$asgn["class_pd"]] = $asgn;
			}
			return $asgns;
		} else{
			die("<span style=\"color:red;\"><b>
				You must <a href=\"add_asgn.php\">add an assignment</a>.</b></span>");
		}
	}
?>