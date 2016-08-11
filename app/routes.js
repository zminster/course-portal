// app/routes.js

var upload 		= require('./upload.js');		/* upload handling for handins */
var middleware	= require('./middleware.js');	/* login & handin verification */
var moment		= require('moment');			/* timing handins */
var conn		= require('./database_ops.js').connection;
var bcrypt 		= require('bcrypt-nodejs');		/* changing passwords */
var fs 			= require('fs');

var handinDir 	= '/course/csp/handin/'; 		/* If changing, also change in upload.js */


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

    // show password change form
    app.get('/password', middleware.isLoggedIn, function(req, res) {
    	res.render("password.html", {
    		user: req.user,
    		message: req.flash('resetMessage')
    	});
    });

    // process password change form
    app.post('/password', middleware.isLoggedIn, function(req, res) {
    	// error checks
    	if (req.body.new_password != req.body.new_password_repeat) {
    		req.flash('resetMessage', 'Passwords did not match.');
    		res.redirect('/password'); return;
    	} else if (!checkPassword(req.body.new_password)) {
    		req.flash('resetMessage', 'Passwords must have one capital letter, one lowercase letter, and one number.');
    		res.redirect('/password'); return;
    	} else {	// verify current password first
            conn.query("SELECT password FROM user WHERE username = ?",[req.user.username], function(err, rows){
            	if (!bcrypt.compareSync(req.body.current_password, rows[0].password)){
            		req.flash('resetMessage', 'Incorrect current password.');
            		res.redirect('/password'); return;
            	} else {
					conn.query("UPDATE user SET password=?, change_flag=0 WHERE uid=?", [bcrypt.hashSync(req.body.new_password), req.user.uid],
						function(err, e) {
							req.logout();
							req.flash('loginMessage', 'Password changed! Log in again with your new password.');
							res.redirect('/login');
						});
				}
    		});
        }
    });

	/**************************************
	  PRIVILEGED STATIC PAGES
	 **************************************/
	app.get('/resources', middleware.isLoggedIn, middleware.isPasswordFresh, function(req, res) {
		//TODO: Resources page
		res.render('demo.html', {
			user : req.user.name // get the user out of session and pass to template
		});
	});

	app.get('/lessons', middleware.isLoggedIn, middleware.isPasswordFresh, function(req, res) {
		// TODO: Lessons page
		res.render('demo.html', {
			user : req.user.name // get the user out of session and pass to template
		});
	});

	app.get('/feedback/:asgn_id', middleware.isLoggedIn, middleware.isPasswordFresh, middleware.isLegitFeedback, function(req, res) {
		// find feedback in filesystem
		var file_path = handinDir + req.params.asgn_id + '/' + req.user.username + '/grade_comments.txt';
		fs.stat(file_path, function(err, stat) {
			if (!err) {
				fs.readFile(file_path, function(err, data) {
					if(!err) {
						res.render('feedback.html', {user: req.user, data: data});
					} else {
						res.render('feedback.html', {user: req.user, error: 'Feedback should be available, but I\'m having trouble opening the file on my end.\
					You should email Mr. Minster about this.'});
					}
				});
			} else {
				res.render('feedback.html', {user: req.user, error: 'Feedback should be available, but I\'m having trouble finding it on my end.\
					You should email Mr. Minster about this.'});
			}
		});
	});

	/**************************************
	  ASSIGNMENTS & GRADES PORTAL
	 **************************************/
	app.get('/portal', middleware.isLoggedIn, middleware.isPasswordFresh, function(req, res) {
		// prepare massive datagram for delivery to portal renderer
		var d = {};
		d['user'] = req.user;
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
							types[averages[i].type_id].avg = smooth_avg;
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
	app.get('/handin/:asgn_id', middleware.isLoggedIn, middleware.isPasswordFresh, middleware.isLegitHandin, function(req, res) {
		var late = moment().isAfter(moment(req.date_due)) ? 1 : 0;
		res.render('handin.html', {
			message: req.flash('handinMessage'),
			asgn_id: req.params.asgn_id,
			asgn_name: req.asgn_name,
			late: late,
			user: req.user
		});
	});

	app.post('/handin/:asgn_id', middleware.isLoggedIn, middleware.isPasswordFresh, middleware.isLegitHandin, function(req, res) {
		upload(req, res, function(err) {
			if (err) {
				console.log("ERR: " + err)
				if (!req.body.collab)
					req.flash('handinMessage', 'You must agree to the Collaboration Statement.');
				else
					req.flash('handinMessage', 'Files provided exceed upload limits. Please reduce the size or turn in an archive.');
				res.redirect('/handin/' + req.params.asgn_id);
			} else if (!req.files || req.files.length == 0) {
				req.flash('handinMessage', 'I didn\'t receive your files - something went wrong. Try to hand in again.');
				res.redirect('/handin/' + req.params.asgn_id);
			} else {
				// capture due date
				var now = moment();
				var due = moment(req.date_due);
				var time = moment().format('YYYY-MM-DD HH:mm:ss');
				var late = now.isAfter(due) ? 1 : 0;
				var late_days = Math.ceil(now.diff(due, 'days', true));
				var friendly_time = now.format('llll');
				// record handin
				conn.query("UPDATE grades SET handed_in=1, handin_time=?, late=? WHERE uid=? AND asgn_id=?",[time, late, req.user.uid, req.params.asgn_id],
					function(err, rows) {
						if (!err) {
							res.render("handin_success.html", {
								asgn_id: req.params.asgn_id,
								asgn_name: req.asgn_name,
								time: friendly_time,
								late: late_days,
								files: req.files,
								user: req.user
							});
						} else {
							req.flash('handinMessage', 'There was an issue recording your handin time in the database. Try to hand in again.');
							res.redirect('/handin/' + req.params.asgn_id);
						}
					}
				);
			}
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

function construct_assignment(row) {
	var asgn 		= {};

	var date_out 	= moment(row['date_out']);
	var date_due 	= moment(row['date_due']);
	var days_before = date_due.clone().subtract(2, 'days');
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
		asgn['handin_status'] = 'fa fa-lock';
	else if (row['handed_in']) {
		asgn['handin_status'] = 'fa fa-check-circle';
		var handin_time = moment(row['handin_time']).format('llll');
		asgn['handin_time'] = handin_time;
	}

	// late (symbol class for late column)
	if (!row['nreq'] && row['late'] && row['handed_in'])
		asgn['late'] = 'fa fa-times-circle';
	else if (!row['nreq'] && !row['late'] && row['handed_in'])
		asgn['late'] = 'fa fa-check-circle';

	// grade status
	if (row['nreq'])
		asgn['grade_status'] = 'fa fa-ban';		// ban symbol
	else if (!row['handed_in'])
		asgn['grade_status'] = 'fa fa-question-circle';		// question mark
	else if (!row['graded'])
		asgn['grade_status'] = 'fa fa-cog fa-spin';	// spinning cog
	else
		asgn['score'] = row['score'];		// show score instead of symbol

	asgn['pt_value'] = row['pt_value'];

	if (row['graded'] && row['can_view_feedback'])
		asgn['feedback'] = '/feedback/' + row['asgn_id'];

	return asgn;
}

function checkPassword(str)
{
	// at least one number, one lowercase and one uppercase letter
	// at least six characters that are letters, numbers or the underscore
	var re = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])\w{6,}$/;
	return re.test(str);
}