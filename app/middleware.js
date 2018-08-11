//app/middleware.js

var conn 				= require('./database_ops').connection;
var moment				= require('moment');			/* timing handins */

var err_can_handin 		= "Students in your class period aren't allowed to hand in this assignment right now.";
var err_invalid_handin	= "That's not a valid assignment.";
var db_error 			= "There was a fatal database error.";
var err_chomped			= "This assignment is currently being graded; you cannot turn it in again.";
var err_nreq			= "You are not required to complete this assignment; you cannot turn it in.";

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
					res.render("error.html", {error: db_error, user:req.user});
				}
		});
	},

	// middleware: ensures handin is legit before processing
	isLegitHandin: function(req, res, next) {
		var asgn_id = req.params.asgn_id;
		if (asgn_id) {	// assignment ID specified
			// query for valid assignment ID
			conn.query("SELECT * FROM assignment WHERE asgn_id = ?",[asgn_id], function(err, rows){
				if (!err && rows.length > 0) {
					req.asgn = rows[0];
					// get format information
					conn.query("SELECT format_id, f.name, f.description, is_file, regex, validation_help\
						FROM assignment a JOIN assignment_format f ON a.format = f.format_id WHERE a.asgn_id = ?",[asgn_id],
						function(err, rows){
						if (!err, rows.length > 0){
							req.asgn.format = rows[0];	// store format information in accessible object
							conn.query("SELECT can_handin, date_due FROM assignment_meta WHERE asgn_id = ? AND class_pd = ?",[asgn_id, req.user.class_pd],
								function(err, rows){
									if (!err && rows.length > 0 && rows[0].can_handin == 1) {
										req.date_due = rows[0].date_due;
										conn.query("SELECT nreq, chomped, extension FROM grades WHERE asgn_id = ? AND uid = ?",[asgn_id, req.user.uid], function(err, rows) {
											if (!err && !rows[0].chomped && !rows[0].nreq) {
												// associate extension
												if (rows[0].extension)
													req.extension = rows[0].extension;
												// calculate timeliness
												var now = moment();
												var due = moment(req.date_due);
												if (req.extension)
													due.add(req.extension, 'h');
												var late_days = Math.ceil(now.diff(due, 'days', true));
												req.late_days = (late_days < 0 ? 0 : late_days);
												return next();
											}
											else if (rows[0].nreq)
												res.render('error.html', {error: err_nreq, user: req.user});
											else
												res.render('error.html', {error: err_chomped, user: req.user});
										})
									} else {
										res.render('error.html', {error: err_can_handin, user: req.user});
									}
							});
						} else {
							res.render('error.html', {error: err_invalid_handin, user: req.user});
						}
					});
				} else {
					res.render('error.html', {error: err_invalid_handin, user: req.user});
				}
			});
		} else {
			res.render('error.html', {error: err_invalid_handin, user: req.user});
		}
	},

	// middleware: ensures feedback is visible before exposing
	isLegitFeedback: function(req, res, next) {
		var asgn_id = req.params.asgn_id;
		if (asgn_id) {	// assignment ID specified
			// query for valid assignment ID & visibility
			conn.query("SELECT score, can_view_feedback, chomped FROM grades WHERE asgn_id = ? AND uid = ?",[asgn_id, req.user.uid], function(err, rows) {
				if (!err && rows.length > 0 && rows[0].chomped == 1 && rows[0].can_view_feedback == 1 && rows[0].score) {
					return next();
				} else {
					res.render('feedback.html', {user: req.user, error: 'Feedback is not available for you on that assignment.'});
					return;
				}
			});
		}
	}
}