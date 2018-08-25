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
var lex			= require('greenlock-express');
var engines		= require('consolidate');
var settings	= require('./config/settings');

// consts
var DEV_PORT 	= 8080;
var DOMAIN		= 'cs.stab.org';
var EMAIL		= 'zminster@stab.org';

var app 		= express();

if (settings.production) {
	'use strict';
	var lex = lex.create({
		version: 'draft-11',
		server: 'https://acme-v02.api.letsencrypt.org/directory',
		challenges: {'http-01': require('le-challenge-fs').create({ webrootPath: '/tmp/acme-challenges' }) },
		store: require('le-store-certbot').create({ webrootPath: '/tmp/acme-challenges' }),
		approveDomains: [ 'cs.stab.org', 'localhost' ],
		email: 'zminster@stab.org',
		agreeTos: true
	});
}

// configuration
process.umask(0);
require('./config/passport')(passport);	// passport gets configured

app.engine('html', engines.hogan);
app.set('views', __dirname +'/templates');
settings.production ? NULL : app.use(morgan('dev'));
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

if (settings.production) {
	// production: 80/443/5001 (SSL enabled)
	require('http').createServer(lex.middleware(require('redirect-https')())).listen(DEV_PORT, function () {
	  console.log("Listening for ACME http-01 challenges on", this.address());
	});

	require('https').createServer(lex.httpsOptions, lex.middleware(app)).listen(443, function () {
	  console.log("Listening for ACME tls-sni-01 challenges and serve app on", this.address());
	});
} else {
	app.listen(DEV_PORT, function() { console.log("Development server started ", this.address()); });
}
