<html>
<head>
	<title>Portal Admin :: Global Missing</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body class="report">
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST
		// goals:
		//  - report ALL NTIs in all periods for assignments past due
		//  - report all lates in all periods
		//	- report all Turned In & Not Graded in all periods

		$trimester = $_POST["trimester"];
		$assignments = get_all_assignments($conn);

		$stat_select = $conn->prepare("SELECT username, grades.asgn_id, class_pd, nreq, late, handed_in, handin_time, chomped, extension, score FROM grades JOIN membership ON grades.uid = membership.uid JOIN user ON grades.uid = user.uid JOIN assignment ON grades.asgn_id = assignment.asgn_id WHERE trimester = ? ORDER BY class_pd, asgn_id, username");

		$stat_select->bind_param("i", $trimester);
		$stat_select->bind_result($username, $asgn_id, $class_pd, $nreq, $late, $handed_in, $handin_time, $chomped, $extension, $score);
		$stat_select->execute();
		$stat_select->store_result();
		echo($stat_select->error);

		$a_nti  		= [];
		$a_late 		= [];
		$a_needgrade 	= [];

		while($stat_select->fetch()) {
			if (!$nreq && !$handed_in)
				array_push($a_nti, [$class_pd, $username, $asgn_id]);
			if ($late)
				array_push($a_late, [$class_pd, $username, $asgn_id, $handin_time, $extension]);
			if ($handed_in && (!$chomped || !$score))
				array_push($a_needgrade, [$class_pd, $username, $asgn_id, $chomped]);
		}

		?>
		<h1>Global Assignment Issues - Trimester <?php echo($trimester); ?></h1>
		<h2>NTI</h2>
		<table border="1">
			<tr>
				<th>Class Period</th>
				<th>Username</th>
				<th>Assignment</th>
			</tr>
		<?php
		foreach ($a_nti as $u) {
			?><tr><?php
			echo("<td>" . $u[0] . "</td>");
			echo("<td>" . $u[1] . "</td>");
			echo("<td>" . $u[2] . ".)" . $assignments[$u[2]]['name'] . "</td>");
			?></tr><?php
		}
		?></table>

		<h2>Late</h2>
		<table border="1">
			<tr>
				<th>Class Period</th>
				<th>Username</th>
				<th>Assignment</th>
				<th>Handin Time</th>
				<th>Diff (Including Extension)</th>
				<th>Extension</th>
			</tr>
		<?php
		foreach ($a_late as $u) {
			?><tr><?php
			echo("<td>" . $u[0] . "</td>");
			echo("<td>" . $u[1] . "</td>");
			echo("<td>#" . $u[2] . ") " . $assignments[$u[2]]['name'] . "</td>");
			echo("<td>" . $u[3] . "</td>");
			// calculate difference from handin in seconds, taking extensions into account
			$meta = get_all_assignment_data($conn, $u[2]);
			$due = strtotime($meta[$u[0]]['date_due']);
			$handin = strtotime($u[3]);
			if ($u[4])
				$due += (3600 * $u[4]);	// seconds in an hour
			$diff = $handin - $due;
			echo ("<td>" . ($diff/3600) . " hrs</td>");
			echo ("<td>" . $u[4] . " hrs</td>");
			?></tr><?php
		}
		?></table>

		<h2 style="color:red">Turned In &amp; Not Graded</h2>
		<table border="1">
			<tr>
				<th>Class Period</th>
				<th>Username</th>
				<th>Assignment</th>
				<th>Chomped?</th>
			</tr>
		<?php
		foreach ($a_needgrade as $u) {
			?><tr><?php
			echo("<td>" . $u[0] . "</td>");
			echo("<td>" . $u[1] . "</td>");
			echo("<td>#" . $u[2] . ") " . $assignments[$u[2]]['name'] . "</td>");
			echo($u[3] ? "<td>Yes</td>" : "<td>No</td>");
			?></tr><?php
		}
		?></table>

		<div><a href="report_nti.php">Again</a> | 
		<a href="index.php">Menu</a></div><?php 
	} else {	// GET: display edit options
		?>
		<h1>Global Assignment Issues</h1>
		<form action="report_nti.php" method="POST">
			<p>Select trimester for which to generate report.</p>

			<div><label for="trimester">Trimester:</label>
				<select id="trimester" name="trimester">
					<option value="1">T1</option>
					<option value="2">T2</option>
					<option value="3">T3</option>
				</select>
			</div>

			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>