<html>
<head>
	<title>Portal Admin :: Class Grade Report</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body class="report">
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$classes = get_class_pds($conn);

	if ($method == 'POST') {	// POST
		// goal:
		//  - report every assignment in the trimester as Veracross does

		// global vars
		$class_pd = $_POST["class_pd"];
		$trimester = $_POST["trimester"];

		// QUERIES

		// this gives us assignments to spread across the header row
		// 		they are ordered by due date to match grade query below
		$asgn_select = $conn->prepare("SELECT assignment.asgn_id, assignment.name, date_due, assignment_type.name, honors_possible, pt_value
			FROM assignment
				JOIN assignment_meta ON assignment.asgn_id = assignment_meta.asgn_id
				JOIN assignment_type ON assignment.type = assignment_type.type_id
			WHERE class_pd = ? AND trimester = ?
			ORDER BY date_due DESC");

		// this gives us each student's grade on each assignment
		// ordered by the last name of the student (so all assignments are grouped by student)
		// 		then ordered by due dates (so assignments are in same order as header row)
		$grade_select = $conn->prepare("SELECT grades.uid, last_name, first_name, nreq, handed_in, extension, late, chomped, score, honors_possible, honors_earned, grades.asgn_id
			FROM grades 
				JOIN membership ON grades.uid = membership.uid
				JOIN assignment ON grades.asgn_id = assignment.asgn_id 
				JOIN assignment_meta ON assignment_meta.class_pd = membership.class_pd AND grades.asgn_id = assignment_meta.asgn_id
				JOIN user_meta ON grades.uid = user_meta.uid
				JOIN user ON user_meta.uid = user.uid
				JOIN user_role ON user.role = user_role.rid
			WHERE membership.class_pd = ? AND trimester = ? AND reporting_enabled = 1 ORDER BY last_name, first_name, date_due DESC");

		$asgn_select->bind_param("ii", $class_pd, $trimester);
		$grade_select->bind_param("ii", $class_pd, $trimester);

		$asgn_select->bind_result($asgn_id, $name, $date_due, $type, $honors_possible, $pt_value);
		$grade_select->bind_result($uid, $last_name, $first_name, $nreq, $handed_in, $extension, $late, $chomped, $score, $honors_possible, $honors_earned, $asgn_id);

		?><h1>Class Grade Report &raquo; Period <?php echo($class_pd); ?> &raquo; Trimester <?php echo($trimester);?></h1>

		<ul>
			<li>Headings highlighted in <span style="background-color:#D5E3F1; font-weight:bold;">BLUE</span> indicate that AP credit could be earned on the assignment. Scores bounded in <span style="background-color:#D5E3F1; font-weight:bold;">BLUE</span> indicate credit was earned.</li>
			<li>Headings highlighted in <span style="background-color:#FFD880; font-weight:bold;">YELLOW</span> indicate a late handin.</li>
			<li>Scores in <span style="color:red; font-weight:bold;">RED</span> are failing grades.</li>
			<li>Assignments marked with a &#9745; are pending grading.</li>
		</ul>

		<h2>Gradebook</h2>
		<?php
		// keep track of point values (to highlight failing grades)
		$pt_values = [];
		// begin: header row
		$asgn_select->execute();
		$asgn_select->store_result();
		echo($asgn_select->error);
		?>
		<table border="1" class="gradebook class_gradebook">
			<tr>
			<th>Student</th>
			<?php
			while($asgn_select->fetch()) {
				$dt_due = new DateTime($date_due);
				$pt_values[$asgn_id] = $pt_value;
				?><th<?php echo($honors_possible ? " class=\"honors_possible\"" : NULL); ?>>
					<span><?php echo($dt_due->format('D - M d')); ?></span>
					<span><?php echo($type); ?></span>
					<span><?php echo('(#' . $asgn_id . ") " . $name); ?></span>
					<span><?php echo('Max: <span>' . $pt_value . '</span>'); ?></span>
				</th><?php
			}?></tr><tr><?php

			// begin: student rows
			$grade_select->execute();
			$grade_select->store_result();
			echo($grade_select->error);

			$curr_student = "";
			while($grade_select->fetch()) {
				if (strcmp($curr_student, $uid) != 0) {	// start new row for new student
					$curr_student = $uid;
					?></tr><tr>
						<td><span><?php echo($last_name); ?></span>, <?php echo($first_name); ?></td><?php
				}
				// figure out gradebook entry for this row
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
						if ($chomped && $score){	// graded
							$entry_text = $entry_text . round($score,2);
							$entry_class = $entry_class . " graded";

							if (($score / (float) $pt_values[$asgn_id]) <= 0.6)
								$entry_class = $entry_class . " low_grade";

							if ($honors_possible && $honors_earned)
								$entry_class = $entry_class . " honors_earned";
						}
						else 						// otherwise, pending grade
							$entry_text = $entry_text . "&#9745;";

						if ($extension)
							$entry_text = $entry_text . "<span>(+" . $extension . "hrs)</span>";

						if ($late)
							$entry_class = $entry_class . " late";
					}
				}
				echo("<td><div class=\"" . $entry_class . "\">" . $entry_text . "</div></td>");
			} ?>
			</tr>
		</table>
		
		<div style="margin-top:25px;"><a href="report_grades.php">Generate Another Report</a> | 
		<a href="index.php">Menu</a></div><?php 
	} else {	// GET: display edit options
		?>
		<h1>Class Period Grade Report</h1>
		<form action="report_grades.php" method="POST">
			<p>Select trimester and class period in which to generate a grade report.</p>

			<div><label for="trimester">Trimester:</label>
				<select id="trimester" name="trimester">
					<option value="1">T1</option>
					<option value="2">T2</option>
					<option value="3">T3</option>
				</select>
			</div>

			<div><label for="class_pd">Class Period:</label>
				<select id="class_pd" name="class_pd"><?php
					foreach ($classes as $class_pd) {
						?><option value="<?php echo($class_pd);?>">
							<?php echo($class_pd); ?>
						</option><?php
					}?>
				</select>
			</div>

			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>