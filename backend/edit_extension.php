<html>
<head>
	<title>Portal Admin :: Grant Extension</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Grant Extension</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$users = get_all_userinfo($conn);
	$assignments = get_all_assignments($conn);

	if ($method == 'POST') {	// POST
		$asgn_id = $_POST["asgn_id"];
		$uid = $_POST["uid"];
		$extension = $_POST["extension"];
		$recompute_late = array_key_exists("recompute_late", $_POST) ? 1 : 0;

		$extend_update	= $conn->prepare("UPDATE grades SET extension = ? WHERE uid=? AND asgn_id=?");
		$info_select	= $conn->prepare("SELECT handin_time, date_due FROM grades JOIN membership on grades.uid = membership.uid JOIN assignment_meta ON assignment_meta.asgn_id = grades.asgn_id AND membership.class_pd = assignment_meta.class_pd WHERE grades.uid=? AND grades.asgn_id=?");
		$late_update	= $conn->prepare("UPDATE grades SET late = ? WHERE uid=? AND asgn_id=?");

		$extend_update->bind_param("iii", $extension, $uid, $asgn_id);
		$late_update->bind_param("iii", $recomputed_late, $uid, $asgn_id);
		$extend_update->execute();
		echo($extend_update->error);

		$info_select->bind_param("ii", $uid, $asgn_id);
		$info_select->bind_result($handin_time, $date_due);
		$info_select->execute();
		$info_select->store_result();
		$info_select->fetch();

		$date_due = new DateTime($date_due);
		?><table border="1"><tr><td>Original Due Date:</td><td><?php echo($date_due->format('m/d/Y h:i A')); ?></td></tr><?php
		$date_due->add(new DateInterval("PT".$extension."H"));
		?><tr style="font-weight:bold;"><td>New Due Date:</td><td><?php echo($date_due->format('m/d/Y h:i A')); ?></td></tr><?php
		
		// update lates if necessary
		if ($handin_time && $recompute_late) {

			$handin_time = new DateTime($handin_time);
			?><tr><td>Handin Time:</td><td><?php echo($handin_time->format('m/d/Y h:i A')); ?></td></tr><?php

			if ($handin_time < $date_due) {
				$recomputed_late = 0;
			} else {
				$recomputed_late = 1;
			}
			$late_update->execute();
			echo($late_update->error);
			?><tr><td>Assignment Marked:</td><td><b><?php echo($recomputed_late ? "LATE" : "NOT LATE"); ?></b></td></tr><?php
		}

		?></table><p style="font-weight:bold">Extension Granted: <?php echo($users[$uid]["name"]); ?> / <?php echo($assignments[$asgn_id]["name"]); ?></p>
			<div><a href="edit_extension.php">Again</a> | 
			<a href="index.php">Menu</a></div><?php

	} else {	// GET: display edit options
		?><form action="edit_extension.php" method="POST">
			<p>Select student and assignment to extend, then input number of extension hours.</p>

			<div><label for="uid">Student:</label><select id="uid" name="uid">
				<?php
				foreach ($users as $user) {
					?><option value="<?php echo($user["uid"]);?>">
						<?php echo($user["class_pd"]);?>.) <?php echo($user["username"]);?> [<?php echo($user["name"]); ?>]
					</option><?php
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

			<div><label for="extension">Extension Hours:</label><input id="extension" name="extension" type="text" placeholder="e.g. 24" /></div>

			<div><label for="recompute_late">Recompute Late?</label><input id="recompute_late" name="recompute_late" type="checkbox" value="1" checked /></div>

			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>