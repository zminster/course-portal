<html>
<head>
	<title>Portal Admin</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>STAB CS :: Portal Admin (<?php echo(gethostname()); ?>)</h1>

	<h2>Setup</h2>
	<ul>
		<li><a href="add_class.php">Add Class Period</a></li>
		<li><a href="add_user_role.php">Add User Role</a></li>
		<li><a href="edit_user_role.php">Edit/Delete User Role</a></li>
		<li><a href="add_asgn_type.php">Add Assignment Category</a></li>
		<li><a href="edit_asgn_type.php">Edit/Delete Assignment Category</a></li>
		<li><a href="add_asgn_format.php">Add Assignment Format</a></li>
		<li><a href="edit_asgn_format.php">Edit/Delete Assignment Format</a></li>
		<li><a href="edit_settings.php">Edit System Settings</a></li>
	</ul>

	<h2>User Management</h2>
	<ul>
		<li><a href="report_users.php">Show All Users</a></li>
		<li><a href="add_user.php">Add User (Batch)</a></li>
		<li><a href="add_user_single.php">Add User (Single)</a></li>
		<li><a href="edit_user.php">Edit/Delete User</a></li>
	</ul>


	<h2>Grade/Assignment Management</h2>
	<ul>
		<li><a href="add_asgn.php">Add Assignment</a></li>
		<li><a href="edit_asgn.php">Edit/Delete Assigment</a></li>
		<li><a href="edit_extension.php">Grant Extension</a></li>
		<li><a href="chomp_pre.php">Pre-Chomp Assignment (Copy blank rubrics, shutdown handins)</a></li>
		<li><a href="chomp_post.php">Post-Chomp Assignment (Copy grades from rubrics, allow feedback view)</a></li>
		<li><a href="chomp_force.php">Force-Chomp Assignment (Copy blank rubrics for NTI assignments, optionally shutdown handins)</a></li>
		<li><a href="edit_toggle.php">Toggle Assignment LATE/NREQ/CAN_VIEW_FEEDBACK (Per-Student)</a></li>
		<li><a href="edit_nullify.php">Nullify Assignment (Mark Zero) (Per-Student)</a></li>
		<li><a href="edit_amnesty.php">Toggle Amnesty Period</a></li>
	</ul>

	<h2>Lesson Management</h2>
	<ul>
		<li><a href="add_lesson.php">Add Lesson</a></li>
		<li><a href="edit_lesson.php">Edit/Delete Lesson</a></li>
	</ul>

	<h2>Reports</h2>
	<ul>
		<li><a href="report_asgn.php">Individual Assignment Report</a></li>
		<li><a href="report_nti.php">Missing &amp; Late Assignments (Global)</a></li>
		<li><a href="report_student.php">Student Grade Report (Individual)</a></li>
		<li><a href="report_grades.php">Student Grade Report (Class)</a></li>
	</ul>

</body>
</html>