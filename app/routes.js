// app/routes.js
module.exports = function(app, passport, upload) {

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
	app.get('/resources', isLoggedIn, function(req, res) {


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
	app.get('/handin/:asgn_id', isLoggedIn, function(req, res) {
		res.render('handin.html', {
			asgn_id : req.params.asgn_id
		});
	});

	app.post('/handin/:asgn_id', isLoggedIn, isLegitHandin, upload.single('file'), function(req, res) {
		var asgn_id = req.params.asgn_id;

		console.log("HERE");

		if (!req.file) {
			res.send('No files were uploaded.');
			return;
		} else {
			res.send("Files were uploaded!");
			console.log (req.file);
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

// middleware: ensures user is logged in
function isLoggedIn(req, res, next) {

	// if user is authenticated in the session, carry on
	if (req.isAuthenticated())
		return next();

	// if they aren't redirect them to login screen
	res.redirect('/login');
}

// TODO: middleware: ensures handin is legit before processing
function isLegitHandin(req, res, next) {
	console.log("FUTURE LEGIT CHECK");
	return next();
}