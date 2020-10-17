<html>
<head>
	<title>Portal Admin :: Edit/Delete Assignment</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
	<script src="https://code.jquery.com/jquery-1.10.1.min.js" type="text/javascript"></script>
</head>
<body>
	<h1>Edit/Delete Assignment</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$classes = get_class_pds($conn);
	$types = get_asgn_types($conn);
	$formats = get_asgn_formats($conn);
	$assignments = get_all_assignments($conn);

	if ($method == 'POST') {	// POST
		$asgn_id = $_POST["asgn_id"];
		if (array_key_exists("delete", $_POST)) {	// delete assignment & assoc grades
			$del_1 = $conn->prepare("DELETE FROM assignment WHERE asgn_id = ?");
			$del_2 = $conn->prepare("DELETE FROM assignment_meta WHERE asgn_id = ?");
			$del_3 = $conn->prepare("DELETE FROM grades WHERE asgn_id = ?");
			$del_1->bind_param("i", $asgn_id);
			$del_2->bind_param("i", $asgn_id);
			$del_3->bind_param("i", $asgn_id);
			// execute in reverse order to prevent foreign key constraint violation
			$del_3->execute();
			echo($del_3->error);
			$del_2->execute();
			echo($del_2->error);
			$del_1->execute();
			echo($del_1->error);
			?><p style="font-weight:bold">Assignment <?php echo($asgn_id); ?> Deleted</p>
			<p><a href="index.php">Menu</a></p><?php
		} else if(array_key_exists("edit", $_POST)) {	// edit assignment, update all fields to match form values
		
			// create queries
			$assignment_update	= $conn->prepare("UPDATE assignment SET name=?, type=?, format=?, pt_value=?, trimester=?, honors_possible=?, description=?, url=? WHERE asgn_id=?");
			$meta_update		= $conn->prepare("UPDATE assignment_meta SET date_out=?, date_due=?, displayed=?, can_handin=?, info_changed=? WHERE asgn_id=? AND class_pd=?");
			$grade_update		= $conn->prepare("UPDATE grades SET late=? WHERE uid = ? AND asgn_id = ?");
			$canview_update		= $conn->prepare("UPDATE grades SET can_view_feedback=? WHERE uid = ? AND asgn_id = ?");

			$uids_lookup		= $conn->prepare("SELECT user.uid FROM user INNER JOIN membership ON user.uid = membership.uid WHERE class_pd = ?");
			$handin_time_lookup = $conn->prepare("SELECT handin_time, extension FROM grades WHERE uid = ? AND asgn_id = ?");

			// assignment queries
			$honors_possible = array_key_exists("honors_possible", $_POST) ? 1 : 0;
			$assignment_update->bind_param("siiiiissi", $_POST["name"], $_POST["type"], $_POST["format"], $_POST["pt_value"], $_POST["trimester"], $honors_possible, $_POST["description"], $_POST["url"], $asgn_id);
			$assignment_update->execute();
			echo($assignment_update->error);

			// assignment_meta queries
			$meta_update->bind_param("ssiiiii", $date_out, $date_due, $displayed, $can_handin, $info_changed, $asgn_id, $class_pd);
			$grade_update->bind_param("iii", $recomputed_late, $uid, $asgn_id);
			$canview_update->bind_param("iii", $canview, $uid, $asgn_id);

			$uids_lookup->bind_param("i", $class_pd);
			$uids_lookup->bind_result($uid);
			$handin_time_lookup->bind_param("ii", $uid, $asgn_id);
			$handin_time_lookup->bind_result($handin_time, $extension);

			$meta = $_POST["asgn"];
			foreach (array_keys($meta) as $class_pd) {
				// meta updates
				$date_out = date("Y-m-d H:i", strtotime($meta[$class_pd]["out-date"] . " " . $meta[$class_pd]["out-time"]));
				$date_due = date("Y-m-d H:i", strtotime($meta[$class_pd]["due-date"] . " " . $meta[$class_pd]["due-time"]));
				$displayed = array_key_exists("displayed", $meta[$class_pd]) ? 1 : 0;
				$can_handin = array_key_exists("can_handin", $meta[$class_pd]) ? 1 : 0;
				$info_changed = array_key_exists("info_changed", $meta[$class_pd]) ? 1 : 0;
				$meta_update->execute();
				echo($meta_update->error);

				// per-student updates
				$uids_lookup->execute();
				$uids_lookup->store_result();
				echo($uids_lookup->error);
				$recompute_lates = array_key_exists("recompute_lates", $meta[$class_pd]);
				$update_canview = array_key_exists("update_canview", $meta[$class_pd]);
				$canview = array_key_exists("canview", $meta[$class_pd]) ? 1 : 0;
				while ($uids_lookup->fetch()) {

					// update lates if necessary
					$handin_time_lookup->execute();
					$handin_time_lookup->store_result();
					$handin_time_lookup->fetch();
					if ($handin_time && $recompute_lates) {
						$h_dt = new DateTime($handin_time);
						$d_dt = new DateTime($date_due);
						if ($extension) {
							$ext = new DateInterval("PT".$extension."H");
							$d_dt->add($ext);
						}
						if ($h_dt < $d_dt) {
							$recomputed_late = 0;
						} else {
							$recomputed_late = 1;
						}
						$grade_update->execute();
					}

					// update canview if necessary
					if ($update_canview) {
						$canview_update->execute();
						echo($canview_update->error);
					}
				}
			}
			?><p style="font-weight:bold">Assignment Changed: <?php echo($_POST["name"]); ?></p>
			<div><a href="edit_asgn.php">Again</a> | 
			<a href="index.php">Menu</a></div><?php
		} else {	// assignment selected, display assignment information
			$asgn_data = get_all_assignment_data($conn, $asgn_id);

			// meta grab
			$trimester 		= $assignments[$asgn_id]["trimester"];
			$name			= $assignments[$asgn_id]["name"];
			$meta_type 		= $assignments[$asgn_id]["type"];
			$meta_format	= $assignments[$asgn_id]["format"];
			$pt_value 		= $assignments[$asgn_id]["pt_value"];
			$description 	= $assignments[$asgn_id]["description"];
			$honors 		= $assignments[$asgn_id]["honors_possible"];
			$url 			= $assignments[$asgn_id]["url"];
			?>
			<form action="edit_asgn.php" method="post">
				<input type="hidden" name="asgn_id" value="<?php echo($asgn_id);?>" />
				<input type="hidden" name="edit" value="1" />

				<h2>Assignment <?php echo($asgn_id); ?> Meta Information:</h2>

					<div><label for="delete">Delete?</label><input type="checkbox" id="delete" name="delete" /></div>

					<div><label for="trimester">Term:</label>
						<select id="trimester" name="trimester">
							<option value="1" <?php echo($trimester == 1 ? "selected" : ""); ?>>S1</option>
							<option value="2" <?php echo($trimester == 2 ? "selected" : ""); ?>>S2</option>
						</select>
					</div>
					<div><label for="name">Title:</label><input type="text" id="name" name="name" placeholder="Title" value="<?php echo($name); ?>" /></div>
					<div><label for="type">Type:</label>
					<select id="type" name="type"><?php 	// type selector
						foreach ($types as $type) {
							?><option value="<?php echo($type["type_id"]);?>" <?php echo($meta_type == $type["type_id"] ? "selected" : ""); ?>>
								<?php echo($type["type_id"]);?>.) <?php echo($type["name"]); ?> - <?php echo($type["weight"] * 100); ?>%
							</option><?php
						}?>
					</select></div>
					<div><label for="type">Format:</label>
					<select id="format" name="format"><?php 	// format selector
						foreach ($formats as $format) {
							?><option value="<?php echo($format["format_id"]);?>" <?php echo($meta_format == $format["format_id"] ? "selected" : ""); ?>>
								<?php echo($format["format_id"]);?>.) <?php echo($format["name"]); ?> [<?php echo($format["is_file"] ? "File Upload" : "Text Box"); ?>]
							</option><?php
						}?>
					</select></div>
					<div><label for="pt_value">Points:</label><input type="text" id="pt_value" name="pt_value" placeholder="Point Value" value="<?php echo($pt_value); ?>" /></div>
					<div><label for="honors_possible">AP Option?:</label><input type="checkbox" id="honors_possible" name="honors_possible" value="1" <?php echo($honors ? "checked" : ""); ?> /></div>
					<div><label for="URL">URL:</label><input type="text" id="url" name="url" placeholder="http://principles.cs.stab.org/asgn/hw01.pdf" value="<?php echo($url); ?>" /></div>

					<div><label for="description">Description:</label><textarea id="description" name="description" placeholder="Description"><?php echo($description); ?></textarea>

				<h2>Assignment <?php echo($asgn_id); ?> Per-Class Information:</h2>

					<table>
						<tr>
							<th>Period</th>
							<th>Date Out</th>
							<th>Time Out</th>
							<th>Date Due</th>
							<th>Time Due</th>
							<th>Enabled</th>
							<th>Can Handin</th>
							<th>Info Changed?</th>
							<th>Recompute Lates?</th>
							<th>Update CANVIEW for all?</th>
							<th>CANVIEW Grades</th>
						</tr>
						<?php
						foreach ($classes as $pd) {
							$formatted_out_date = date("Y-m-d", strtotime($asgn_data[$pd]["date_out"]));
							$formatted_out_time = date("H:i", strtotime($asgn_data[$pd]["date_out"]));
							$formatted_due_date = date("Y-m-d", strtotime($asgn_data[$pd]["date_due"]));
							$formatted_due_time = date("H:i", strtotime($asgn_data[$pd]["date_due"]));
							?><tr>
								<td><?php echo($pd); ?></td>
								<td><input id="asgn[<?php echo($pd); ?>][out-date]" name="asgn[<?php echo($pd); ?>][out-date]" type="date" value="<?php echo($formatted_out_date); ?>" /></td>
								<td><input id="asgn[<?php echo($pd); ?>][out-time]" name="asgn[<?php echo($pd); ?>][out-time]" type="time" value="<?php echo($formatted_out_time); ?>" /></td>
								<td><input id="asgn[<?php echo($pd); ?>][due-date]" name="asgn[<?php echo($pd); ?>][due-date]" type="date" value="<?php echo($formatted_due_date); ?>" /></td>
								<td><input id="asgn[<?php echo($pd); ?>][due-time]" name="asgn[<?php echo($pd); ?>][due-time]" type="time" value="<?php echo($formatted_due_time); ?>" /></td>
								<td><input id="asgn[<?php echo($pd); ?>][displayed]" name="asgn[<?php echo($pd); ?>][displayed]" type="checkbox" value="1" <?php echo($asgn_data[$pd]["displayed"] ? "checked" : ""); ?>/></td>
								<td><input id="asgn[<?php echo($pd); ?>][can_handin]" name="asgn[<?php echo($pd); ?>][can_handin]" type="checkbox" value="1" <?php echo($asgn_data[$pd]["can_handin"] ? "checked" : ""); ?>/></td>
								<td><input id="asgn[<?php echo($pd); ?>][info_changed]" name="asgn[<?php echo($pd); ?>][info_changed]" type="checkbox" value="1" <?php echo($asgn_data[$pd]["info_changed"] ? "checked" : ""); ?> /></td>
								<td><input id="asgn[<?php echo($pd); ?>][recompute_lates]" name="asgn[<?php echo($pd); ?>][recompute_lates]" type="checkbox" value="1" checked /></td>
								<td><input id="asgn[<?php echo($pd); ?>][update_canview]" name="asgn[<?php echo($pd); ?>][update_canview]" type="checkbox" value="1" /></td>
								<td><input id="asgn[<?php echo($pd); ?>][canview]" name="asgn[<?php echo($pd); ?>][canview]" type="checkbox" value="1" /></td>
							</tr><?php
						} ?>
					</table>

					<input type="submit" />
			</form>
		<?php }
	} else {	// GET: display edit options
		?><form action="edit_asgn.php" method="POST">
			<p>Select an assignment to edit.</p>
			<div><select name="asgn_id">
				<?php
				foreach ($assignments as $asgn) {
					?><option value="<?php echo($asgn["asgn_id"]);?>">
						<?php echo($asgn["asgn_id"]);?>.) <?php echo($asgn["name"]);?>
					</option><?php
				} ?>
			</select></div>
			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>