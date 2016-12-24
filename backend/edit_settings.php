<html>
<head>
	<title>Portal Admin :: Edit System Settings</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Toggle Amnesty Period</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST: settings request received

		$trimester = $_POST['current_trimester'];

		$db_toggle = $conn->prepare("UPDATE system_settings SET value_int=? WHERE name=?");
		$db_toggle->bind_param("is", $trimester, $setting_name);
		$setting_name = "current_trimester";
		$db_toggle->execute();
		echo($db_toggle->error);

		?><p style="font-weight:bold">Settings Updated.</p>
		<p><a href="edit_amnesty.php">Again</a> | <a href="index.php">Menu</a></p><?php

	} else {	// GET: display settings screen

		$trimesters = $conn->query("SELECT trimester FROM assignment GROUP BY trimester"); ?>

		<form action="edit_settings.php" method="post">
			<div><label for="current_trimester">Current Trimester:</label>
				<select id="current_trimester" name="current_trimester"><?php
					if ($trimesters->num_rows > 0) {
						while($trimester = $trimesters->fetch_assoc()) {
							?><option value="<?php echo($trimester["trimester"]);?>">
								<?php echo($trimester["trimester"]);?>
							</option>
							<?php
						}
					} ?>
				</select>
			</div>
			<input type="submit" />
		</form><?php
	}
	?>
</body>
</html>