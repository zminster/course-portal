<html>
<head>
	<title>Portal Admin</title>
	<link rel="stylesheet" href="static/style.css" type="text/css" />
</head>
<body>
	<h1>STAB CS Principles :: Portal Admin</h1>

	<h2>Setup</h2>
	<ul>
		<li><a href="add_class.php">Add Class Period</a></li>
		<li><a href="add_user.php">Add Student (Batch)</a></li>
		<li><a href="add_user_single.php">Add Student (Single)</a></li>
		<li><a href="add_asgn_type.php">Add Assignment Type</a></li>
	</ul>

	<h2>Manage</h2>
	<ul>
		<li><a href="add_asgn.php">Add Assignment</a></li>
		<li><a href="edit_user.php">Edit/Delete Student</a></li>
		<li><a href="edit_asgn.php">Edit/Delete Assigment</a></li>
		<li><a href="edit_grades.php">Chomp Assignment Grades</a></li>
		<?php // TODO: chomp will set graded=1, allow can_view=1, compute/update score ?>
		<li><a href="edit_toggle.php">Toggle Assignment NREQ/CANVIEW/LATE (Per-Student)</a></li>
		<li><a href="edit_amnesty.php">Toggle Amnesty Period</a></li>
	</ul>

	<h2>Reports</h2>
	<ul>
		<li><a href="report_asgn.php">Individual Assignment Report</a></li>
		<li><a href="report_latemiss.php">Missing &amp; Late Assignments (Global)</a></li>
		<li><a href="report_student.php">Student Grade Report (Individual)</a></li>
		<li><a href="report_grades.php">Student Grade Report (Class)</a></li>
	</ul>

</body>
</html>