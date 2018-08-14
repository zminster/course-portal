<?php $handin_dir = "/course/csp/handin/"; ?>
<html>
<head>
	<title>Portal Admin :: Nullify Assignment (Mark Zero)</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Nullify Assignment (Mark Zero)</h1>
	<?php
	function warning_handler() {
		die("<span style=\"color:red;\"><b>
				File issue (check for existence of rubric; permissions)!</b></span>");
	}
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	if ($method == 'POST') {	// POST

		$asgn_id = $_POST["asgn_id"];
		$uid = $_POST["uid"];

		$grade_update = $conn->prepare("UPDATE grades SET score=0.0001, can_view_feedback=0, chomped=1, honors_earned=0 WHERE uid = ? AND asgn_id = ?");

		$grade_update->bind_param("ii", $uid, $asgn_id);

		// iterate through handed in assignments
		$grade_update->execute();
		echo($grade_update->error); ?>

		<div><a href="edit_nullify.php">Again</a> | 
		<a href="index.php">Menu</a></div><?php
	} else {	// GET: display edit options
		$users = get_all_userinfo($conn);
		$assignments = get_all_assignments($conn);
		$roles = get_user_roles($conn);
		?><form action="edit_nullify.php" method="POST">
			<p>Select a student and assignment to nullify. This process sets score to 0 and <b>permanently disables handing the assignment in</b> for this student.</p>

			<div><label for="uid">Student</label><select id="uid" name="uid">
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