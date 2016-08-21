<html>
<head>
	<title>Portal Admin :: Add User (Single)</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Add User (Single)</h1>
	<?php
	include 'config/database.php';
	include 'common/password_generator.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST: admin is adding (single) user

		// populate vars
		$period = $_POST['period'];
		$username = $_POST['username'];
		$first_name = $_POST['first_name'];
		$last_name = $_POST['last_name'];
		$year = $_POST['year'];
		$email = $_POST['email'];

		// get existing assignments first
		$asgn_select = $conn->prepare("SELECT asgn_id FROM assignment");
		$asgns = [];
		$asgn_select->bind_result($asgn_id);
		$asgn_select->execute();
		$asgn_select->store_result();
		echo($asgn_select->error);
		while($asgn_select->fetch()) {
			array_push($asgns, $asgn_id);
		}

		// initialize & bind queries
		$user_insert = $conn->prepare("INSERT INTO user (username, password, change_flag) VALUES (?, ?, 1)");
		$uid_lookup  = $conn->prepare("SELECT uid FROM user WHERE username = ?");
		$meta_insert = $conn->prepare("INSERT INTO user_meta (uid, first_name, last_name, year, email) VALUES (?, ?, ?, ?, ?)");
		$membership  = $conn->prepare("INSERT INTO membership (uid, class_pd) VALUES (?, ?)");
		$grades		 = $conn->prepare("INSERT INTO grades (uid, asgn_id, nreq, handed_in, late, chomped, can_view_feedback) VALUES (?, ?, 0, 0, 0, 0, 0)");

		$user_insert->bind_param("ss", $username, $password);
		$uid_lookup->bind_param("s", $username);
		$uid_lookup->bind_result($uid);
		$meta_insert->bind_param("issis", $uid, $first_name, $last_name, $year, $email);
		$membership->bind_param("ii", $uid, $period);
		$grades->bind_param("ii", $uid, $asgn_id);

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

		// insert blank grades if assignments already exist
		foreach ($asgns as $asgn_id) {
			$grades->execute();
			echo($grades->error);
		}

		// report result
		?>
		<h2>Student Added :: Period <?php echo($period); ?></h2>
		<div><?php echo($uid); ?>,<b><?php echo($username); ?></b>,<?php echo($first_name . " " . $last_name); ?>,
			<?php echo($gen_pass); ?></div>
		
		<div><a href="add_user_single.php">Again</a> | 
		<a href="index.php">Menu</a></div><?php
	} else {	// GET: display add user screen

		?><form action="add_user_single.php" method="post">
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

			<div><input type="text" name="username" placeholder="Username" /></div>
			<div><input type="text" name="first_name" placeholder="First Name" /></div>
			<div><input type="text" name="last_name" placeholder="Last Name" /></div>
			<div><input type="text" name="year" placeholder="Grade" /></div>
			<div><input type="text" name="email" placeholder="Email" /></div>
			<?php

		} else{
			die("<span style=\"color:red;\"><b>
				You must <a href=\"add_user.php\">add a class</a>.</b></span>");
		}?>

		<input type="submit">
		</form><?php
	}
	?>
</body>
</html>