<html>
<head>
	<title>Portal Admin :: Add User Role</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Add User Role</h1>
	<?php
	include 'config/database.php';

	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST: admin is adding role
		$access_backend = array_key_exists("access_backend", $_POST) ? 1 : 0;
		$class_membership = array_key_exists("class_membership", $_POST) ? 1 : 0;
		$handin_enabled = array_key_exists("handin_enabled", $_POST) ? 1 : 0;
		$reporting_enabled = array_key_exists("reporting_enabled", $_POST) ? 1 : 0;

		$role_insert = $conn->prepare("INSERT INTO user_role (name, access_backend, class_membership, handin_enabled, reporting_enabled) VALUES (?, ?, ?, ?, ?)");
		$role_insert->bind_param("siiii", $_POST['name'], $access_backend, $class_membership, $handin_enabled, $reporting_enabled);
		$role_insert->execute();

		?><p style="font-weight:bold"><?php echo($_POST['name']); ?> Role Added</p>
		<p><a href="add_user_role.php">Again</a> | <a href="index.php">Menu</a></p><?php

	} else {	// GET: display add type screen

			$res = $conn->query("SELECT * FROM user_role");

	if ($res->num_rows > 0) {

		?><h2>Existing Roles:</h2>
			<a href="edit_user_role.php">(Edit an existing role)</a>
			<table>
				<tr>
					<th>Role ID</th>
					<th>Name</th>
					<th>Backend Administrator?</th>
					<th>Bound to Specific Class?</th>
					<th>...&amp; Turns in / Recieves Grades?</th>
					<th>...&amp; Affects Reporting?</th>
				</tr>
				<?php
			while ($role = $res->fetch_assoc()) {
				?><tr>
					<td><?php echo($role["rid"]); ?></td>
					<td><?php echo($role["name"]); ?></td>
					<td><?php echo($role["access_backend"] ? "YES" : "NO"); ?></td>
					<td><?php echo($role["class_membership"] ? "YES" : "NO"); ?></td>
					<td><?php echo($role["handin_enabled"] ? "YES" : "NO"); ?></td>
					<td><?php echo($role["reporting_enabled"] ? "YES" : "NO"); ?></td>
				</tr>
			<?php } ?>
			</table>
			<?php

		}

		?><form action="add_user_role.php" method="post">

		<h2>New Role Information:</h2>

			<div><label for="name">Name:</label><input type="text" id="name" name="name" placeholder="(e.g. Parent)" /></div>
			<div><label for="access_backend">Can this role access this administrative backend?:</label><input type="checkbox" id="access_backend" name="access_backend" value="1" /></div>
			<div><label for="class_membership">Is this role bound to a specific class period?:</label><input type="checkbox" id="class_membership" name="class_membership" value="1" /></div>
			<div><label for="handin_enabled">(If so,) can this user turn in assignments?:</label><input type="checkbox" id="handin_enabled" name="handin_enabled" value="1" /></div>
			<div><label for="reporting_enabled">(If so,) do their grades affect reporting?:</label><input type="checkbox" id="reporting_enabled" name="reporting_enabled" value="1" /></div>

		<input type="submit">
		</form><?php
	}
	?>
</body>
</html>