<?php
	include 'database.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// admin is adding users

		$period = $_POST['period'];

		$students = explode("\n", $_POST['students']);

		$user_insert = $conn->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
		$uid_lookup  = $conn->prepare("SELECT uid FROM user WHERE username = ?");
		$meta_insert = $conn->prepare("INSERT INTO user_meta (uid, name, year, email) VALUES (?, ?, ?, ?)");
		$membership  = $conn->prepare("INSERT INTO membersip (uid, class_pd)");

		$user_insert->bind_param("ss", $username, $password);
		$uid_lookup->bind_param("s", $username);
		$meta_insert->bind_param("isis", $uid, $name, $year, $email);
		$membership->bind_param("ii", $uid, $period);

		foreach ($students as $student) {
			// USER TABLE FORMAT: USERNAME, NAME, YEAR, EMAIL
			$data = explode(",", $student);

			$username = $data[0];
			$name = $data[1];
			$year = $data[2];
			$email = $data[3];

			$password = password_bcrypt("password");

			$user_insert->execute();

			$uid = $uid_lookup->execute()->fetch_assoc()["uid"];

			?>
			<li><?php echo($uid); ?>,<b><?php echo($username); ?></b>,<?php echo($password); ?></li>
			<?php
			$meta_insert->execute();
			$membership->execute();
		}
	} else {	// display add user screen

	}
?>