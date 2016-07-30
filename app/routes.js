// app/routes.js
module.exports = function(app, passport) {

	/**************************************
	  HOME PAGE (not logged in)
	 **************************************/
	app.get('/', function(req, res) {
		// TODO: Home Page
	});

	/**************************************
	  LOGIN FORM
	 **************************************/
	// show the login form
	app.get('/login', function(req, res) {
		// TODO: Login Form
		res.render('login.html', { message: req.flash('loginMessage') });
	});

	// process the login form
	app.post('/login', passport.authenticate('local', {
            successRedirect : '/profile', // redirect to the secure profile section
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
	 PRIVILEGED PAGE EXAMPLE
	 **************************************/
	 // isLoggedIn middleware ensures page will not be rendered unless user is logged in
	app.get('/profile', isLoggedIn, function(req, res) {
		res.render('SUMATOROTHER', {
			user : req.user // get the user out of session and pass to template
		});
	});

	/**************************************
	  LOGOUT
	 **************************************/
	app.get('/logout', function(req, res) {
		req.logout();
		res.redirect('/');
	});
};

// route middleware to make sure
function isLoggedIn(req, res, next) {

	// if user is authenticated in the session, carry on
	if (req.isAuthenticated())
		return next();

	// if they aren't redirect them to the home page
	res.redirect('/');
}