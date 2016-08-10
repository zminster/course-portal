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
            successRedirect : '/portal', // redirect to the secure profile section
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

	app.get('/feedback/:asgn_id', middleware.isLoggedIn, function(req, res) {
		// TODO: middleware for legit comment pull
		// TODO: comment display
	});

	// TODO: Any other static pages

	/**************************************
	  ASSIGNMENTS & GRADES PORTAL
	 **************************************/
	app.get('/portal', middleware.isLoggedIn, function(req, res) {
		// prepare massive datagram for delivery to portal renderer
		var d = {};
		// q1: get all assignment cats
		var q1 = function(cb) {
			conn.query("SELECT * FROM assignment_type", cb);
		};
		// q2: compute cat averages of graded/viewable assignments
		var q2 = function(cb) {
			conn.query("SELECT type_id, SUM(IFNULL(score,0)) / SUM(pt_value) as avg FROM assignment JOIN assignment_type ON type = type_id JOIN grades ON assignment.asgn_id = grades.asgn_id WHERE uid = ? AND graded = 1 AND can_view_feedback = 1 GROUP BY type_id", [req.user.uid], cb);
		}
		// q3: get all assignment data in one fell swoop
		var q3 = function(cb) {
					conn.query("SELECT type, assignment.asgn_id, name, description, url, pt_value,\
				date_out, date_due, can_handin, info_changed, nreq, handed_in, handin_time,\
				late, graded, can_view_feedback, score\
			FROM assignment JOIN assignment_meta ON assignment.asgn_id = assignment_meta.asgn_id\
				JOIN grades ON assignment.asgn_id = grades.asgn_id\
			WHERE uid = ? AND class_pd = ? AND displayed = 1 ORDER BY type, date_out, date_due",
			[req.user.uid, req.user.class_pd], cb);
		};

		// dispatch
		q1(function(err, cats) {
			if (!err) {
				var types = {}; // create empty asgn type list
				for (var i = 0; i < cats.length; i++) {	// dump all found categories into datagram
					var cat = {};
					cat.name = cats[i].name;
					cat.weight = cats[i].weight * 100;
					cat.assignments = [];	// empty assignment list, will add later
					types[cats[i].type_id] = cat;
				}

				q2(function(err, averages){
					if (!err) {
						for (var i = 0; i < averages.length; i++) {
							var smooth_avg = Math.round(averages[i].avg * 1000) / 10;	// avg to one decimal place
							types[averages[i].type_id].avg = averages[i].avg;
						}
					}

					q3(function(err, asgn_arr) {
						if (!err) {
							for (var i = 0; i < asgn_arr.length; i++) {
								var asgn_l = types[asgn_arr[i].type].assignments;	// list of assignments of this type
								asgn_l.push(construct_assignment(asgn_arr[i]));		// construct assignment object
							}

							/* THIS is a disgusting hack
								 basically asgn type IDs may be non-sequential
								 and mustache needs a list, not an object
								 so we need to first treat types like an object 
								 and then back-convert from an object to a list

								 we can do this because ultimately type IDs don't
								 matter on the frontend
								 WEW LAD. */
							d.categories = [];
							Object.keys(types).forEach(function (type) {
								d.categories.push(types[type]);
							});

							console.log(JSON.stringify(d, null, 4));
							res.render('portal.html', d);
						} else {
							res.render("handin_error.html", {error: "There's a problem accessing portal information from the database right now."});
						}
					});
				});
			} else {
				res.render("handin_error.html", {error: "There's a problem accessing portal information from the database right now."});
			}	
		});
	});

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

function construct_assignment(row) {
	var asgn 		= {};

	var date_out 	= moment(row['date_out']);
	var date_due 	= moment(row['date_due']);
	var days_before = date_due.subtract(2, 'days');
	var now 		= moment();

	// basic meta
	asgn['id'] = row['asgn_id'];
	asgn['name'] = row['name'];
	asgn['description'] = row['description'];
	asgn['url'] = row['url'];

	// special flags that will add ribbons to assignment display
	if (row['info_changed'])
		asgn['changed'] = 1;
	if (row['nreq'])
		asgn['nreq'] = 1;

	asgn['date_out'] = date_out.format('llll');
	asgn['date_due'] = date_due.format('llll');
	if (!row['handed_in'])
		asgn['time_left'] = date_due.fromNow();

	// due warnings - classes change background colors of assignment display
	if (now.isBetween(days_before, date_due) && !row['handed_in'])	// assignment due in 24 hrs and NTI
		asgn['due_warn'] = 'today';
	else if (now.isAfter(date_due) && !row['handed_in']) // assignment overdue (NTI, due date passed)
		asgn['due_warn'] = 'overdue';

	// handin status (symbol class) & time if applicable
	if (!row['can_handin'] || row['nreq'])
		asgn['handin_status'] = 'locked';
	else if (row['handed_in']) {
		asgn['handin_status'] = 'handed_in';
		var handin_time = moment(row['handin_time']).format('llll');
		asgn['handin_time'] = handin_time;
	}

	// late (symbol class for late column)
	if (row['late'] && row['handed_in'])
		asgn['late'] = 'late';
	else if (!row['late'] && row['handed_in'])
		asgn['late'] = 'ontime';

	// grade status
	if (row['nreq'] || !row['handed_in'])
		asgn['grade_status'] = 'na';		// question mark
	else if (!row['graded'])
		asgn['grade_status'] = 'pending';	// hourglass
	else 
		asgn['score'] = row['score'];		// show score instead of symbol

	asgn['pt_value'] = row['pt_value'];

	if (row['can_view_feedback'])
		asgn['feedback'] = '/feedback/' + row['asgn_id'];

	return asgn;
}