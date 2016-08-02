<html>
<head>
	<title>Portal Admin :: Add Class</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Add Class</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST: admin is adding class

		$period = $_POST['period'];

		$class_insert = $conn->prepare("INSERT INTO class (class_pd) VALUES (?)");
		$class_insert->bind_param("i", $period);
		$class_insert->execute();

		?><h2>Class Added :: Period <?php echo($period) ?></h2>
		<div><a href="add_class.php">Again</a> | <a href="index.php">Menu</a></div><?php

	} else {	// GET: display add class screen

		?><form action="add_class.php" method="post">
		<div>New Period: <input type="text" name="period" placeholder="e.g. 4" /></div>
		<input type="submit">
		</form><?php
	}
	?>
</body>
</html>