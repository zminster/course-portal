// app/routes.js

var upload 		= require('./upload.js');		/* upload handling for handins */
var middleware	= require('./middleware.js');	/* login & handin verification */
var moment		= require('moment');			/* timing handins */
var conn		= require('./database_ops.js').connection;
var bcrypt 		= require('bcrypt-nodejs');		/* changing passwords */
var fs 			= require('fs');				/* read feedback files */

var handinDir 	= '/course/csp/handin/'; 		/* If changing, also change in upload.js */
var maxSize 	= 1000000;						/* per-handin upload limit (bytes) */
var releaseTime = 465;							/* minutes after midnight to release lessons */

const util = require('util')

/* Routes */
module.exports = function(app, passport) {

	/**************************************
	  HOME PAGE (not logged in)
	 **************************************/
	app.get('/', function(req, res) {
    	res.redirect('/portal');
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
		res.render('resources.html', {
			user : req.user // get the user out of session and pass to template
		});
	});

	app.get('/lessons', middleware.isLoggedIn, middleware.isPasswordFresh, function(req, res) {
		conn.query("SELECT trimester, topic, slide_url, extra_url, release_date FROM lesson JOIN lesson_meta ON lesson.id = lesson_meta.id WHERE class_pd = ? AND visible = 1 ORDER BY trimester, release_date ASC", [req.user.class_pd],
			function(err, lessons) {
				conn.query("SELECT value_int FROM system_settings WHERE name = ?", ["current_trimester"], function(err, setting) {
					var current_trimester = 1;	// default value
					if (setting.length == 1) {	// actual setting found
						current_trimester = setting[0].value_int;
					}

					var trimesters = {};
					var count = 0;
					for (var i = 0; i < lessons.length; i++) {
						var release_date = moment(lessons[i].release_date).add(releaseTime, 'm'); // release lessons at time on release date
						var now = moment();
						if (now.isAfter(release_date)) {	// only expose visible lessons after release date
							var lesson_obj = {};
							lesson_obj.number = count;
							lesson_obj.trimester = lessons[i].trimester;
							lesson_obj.topic = lessons[i].topic;
							lesson_obj.slide_url = lessons[i].slide_url;
							lesson_obj.extra_url = lessons[i].extra_url;
							lesson_obj.released = release_date.format('dddd, MMMM Do YYYY');

							if (trimesters[lessons[i].trimester] === undefined) {
								var trimester_obj = {};
								trimester_obj.name = lessons[i].trimester;
								trimester_obj.lessons = [];
								if (current_trimester == trimester_obj.name)
									trimester_obj.current = 1;
								else
									trimester_obj.current = 0;
								trimesters[lessons[i].trimester] = trimester_obj;
							}
							
							trimesters[lesson_obj.trimester].lessons.push(lesson_obj);
							count++;
						}
					}
					res.render('lessons.html', {
						user : req.user, // get the user out of session and pass to template
						trimesters: Object.keys(trimesters).map(function (key) { return trimesters[key]; })
					});
				});
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
		// q0: get currently active trimester setting
		var q0 = function(cb) {
			conn.query("SELECT value_int FROM system_settings WHERE name = ?", ["current_trimester"], cb);
		};
		// q1: get all active trimesters
		var q1 = function(cb) {
			conn.query("SELECT trimester FROM assignment GROUP BY trimester", cb);
		};
		// q2: get all assignment cats
		var q2 = function(cb) {
			conn.query("SELECT * FROM assignment_type", cb);
		};
		// q3: compute cat averages of scored/viewable assignments TRIMESTER-BY-TRIMESTER
		var q3 = function(cb) {
			conn.query("SELECT type_id, trimester, SUM(score) / SUM(pt_value) as avg FROM assignment JOIN assignment_type ON type = type_id JOIN grades ON assignment.asgn_id = grades.asgn_id WHERE uid = ? AND chomped = 1 AND score IS NOT NULL GROUP BY type_id, trimester", [req.user.uid], cb);
		};
		// q4: get all assignment data in one fell swoop
		var q4 = function(cb) {
			conn.query("SELECT type, assignment.asgn_id, trimester, name, description, url, pt_value,\
					date_out, date_due, can_handin, info_changed, nreq, handed_in, handin_time,\
					late, extension, chomped, can_view_feedback, score, honors_possible, honors_earned\
				FROM assignment JOIN assignment_meta ON assignment.asgn_id = assignment_meta.asgn_id\
					JOIN grades ON assignment.asgn_id = grades.asgn_id\
				WHERE uid = ? AND class_pd = ? AND displayed = 1 ORDER BY trimester, type, date_out, date_due",
			[req.user.uid, req.user.class_pd], cb);
		};

		// dispatch
		q0(function(err, setting) {	// q0: get currently active trimester setting
			if (!err) {
				var current_trimester = 1;	// default value
				if (setting.length == 1) {	// actual setting found
					current_trimester = setting[0].value_int;
				}

				q1(function(err, trimesters) {	// q1: get all active terms
					var terms = {};	// terms object forms associative array of Term identifier (string) to Assignment Types (object) (see below)
					if (!err) {
						for (var i = 0; i < trimesters.length; i++) {
							terms[trimesters[i].trimester] = {};
							terms[trimesters[i].trimester].name = trimesters[i].trimester;
							terms[trimesters[i].trimester].types = {};
							if (current_trimester == trimesters[i].trimester)
								terms[trimesters[i].trimester].current = 1;
							else
								terms[trimesters[i].trimester].current = 0;
						}

						q2(function(err, cats) {	// q2: get all assignment cats
							if (!err) {
								// overall goal: create entry in types associative array corresponding to this cat in every trimester in "terms" assoc. array
								for (var i = 0; i < cats.length; i++) {	// dump all found categories into datagram
									Object.keys(terms).forEach(function(term) {
										var cat = {};
										cat.name = cats[i].name;
										cat.weight = cats[i].weight * 100;
										cat.assignments = [];	// empty assignment list, will add later
										terms[term].types[cats[i].type_id] = cat;
									});
								}

								q3(function(err, averages){	// q3: find cat averages per-trimester
									if (!err) {
										for (var i = 0; i < averages.length; i++) {
											var smooth_avg = Math.round(averages[i].avg * 1000) / 10;	// avg to one decimal place
											terms[averages[i].trimester].types[averages[i].type_id].avg = smooth_avg;
										}
									}

									q4(function(err, asgn_arr) {	// q4: get and associate all assignment data
										if (!err) {
											var now = moment();
											for (var i = 0; i < asgn_arr.length; i++) {
												var asgn_l = terms[asgn_arr[i].trimester].types[asgn_arr[i].type].assignments;	// list of assignments of this type
												if (now.isAfter(moment(asgn_arr[i].date_out)))	// only display assignments that are already past "out" date
													asgn_l.push(construct_assignment(asgn_arr[i]));		// construct assignment object
											}

											/* THIS is a disgusting hack
												 basically asgn type IDs may be non-sequential
												 and mustache needs a list, not an object
												 so we need to first treat types like an object
												 (where type ID is the key and assignment is the value)
												 and then back-convert from an object to a list

												 we can do this because ultimately type IDs don't
												 matter on the frontend
												 WEW LAD. */

											Object.keys(terms).forEach(function(term) {
												terms[term].categories = [];
												Object.keys(terms[term].types).forEach(function (type) {
													terms[term].categories.push(terms[term].types[type]);
												});
											});
											d.trimesters = Object.keys(terms).map(function (term) { return terms[term]; });
											
											res.render('portal.html', d);
										} else {
											res.render("error.html", {error: "There's a problem accessing portal information from the database right now.", user: req.user});
										}
									});
								});

							} else {
									res.render("error.html", {error: "There's a problem accessing portal information from the database right now.", user: req.user});
							}
						});
					} else {
						res.render("error.html", {error: "There's a problem accessing portal information from the database right now.", user: req.user});
					}
				});
			} else {
				res.render("error.html", {error: "There's a problem accessing portal information from the database right now.", user: req.user});
			}
		});
	});

	/**************************************
	  HANDIN FLOW
	 **************************************/
	app.get('/handin/:asgn_id', middleware.isLoggedIn, middleware.isPasswordFresh, middleware.isLegitHandin, function(req, res) {
		var date_due = moment(req.date_due);
		if (req.extension)
			date_due.add(req.extension, 'h');
		var late = moment().isAfter(date_due) ? 1 : 0;
		res.render('handin.html', {
			message: req.flash('handinMessage'),
			asgn_id: req.params.asgn_id,
			asgn_name: req.asgn_name,
			late: late,
			user: req.user
		});
	});

	app.post('/handin/:asgn_id', middleware.isLoggedIn, middleware.isPasswordFresh, middleware.isLegitHandin, upload,
		function(req, res) {
			if (req.err) {
				req.flash('handinMessage', req.err);
				res.redirect('/handin/' + req.params.asgn_id);
			}
			else if (!req.body.collab) {
				req.flash('handinMessage', 'You must agree to the Collaboration Statement and upload again.');
				res.redirect('/handin/' + req.params.asgn_id);
			}
			else if (!req.files || req.files.length == 0) {
				req.flash('handinMessage', 'I didn\'t receive any files. Try to hand in again.');
				res.redirect('/handin/' + req.params.asgn_id);
			} else {
				// capture due date
				var now = moment();
				var due = moment(req.date_due);
				if (req.extension)
					due.add(req.extension, 'h');
				var time = moment().format('YYYY-MM-DD HH:mm:ss');
				var late = now.isAfter(due) ? 1 : 0;
				var late_days = Math.ceil(now.diff(due, 'days', true));
				late_days = (late_days < 0 ? 0 : late_days);
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

	/**************************************
	  LOGOUT
	 **************************************/
	app.get('/logout', function(req, res) {
		req.logout();
		res.redirect('/');
	});

	app.use(function(req, res, next){
		res.status(404).render('error.html', {error: "Requested URL does not exist.", user: req.user});
	});
};

function construct_assignment(row) {
	var asgn 		= {};

	var date_out 	= moment(row['date_out']);
	var date_due 	= moment(row['date_due']);
	if (row['extension']) {
		// recompute due date if there is an extension
		date_due.add(row['extension'], 'h');
	}
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
	if (row['extension'])
		asgn['extension'] = row['extension'];

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
	} else if (!row['handed_in'] && row['score'])
		asgn['handin_status'] = 'fa fa-times-circle';
	if (row['chomped'])
		asgn['chomped'] = 'true';

	// late (symbol class for late column)
	if (!row['nreq'] && row['late'] && row['handed_in']) {
		asgn['late'] = 'fa fa-times-circle';
		var handin_time = moment(row['handin_time']);
		asgn['late_days'] = Math.ceil(handin_time.diff(date_due, 'days', true));
	}
	else if (!row['nreq'] && !row['late'] && row['handed_in'])
		asgn['late'] = 'fa fa-check-circle';

	// grade status
	if (row['nreq'])													// NREQ:
		asgn['grade_status'] = 'fa fa-ban';								//   ban symbol
	else if ((!row['score'] && !row['handed_in']) || !row['chomped'])	// NTI OR Not Chomped:
		asgn['grade_status'] = 'fa fa-question-circle';					//   question mark
	else if (!row['score'])												// Chomped but not yet scored:
		asgn['grade_status'] = 'fa fa-cog fa-spin';						//   spinning cog
	else																// Scored:
		asgn['score'] = row['score']									//   show score instead of symbol
		
	if (row['honors_possible'])
		if (!row['handed_in'] || !row['chomped'] || !row['score'] || !row['can_view_feedback'])
			asgn['honors'] = 'fa fa-question';
		else if (row['honors_earned'])
			asgn['honors'] = 'fa fa-thumbs-o-up';
		else
			asgn['honors'] = 'fa fa-thumbs-o-down';

	asgn['pt_value'] = row['pt_value'];

	if (row['score'] && row['chomped'] && row['can_view_feedback'])
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
