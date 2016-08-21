<?php $handin_dir = "/course/csp/handin/"; ?>
<html>
<head>
	<title>Portal Admin :: Chomp Assignment (Post Grades &amp; Comments)</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Chomp Assignment (Post Grades &amp; Comments)</h1>
	<?php
	function warning_handler() {
		die("<span style=\"color:red;\"><b>
				File issue (check for existence of rubric; permissions)!</b></span>");
	}
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$classes = get_class_pds($conn);
	$assignments = get_all_assignments($conn);
	if ($method == 'POST') {	// POST

		$asgn_id = $_POST["asgn_id"];
		$asgn_name = $assignments[$asgn_id]['name'];
		$class_pd = $_POST["class_pd"];
		$canview = array_key_exists("canview", $_POST) ? 1 : 0;
	
		$student_select	= $conn->prepare("SELECT grades.uid, username FROM grades JOIN membership ON grades.uid = membership.uid JOIN user ON grades.uid = user.uid WHERE chomped = 1 AND class_pd = ? AND asgn_id = ? ORDER BY grades.uid");
		$grade_update = $conn->prepare("UPDATE grades SET score=?, can_view_feedback=?, honors_earned=? WHERE uid = ? AND asgn_id = ?");

		$student_select->bind_param("ii", $class_pd, $asgn_id);
		$student_select->bind_result($uid, $username);
		$grade_update->bind_param("diiii", $score, $canview, $honors_earned, $uid, $asgn_id);

		// iterate through handed in assignments
		$student_select->execute();
		$student_select->store_result();
		while ($student_select->fetch()) {
			$rubric_filename = $handin_dir . $asgn_id . "/" . $username . "/" . "grade_comments.txt";
			if (file_exists($rubric_filename)) {
				$f_rubric = file($rubric_filename);
				$match_str = $asgn_name . " Total: ";
				for ($i = 0; $i < count($f_rubric); $i++) {
					if (strpos($f_rubric[$i], $match_str) !== FALSE) {
						preg_match('/: (\d+\.?\d*)\//', $f_rubric[$i], $m); // regex matches ": XX/"
						if (isset($m[1]))
							$score = $m[1];
						if ($assignments[$asgn_id]["honors_possible"]) {
							preg_match('/\? : ([a-z]*)/i', $f_rubric[$i+1], $m); // regex matches "? : YYY"; YYY should be either "YES" or "NO"
							if (isset($m[1]))
								$honors_earned = trim($m[1]);
						}
						break;
					}
				}
				if ($score === NULL) {
					?><p style="color:red;"><b>Missing final grade for <?php echo($username); ?>!</b></p><?php
				} else {
					if ($assignments[$asgn_id]["honors_possible"])
						$honors_earned = strcasecmp($honors_earned, "yes") == 0 ? 1 : 0;
					else
						$honors_earned = NULL;
					$grade_update->execute();
					echo($grade_update->error);
					?><p><b>Entered final grade of <?php echo($score); echo(isset($honors_earned) ? ($honors_earned ? " (Honors Earned)" : " (Honors NOT Earned)") : NULL); ?> for <?php echo($username); ?>!</b></p><?php
				}
			} else {
				?><p style="color:red;"><b>Missing rubric for <?php echo($username); ?>!</b></p><?php
			}
		}
		
		?><p style="font-weight:bold">Post-Chomp Complete: <?php echo($asgn_name); ?></p>
		<div><a href="chomp_post.php">Again</a> | 
		<a href="index.php">Menu</a></div><?php
	} else {	// GET: display edit options
		?><form action="chomp_post.php" method="POST">
			<p>Select a post-chomp pair. <b>Grades should be finished for all chomped assignments for this to work.</b></p>

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

			<div><label for="overwrite">Allow comment view?</label><input type="checkbox" id="canview" name="canview" /></div>

			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>