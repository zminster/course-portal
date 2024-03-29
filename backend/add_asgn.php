<html>
<head>
	<title>Portal Admin :: Add Assignment</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
	<script src="https://code.jquery.com/jquery-1.10.1.min.js" type="text/javascript"></script>
</head>
<body>
	<h1>Add Assignment</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$classes = get_class_pds($conn);
	$types = get_asgn_types($conn);
	$formats = get_asgn_formats($conn);

	if ($method == 'POST') {	// POST: admin is adding asgn
		
		// create queries
		$assignment_insert	= $conn->prepare("INSERT INTO assignment (name, type, format, pt_value, trimester, honors_possible, description, url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
		$meta_insert		= $conn->prepare("INSERT INTO assignment_meta (asgn_id, class_pd, date_out, date_due, displayed, can_handin, info_changed) VALUES (?, ?, ?, ?, ?, ?, ?)");
		$grade_insert		= $conn->prepare("INSERT INTO grades (uid, asgn_id, nreq, handed_in, late, chomped, can_view_feedback) VALUES (?, ?, 0, 0, 0, 0, 0)");

		$asgn_id_lookup		= $conn->prepare("SELECT asgn_id FROM assignment WHERE name = ?");
		$uids_lookup		= $conn->prepare("SELECT user.uid FROM user INNER JOIN membership ON user.uid = membership.uid WHERE class_pd = ?");

		// assignment queries
		$honors_possible = array_key_exists("honors_possible", $_POST) ? 1 : 0;
		$assignment_insert->bind_param("siiiiiss", $_POST["name"], $_POST["type"], $_POST["format"], $_POST["pt_value"], $_POST["trimester"], $honors_possible, $_POST["description"], $_POST["url"]);
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
			$date_out = date("Y-m-d H:i", strtotime($meta[$class_pd]["out-date"] . " " . $meta[$class_pd]["out-time"]));
			$date_due = date("Y-m-d H:i", strtotime($meta[$class_pd]["due-date"] . " " . $meta[$class_pd]["due-time"]));
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
		if (!is_dir("/course/".$_ENV['COURSE_CODE']."/handin/" . $asgn_id)) {
			if (!mkdir("/course/".$_ENV['COURSE_CODE']."/handin/" . $asgn_id, 0770)) {
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

				<div><label for="trimester">Term:</label>
					<select id="trimester" name="trimester">
						<option value="1">S1</option>
						<option value="2">S2</option>
					</select>
				</div>
				<div><label for="name">Title:</label><input type="text" id="name" name="name" placeholder="Title" /></div>
				<div><label for="type">Category:</label>
				<select id="type" name="type"><?php 	// type selector
					foreach ($types as $type) {
						?><option value="<?php echo($type["type_id"]);?>">
							<?php echo($type["type_id"]);?>.) <?php echo($type["name"]); ?> - <?php echo($type["weight"] * 100); ?>%
						</option><?php
					}?>
				</select></div>
				<div><label for="format">Format:</label>
				<select id="format" name="format"><?php 	// format selector
					foreach ($formats as $format) {
						?><option value="<?php echo($format["format_id"]);?>">
							<?php echo($format["format_id"]);?>.) <?php echo($format["name"]); ?> [<?php echo($format["is_file"] ? "File Upload" : "Text Box"); ?>]
						</option><?php
					}?>
				</select></div>
				<div><label for="pt_value">Points:</label><input type="text" id="pt_value" name="pt_value" placeholder="Point Value" /></div>
				<div><label for="honors_possible">AP Option?:</label><input type="checkbox" id="honors_possible" name="honors_possible" value="1" /></div>
				<div><label for="URL">URL:</label><input type="text" id="url" name="url" placeholder="http://principles.cs.stab.org/asgn/hw01.pdf" /></div>

				<div><label for="description">Description:</label><textarea id="description" name="description" placeholder="Description"></textarea>

			<h2>Per-Class Information:</h2>

				<table border="1">
					<tr>
						<th>Period</th>
						<th>Date Out</th>
						<th>Time Out</th>
						<th>Date Due</th>
						<th>Time Due</th>
						<th>Enabled</th>
						<th>Can Handin</th>
						<th>Info Changed?</th>
					</tr>
					<?php
					foreach ($classes as $pd) {
						?><tr>
							<td><?php echo($pd); ?></td>
							<td><input type="date" id="asgn[<?php echo($pd); ?>][out-date]" name="asgn[<?php echo($pd); ?>][out-date]" /></td>
							<td><input type="time" id="asgn[<?php echo($pd); ?>][out-time]" name="asgn[<?php echo($pd); ?>][out-time]" /></td>
							<td><input type="date" id="asgn[<?php echo($pd); ?>][due-date]" name="asgn[<?php echo($pd); ?>][due-date]" /></td>
							<td><input type="time" id="asgn[<?php echo($pd); ?>][due-time]" name="asgn[<?php echo($pd); ?>][due-time]" /></td>
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