<html>
<head>
	<title>Portal Admin :: Toggle Assignment NREQ/CANVIEW/LATE</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Toggle Assignment NREQ/CANVIEW/LATE</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST
		$uid = $_POST['uid'];
		$asgn_id = $_POST['asgn_id'];		

		if(array_key_exists("edit", $_POST)) {	// update with toggles from form
			$nreq = array_key_exists("nreq", $_POST) ? 1 : 0;
			$late = array_key_exists("late", $_POST) ? 1 : 0;
			$canview = array_key_exists("canview", $_POST) ? 1 : 0;
			$chomped = array_key_exists("chomped", $_POST) ? 1 : 0;

			// nullify score if null_score is set, otherwise re-enter same score
			$null_score = array_key_exists("null_score", $_POST) ? 1 : 0;
			$score = array_key_exists("score", $_POST) ? $_POST["score"] : "NULL";
			$score = $null_score ? "NULL" : $score;

			// create query
			$toggle_query	= $conn->prepare("UPDATE grades SET nreq=?, late=?, can_view_feedback=?, chomped=?, score=? WHERE uid=? AND asgn_id=?");
			$toggle_query->bind_param("iiiii", $nreq, $late, $canview, $chomped, $score, $uid, $asgn_id);

			// exec
			$toggle_query->execute();
			echo($toggle_query->error);

			?><p style="font-weight:bold">User Updated: <?php echo($uid); ?> / <?php echo($asgn_id); ?></p>
			<div><a href="edit_toggle.php">Again</a> | 
			<a href="index.php">Menu</a></div><?php
		} else {	// pair selected, look up & present editable information
			$users = get_all_userinfo($conn);
			$user = $users[$uid];

			$lookup = $conn->prepare("SELECT nreq, handed_in, handin_time, late, chomped, can_view_feedback, score FROM grades WHERE uid=? AND asgn_id=?");
			$lookup->bind_param("ii", $uid, $asgn_id);
			$lookup->bind_result($nreq, $handed_in, $handin_time, $late, $chomped, $can_view_feedback, $score);
			$lookup->execute();
			echo($lookup->error);
			$lookup->store_result();
			$lookup->fetch();
			?>
			<form action="edit_toggle.php" method="post">
				<input type="hidden" name="uid" value="<?php echo($uid);?>" />
				<input type="hidden" name="asgn_id" value="<?php echo($asgn_id);?>" />
				<input type="hidden" name="edit" value="1" />

				<table>
					<tr>
						<th>UID</th>
						<th>ASGN ID</th>
						<th>(Handed in)?</th>
						<th>(Handin Time)</th>
						<th>NREQ?</th>
						<th>LATE?</th>
						<th>CANVIEW?</th>
						<th>CHOMPED (Handin Locked)?</th>
						<th>SCORE (Check to NULL)</th>
					</tr>
					<tr>
						<td><?php echo($uid); ?></td>
						<td><?php echo($asgn_id); ?></td>
						<td><?php echo($handed_in ? "YES" : "NO"); ?></td>
						<td><?php echo($handin_time ? $handin_time : "N/A"); ?></td>
						<td><input type="checkbox" id="nreq" name="nreq" <?php echo($nreq ? "checked" : ""); ?>/></td>
						<td><input type="checkbox" id="late" name="late" <?php echo($late ? "checked" : ""); ?>/></td>
						<td><input type="checkbox" id="canview" name="canview" <?php echo($can_view_feedback ? "checked" : ""); ?>/></td>
						<td><input type="checkbox" id="chomped" name="chomped" <?php echo($chomped ? "checked" : ""); ?>/></td>
						<td>
							<?php echo($score ? $score : "NULL"); ?> 
							<?php echo($score ? "<input type=\"hidden\" name=\"score\" value=\"" + $score + "\" />" : "" ?>
							<input type="checkbox" id="null_score" name="null_score" />
						</td>
					</tr>
				</table>
				<input type="submit" />
			</form>
		<?php }
	} else {	// GET: display edit options
		$users = get_all_userinfo($conn);
		$assignments = get_all_assignments($conn);
		?><form action="edit_toggle.php" method="POST">
			<p>Select a toggle pair.</p>

			<div><label for="uid">Student:</label><select id="uid" name="uid">
				<?php
				foreach ($users as $user) {
					?><option value="<?php echo($user["uid"]);?>">
						<?php echo($user["class_pd"]);?>) <?php echo($user["username"]);?> [<?php echo($user["name"]); ?>]
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

			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>