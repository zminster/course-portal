<html>
<head>
	<title>Portal Admin :: Edit/Delete Assignment Format</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Edit/Delete Assignment Format</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST
		$format_id = $_POST['format_id'];

		if (array_key_exists("delete", $_POST)) {	// delete format
			$del_1 = $conn->prepare("DELETE FROM assignment_format WHERE format_id = ?");
			$del_1->bind_param("i", $format_id);
			// foreign key constraint violation is likely; error message will print from DB
			$del_1->execute();

			if (strpos($del_1->error, "foreign key") !== false) {
				?><p style="font-weight:bold; color: red;">Could not delete Format # <?php echo($format_id); ?>: assignments still refer to this format (<a href="edit_asgn.php">modify/delete them first</a>).</p><?php
			} else if ($del_1->error) {
				?><p style="font-weight:bold; color: red;"><echo($del_1->error);</p><?php
			} else {
				?><p style="font-weight:bold">Format #<?php echo($format_id); ?> [<?php echo($_POST["name"]);?>] Deleted</p><?php
			}
			?>
			<p><a href="index.php">Menu</a></p><?php
		} else if(array_key_exists("edit", $_POST)) {	// edit format
			$is_file = array_key_exists("is_file", $_POST) ? 1 : 0;

			// run update query
			$format_update	= $conn->prepare("UPDATE assignment_format SET name=?, description=?, is_file=?, regex=?, validation_help=? WHERE format_id=?");
			$format_update->bind_param("ssissi", $_POST['name'], $_POST['description'], $is_file, $_POST['regex'], $_POST['validation_help'], $format_id);
			$format_update->execute();
			echo($format_update->error);

			?><p style="font-weight:bold">Format #<?php echo($format_id);?> Updated: <?php echo($_POST["name"]); ?></p>
			<div><a href="edit_asgn_format.php">Again</a> | 
			<a href="index.php">Menu</a></div><?php
		} else {	// format selected, display editable format information
			$formats = get_asgn_formats($conn);
			$format = $formats[$format_id];
			?>
			<form action="edit_asgn_format.php" method="post">
				<input type="hidden" name="format_id" value="<?php echo($format_id);?>" />
				<input type="hidden" name="edit" value="1" />

				<div><label for="delete">Delete?</label><input type="checkbox" id="delete" name="delete" /></div>
				<p style="color:red;"><em>WARNING!</em> Deletion will error out if any assignments are still associated with this format.</p>

				<div><label for="name">Friendly Name:</label><input type="text" id="name" name="name" placeholder="(e.g. Snap! Link)" value="<?php echo($format['name']); ?>" /></div>
				<div><label for="description">Description (HTML):</label><textarea id="description" name="description" placeholder="(e.g. Submit a link to your Snap file, see instructions)"><?php echo($format['description']); ?></textarea></div>
				<div><label for="is_file">Does this require a file upload?:</label><input type="checkbox" id="is_file" name="is_file" value="1"<?php echo($format['is_file'] ? "checked" : ""); ?> /></div>
				<div><label for="regex">Regex Validation String:</label><input type="text" id="regex" name="regex" placeholder="/\/snap.html#present:Username=(\w+)&ProjectName=(\w+)/g" value="<?php echo($format['regex']); ?>" /></div>
				<div><label for="validation_help">Validation Error Message (HTML):</label><textarea id="validation_help" name="validation_help" placeholder="You must submit URLs in the format: snap.html/#present:Username=...."><?php echo($format['validation_help']); ?></textarea></div>
				
				<input type="submit" />
			</form>
		<?php }
	} else {	// GET: display edit options
		$formats = get_asgn_formats($conn);
		?><form action="edit_asgn_format.php" method="POST">
			<p>Select a format to edit.</p>
			<div><select name="format_id">
				<?php
				foreach ($formats as $format) {
					?><option value="<?php echo($format["format_id"]);?>">
						<?php echo($format["format_id"]);?>) <?php echo($format["name"]);?> [<?php echo($format["is_file"] ? "File Upload" : "Text Box"); ?>]
					</option><?php
				} ?>
			</select></div>
			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>