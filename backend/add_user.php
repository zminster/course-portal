<html>
<head>
	<title>Portal Admin :: Add User (Batch)</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Add User (Batch)</h1>
	<?php
	include 'config/database.php';
	include 'common/password_generator.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST: admin is adding users

		$period = $_POST['period'];

		$students = explode("\n", $_POST['students']);

		$user_insert = $conn->prepare("INSERT INTO user (username, password, change_flag) VALUES (?, ?, 1)");
		$uid_lookup  = $conn->prepare("SELECT uid FROM user WHERE username = ?");
		$meta_insert = $conn->prepare("INSERT INTO user_meta (uid, name, year, email) VALUES (?, ?, ?, ?)");
		$membership  = $conn->prepare("INSERT INTO membership (uid, class_pd) VALUES (?, ?)");

		$user_insert->bind_param("ss", $username, $password);
		$uid_lookup->bind_param("s", $username);
		$uid_lookup->bind_result($uid);
		$meta_insert->bind_param("isis", $uid, $name, $year, $email);
		$membership->bind_param("ii", $uid, $period);
		?><h2>Students Added :: Period <?php echo($period) ?></h2><ul><?php
		$ct = 0;

		foreach ($students as $student) {
			$ct++;
			$data = explode(",", $student);

			if (count($data) != 4)
				die("MALFORMED STUDENT LINE " + $ct + ": " + $student);

			// extract vars
			$username = $data[0];
			$name = $data[1];
			$year = $data[2];
			$email = $data[3];

			// generate password
			$gen_pass = generate_easy_password();
			$password = password_hash($gen_pass, PASSWORD_BCRYPT);
			$password[2] = 'a';

			// insert into auth table
			$user_insert->execute();
			echo($user_insert->error); 

			// find assigned UID
			$uid_lookup->execute();
			echo($uid_lookup->error); 
			$uid_lookup->fetch();
			$uid_lookup->free_result();

			// insert into user meta
			$meta_insert->execute();
			echo($meta_insert->error); 

			// insert into membership
			$membership->execute();
			echo($membership->error);

			// report result
			?>
			<li><?php echo($uid); ?>,<b><?php echo($username); ?></b>,<?php echo($name); ?>,<?php echo($gen_pass); ?></li>
			<?php
		}
		?></ul><div><a href="add_user.php">Again</a> | 
		<a href="index.php">Menu</a></div><?php
	} else {	// GET: display add user screen

		?><form action="add_user.php" method="post">
		<div>Class Period (<a href="add_class.php">Add</a>):<?php
		
		$res = $conn->query("SELECT * FROM class");

		if ($res->num_rows > 0) {

			?><select name="period"><?php
			while ($class_pd = $res->fetch_assoc()) {
				?><option value="<?php echo($class_pd["class_pd"]);?>">
					<?php echo($class_pd["class_pd"]); ?>
				</option><?php
			}?>
			</select></div>

			<div>Add users on newlines in the following comma-separated
			format: [username],[name],[year],[email]</div>
			<?php

		} else{
			die("<span style=\"color:red;\"><b>
				You must <a href=\"add_class.php\">add a class</a>.</b></span>");
		}?>

		<textarea id="students" name="students"></textarea>
		<input type="submit">
		</form><?php
	}
	?>
</body>
</html>