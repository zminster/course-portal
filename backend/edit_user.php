<html>
<head>
	<title>Portal Admin :: Edit/Delete Student</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Edit/Delete Student</h1>
	<?php
	include 'config/database.php';
	include 'common/password_generator.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST
		$uid = $_POST['uid'];

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
			$del_3->execute();
			echo($del_3->error);
			$del_2->execute();
			echo($del_2->error);
			$del_1->execute();
			echo($del_1->error);
			?><p style="font-weight:bold">Student <?php echo($uid); ?> [<?php echo($_POST["name"]);?>] Deleted</p>
			<p><a href="index.php">Menu</a></p><?php
		} else if(array_key_exists("edit", $_POST)) {	// edit assignment, update all fields to match form values
		
			// create queries
			$username_update	= $conn->prepare("UPDATE user SET username=? WHERE uid=?");
			$password_change	= $conn->prepare("UPDATE user SET password=?, change_flag=1 WHERE uid=?");
			$meta_update		= $conn->prepare("UPDATE user_meta SET name=?, year=?, email=? WHERE uid=?");
			$period_update		= $conn->prepare("UPDATE membership SET class_pd=? WHERE uid = ?");

			// bindings
			$username_update->bind_param("si", $_POST["username"], $uid);
			$password_change->bind_param("si", $password, $uid);
			$meta_update->bind_param("sssi", $_POST["name"], $_POST["year"], $_POST["email"], $uid);
			$period_update->bind_param("ii", $_POST["class_pd"], $uid);

			// exec default ("always") edits
			$username_update->execute();
			echo($username_update->error);
			$meta_update->execute();
			echo($meta_update->error);
			$period_update->execute();
			echo($period_update->error);

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
			?><p style="font-weight:bold">User Updated: <?php echo($_POST["name"]); ?></p>
			<div><a href="edit_user.php">Again</a> | 
			<a href="index.php">Menu</a></div><?php
		} else {	// user selected, display user information
			$users = get_all_userinfo($conn);
			$user = $users[$uid];
			?>
			<form action="edit_user.php" method="post">
				<input type="hidden" name="uid" value="<?php echo($uid);?>" />
				<input type="hidden" name="edit" value="1" />

				<div><label for="delete">Delete?</label><input type="checkbox" id="delete" name="delete" /></div>
				<p>WARNING! Delete irreversibly removes grades and handin records.</p>

				<div><label for="changepass">Regen Password?</label><input type="checkbox" id="changepass" name="changepass" /></div>

				<div><label for="class_pd">Class Period:</label><?php

					$classes = get_class_pds($conn);

					?><select id="class_pd" name="class_pd"><?php
					foreach ($classes as $class_pd) {
						?><option value="<?php echo($class_pd);?>"
							<?php echo($class_pd == $user["class_pd"] ? "selected" : "");?>>
							<?php echo($class_pd); ?>
						</option><?php
					}?>
					</select>
				</div>

				<div><label for="username">Username:</label><input type="text" id="username" name="username" placeholder="Username" value="<?php echo($user["username"]); ?>" /></div>
				<div><label for="name">Name:</label><input type="text" id="name" name="name" placeholder="Name" value="<?php echo($user["name"]); ?>" /></div>
				<div><label for="year">Year:</label><input type="text" id="year" name="year" placeholder="e.g. 12" value="<?php echo($user["year"]); ?>" /></div>
				<div><label for="email">Email:</label><input type="text" id="email" name="email" placeholder="Email" value="<?php echo($user["email"]); ?>" /></div>
				
				<input type="submit" />
			</form>
		<?php }
	} else {	// GET: display edit options
		$users = get_all_userinfo($conn);
		?><form action="edit_user.php" method="POST">
			<p>Select a user to edit.</p>
			<div><select name="uid">
				<?php
				foreach ($users as $user) {
					?><option value="<?php echo($user["uid"]);?>">
						<?php echo($user["class_pd"]);?>) <?php echo($user["username"]);?> [<?php echo($user["name"]); ?>]
					</option><?php
				} ?>
			</select></div>
			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>