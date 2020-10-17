<html>
<head>
	<title>Portal Admin :: Edit/Delete Lesson</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
	<script src="https://code.jquery.com/jquery-1.10.1.min.js" type="text/javascript"></script>
</head>
<body>
	<h1>Edit/Delete Lesson</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$classes = get_class_pds($conn);
	$lessons = get_all_lessons($conn);

	if ($method == 'POST') {	// POST
		$id = $_POST["id"];
		if (array_key_exists("delete", $_POST)) {	// delete lesson & meta
			$del_1 = $conn->prepare("DELETE FROM lesson WHERE id = ?");
			$del_2 = $conn->prepare("DELETE FROM lesson_meta WHERE id = ?");
			$del_1->bind_param("i", $id);
			$del_2->bind_param("i", $id);
			// execute in reverse order to prevent foreign key constraint violation
			$del_2->execute();
			echo($del_2->error);
			$del_1->execute();
			echo($del_1->error);
			?><p style="font-weight:bold">Lesson <?php echo($id); ?> Deleted</p>
			<p><a href="index.php">Menu</a></p><?php
		} else if(array_key_exists("edit", $_POST)) {	// edit lesson, update all fields to match form values
		
			// create queries
			$lesson_update	= $conn->prepare("UPDATE lesson SET trimester=?, topic=?, slide_url=?, extra_url=? WHERE id=?");
			$meta_update	= $conn->prepare("UPDATE lesson_meta SET release_date=?, visible=? WHERE id=? AND class_pd=?");

			// update lesson (universal)
			if ($_POST["extra_url"] != NULL && $_POST["extra_url"] == "")
				$_POST["extra_url"] = NULL;

			$lesson_update->bind_param("isssi", $_POST["trimester"], $_POST["topic"], $_POST["slide_url"], $_POST["extra_url"], $id);
			$lesson_update->execute();
			echo($lesson_update->error);

			// prepare and run updates for each class period
			$meta_update->bind_param("siii", $release_date, $visible, $id, $class_pd);
			$meta = $_POST["lesson"];
			foreach (array_keys($meta) as $class_pd) {
				$release_date = date("Y-m-d", strtotime($meta[$class_pd]["release_date"]));
				$visible = array_key_exists("visible", $meta[$class_pd]) ? 1 : 0;
				$meta_update->execute();
				echo($meta_update->error);
			}
			?><p style="font-weight:bold">Lesson Changed: <?php echo($_POST["topic"]); ?> (ID #<?php echo($id); ?>)</p>
			<div><a href="edit_lesson.php">Again</a> | 
			<a href="index.php">Menu</a></div><?php
		} else {	// lesson selected, display lesson information
			$lesson_data = get_all_lesson_data($conn, $id);

			// meta grab
			$trimester 	= $lessons[$id]["trimester"];
			$topic	 	= $lessons[$id]["topic"];
			$slide_url 	= $lessons[$id]["slide_url"];
			$extra_url 	= $lessons[$id]["extra_url"];
			?>
			<form action="edit_lesson.php" method="post">
				<input type="hidden" name="id" value="<?php echo($id);?>" />
				<input type="hidden" name="edit" value="1" />

				<h2>Lesson (ID #<?php echo($id); ?>) Meta Information:</h2>

					<div><label for="delete">Delete?</label><input type="checkbox" id="delete" name="delete" /></div>

					<div><label for="trimester">Term:</label>
						<select id="trimester" name="trimester">
							<option value="1" <?php echo($trimester == 1 ? "selected" : ""); ?>>S1</option>
							<option value="2" <?php echo($trimester == 2 ? "selected" : ""); ?>>S2</option>
						</select>
					</div>
					<div><label for="topic">Topic:</label><input type="text" id="topic" name="topic" placeholder="(e.g. Graph Algorithms)" value="<?php echo($topic); ?>" /></div>

					<div><label for="slide_url">Slide URL:</label><input type="text" id="slide_url" name="slide_url" placeholder="http://principles.cs.stab.org/asgn/hw01.pdf" value="<?php echo($slide_url); ?>" /></div>

					<div><label for="extra_url">Slide URL:</label><input type="text" id="extra_url" name="extra_url" placeholder="http://principles.cs.stab.org/asgn/hw01.pdf" value="<?php echo($extra_url); ?>" /></div>

				<h2>Lesson (ID #<?php echo($id); ?>) Per-Class Information:</h2>

					<table border="1" style="border:0;">
						<tr>
							<th>Period</th>
							<th>Release Date</th>
							<th>Visible?</th>
						</tr>
						<?php
						foreach ($classes as $pd) {
							$formatted_release = date("Y-m-d", strtotime($lesson_data[$pd]["release_date"]));
							?><tr>
								<td><?php echo($pd); ?></td>
								<td><input id="lesson[<?php echo($pd); ?>][release_date]" name="lesson[<?php echo($pd); ?>][release_date]" type="date" value="<?php echo($formatted_release); ?>" /></td>
								<td><input id="lesson[<?php echo($pd); ?>][visible]" name="lesson[<?php echo($pd); ?>][visible]" type="checkbox" value="1" <?php echo($lesson_data[$pd]["visible"] ? "checked" : ""); ?>/></td>
							</tr><?php
						} ?>
					</table>

					<input type="submit" />
			</form>
		<?php }
	} else {	// GET: display edit options
		?><form action="edit_lesson.php" method="POST">
			<p>Select a lesson to edit.</p>
			<div><select name="id">
				<?php
				foreach ($lessons as $lesson) {
					?><option value="<?php echo($lesson["id"]);?>">
						<?php echo($lesson["id"]);?>.) <?php echo($lesson["topic"]);?>
					</option><?php
				} ?>
			</select></div>
			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>