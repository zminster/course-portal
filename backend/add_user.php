<html>
<head>
	<title>Portal Admin :: Add User (Batch)</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script> 
</head>
<body>
	<h1>Add User (Batch)</h1>
	<?php
	include 'config/database.php';
	include 'common/password_generator.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$roles = get_user_roles($conn);

	if ($method == 'POST') {	// POST: admin is adding users

		$period = $_POST['period'];
		$role = $_POST['role'];

		$students = explode("\n", $_POST['students']);

		$user_insert = $conn->prepare("INSERT INTO user (username, password, change_flag, role) VALUES (?, ?, 1, ?)");
		$uid_lookup  = $conn->prepare("SELECT uid FROM user WHERE username = ?");
		$meta_insert = $conn->prepare("INSERT INTO user_meta (uid, first_name, last_name, year, email) VALUES (?, ?, ?, ?, ?)");
		$membership  = $conn->prepare("INSERT INTO membership (uid, class_pd) VALUES (?, ?)");
		$asgn_select = $conn->prepare("SELECT asgn_id FROM assignment");
		$grades		 = $conn->prepare("INSERT INTO grades (uid, asgn_id, nreq, handed_in, late, chomped, can_view_feedback) VALUES (?, ?, 0, 0, 0, 0, 0)");

		// get existing assignments first
		$asgns = [];
		$asgn_select->bind_result($asgn_id);
		$asgn_select->execute();
		$asgn_select->store_result();
		echo($asgn_select->error);
		while($asgn_select->fetch()) {
			array_push($asgns, $asgn_id);
		}

		$user_insert->bind_param("ssi", $username, $password, $role);
		$uid_lookup->bind_param("s", $username);
		$uid_lookup->bind_result($uid);
		$meta_insert->bind_param("issis", $uid, $first_name, $last_name, $year, $email);
		$membership->bind_param("ii", $uid, $period);
		$grades->bind_param("ii", $uid, $asgn_id);
		?><h2><?php echo($roles[$role]['name']); ?> Users Added<?php echo($roles[$role]['class_membership'] ? " :: Period " . $period : ""); ?></h2><ul><?php
		$ct = 0;

		foreach ($students as $student) {
			$ct++;
			$data = explode(",", $student);

			if (count($data) != 5)
				die("MALFORMED STUDENT LINE " + $ct + ": " + $student);

			// extract vars
			$username = trim($data[0]);
			$last_name = trim($data[1]);
			$first_name = trim($data[2]);
			$year = trim($data[3]);
			$email = trim($data[4]);

			// generate password
			$gen_pass = generate_easy_password();
			$password = password_hash($gen_pass, PASSWORD_BCRYPT);
			$password[2] = 'a';
			// insert into auth table
			$user_insert->execute();
			echo($user_insert->error); 

			// find assigned UID
			$uid_lookup->execute();
			$uid_lookup->bind_result($uid);
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
			<li><?php echo($uid); ?>,<b><?php echo($username); ?></b>,<?php echo($first_name . " " . $last_name); ?>,<?php echo($gen_pass); ?></li>
			<?php
		}
		?></ul><div><a href="add_user.php">Again</a> | 
		<a href="index.php">Menu</a></div><?php
	} else {	// GET: display add user screen
		$class_pds = get_class_pds($conn);
		?><form action="add_user.php" method="post">
		<div>
			<label for="role">Role for these users:</label>
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

		<div>Add users on newlines in the following comma-separated
		format: [username],[last name],[first name],[year],[email]</div>

		<textarea id="students" name="students"></textarea>
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
