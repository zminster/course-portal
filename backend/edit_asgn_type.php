<html>
<head>
	<title>Portal Admin :: Edit/Delete Assignment Category</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>Edit/Delete Assignment Category</h1>
	<?php
	include 'config/database.php';
	$method = $_SERVER['REQUEST_METHOD'];

	if ($method == 'POST') {	// POST
		$type_id = $_POST['type_id'];

		if (array_key_exists("delete", $_POST)) {	// delete type
			$del_1 = $conn->prepare("DELETE FROM assignment_type WHERE type_id = ?");
			$del_1->bind_param("i", $type_id);
			// foreign key constraint violation is likely; error message will print from DB
			$del_1->execute();

			if (strpos($del_1->error, "foreign key") !== false) {
				?><p style="font-weight:bold; color: red;">Could not delete Category (Type) # <?php echo($type_id); ?>: assignments still refer to this category (<a href="edit_asgn.php">modify/delete them first</a>).</p><?php
			} else if ($del_1->error) {
				?><p style="font-weight:bold; color: red;"><echo($del_1->error);</p><?php
			} else {
				?><p style="font-weight:bold">Category #<?php echo($type_id); ?> [<?php echo($_POST["name"]);?>] Deleted</p><?php
			}
			?>
			<p><a href="index.php">Menu</a></p><?php
		} else if(array_key_exists("edit", $_POST)) {	// edit type
			// run update query
			$type_update = $conn->prepare("UPDATE assignment_type SET name=?, weight=? WHERE type_id=?");
			$type_update->bind_param("sdi", $_POST['name'], $_POST['weight'], $type_id);
			$type_update->execute();
			echo($type_update->error);

			?><p style="font-weight:bold">Category #<?php echo($type_id);?> Updated: <?php echo($_POST["name"]); ?></p>
			<div><a href="edit_asgn_type.php">Again</a> | 
			<a href="index.php">Menu</a></div><?php
		} else {	// type selected, display editable type information
			$types = get_asgn_types($conn);
			$type = $types[$type_id];
			?>
			<form action="edit_asgn_type.php" method="post">
				<input type="hidden" name="type_id" value="<?php echo($type_id);?>" />
				<input type="hidden" name="edit" value="1" />

				<div><label for="delete">Delete?</label><input type="checkbox" id="delete" name="delete" /></div>
				<p style="color:red;"><em>WARNING!</em> Deletion will error out if any assignments are still associated with this category.</p>

				<div><label for="name">Name:</label><input type="text" id="name" name="name" placeholder="e.g. Lab" value="<?php echo($type['name']); ?>" /></div>
				<div><label for="weight">Weight:</label><input type="text" id="weight" name="weight" placeholder="e.g. 0.3" value="<?php echo($type['weight']); ?>" /></div>
				
				<input type="submit" />
			</form>
		<?php }
	} else {	// GET: display edit options
		$types = get_asgn_types($conn);
		?><form action="edit_asgn_type.php" method="POST">
			<p>Select a category to edit.</p>
			<div><select name="type_id">
				<?php
				foreach ($types as $type) {
					?><option value="<?php echo($type["type_id"]);?>">
						<?php echo($type["type_id"]);?>) <?php echo($type["name"]);?> (<?php echo($type["weight"]); ?>)
					</option><?php
				} ?>
			</select></div>
			<div><input type="submit" /></div>
		</form>

	<?php } ?>
</body>
</html>