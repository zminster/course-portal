<html>
<head>
	<title>Portal Admin :: Individual Grade Report</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body class="report">
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$users = get_all_userinfo($conn);
	$roles = get_user_roles($conn);

	if ($method == 'POST') {	// POST
		// goal:
		//  - report every assignment in the trimester as Veracross does, but for a single student

		// global vars
		$uid = $_POST["uid"];
		$trimester = $_POST["trimester"];
		$assignments = get_all_assignments($conn);
		$user_meta = $users[$uid];

		// prepare queries (dump everything)
		$grade_select = $conn->prepare("SELECT grades.asgn_id, assignment.name, date_due, assignment_type.name, nreq, handed_in, extension, late, chomped, score, honors_possible, honors_earned, pt_value
			FROM grades 
				JOIN membership ON grades.uid = membership.uid
				JOIN assignment ON grades.asgn_id = assignment.asgn_id 
				JOIN assignment_type ON type = type_id 
				JOIN assignment_meta ON assignment_meta.class_pd = membership.class_pd AND grades.asgn_id = assignment_meta.asgn_id
			WHERE grades.uid = ? AND trimester = ? AND displayed = 1 ORDER BY date_due");

		$avg_select = $conn->prepare("SELECT assignment_type.name, weight, SUM(score) / SUM(pt_value) as avg FROM assignment JOIN assignment_type ON type = type_id JOIN grades ON assignment.asgn_id = grades.asgn_id WHERE uid = ? AND trimester = ? AND chomped = 1 AND score IS NOT NULL GROUP BY type_id");

		$grade_select->bind_param("ii", $uid, $trimester);
		$avg_select->bind_param("ii", $uid, $trimester);
		$grade_select->bind_result($asgn_id, $name, $date_due, $type, $nreq, $handed_in, $extension, $late, $chomped, $score, $honors_possible, $honors_earned, $pt_value);
		$avg_select->bind_result($type, $weight, $avg);

		?><h1>Individual Grade Report &raquo; <?php echo($user_meta["first_name"] . " " . $user_meta["last_name"] . " (" . $roles[$user_meta[role]]['name'] . ")" . " &raquo; Term " . $trimester); ?></h1>

		<ul>
			<li>Headings highlighted in <span style="background-color:#D5E3F1; font-weight:bold;">BLUE</span> indicate that AP credit could be earned on the assignment. Scores bounded in <span style="background-color:#D5E3F1; font-weight:bold;">BLUE</span> indicate credit was earned.</li>
			<li>Headings highlighted in <span style="background-color:#FFD880; font-weight:bold;">YELLOW</span> indicate a late handin.</li>
			<li>Scores in <span style="color:red; font-weight:bold;">RED</span> are failing grades.</li>
			<li>Assignments marked with a &#9745; are pending grading.</li>
		</ul>

		<h2>Gradebook State</h2>
		<?php if (!$roles[$user_meta[role]]['reporting_enabled']) echo("<div style='color:red; font-weight:bold; margin-bottom:20px;'>NOTICE: This user does not affect reporting.</div>"); ?>
		<?php
		// gradebook view
		$grade_select->execute();
		$grade_select->store_result();
		echo($grade_select->error);

		$gradebook_entries = [];
		?>
		<table border="1" class="gradebook">
			<tr><?php
			// header row
			while($grade_select->fetch()) {
				$dt_due = new DateTime($date_due);
				?><th<?php echo($honors_possible ? " class=\"honors_possible\"" : NULL); ?>>
					<span><?php echo($dt_due->format('D - M d')); ?></span>
					<span><?php echo($type); ?></span>
					<span><?php echo('(#' . $asgn_id . ") " . $name); ?></span>
					<span><?php echo('Max: <span>' . $pt_value . '</span>'); ?></span>
					<?php
					// figure out correct gradebook entry
					$entry_class = "";
					$entry_text = "";

					if ($nreq) {
						$entry_class = "nreq";
						$entry_text = "NREQ";
					}
					else {	// ignore all other stuff if NREQ
						if (!$handed_in) {
							$entry_class = $entry_class . "nti";
							$entry_text = $entry_text . "NTI";
							if ($chomped) // this means the student was marked 0 for not turning in
								$entry_text = $entry_text . " (Zero)";
						}
						else { // ignore all other stuff if NTI
							if ($late)
								$entry_class = $entry_class . " late";
	
							if ($chomped && $score){	// graded
								$entry_text = $entry_text . round($score,2);
								$entry_class = $entry_class . " graded";

								if (($score / $pt_value) <= 0.6)
									$entry_class = $entry_class . " low_grade";

								if ($honors_possible && $honors_earned)
									$entry_class = $entry_class . " honors_earned";
							}
							else 						// otherwise, pending grade
								$entry_text = $entry_text . "&#9745;";

							if ($extension)
								$entry_text = $entry_text . "<span>(+" . $extension . "hrs)</span>";
						}
					}
					array_push($gradebook_entries, "<div class=\"" . $entry_class . "\">" . $entry_text . "</div>");
					?>
				</th><?php
			}?></tr>

			<tr><?php
			// gradebook entries
			for ($i=0; $i < count($gradebook_entries); $i++) { 
				?><td><?php echo($gradebook_entries[$i]); ?></td><?php
			}?></tr>
		</table>
		
		<h2>Category Averages</h2>
		<table border="1">
			<tr>
				<th>Category</th>
				<th>Average</th>
				<th>Weight</th>
			</tr>
		<?php
		$avg_select->execute();
		$avg_select->store_result();
		echo($avg_select->error);

		while($avg_select->fetch()) {
			?><tr><?php
			echo("<td>" . $type . "</td>");
			echo("<td" . ($avg * 100 <= 60 ? " class=\"low_grade\"" : "") . ">" . round($avg * 100, 2) . "%</td>");
			echo("<td>" . round($weight * 100, 2) . "%</td>");
			?></tr><?php
		}
		?>
		</table>

		<div style="margin-top:25px;"><a href="report_student.php">Generate Another Report</a> | 
		<a href="index.php">Menu</a></div><?php 
	} else {	// GET: display edit options
		?>
		<h1>Individual Grade Report</h1>
		<form action="report_student.php" method="POST">
			<p>Select student and term for whom to generate an individual grade report.</p>

			<div><label for="trimester">Term:</label>
				<select id="trimester" name="trimester">
					<option value="1">S1</option>
					<option value="2">S2</option>
				</select>
			</div>

			<div><select name="uid">
				<?php
				foreach ($users as $user) {
					$role = $roles[$user["role"]];
					if ($role['handin_enabled']) {
					?><option value="<?php echo($user["uid"]);?>">
						[<?php echo($role["name"]);?>] <?php echo($role['class_membership'] ? "Pd. ".$user["class_pd"].")" : "");?> <?php echo($user["username"]);?> [<?php echo($user["first_name"]. " " . $user["last_name"]); ?>]
					</option><?php
					}
				} ?>
			</select></div>

			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>