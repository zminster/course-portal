<html>
<head>
	<title>Portal Admin :: Add Assignment</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
	<script src="https://code.jquery.com/jquery-1.10.1.min.js" type="text/javascript"></script>
	<script src="//cdn.jsdelivr.net/webshim/1.14.5/polyfiller.js"></script>
	<script>
	    webshims.setOptions('forms-ext', {types: 'datetime-local'});
		webshims.polyfill('forms forms-ext');
	</script>
</head>
<body>
	<h1>Add Assignment</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$classes = get_class_pds($conn);
	$types = get_asgn_types($conn);

	if ($method == 'POST') {	// POST: admin is adding asgn
		
		// create queries
		$assignment_insert	= $conn->prepare("INSERT INTO assignment (name, type, pt_value, description, url) VALUES (?, ?, ?, ?, ?)");
		$meta_insert		= $conn->prepare("INSERT INTO assignment_meta (asgn_id, class_pd, date_out, date_due, displayed, can_handin, info_changed) VALUES (?, ?, ?, ?, ?, ?, ?)");
		$grade_insert		= $conn->prepare("INSERT INTO grades (uid, asgn_id, nreq, handed_in, late, chomped, can_view_feedback) VALUES (?, ?, 0, 0, 0, 0, 0)");

		$asgn_id_lookup		= $conn->prepare("SELECT asgn_id FROM assignment WHERE name = ?");
		$uids_lookup		= $conn->prepare("SELECT user.uid FROM user INNER JOIN membership ON user.uid = membership.uid WHERE class_pd = ?");

		// assignment queries
		$assignment_insert->bind_param("siiss", $_POST["name"], $_POST["type"], $_POST["pt_value"], $_POST["description"], $_POST["url"]);
		$assignment_insert->execute();
		echo($assignment_insert->error);

		$asgn_id_lookup->bind_param("s", $_POST["name"]);
		$asgn_id_lookup->bind_result($asgn_id);
		$asgn_id_lookup->execute();
		echo($asgn_id_lookup->error); 
		$asgn_id_lookup->fetch();
		$asgn_id_lookup->free_result();

		// assignment_meta queries
		$meta_insert->bind_param("iissiii", $asgn_id, $class_pd, $date_out, $date_due, $displayed, $can_handin, $info_changed);
		$grade_insert->bind_param("ii", $uid, $asgn_id);
		$uids_lookup->bind_param("i", $class_pd);
		$uids_lookup->bind_result($uid);

		$meta = $_POST["asgn"];
		foreach (array_keys($meta) as $class_pd) {
			$date_out = date("Y-m-d H:i:s", strtotime($meta[$class_pd]["out"]));
			$date_due = date("Y-m-d H:i:s", strtotime($meta[$class_pd]["due"]));
			$displayed = array_key_exists("displayed", $meta[$class_pd]) ? 1 : 0;
			$can_handin = array_key_exists("can_handin", $meta[$class_pd]) ? 1 : 0;
			$info_changed = array_key_exists("info_changed", $meta[$class_pd]) ? 1 : 0;
			$meta_insert->execute();
			echo($meta_insert->error);

			// assign empty grades row to each student in this class
			$uids_lookup->execute();
			$uids_lookup->store_result();
			echo($uids_lookup->error);
			while ($uids_lookup->fetch()) {
				$grade_insert->execute();
				echo($grade_insert->error);
			}
		}

		// assignment filesystem changes
		$old = umask(0);
		if (!is_dir("/course/csp/handin/" . $asgn_id)) {
			if (!mkdir("/course/csp/handin/" . $asgn_id, 0770)) {
				echo("<p>ERROR: Could not create assignment handin directory.</p>");
			}
		} else {
			echo("<p>Assignment handin directory already exists??</p>");
		}
		umask($old);
		?><p style="font-weight:bold">Assignment Added: <?php echo($_POST["name"]); ?> (ID #<?php echo($asgn_id); ?>)</p>
		<div><a href="add_asgn.php">Again</a> | 
		<a href="index.php">Menu</a></div><?php
	} else {	// GET: display add asgn screen

		?>
		<form action="add_asgn.php" method="post">

			<h2>Meta Information:</h2>

				<div><label for="name">Title:</label><input type="text" id="name" name="name" placeholder="Title" /></div>
				<div><label for="type">Type:</label>
				<select id="type" name="type"><?php 	// type selector
					foreach ($types as $type) {
						?><option value="<?php echo($type["type_id"]);?>">
							<?php echo($type["type_id"]);?>.) <?php echo($type["name"]); ?> - <?php echo($type["weight"] * 100); ?>%
						</option><?php
					}?>
				</select></div>
				<div><label for="pt_value">Points:</label><input type="text" id="pt_value" name="pt_value" placeholder="Point Value" /></div>
				<div><label for="URL">URL:</label><input type="text" id="url" name="url" placeholder="http://principles.cs.stab.org/asgn/hw01.pdf" /></div>

				<div><label for="description">Description:</label><textarea id="description" name="description" placeholder="Description"></textarea>

			<h2>Per-Class Information:</h2>

				<table>
					<tr>
						<th>Period</th>
						<th>Date Out</th>
						<th>Date Due</td>
						<th>Display on Portal</th>
						<th>Can Handin</th>
						<th>Info Changed?</th>
					</tr>
					<?php
					foreach ($classes as $pd) {
						?><tr>
							<td><?php echo($pd); ?></td>
							<td><input id="asgn[<?php echo($pd); ?>][out]" name="asgn[<?php echo($pd); ?>][out]" type="datetime-local" /></td>
							<td><input id="asgn[<?php echo($pd); ?>][due]" name="asgn[<?php echo($pd); ?>][due]" type="datetime-local" /></td>
							<td><input id="asgn[<?php echo($pd); ?>][displayed]" name="asgn[<?php echo($pd); ?>][displayed]" type="checkbox" value="1" /></td>
							<td><input id="asgn[<?php echo($pd); ?>][can_handin]" name="asgn[<?php echo($pd); ?>][can_handin]" type="checkbox" value="1" /></td>
							<td><input id="asgn[<?php echo($pd); ?>][info_changed]" name="asgn[<?php echo($pd); ?>][info_changed]" type="checkbox" value="1" /></td>
						</tr><?php
					} ?>
				</table>

				<input type="submit" />
		</form>
	<?php } ?>
</body>
</html>