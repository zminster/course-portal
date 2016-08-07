<html>
<head>
	<title>Portal Admin :: Toggle Amnesty Period</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Toggle Amnesty Period</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$classes = get_class_pds($conn);

	if ($method == 'POST') {	// POST: admin is toggling

		$period = $_POST['period'];
		$amnesty = array_key_exists("amnesty", $_POST) ? 1 : 0;

		$db_toggle = $conn->prepare("UPDATE class SET amnesty=? WHERE class_pd=?");
		$db_toggle->bind_param("ii", $amnesty, $period);
		$db_toggle->execute();
		echo($db_toggle->error);

		?><p style="font-weight:bold">Period <?php echo($period); ?> Amnesty <?php echo($amnesty == 1 ? "ON" : "OFF"); ?></p>
		<p><a href="edit_amnesty.php">Again</a> | <a href="index.php">Menu</a></p><?php

	} else {	// GET: display add class screen

		$res = $conn->query("SELECT * FROM class");

		if ($res->num_rows > 0) {
			?><div>Existing class periods:</div><ul><?php
			while ($class_pd = $res->fetch_assoc()) {
				?><li>
					<?php echo($class_pd["class_pd"]); ?> - 
					<?php echo($class_pd["amnesty"] == 1 ? "ON" : "OFF"); ?>
				</li>
			<?php } ?>
			</ul>
			<?php } ?>

		<form action="edit_amnesty.php" method="post">
			<div><label for="period">Class Period:</label>
				<select id="period" name="period"><?php
					foreach ($classes as $class_pd) {
						?><option value="<?php echo($class_pd);?>">
							<?php echo($class_pd); ?>
						</option><?php
					}?>
				</select>
			</div>
			<div><label for="amnesty">Amnesty?</label><input type="checkbox" id="amnesty" name="amnesty" /></div>
			<input type="submit" />
		</form><?php
	}
	?>
</body>
</html>