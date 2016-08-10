//app/middleware.js

var conn 				= require('./database_ops').connection;
var err_can_handin 		= "Students in your class period aren't allowed to hand in this assignment right now.";
var err_invalid_handin	= "That's not a valid assignment.";
var db_error 			= "There was a fatal database error.";

module.exports = {
	// middleware: ensures user is logged in
	isLoggedIn: function(req, res, next) {
		// if user is authenticated in the session
		if (req.isAuthenticated())
			return next();

		// if they aren't redirect them to login screen
		res.redirect('/login');
	},

	// middleware: ensures password change if change_flag is set
	isPasswordFresh: function(req, res, next) {
				// ensure password does not need to be changed
		conn.query("SELECT change_flag FROM user WHERE uid = ?", [req.user.uid],
			function(err, reset) {
				if (!err) {
					if (reset[0].change_flag == 1) {
						req.flash('resetMessage', 'Welcome! You must choose a new password now.')
						res.redirect('/password');
					}
					else
						return next();
				} else {
					res.render("handin_error.html", {error: db_error});
				}
		});
	},

	// middleware: ensures handin is legit before processing
	// TODO: block NREQ from handing in
	isLegitHandin: function(req, res, next) {
		var asgn_id = req.params.asgn_id;
		if (asgn_id) {	// assignment ID specified
			// query for valid assignment ID
			conn.query("SELECT * FROM assignment WHERE asgn_id = ?",[asgn_id], function(err, rows){
				if (!err && rows.length > 0) {
					req.asgn_name = rows[0].name;
					conn.query("SELECT can_handin, date_due FROM assignment_meta WHERE asgn_id = ? AND class_pd = ?",[asgn_id, req.user.class_pd],
						function(err, rows){
							console.log(rows);
							if (!err && rows.length > 0 && rows[0].can_handin == 1) {
								req.date_due = rows[0].date_due;
								return next();
							} else {
								res.render("handin_error.html", {error: err_can_handin});
								return;
							}
						});
				} else {
					res.render("handin_error.html", {error: err_invalid_handin});
					return;
				}
			});
		} else {
			res.render("handin_error.html", {error: err_invalid_handin});
			return;
		}
	}
}