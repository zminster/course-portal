// Course Portal Frontend: Server
// Author: zminster

// reqs
require('dotenv').config()
var express 	= require('express');
var session 	= require('express-session');
var cookieParser= require('cookie-parser');
var bodyParser 	= require('body-parser');
var morgan		= require('morgan');
var passport	= require('passport');
var flash		= require('connect-flash');
var engines		= require('consolidate');

// consts
var app 		= express();

// configuration
process.umask(0);
require('./config/passport')(passport);

app.engine('html', engines.hogan);
app.set('views', __dirname +'/templates');
if (!process.env.PRODUCTION) app.use(morgan('dev'));
app.use(cookieParser());
app.use(bodyParser.urlencoded({
	extended: false
}));
app.use(bodyParser.json());
app.use(session({
	secret: process.env.SESSION_SECRET,
	resave: true,
	saveUninitialized: true
}));
app.use(passport.initialize());
app.use(passport.session());
app.use(flash());
app.use(express.static(__dirname + '/public'));

// routing
require('./app/routes.js')(app, passport);

app.listen(process.env.PORT, function() { console.log("Course Potal started: ", this.address()); });
