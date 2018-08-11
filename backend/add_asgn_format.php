<html>
<head>
	<title>Portal Admin :: Add Assignment Format</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Add Assignment Format</h1>
	<?php
	include 'config/database.php';

	$res = $conn->query("SELECT * FROM assignment_format");

	if ($res->num_rows > 0) {

		?><h2>Existing Formats:</h2>
		<a href="edit_asgn_format.php">(Edit an existing format)</a>
		<table>
			<tr>
				<th>Format ID</th>
				<th>Name</th>
				<th>Friendly Description</th>
				<th>File Upload?</th>
				<th>Regex Validation String</th>
				<th>Validation Help Message</th>
			</tr>
			<?php
		while ($format = $res->fetch_assoc()) {
			?><tr>
				<td><?php echo($format["format_id"]); ?></td>
				<td><?php echo($format["name"]); ?></td>
				<td><?php echo($format["description"]); ?></td>
				<td><?php echo($format["is_file"] ? "YES" : "NO"); ?></td>
				<td><pre><?php echo($format["regex"]); ?></pre></td>
				<td><?php echo($format["validation_help"]); ?></td>
			</tr>
		<?php } ?>
		</table>
		<?php

	}

	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST: admin is adding type
		$is_file = array_key_exists("is_file", $_POST) ? 1 : 0;

		$format_insert = $conn->prepare("INSERT INTO assignment_format (name, description, is_file, regex, validation_help) VALUES (?, ?, ?, ?, ?)");
		$format_insert->bind_param("ssiss", $_POST['name'], $_POST['description'], $is_file, $_POST['regex'], $_POST['validation_help']);
		$format_insert->execute();

		?><p style="font-weight:bold">Format Added</p>
		<p><a href="add_asgn_format.php">Again</a> | <a href="index.php">Menu</a></p><?php

	} else {	// GET: display add type screen

		?><form action="add_asgn_format.php" method="post">

		<h2>New Format Information:</h2>

			<div><label for="name">Friendly Name:</label><input type="text" id="name" name="name" placeholder="(e.g. Snap! Link)" /></div>
			<div><label for="description">Description:</label><textarea id="description" name="description" placeholder="(e.g. Submit a link to your Snap file, see instructions)"></textarea></div>
			<div><label for="is_file">Does this require a file upload?:</label><input type="checkbox" id="is_file" name="is_file" value="1" /></div>
			<div><label for="regex">Regex Validation String:</label><input type="text" id="regex" name="regex" placeholder="/\/snap.html#present:Username=(\w+)&ProjectName=(\w+)/g" /></div>
			<div><label for="validation_help">Validation Error Message:</label><input type="text" id="validation_help" name="validation_help" placeholder="You must submit URLs in the format: snap.html/#present:Username=...." /></div>

		<input type="submit">
		</form><?php
	}
	?>
</body>
</html>