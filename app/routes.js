// app/routes.js

var upload 		= require('./upload.js');		/* upload handling for handins */
var middleware	= require('./middleware.js');	/* login & handin verification */

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
		res.render('handin.html', {
			asgn_id : req.params.asgn_id
		});
	});

	app.post('/handin/:asgn_id', middleware.isLoggedIn, middleware.isLegitHandin, upload, function(req, res) {
		if (!req.files) {
			res.render('handin_error.html', {error: 'Files not received!'});
			return;
		} else {
			res.send("ALL GOOD!\n" + req.files);
			console.log (req.files);
			return;
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