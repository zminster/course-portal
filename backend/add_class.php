<html>
<head>
	<title>Portal Admin :: Add Class</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Add Class Period</h1>
	<?php
	include 'config/database.php';

	$res = $conn->query("SELECT * FROM class");

	if ($res->num_rows > 0) {

		?><div>Existing class periods:</div><ul><?php
		while ($class_pd = $res->fetch_assoc()) {
			?><li>
				<?php echo($class_pd["class_pd"]); ?>
			</li>
		<?php } ?>
		</ul>
		<?php

	}

	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST: admin is adding class

		$period = $_POST['period'];

		// STEP 1: create class entry
		$class_insert = $conn->prepare("INSERT INTO class (class_pd, amnesty) VALUES (?, 0)");
		$class_insert->bind_param("i", $period);
		$class_insert->execute();
		echo($class_insert->error);

		// STEP 2: create assignment meta rows for class (handle existing assignments)
		$asgn_select = $conn->prepare("SELECT asgn_id FROM assignment");
		$meta_insert = $conn->prepare("INSERT INTO assignment_meta (asgn_id, class_pd, date_out, date_due, displayed, can_handin, info_changed) VALUES (?, ?, '1000-01-01 00:00:00', '1000-01-01 00:00:00', 0, 0, 0)");
		$meta_insert->bind_param("ii", $asgn_id, $period);

		// 2.1: get all existing assignments
		$asgns = [];
		$asgn_select->bind_result($asgn_id);
		$asgn_select->execute();
		$asgn_select->store_result();
		echo($asgn_select->error);
		while($asgn_select->fetch()) {
			array_push($asgns, $asgn_id);
		}

		// 2.2: insert assignment meta rows
		foreach ($asgns as $asgn_id) {
			$meta_insert->execute();
			echo($meta_insert->error);
		}

		?><p style="font-weight:bold">Class Added :: Period <?php echo($period); ?></p>
		<p><a href="add_class.php">Again</a> | <a href="index.php">Menu</a></p><?php

	} else {	// GET: display add class screen

		?><form action="add_class.php" method="post">
		<div>New Period: <input type="text" name="period" placeholder="e.g. 4" /></div>
		<input type="submit">
		</form><?php
	}
	?>
</body>
</html>