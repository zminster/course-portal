<html>
<head>
	<title>Portal Admin :: Individual Assignment Report</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body class="report">
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$assignments = get_all_assignments($conn);

	// do_stats
	// Input: array of digits
	// Output: array with following data mapped to indicies:
	// [0] -> Mean, [1] -> Median, [2] -> Range, [3] -> Std. Dev.
	function do_stats($array) {
		$res = [];
		// mean
		$count = count($array);
		$sum = array_sum($array);
		$res[0] = $sum / $count;

		// median
		rsort($array);
		$middle = round($count / 2);
		$res[1] = $array[$middle-1];

		// range
		$lrg = $array[0];
		sort($array);
		$sml = $array[0];
		$res[2] = $lrg - $sml;

		// std deviation
		$res[3] = stats_standard_deviation($array);
		return $res;
	}

	function print_stats_row($stats, $first) {
		?><tr><td><?php echo($first); ?></td><?php
		?><td><?php echo(round($stats[0],2)); ?></td><?php
		?><td><?php echo(round($stats[1],2)); ?></td><?php
		?><td><?php echo(round($stats[2],2)); ?></td><?php
		?><td><?php echo(round($stats[3],2)); ?></td></tr><?php
		return;
	}

	if ($method == 'POST') {	// POST

		$classes = get_class_pds($conn);
		$asgn_id = $_POST["asgn_id"];
		$meta = get_all_assignment_data($conn, $asgn_id);

		?><h1>General Stats</h1><?php
		$stat_select = $conn->prepare("SELECT username, class_pd, nreq, late, handed_in, handin_time, chomped, extension, score FROM grades JOIN membership ON grades.uid = membership.uid JOIN user ON grades.uid = user.uid WHERE asgn_id = ? ORDER BY class_pd, username");
		$stat_select->bind_param("i", $asgn_id);
		$stat_select->bind_result($username, $class_pd, $nreq, $late, $handed_in, $handin_time, $chomped, $extension, $score);
		$stat_select->execute();
		$stat_select->store_result();
		echo($stat_select->error);

		$a_nreq 		= [];
		$a_late 		= [];
		$a_nti  		= [];
		$a_needgrade 	= [];
		$a_extension 	= [];

		while($stat_select->fetch()) {
			if ($nreq)
				array_push($a_nreq, [$class_pd, $username]);
			else if (!$handed_in)
				array_push($a_nti, [$class_pd, $username]);
			if ($late)
				array_push($a_late, [$class_pd, $username, $handin_time, $extension]);
			if ($handed_in && (!$chomped || !$score))
				array_push($a_needgrade, [$class_pd, $username, $chomped]);
			if ($extension)
				array_push($a_extension, [$class_pd, $username, $extension]);
		}

		// NTI/LATE/NREQ per period
		// Turned in and not graded per period

		?><h2>NTI</h2>
		<table border="1">
			<tr>
				<th>Class Period</th>
				<th>Username</th>
			</tr>
		<?php
		foreach ($a_nti as $u) {
			?><tr><?php
			echo("<td>" . $u[0] . "</td>");
			echo("<td>" . $u[1] . "</td>");
			?></tr><?php
		}
		?></table>

		<h2>Late</h2>
		<table border="1">
			<tr>
				<th>Class Period</th>
				<th>Username</th>
				<th>Handin Time</th>
				<th>Diff (Including Extension)</th>
			</tr>
		<?php
		foreach ($a_late as $u) {
			?><tr><?php
			echo("<td>" . $u[0] . "</td>");
			echo("<td>" . $u[1] . "</td>");
			echo("<td>" . $u[2] . "</td>");
			// calculate difference from handin in seconds, taking extensions into account
			$due = strtotime($meta[$u[0]]['date_due']);
			$handin = strtotime($u[2]);
			if ($u[3])
				$due += (3600 * $u[3]);	// seconds in an hour
			$diff = $handin - $due;
			echo ("<td>" . ($diff/3600) . " hrs</td>");
			?></tr><?php
		}
		?></table>

		<h2>NREQ</h2>
		<table border="1">
			<tr>
				<th>Class Period</th>
				<th>Username</th>
			</tr>
		<?php
		foreach ($a_nreq as $u) {
			?><tr><?php
			echo("<td>" . $u[0] . "</td>");
			echo("<td>" . $u[1] . "</td>");
			?></tr><?php
		}
		?></table>

		<h2>Extensions</h2>
		<table border="1">
			<tr>
				<th>Class Period</th>
				<th>Username</th>
				<th>Extension Hrs</th>
			</tr><?php
		foreach ($a_extension as $u) {
			?><tr><?php
			echo("<td>" . $u[0] . "</td>");
			echo("<td>" . $u[1] . "</td>");
			echo("<td>" . $u[2] . "</td>");
			?></tr><?php
		}
		?></table>


		<h2 style="color:red">Turned In &amp; Not Graded</h2>
		<table border="1">
			<tr>
				<th>Class Period</th>
				<th>Username</th>
				<th>Chomped?</th>
			</tr>
		<?php
		foreach ($a_needgrade as $u) {
			?><tr><?php
			echo("<td>" . $u[0] . "</td>");
			echo("<td>" . $u[1] . "</td>");
			echo($u[2] ? "<td>Yes</td>" : "<td>No</td>");
			?></tr><?php
		}
		?></table>


		<h1>Aggregate Statistics</h1><?php
		// Aggregate stats per period (mean, median, range, std. dev)
		// Aggregate stats total (across all periods)
		
		$stat_select = $conn->prepare("SELECT score, handin_time, extension, class_pd FROM grades JOIN membership ON grades.uid = membership.uid JOIN user ON grades.uid = user.uid WHERE asgn_id = ? AND handed_in = 1 AND chomped = 1 AND score IS NOT NULL ORDER BY handin_time, grades.uid");
		$stat_select->bind_param("i", $asgn_id);
		$stat_select->bind_result($score, $handin_time, $extension, $class_pd);
		$stat_select->execute();
		$stat_select->store_result();
		echo($stat_select->error);

		$a_score		= array();
		$handin_diff	= array();

		foreach ($classes as $class_pd) {
			$a_score[$class_pd] = [];
			$handin_diff[$class_pd] = [];
		}

		while($stat_select->fetch()) {
			array_push($a_score[$class_pd], $score);

			// calculate difference from handin in seconds, taking extensions into account
			$due = strtotime($meta[$class_pd]['date_due']);
			$handin = strtotime($handin_time);
			if ($extension)
				$due += (3600 * $extension);	// seconds in an hour
			$diff = ($handin - $due) / 3600;
			array_push($handin_diff[$class_pd], $diff);
		}

		$tot_score = array();
		$tot_late  = array();
		foreach ($classes as $class_pd) {
			$tot_score = array_merge($tot_score, $a_score[$class_pd]);
			$tot_late = array_merge($tot_late, $handin_diff[$class_pd]);
		}

		?><h2>Scores</h2>
		<table border="1">
		<tr>
			<th>Class Period</th>
			<th>Average</th>
			<th>Median</th>
			<th>Range</th>
			<th>Standard Deviation</th>
		</tr>
		<?php
		foreach ($classes as $class_pd) {
			if (count($a_score[$class_pd]) > 0) {
				$stats = do_stats($a_score[$class_pd]);
				print_stats_row($stats, $class_pd);
			}
		}
		$stats = do_stats($tot_score);
		print_stats_row($stats, "ALL"); ?>
		</table>

		<h2>Handin Diff (in hours)</h2>
		<table border="1">
		<tr>
			<th>Class Period</th>
			<th>Average</th>
			<th>Median</th>
			<th>Range</th>
			<th>Standard Deviation</th>
		</tr>
		<?php
		foreach ($classes as $class_pd) {
			if (count($handin_diff[$class_pd]) > 0) {
				$stats = do_stats($handin_diff[$class_pd]);
				print_stats_row($stats, $class_pd);
			}
		}
		$stats = do_stats($tot_late);
		print_stats_row($stats, "ALL"); ?>
		</table>

		<div><a href="report_asgn.php">Report Another Assignment</a> | 
		<a href="index.php">Menu</a></div><?php 
	} else {	// GET: display edit options
		?><form action="report_asgn.php" method="POST">
			<p>Select assignment for which to generate report.</p>

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