<html>
<head>
	<title>Portal Admin :: Add User (Single)</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>
<body>
	<h1>Add User (Single)</h1>
	<?php
	include 'config/database.php';
	include 'common/password_generator.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$roles = get_user_roles($conn);

	if ($method == 'POST') {	// POST: admin is adding (single) user

		// populate vars
		$role = $_POST['role'];
		$period = $_POST['period'];
		$username = $_POST['username'];
		$first_name = $_POST['first_name'];
		$last_name = $_POST['last_name'];
		$year = $_POST['year'];
		$email = $_POST['email'];
		$advisor_email = $_POST['advisor_email'];

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
		$user_insert = $conn->prepare("INSERT INTO user (username, password, change_flag, role) VALUES (?, ?, 1, ?)");
		$uid_lookup  = $conn->prepare("SELECT uid FROM user WHERE username = ?");
		$meta_insert = $conn->prepare("INSERT INTO user_meta (uid, first_name, last_name, year, email, advisor_email) VALUES (?, ?, ?, ?, ?, ?)");
		$membership  = $conn->prepare("INSERT INTO membership (uid, class_pd) VALUES (?, ?)");
		$grades		 = $conn->prepare("INSERT INTO grades (uid, asgn_id, nreq, handed_in, late, chomped, can_view_feedback) VALUES (?, ?, 0, 0, 0, 0, 0)");

		$user_insert->bind_param("ssi", $username, $password, $role);
		$uid_lookup->bind_param("s", $username);
		$uid_lookup->bind_result($uid);
		$meta_insert->bind_param("ississ", $uid, $first_name, $last_name, $year, $email, $advisor_email);
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

		// insert into membership (if Role requires)
		if ($roles[$role]['class_membership']) {
			$membership->execute();
			echo($membership->error);
		}

		// insert blank grades if assignments already exist
		foreach ($asgns as $asgn_id) {
			$grades->execute();
			echo($grades->error);
		}

		// report result
		?>
			<h2><?php echo($roles[$role]['name']); ?> User Added<?php echo($roles[$role]['class_membership'] ? " :: Period " . $period : ""); ?></h2>		<div><?php echo($uid); ?>,<b><?php echo($username); ?></b>,<?php echo($first_name . " " . $last_name); ?>,
			<?php echo($gen_pass); ?></div>
		
		<div><a href="add_user_single.php">Again</a> | 
		<a href="index.php">Menu</a></div><?php
	} else {	// GET: display add user screen
		$class_pds = get_class_pds($conn);
		?><form action="add_user_single.php" method="post">

			<div>
				<label for="role">Role:</label>
				<select id="role" name="role">
					<?php
					foreach ($roles as $role) {
						?><option value="<?php echo($role["rid"]);?>">
							<?php echo($role["rid"]);?>) <?php echo($role["name"]);?>
						</option><?php
					} ?>
				</select>
			</div>

			<div id="class_pd" style="display:none;">
				<label for="period">Class Period (<a href="add_class.php">Add?</a>):</label>
				<select id="period" name="period"><?php
					foreach ($class_pds as $class_pd) {
						?><option value="<?php echo($class_pd);?>">
							<?php echo($class_pd); ?>
						</option><?php
					}?>
				</select><span style="font-weight:bold; color:LimeGreen;"> (Users in this role require a section assignment.)</span>
			</div>

			<div><label for="username">Username:</label><input type="text" id="username" name="username" placeholder="jcarberr" /></div>
			<div><label for="first_name">First Name:</label><input type="text" id="first_name" name="first_name" placeholder="Josiah" /></div>
			<div><label for="last_name">Last Name:</label><input type="text" id="last_name" name="last_name" placeholder="Carberry" /></div>
			<div><label for="year">Grade:</label><input type="text" id="year" name="year" placeholder="10" /></div>
			<div><label for="email">Email:</label><input type="text" ie="email" name="email" placeholder="jcarberr@brown.edu" /></div>
			<div><label for="advisor_email">Advisor Email:</label><input type="text" ie="advisor_email" name="advisor_email" placeholder="jcarberr@brown.edu" /></div>

		<input type="submit">
		</form>
		<!-- Require class membership information only if Role dictates it -->
		<script type="text/javascript">
			$('#role').val('');
			$('#period').val('');
			$('#role').change(function() {
				var rid = parseInt($('select#role option:selected').val());
				if (<?php foreach($roles as $role) { if ($role['class_membership']) echo("rid===" . $role['rid'] . "||"); } ?>false) {
					$("#class_pd").show();
				} else {
					$("#class_pd").hide();
				}
			});
		</script>
		<?php
	}
	?>
</body>
</html>