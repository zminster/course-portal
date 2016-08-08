// app/routes.js

var upload 		= require('./upload.js');		/* upload handling for handins */
var middleware	= require('./middleware.js');	/* login & handin verification */
var moment		= require('moment');			/* timing handins */
var conn		= require('./database_ops.js').connection;

/* Routes */
module.exports = function(app, passport) {

	/**************************************
	  HOME PAGE (not logged in)
	 **************************************/
	app.get('/', function(req, res) {
		// TODO: Home Page Template
		res.end("Not authenticated");
	});

	/**************************************
	  LOGIN
	 **************************************/
	// show the login form
	app.get('/login', function(req, res) {
		res.render('login.html', { message: req.flash('loginMessage') });
	});

	// process the login form
	app.post('/login', passport.authenticate('local', {
            successRedirect : '/resources', // redirect to the secure profile section
            failureRedirect : '/login', // redirect back to the signup page if there is an error
            failureFlash : true // allow flash messages
		}),
        function(req, res) {
            if (req.body.remember) {
              req.session.cookie.maxAge = 1000 * 60 * 3;
            } else {
              req.session.cookie.expires = false;
            }
        res.redirect('/');
    });

	/**************************************
	  PRIVILEGED STATIC PAGES
	 **************************************/
	 // isLoggedIn middleware ensures page will not be rendered unless user is logged in
	app.get('/resources', middleware.isLoggedIn, function(req, res) {


		res.render('demo.html', {
			user : req.user.name // get the user out of session and pass to template
		});
	});

	// TODO: Any other static pages

	/**************************************
	  ASSIGNMENTS & GRADES PORTAL
	 **************************************/
	 // TODO: Ask DB for information, render Mustache template (also TODO)

	/**************************************
	  HANDIN FLOW
	 **************************************/
	app.get('/handin/:asgn_id', middleware.isLoggedIn, middleware.isLegitHandin, function(req, res) {
		var late = moment().isAfter(moment(req.date_due)) ? 1 : 0;
		res.render('handin.html', {
			asgn_id: req.params.asgn_id,
			asgn_name: req.asgn_name,
			late: late
		});
	});

	app.post('/handin/:asgn_id', middleware.isLoggedIn, middleware.isLegitHandin, upload, function(req, res) {
		if (!req.files) {
			res.render('handin_error.html', {error: 'I didn\'t receive your files - something went wrong. Try to hand in again.'});
		} else {
			// capture due date
			var now = moment();
			var time = moment().format('YYYY-MM-DD HH:mm:ss');
			var late = now.isAfter(moment(req.date_due)) ? 1 : 0;
			console.log("Handin time: " + time);
			console.log("Late?: " + late);
			console.log("Files: " + req.files);
			// record handin
			conn.query("UPDATE grades SET handed_in=1, handin_time=?, late=? WHERE uid=? AND asgn_id=?",[time, late, req.user.uid, req.params.asgn_id],
				function(err, rows) {
					if (!err) {
						res.render("handin_success.html", {
							asgn_id: req.params.asgn_id,
							asgn_name: req.asgn_name,
							time: now,
							late: late,
							files: req.files
						});
					} else {
						res.render("handin_error.html", {error: "There was an issue recording your handin time in the database."});
					}
				});
		}
	});

	/**************************************
	  LOGOUT
	 **************************************/
	app.get('/logout', function(req, res) {
		req.logout();
		res.redirect('/');
	});
};