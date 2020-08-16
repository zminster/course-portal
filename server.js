// Course Portal Frontend: Server
// Author: zminster

// reqs
require('dotenv').config({path: __dirname + '/.env'});
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
require('./config/passport')(passport);

app.engine('html', engines.hogan);
app.set('views', __dirname +'/templates');
if (!process.env.PRODUCTION == 1) app.use(morgan('dev'));
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

app.listen(process.env.PORT, 'localhost', function() { console.log("Course Potal started: ", this.address()); });
