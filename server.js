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

// consts
//var PORT 		= 8080;
var DOMAIN		= "cs.stab.org";
var EMAIL		= "zminster@stab.org";

'use strict';

// init
var app 		= express();
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

app.use(morgan('dev'));

app.get("/", function(req, res) {
	res.send("Hello!");
	res.end();
});

lex.onRequest = app;

lex.listen([80], [443, 5001], function () {
	var protocol = ('requestCert' in this) ? 'https': 'http';
	console.log("Listening at " + protocol + '://localhost:' + this.address().port);
});