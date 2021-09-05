// Course Portal Frontend: Server
// Author: zminster

// reqs
require('dotenv').config({path: __dirname + '/.env'});
var express 	= require('express');
var db 			= require("./app/database_ops.js");
var bodyParser 	= require('body-parser');
var passport	= require('passport');
var morgan		= require('morgan');
var flash		= require('connect-flash');
var engines		= require('consolidate');

// consts
var app 		= express();

// configuration
require('./config/passport')(passport);

app.engine('html', engines.hogan);
app.set('views', __dirname +'/templates');
if (!process.env.PRODUCTION == 1) app.use(morgan('dev'));
app.use(bodyParser.urlencoded({
	extended: false
}));
app.use(bodyParser.json());
app.use(db.session({
	key: process.env.COURSE_CODE,
	secret: process.env.SESSION_SECRET,
	store: db.sessionStore,
	resave: false,
	saveUninitialized: false,
	cookie: {
		maxAge: 604800000,
		secure: process.env.PRODUCTION != 0,
		domain: process.env.DOMAIN,
		sameSite: 'lax'
	}
}));
app.use(passport.initialize());
app.use(passport.session());
app.use(flash());
app.use(express.static(__dirname + '/public'));

// routing
require('./app/routes.js')(app, passport);

app.listen(process.env.PORT, 'localhost', function() { console.log("Course Potal started: ", this.address()); });
