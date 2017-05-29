<?php 	$rubric_dir = "/course/csp/rubric/";
		$handin_dir = "/course/csp/handin/"; ?>
<html>
<head>
	<title>Portal Admin :: Force Chomp Assignment</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />

	<script language="JavaScript">
		function toggle(source, type) {
			var inputs = document.getElementsByTagName("input");
			for(var i = 0; i < inputs.length; i++) {
				if(inputs[i].type == "checkbox" && inputs[i].name.includes(type)) {
					inputs[i].checked = source.checked; 
				}
			}
		}
	</script>
</head>
<body>
	<h1>Force Chomp Assignment (Prep Rubrics for NTIs)</h1>
	<?php
	function warning_handler() {
		die("<span style=\"color:red;\"><b>
				File issue (check for existence of rubric; permissions)!</b></span>");
	}
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$assignments = get_all_assignments($conn);

	if ($method == 'POST') {	// POST
		$asgn_id = $_POST["asgn_id"];

		if(array_key_exists("chomp", $_POST)) {	// stage 2 complete, update to reflect checked values on form
			$users = get_all_userinfo($conn);
			
			// get rubric file (will safely error out if it doesn't exist)
			//set_error_handler("warning_handler", E_WARNING);
			$f_rubric = file($rubric_dir . $asgn_id . ".txt");

			// create queries
			$meta_select		= $conn->prepare("SELECT date_due, pt_value, name, honors_possible FROM assignment JOIN assignment_meta ON assignment.asgn_id = assignment_meta.asgn_id WHERE assignment.asgn_id = ? AND class_pd = ?");
			$grade_select		= $conn->prepare("SELECT extension FROM grades WHERE uid = ? AND asgn_id = ?");
			$grade_update		= $conn->prepare("UPDATE grades SET chomped=1, handed_in=? WHERE uid = ? AND asgn_id = ?");

			$meta_select->bind_param("ii", $asgn_id, $class_pd);
			$meta_select->bind_result($date_due, $pt_value, $asgn_name, $honors_possible);
			$grade_select->bind_param("ii", $uid, $asgn_id);
			$grade_select->bind_result($extension);
			$grade_update->bind_param("iii", $remove_nti, $uid, $asgn_id);

			$force = $_POST['force'];

			// iterate through desired assignments
			foreach (array_keys($_POST['force']) as $uid) {
				$student 	= $users[$uid];
				$class_pd 	= $student['class_pd'];

				// get meta information (set $date_due, $pt_value, $name, $honors_possible as appropriate for $class_pd)
				$meta_select->execute();
				$meta_select->store_result();
				$meta_select->fetch();
				echo($meta_select->error);

				// get extension information for this user (set $extension)
				$grade_select->execute();
				$grade_select->store_result();
				$grade_select->fetch();
				echo($grade_select->error);

				// create handin directory (if necessary)
				$rubric_path = $handin_dir . $asgn_id . "/" . $student['username'] . "/";
				$old = umask(0);
				if (!is_dir($rubric_path)) {
					if (!mkdir($rubric_path, 0770)) {
						echo("<p><span style=\"color:red;\"><b>ERROR: Could not create assignment rubric path: " 
							. $rubric_path . "</b></span></p>");
					}
				}
				umask($old);

				// create empty rubric
				$rubric_filename = $rubric_path . "grade_comments.txt";
				$rnr = "";
				$do_not_update = 0;
				if (!file_exists($rubric_filename)) {
					// generate corrected due date if extension
					$d_dt = new DateTime($date_due);
					if ($extension) {
						$ext = new DateInterval("PT".$extension."H");
						$d_dt->add($ext);
					}

					// copy & update rubric array as required
					$first_name = 
					$rubric = $f_rubric;
					for ($i = 0; $i < count($rubric); $i++) {
						if (strpos($rubric[$i], "CSP_HEAD") !== FALSE) {
							$insert = ["Student: " . $student['first_name'] . " " . $student['last_name'] . " (" . $student['username'] . ")" . PHP_EOL,
								"Period: " . $class_pd . PHP_EOL,
								"Turned In: N/A" . 
								"\t(Due: " . $d_dt->format("D, M d, Y g:i A") . ($extension ? " - extended" : "") . ")"  . PHP_EOL
							];
							array_splice($rubric, $i, 1, $insert);
						} else if (strpos($rubric[$i], "CSP_FOOT") !== FALSE) {
							$insert = ["Late Handin Penalty: /0"  . PHP_EOL,
							$asgn_name . " Total: /" . $pt_value  . PHP_EOL];
							$honors_possible ? array_push($insert, "AP Credit Earned? : " . PHP_EOL) : NULL;
							array_splice($rubric, $i, 1, $insert);
						}
					}

					$newfile = implode("<br>", $rubric);
					set_error_handler(function($errno, $errstr) use (&$rnr, &$do_not_update) {
						$rnr = " <span style=\"color:red;\"><b>GRADE FILE ERROR: " . $errstr . "</b> (database unchanged)</span>";
						$do_not_update = 1; });
					file_put_contents($rubric_filename, implode("", $rubric)); // write rubric to user's handin dir
					restore_error_handler();
				} else {
					$rnr = " (Old rubric not replaced)";
				}

				if (!$do_not_update) {
					$remove_nti = array_key_exists($uid, $_POST['remove']) ? 1 : 0;
					$grade_update->execute();
				}
				?><p> Force Chomped: <?php echo($student['username']); echo($rnr ? $rnr : ""); ?></p><?php
			}

		} else { // present stage 2 options (form with checkboxes for assignments)
			?><h2>Assignment: <?php echo($assignments[$asgn_id]['name'] . "(" . $asgn_id . ")"); ?> </h2> <?php
			$lookup = $conn->prepare("SELECT grades.uid, username, class_pd, handed_in FROM grades JOIN membership ON grades.uid = membership.uid JOIN user ON grades.uid = user.uid WHERE asgn_id=? AND handed_in=0 ORDER BY class_pd, username ASC;");

			$lookup->bind_param("i", $asgn_id);
			$lookup->bind_result($uid, $username, $class_pd, $handed_in);

			$lookup->execute();
			$lookup->store_result(); ?>
			<form action="chomp_force.php" method="post">
				<input type="hidden" name="asgn_id" value="<?php echo($asgn_id);?>" />
				<input type="hidden" name="chomp" value="1" />

				<div><label for="f_all">Force Chomp All?</label><input type="checkbox" id="f_all" name="f_all" onclick="toggle(this, 'force')" /></div>
				<div><label for="r_all">Remove NTI All?</label><input type="checkbox" id="r_all" name="r_all" onclick="toggle(this, 'remove')" /></div>

				<table>
					<tr>
						<th>Period</th>
						<th>Student</th>
						<th>Status</th>
						<th>Force?</th>
						<th>Also remove NTI?</th>
					</tr>
					<?php while ($lookup->fetch()) { ?>
						<tr>
							<td><?php echo($class_pd); ?></td>
							<td><?php echo($username); ?></td>
							<td><?php echo($handed_in ? "Turned In" : "NTI"); ?></td>
							<td><input type="checkbox" id="force[<?php echo($uid); ?>]" name="force[<?php echo($uid); ?>]" /></td>
							<td><input type="checkbox" id="remove[<?php echo($uid); ?>]" name="remove[<?php echo($uid); ?>]" /></td>
						</tr>
					<?php } ?>
				</table>

				<input type="submit" />
			</form>
			<?php
		}
	} else {	// GET: display edit options
		?><form action="chomp_force.php" method="POST">
			<p>Select an assignment to force chomp. This process copies stripped-down rubrics on assignments and marks them turned in (not late), then (optionally) locks future handin. This is useful for participation/discussion grades (no deliverable), group projects in which one student turned in on behalf of multiple students, or Performance Tasks (handin is done via the College Board).</p>

			<div><label for="asgn_id">Assignment:</label><select id="asgn_id" name="asgn_id">
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