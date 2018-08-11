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
				$types[$type["type_id"]] = $type;
			}
			return $types;
		} else{
			die("<span style=\"color:red;\"><b>
				You must <a href=\"add_asgn_type.php\">add an assignment type</a>.</b></span>");
		}
	}

	function get_asgn_formats($conn) {
		$res = $conn->query("SELECT * FROM assignment_format");
		$formats = array();

		if ($res->num_rows > 0) {
			while ($format = $res->fetch_assoc()) {
				$formats[$format["format_id"]] = $format;
			}
			return $formats;
		} else{
			die("<span style=\"color:red;\"><b>
				You must <a href=\"add_asgn_format.php\">add an assignment format</a>.</b></span>");
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

	function get_all_lessons($conn) {
		$res = $conn->query("SELECT * FROM lesson");
		$lessons = array();

		if ($res->num_rows > 0) {
			while ($lesson = $res->fetch_assoc()) {
				$lessons[$lesson["id"]] = $lesson;
			}
			return $lessons;
		} else{
			die("<span style=\"color:red;\"><b>
				You must <a href=\"add_asgn.php\">add a lesson</a>.</b></span>");
		}
	}

	function get_all_lesson_data($conn, $id) {
		$res = $conn->query("SELECT * FROM lesson_meta WHERE id = ".$id);
		$lessons = array();

		if ($res->num_rows > 0) {
			while ($lesson = $res->fetch_assoc()) {
				$lessons[$lesson["class_pd"]] = $lesson;
			}
			return $lessons;
		} else{
			die("<span style=\"color:red;\"><b>
				You must <a href=\"add_lesson.php\">add a lesson</a>.</b></span>");
		}
	}

	function get_all_userinfo($conn) {
		$res = $conn->query("SELECT user.uid, class_pd, username, first_name, last_name, year, email FROM user INNER JOIN membership ON user.uid = membership.uid INNER JOIN user_meta ON user.uid = user_meta.uid ORDER BY class_pd, username");
		$users = array();

		if ($res->num_rows > 0) {
			while ($user = $res->fetch_assoc()) {
				$users[$user["uid"]] = $user;
			}
			return $users;
		}  else{
			die("<span style=\"color:red;\"><b>
				You must <a href=\"add_user.php\">add users</a>.</b></span>");
		}
	}
?>