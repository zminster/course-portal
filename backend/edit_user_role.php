<html>
<head>
	<title>Portal Admin :: Edit/Delete User Role</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Edit/Delete User Role</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST
		$rid = $_POST['rid'];

		if (array_key_exists("delete", $_POST)) {	// delete role
			$del_1 = $conn->prepare("DELETE FROM user_role WHERE rid = ?");
			$del_1->bind_param("i", $rid);
			// foreign key constraint violation is likely; error message will print from DB
			$del_1->execute();

			if (strpos($del_1->error, "foreign key") !== false) {
				?><p style="font-weight:bold; color: red;">Could not delete Role # <?php echo($rid); ?>: users are still assigned to this Role (<a href="report_users.php">modify/delete them first</a>).</p><?php
			} else if ($del_1->error) {
				?><p style="font-weight:bold; color: red;"><echo($del_1->error);</p><?php
			} else {
				?><p style="font-weight:bold">Role #<?php echo($rid); ?> [<?php echo($_POST["name"]);?>] Deleted</p><?php
			}
			?>
			<p><a href="index.php">Menu</a></p><?php
		} else if(array_key_exists("edit", $_POST)) {	// edit format
			$access_backend = array_key_exists("access_backend", $_POST) ? 1 : 0;
			$class_membership = array_key_exists("class_membership", $_POST) ? 1 : 0;
			$handin_enabled = array_key_exists("handin_enabled", $_POST) ? 1 : 0;
			$reporting_enabled = array_key_exists("reporting_enabled", $_POST) ? 1 : 0;

			// run update query
			$role_update	= $conn->prepare("UPDATE user_role SET name=?, access_backend=?, class_membership=?, handin_enabled=?, reporting_enabled=? WHERE rid=?");
			$role_update->bind_param("siiiii", $_POST['name'], $access_backend, $class_membership, $handin_enabled, $reporting_enabled, $rid);
			$role_update->execute();
			echo($role_update->error);

			?><p style="font-weight:bold">Role <?php echo($_POST['name']); ?> (#<?php echo($rid);?>) Updated</p>
			<div><a href="edit_user_role.php">Again</a> | 
			<a href="index.php">Menu</a></div><?php
		} else {	// role selected, display editable fields
			$roles = get_user_roles($conn);
			$role = $roles[$rid];
			?>
			<form action="edit_user_role.php" method="post">
				<input type="hidden" name="rid" value="<?php echo($rid);?>" />
				<input type="hidden" name="edit" value="1" />

				<div><label for="delete">Delete?</label><input type="checkbox" id="delete" name="delete" /></div>
				<p style="color:red;"><em>WARNING!</em> Deletion will not work if any users are still associated with this Role.</p>

				<div><label for="name">Name:</label><input type="text" id="name" name="name" placeholder="(e.g. Parent)" value="<?php echo($role['name']); ?>" /></div>
				<div><label for="access_backend">Can this role access this administrative backend?:</label><input type="checkbox" id="access_backend" name="access_backend" value="1"<?php echo($role['access_backend'] ? "checked" : ""); ?> /></div>
				<div><label for="class_membership">Is this role bound to a specific class period?:</label><input type="checkbox" id="class_membership" name="class_membership" value="1"<?php echo($role['class_membership'] ? "checked" : ""); ?> /></div>
				<div><label for="handin_enabled">(If so,) can this user turn in assignments?:</label><input type="checkbox" id="handin_enabled" name="handin_enabled" value="1"<?php echo($role['handin_enabled'] ? "checked" : ""); ?> /></div>
				<div><label for="reporting_enabled">(If so,) do their grades affect reporting?:</label><input type="checkbox" id="reporting_enabled" name="reporting_enabled" value="1"<?php echo($role['reporting_enabled'] ? "checked" : ""); ?> /></div>
				
				<input type="submit" />
			</form>
		<?php }
	} else {	// GET: display edit options
		$roles = get_user_roles($conn);
		?><form action="edit_user_role.php" method="POST">
			<p>Select a Role to edit.</p>
			<div><select name="rid">
				<?php
				foreach ($roles as $role) {
					?><option value="<?php echo($role['rid']);?>">
						<?php echo($role["rid"]);?>) <?php echo($role["name"]);?>
					</option><?php
				} ?>
			</select></div>
			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>