<html>
<head>
	<title>Portal Admin :: Add Assignment Type</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Add Assignment Type</h1>
	<?php
	include 'config/database.php';

	$res = $conn->query("SELECT * FROM assignment_type");

	if ($res->num_rows > 0) {

		?><div>Existing assignment types:</div>
		<table>
			<tr>
				<th>Type ID</th>
				<th>Name</th>
				<th>Weight</th>
			</tr>
			<?php
		while ($type = $res->fetch_assoc()) {
			?><tr>
				<td><?php echo($type["type_id"]); ?></td>
				<td><?php echo($type["name"]); ?></td>
				<td><?php echo($type["weight"]); ?></td>
			</tr>
		<?php } ?>
		</table>
		<?php

	}

	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST: admin is adding type

		$name = $_POST['name'];
		$weight = $_POST['weight'];

		$class_insert = $conn->prepare("INSERT INTO assignment_type (name, weight) VALUES (?, ?)");
		$class_insert->bind_param("sd", $name, $weight);
		$class_insert->execute();

		?><p style="font-weight:bold">Type Added</p>
		<p><a href="add_asgn_type.php">Again</a> | <a href="index.php">Menu</a></p><?php

	} else {	// GET: display add type screen

		?><form action="add_asgn_type.php" method="post">
		<div>Name: <input type="text" name="name" placeholder="e.g. Lab" /></div>
		<div>Weight: <input type="text" name="weight" placeholder="e.g. 0.3" /></div>
		<input type="submit">
		</form><?php
	}
	?>
</body>
</html>