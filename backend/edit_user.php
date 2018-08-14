<html>
<head>
	<title>Portal Admin :: Edit/Delete User</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script> 
</head>
<body>
	<h1>Edit/Delete User</h1>
	<?php
	include 'config/database.php';
	include 'common/password_generator.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST
		$uid = $_POST['uid'];
		$users = get_all_userinfo($conn);
		$user = $users[$uid];
		$roles = get_user_roles($conn);
		$role = $roles[$user['role']];

		if (array_key_exists("delete", $_POST)) {	// delete assignment & assoc grades
			$del_1 = $conn->prepare("DELETE FROM user WHERE uid = ?");
			$del_2 = $conn->prepare("DELETE FROM user_meta WHERE uid = ?");
			$del_3 = $conn->prepare("DELETE FROM membership WHERE uid = ?");
			$del_4 = $conn->prepare("DELETE FROM grades WHERE uid = ?");
			$del_1->bind_param("i", $uid);
			$del_2->bind_param("i", $uid);
			$del_3->bind_param("i", $uid);
			$del_4->bind_param("i", $uid);
			// execute in reverse order to prevent foreign key constraint violation
			$del_4->execute();
			echo($del_4->error);
			if ($role['class_membership']) {
				$del_3->execute();
				echo($del_3->error);
			}
			$del_2->execute();
			echo($del_2->error);
			$del_1->execute();
			echo($del_1->error);
			?><p style="font-weight:bold">User <?php echo($uid); ?> [<?php echo($_POST["name"]);?>] Deleted</p>
			<p><a href="index.php">Menu</a></p><?php
		} else if(array_key_exists("edit", $_POST)) {	// edit assignment, update all fields to match form values
		
			// create queries
			$username_update	= $conn->prepare("UPDATE user SET username=? WHERE uid=?");
			$password_change	= $conn->prepare("UPDATE user SET password=?, change_flag=1 WHERE uid=?");
			$role_change		= $conn->prepare("UPDATE user SET role=? WHERE uid=?");
			$meta_update		= $conn->prepare("UPDATE user_meta SET first_name=?, last_name=?, year=?, email=? WHERE uid=?");
			$period_update		= $conn->prepare("UPDATE membership SET class_pd=? WHERE uid = ?");
			$membership_removal = $conn->prepare("DELETE FROM membership WHERE uid = ?");
			$membership_add		= $conn->prepare("INSERT INTO membership (uid, class_pd) VALUES (?, ?)");

			// bindings
			$username_update->bind_param("si", $_POST["username"], $uid);
			$password_change->bind_param("si", $password, $uid);
			$role_change->bind_param("ii", $_POST["role"], $uid);
			$meta_update->bind_param("ssssi", $_POST["first_name"], $_POST["last_name"], $_POST["year"], $_POST["email"], $uid);
			$period_update->bind_param("ii", $_POST["class_pd"], $uid);
			$membership_removal->bind_param("i", $uid);
			$membership_add->bind_param("ii", $uid, $_POST["class_pd"]);

			// exec default ("always") edits
			$username_update->execute();
			echo($username_update->error);
			$meta_update->execute();
			echo($meta_update->error);

			// conditional role change
			if ($role['rid'] != $_POST['role']) {	// role modified
				$oldrole = $role;
				$role = $roles[$_POST['role']];
				if ($oldrole['class_membership'] && !$role['class_membership']) {
					$membership_removal->execute();
					echo($membership_removal->error);
					echo("<p>User removed from class: " . $user['class_pd'] . "</p>");
				} else if ($role['class_membership'] && !$oldrole['class_membership']) {
					$membership_add->execute();
					echo($membership_add->error);
					echo("<p>User added to class: " . $_POST["class_pd"] . "</p>");
				}
				$role_change->execute();
				echo($role_change->error);
				echo("<p>Role changed to: " . $role['name'] . "</p>");
			}

			// conditional membership change
			if ($role['class_membership']) {	// role includes class membership
				$period_update->execute();
				echo($period_update->error);
			}

			// conditional password change
			if (array_key_exists("changepass", $_POST)) {
				$gen_pass = generate_easy_password();
				$password = password_hash($gen_pass, PASSWORD_BCRYPT);
				$password[2] = 'a';
				$password_change->execute();
				echo($password_change->error);

				?><p>Password changed to: <span style="font-weight:bold">
					<?php echo($gen_pass); ?></span></p><?php
			}
			?><p style="font-weight:bold">User Updated: <?php echo($_POST["first_name"] . " " . $_POST["last_name"]); ?></p>
			<div><a href="edit_user.php">Again</a> | 
			<a href="index.php">Menu</a></div><?php
		} else {	// user selected, display user information
			?>
			<form action="edit_user.php" method="post">
				<input type="hidden" name="uid" value="<?php echo($uid);?>" />
				<input type="hidden" name="edit" value="1" />
				<input type="hidden" id="membership" name="membership" value="<?php echo($role['class_membership']); ?>" />


				<div><label for="delete">Delete?</label><input type="checkbox" id="delete" name="delete" /><span id="delwarning" style="font-weight:bold; color:red;"> WARNING! Delete irreversibly removes grades and handin records.</span></div>

				<div><label for="changepass">Regen Password?</label><input type="checkbox" id="changepass" name="changepass" /></div>

				<div>
					<label for="role">Role:</label>
					<select id="role" name="role">
						<?php
						foreach ($roles as $iterrole) {
							?><option value="<?php echo($iterrole["rid"]);?>"
								<?php echo($role["rid"] == $iterrole["rid"] ? "selected" : ""); ?>>
								<?php echo($iterrole["rid"]);?>) <?php echo($iterrole["name"]);?>
							</option><?php
						} ?>
					</select>
				</div>

				<div id="class_pd"><label for="class_pd">Class Period:</label><?php

					$classes = get_class_pds($conn);

					?><select name="class_pd"><?php
					foreach ($classes as $class_pd) {
						?><option value="<?php echo($class_pd);?>"
							<?php echo($class_pd == $user["class_pd"] ? "selected" : "");?>>
							<?php echo($class_pd); ?>
						</option><?php
					}?>
					</select><span style="font-weight:bold; color:LimeGreen;"> (Users in this role require a section assignment.)</span>
				</div>

				<div><label for="username">Username:</label><input type="text" id="username" name="username" placeholder="Username" value="<?php echo($user["username"]); ?>" /></div>
				<div><label for="first_name">First Name:</label><input type="text" id="first_name" name="first_name" placeholder="First Name" value="<?php echo($user["first_name"]); ?>" /></div>
				<div><label for="last_name">Last Name:</label><input type="text" id="last_name" name="last_name" placeholder="Last Name" value="<?php echo($user["last_name"]); ?>" /></div>
				<div><label for="year">Year:</label><input type="text" id="year" name="year" placeholder="e.g. 12" value="<?php echo($user["year"]); ?>" /></div>
				<div><label for="email">Email:</label><input type="text" id="email" name="email" placeholder="Email" value="<?php echo($user["email"]); ?>" /></div>
				
				<input type="submit" />
			</form>
			<script type="text/javascript">
				<?php echo(!$role['class_membership'] ? "$('#class_pd').hide();" : ""); ?>
				$('#delwarning').hide();
				$("#delete").change(function() { $('#delwarning').toggle(); });
				$('#role').change(function() {
					var rid = parseInt($('select#role option:selected').val());
					if (<?php foreach($roles as $role) { if ($role['class_membership']) echo("rid===" . $role['rid'] . "||"); } ?>false) {
						$("#membership").val(1);
						$("#class_pd").show();
					} else {
						$("#membership").val(0);
						$("#class_pd").hide();
					}
				});
			</script>
		<?php }
	} else {	// GET: display edit options
		$users = get_all_userinfo($conn);
		$roles = get_user_roles($conn);
		?><form action="edit_user.php" method="POST">
			<p>Select a user to edit.</p>
			<div><select name="uid" multiple>
				<?php
				foreach ($users as $user) {
					?><option value="<?php echo($user["uid"]);?>">
						<?php $role = $roles[$user["role"]]; // convenience var ?>
						[<?php echo($role["name"]);?>] <?php echo($role['class_membership'] ? "Pd. ".$user["class_pd"].")" : "");?> <?php echo($user["username"]);?> [<?php echo($user["first_name"]. " " . $user["last_name"]); ?>]
					</option><?php
				} ?>
			</select></div>
			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>