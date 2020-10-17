<html>
<head>
	<title>Portal Admin :: Add Lesson</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
	<script src="https://code.jquery.com/jquery-1.10.1.min.js" type="text/javascript"></script>
</head>
<body>
	<h1>Add Lesson</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];
	$classes = get_class_pds($conn);

	if ($method == 'POST') {	// POST: admin is adding asgn
		
		// create queries
		$lesson_insert		= $conn->prepare("INSERT INTO lesson (trimester, topic, slide_url, extra_url) VALUES (?, ?, ?, ?)");
		$meta_insert		= $conn->prepare("INSERT INTO lesson_meta (id, class_pd, release_date, visible) VALUES (?, ?, ?, ?)");

		$lesson_id_lookup	= $conn->prepare("SELECT id FROM lesson WHERE topic = ?");

		// insert lesson (universal)
		if ($_POST["extra_url"] != NULL && $_POST["extra_url"] == "")
			$_POST["extra_url"] = NULL;

		$lesson_insert->bind_param("isss", $_POST["trimester"], $_POST["topic"], $_POST["slide_url"], $_POST["extra_url"]);
		$lesson_insert->execute();
		echo($lesson_insert->error);

		// ID (primary key) lookup
		$lesson_id_lookup->bind_param("s", $_POST["topic"]);
		$lesson_id_lookup->bind_result($id);
		$lesson_id_lookup->execute();
		echo($lesson_id_lookup->error); 
		$lesson_id_lookup->fetch();
		$lesson_id_lookup->free_result();

		// prepare & run queries for each class period
		$meta_insert->bind_param("iisi", $id, $class_pd, $release_date, $visible);
		$meta = $_POST["lesson"];
		foreach (array_keys($meta) as $class_pd) {
			$release_date = date("Y-m-d", strtotime($meta[$class_pd]["release_date"]));
			$visible = array_key_exists("visible", $meta[$class_pd]) ? 1 : 0;
			$meta_insert->execute();
			echo($meta_insert->error);
		}
		?><p style="font-weight:bold">Lesson Added: <?php echo($_POST["topic"]); ?> (ID #<?php echo($id); ?>)</p>
		<div><a href="add_lesson.php">Again</a> | 
		<a href="index.php">Menu</a></div><?php
	} else {	// GET: display add asgn screen

		?>
		<form action="add_lesson.php" method="post">

			<h2>Meta Information:</h2>

				<div><label for="trimester">Term:</label>
					<select id="trimester" name="trimester">
						<option value="1">S1</option>
						<option value="2">S2</option>
					</select>
				</div>
				<div><label for="topic">Topic:</label><input type="text" id="topic" name="topic" placeholder="(e.g. Graph Algorithms)" /></div>
				<div><label for="slide_url">Slide URL:</label><input type="text" id="slide_url" name="slide_url" placeholder="http://principles.cs.stab.org/asgn/hw01.pdf" /></div>
				<div><label for="extra_url">Extra URL:</label><input type="text" id="extra_url" name="extra_url" placeholder="http://principles.cs.stab.org/asgn/hw01.pdf" /></div>

			<h2>Per-Class Information:</h2>

				<table border="1" style="border:0;">
					<tr>
						<th>Period</th>
						<th>Release Date</th>
						<th>Visible?</td>
					</tr>
					<?php
					foreach ($classes as $pd) {
						?><tr>
							<td><?php echo($pd); ?></td>
							<td><input id="lesson[<?php echo($pd); ?>][release_date]" name="lesson[<?php echo($pd); ?>][release_date]" type="date" /></td>
							<td><input id="lesson[<?php echo($pd); ?>][visible]" name="lesson[<?php echo($pd); ?>][visible]" type="checkbox" value="1" checked /></td>
						</tr><?php
					} ?>
				</table>

				<input type="submit" />
		</form>
	<?php } ?>
</body>
</html>