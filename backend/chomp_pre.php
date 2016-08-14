<?php 	$rubric_dir = "/course/csp/rubric/";
		$handin_dir = "/course/csp/handin/"; ?>
<html>
<head>
	<title>Portal Admin :: Chomp Assignment (Prep Grade)</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Chomp Assignment (Prep Grade)</h1>
	<?php
	function warning_handler() {
		die("<span style=\"color:red;\"><b>
				File issue (check for existence of rubric; permissions)!</b></span>");
	}
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$classes = get_class_pds($conn);
	$types = get_asgn_types($conn);
	$assignments = get_all_assignments($conn);
	if ($method == 'POST') {	// POST
		// steps:
		// - Set chomped=1 on all assignments where handed_in=1 (prevents handing in over top)
		// - Copy grade_comments.txt from rubric folder to each folder where handed_in=1
		// 		- Put late days into grade_comments.txt
		//		- Put username & period into grade_comments.txt
		//		- Put handin timestamp into grade_comments.txt
		//		- Put total into grade_comments.txt
		$asgn_id = $_POST["asgn_id"];
		$class_pd = $_POST["class_pd"];
		$overwrite = array_key_exists("overwrite", $_POST) ? 1 : 0;

		// get rubric file (will safely error out if it doesn't exist)
		//set_error_handler("warning_handler", E_WARNING);
		$f_rubric = file($rubric_dir . $asgn_id . ".txt");
	
		// create queies
		$meta_select		= $conn->prepare("SELECT date_due, pt_value, name FROM assignment JOIN assignment_meta ON assignment.asgn_id = assignment_meta.asgn_id WHERE assignment.asgn_id = ? AND class_pd = ?");
		$grade_select		= $conn->prepare("SELECT grades.uid, username, name, late, handin_time FROM grades JOIN membership ON grades.uid = membership.uid JOIN user ON grades.uid = user.uid JOIN user_meta ON grades.uid = user_meta.uid WHERE handed_in=1 AND asgn_id=? AND class_pd=?");
		$grade_update		= $conn->prepare("UPDATE grades SET chomped=1 WHERE uid = ? AND asgn_id = ?");

		$meta_select->bind_param("ii", $asgn_id, $class_pd);
		$meta_select->bind_result($date_due, $pt_value, $asgn_name);
		$grade_select->bind_param("ii", $asgn_id, $class_pd);
		$grade_select->bind_result($uid, $username, $name, $late, $handin_time);
		$grade_update->bind_param("ii", $uid, $asgn_id);

		// get meta information (set $date_due, $pt_value)
		$meta_select->execute();
		$meta_select->store_result();
		$meta_select->fetch();
		echo($meta_select->error);
		$due_t = strtotime($date_due);

		// iterate through handed in assignments
		$grade_select->execute();
		$grade_select->store_result();
		while ($grade_select->fetch()) {
			$rubric_filename = $handin_dir . $asgn_id . "/" . $username . "/" . "grade_comments.txt";
			$rnr = "";
			if ($overwrite || !file_exists($rubric_filename)) {
				// determine late days
				$handin_t = strtotime($handin_time);
				if ($late) {
					$h_dt = new DateTime($handin_time);
					$d_dt = new DateTime($date_due);
					$interval = $h_dt->diff($d_dt);
					$late_days = $interval->format('%a');
					$late_days = $late_days+1;
				}
				// copy & update rubric array as required
				$rubric = $f_rubric;
				for ($i = 0; $i < count($rubric); $i++) {
					if (strpos($rubric[$i], "CSP_HEAD") !== FALSE) {
						$insert = ["Student: " . $name . " (" . $username . ")" . PHP_EOL,
							"Period: " . $class_pd . PHP_EOL,
							"Turned In: " . date("D, M d, Y g:i A", $handin_t) . 
							"\t(Due: " . date("D, M d, Y g:i A", $due_t) . ")"  . PHP_EOL
						];
						array_splice($rubric, $i, 1, $insert);
					} else if (strpos($rubric[$i], "CSP_FOOT") !== FALSE) {

						$insert = ["Late Handin Penalty: /0"  . PHP_EOL,
						$asgn_name . " Total: /" . $pt_value  . PHP_EOL];
						$late_days ? array_unshift($insert, "Late Days: " . $late_days . PHP_EOL) : NULL;
						array_splice($rubric, $i, 1, $insert);
					}
				}

				$newfile = implode("<br>", $rubric);
				file_put_contents($rubric_filename, implode("", $rubric)); // write rubric to user's handin dir
			} else {
				$rnr = " (Old rubric not replaced)";
			}
			$grade_update->execute();
			echo($grade_update->error);
			?><p> Chomped: <?php echo($username); echo($rnr ? $rnr : ""); ?></p><?php
		}

		// report NTIs, lates, & NREQs
		?><h2>Stats Brief</h2><?php
		$stat_select = $conn->prepare("SELECT username, nreq, late, handed_in FROM grades JOIN membership ON grades.uid = membership.uid JOIN user ON grades.uid = user.uid WHERE asgn_id = ? AND class_pd = ? ORDER BY username ASC");
		$stat_select->bind_param("ii", $asgn_id, $class_pd);
		$stat_select->bind_result($username, $nreq, $late, $handed_in);
		$stat_select->execute();
		$stat_select->store_result();
		echo($stat_select->error);

		$a_nreq = [];
		$a_late = [];
		$a_nti  = [];

		while($stat_select->fetch()) {
			if ($nreq)
				array_push($a_nreq, $username);
			else if (!$handed_in)
				array_push($a_nti, $username);

			if ($late)
				array_push($a_late, $username);
		}

		?><h3>NREQ</h3><ul><?php
		foreach ($a_nreq as $u) {
			echo("<li>" . $u . "</li>");
		}
		?></ul><h3>Late</h3><ul><?php
		foreach ($a_late as $u) {
			echo("<li>" . $u . "</li>");
		}
		?></ul><h3>NTI</h3><ul><?php
		foreach ($a_nti as $u) {
			echo("<li>" . $u . "</li>");
		}
		?></ul>

		<p style="font-weight:bold">Pre-Chomp Complete: <?php echo($asgn_name); ?></p>
		<div><a href="chomp_pre.php">Again</a> | 
		<a href="index.php">Menu</a></div><?php
	} else {	// GET: display edit options
		?><form action="chomp_pre.php" method="POST">
			<p>Select a pre-chomp pair.</p>

			<div><label for="asgn_id">Assignment:</label><select id="asgn_id" name="asgn_id">
				<?php
				foreach ($assignments as $asgn) {
					?><option value="<?php echo($asgn["asgn_id"]);?>">
						<?php echo($asgn["asgn_id"]);?>.) <?php echo($asgn["name"]);?>
					</option><?php
				} ?>
			</select></div>

			<div><label for="class_pd">Class Period:</label><select id="class_pd" name="class_pd">
				<?php
				foreach ($classes as $class_pd) {
					?><option value="<?php echo($class_pd);?>">
						<?php echo($class_pd); ?>
					</option><?php
				} ?>
			</select></div>

			<div><label for="overwrite">Overwrite Already Chomped?</label><input type="checkbox" id="overwrite" name="overwrite" /></div>
			<p>WARNINGS:
				<ul>
					<li>Overwrite will DELETE any grade files already filled in.</li>
					<li>Failure to overwrite will NOT include new late information.</li>
				</ul>
			</p>


			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>