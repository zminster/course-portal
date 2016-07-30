// Course Portal Frontend: Server
// Author: zminster

// reqs
var express 	= require('express');
var session 	= require('express-session');
var cookieParser= require('cookie-parser');
var bodyParser 	= require('body-parser');
var morgan		= require('morgan');
var passport	= require('passport');
var flash		= require('connect-flash');
var lex			= require('letsencrypt-express').testing();
var engines		= require('consolidate');

// consts
var DEV_PORT 	= 8080;
var DOMAIN		= 'cs.stab.org';
var EMAIL		= 'zminster@stab.org';

var app 		= express();
if (process.env.NODE_ENV == 'production') {
	'use strict';
	var lex = lex.create({
		configDir: require('os').homedir() + '/letsencrypt/etc',
		approveRegistration: function (hostname, approve) {
			if (hostname === DOMAIN) {
				approve(null, {
					domains: [DOMAIN],
					email: EMAIL,
					agreeTos: true
				});
			}
		}
	});
}

// configuration
require('./config/passport')(passport);	// passport gets configured

//app.engine('html', engines.hogan);
//app.set('views', __dirname +'/templates');
app.set('view engine', 'ejs');
app.use(morgan('dev'));
app.use(cookieParser());
app.use(bodyParser.urlencoded({
	extended: true
}));
app.use(bodyParser.json());
app.use(session({
	secret: '3GcdA580QSFonX4MZ9z6rvY0G1WRCFB1',
	resave: true,
	saveUninitialized: true
}));
app.use(passport.initialize());
app.use(passport.session());
app.use(flash());
app.use(express.static(__dirname + '/public'));

// routing
require('./app/routes.js')(app, passport);

// production: 80/443/5001 (SSL enabled)
if (process.env.NODE_ENV == 'production') {
	lex.onRequest = app;

	lex.listen([80], [443, 5001], function () {
		var protocol = ('requestCert' in this) ? 'https': 'http';
		console.log("Listening at " + protocol + '://localhost:' + this.address().port);
	});
}
// dev: 8080 (SSL disabled)
else {
	app.listen(DEV_PORT, function() {
		console.log("Listening at http://localhost:8080");
	})
}