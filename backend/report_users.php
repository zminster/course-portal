<html>
<head>
	<title>Portal Admin :: All Users</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script> 
</head>
<body class="report userreport">

	<h1>All Users</h1>

	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$users = get_all_userinfo($conn);
	$roles = get_user_roles($conn);

	foreach ($roles as $role) {
		echo("<h2>" . $role['name'] . "</h2>");
		?><table border="1">
			<tr>
				<th>Username</th>
				<th>Name</th>
				<th>UID</th>
				<?php echo($role['class_membership'] ? "<th>Class Period</th>" : ""); ?>
				<th>Edit/Delete</th>
			</tr>
		<?php
		foreach ($users as $user) {
			if ($user['role'] === $role['rid']) {
				?><tr>
					<td><?php echo($user['username']); ?></td>
					<td><?php echo($user['last_name'] . ", " . $user['first_name']); ?></td>
					<td><?php echo($user['uid']); ?></td>
					<?php if ($role['class_membership']) { ?><td><?php echo($user['class_pd']); ?></td><?php } ?>
					<td><span class="edit" id="<?php echo($user['uid']); ?>">Edit/Delete</span></td>
				</tr><?php
			}
		} ?>
		</table><?php
	}
	?>
	<form method="POST" action="edit_user.php" id="edit_form">
		<input type="hidden" name="uid" id="uid" />
	</form>
	<a href="index.php">Menu</a></div>
	<script type="text/javascript">
		$('.edit').click(function() {
			$('#uid').val($(this).attr('id'));
			$('#edit_form').submit();
		})
	</script>
</body>
</html>