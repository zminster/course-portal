//app/upload.js

/* Configuration */
var limits = {
	fileSize : 10240000,
	files : 5
};
var handinDir 	= '/course/csp/handin/';			/* handin dirs are created relative to here */

/* Handin upload */
var busboy		= require('connect-busboy');		/* process multipart forms */
var fs 			= require('fs');					/* streaming handins to dir */
var mkdirp		= require('mkdirp');				/* creating handin dir */
var exec 		= require('child_process').exec;	/* removing old handins */

var err_fs		= "There was a fatal error saving your response.";

// middleware: prepares filesystem and dumps files encoded in form
module.exports = function (req, res, next) {
	req.handin_path = handinDir + req.params.asgn_id + '/' + req.user.class_pd + '/' + req.user.username;
	req.handin_path = req.handin_path.replace(/\s+/g, '');						// remove any spaces from path prior to execution
	exec('rm -rf ' + req.handin_path + '/*', function (err, stdout, stderr) { 	// remove old handin dir (ignore EEXIST)
		mkdirp(req.handin_path, {mode: 0770}, function(err, made) {				// create new handin dir
			var bb = busboy({limits: limits});					// read & begin processing handin form
			bb(req, res, function() {
				if (!req.busboy) {
					req.flash('handinMessage', 'Something went wrong. Try to hand in again.');
					res.redirect('/handin/' + req.params.asgn_id);
					res.end();
				}

				req.files = [];
				req.asgn.format.validation_pass = false;
				var regex = new RegExp(req.asgn.format.regex, 'g');
				var files = 0, finished = false, write_rows = [];

				// field processing - add to req.body
				req.busboy.on('field', function(fieldname, val) {
					req.body[fieldname] = val;
					if (!req.asgn.format.is_file) {	// prepare text fields for writing if this is non-file
						if (fieldname === "submission")	// verify regex match
							write_rows.push(val);
							if (req.asgn.format.regex && val.match(regex)) {
								req.asgn.format.validation_pass = true;
							}
					}
				});

				// file processing - route streams properly and add to req.files
				req.busboy.on('file', function(fieldname, file, filename, encoding, mimetype) {
					if (!req.asgn.format.is_file) {	// reject files for non-file handins
						req.err = 'You may not upload files for this assignment. Please review the instructions.';
						return next();
					}

					file.on('limit', function () {
						req.err = 'You may only upload files of maximum size ' + (limits.fileSize / 1024 / 1000) + 'MB.\
						Reduce file size or put files in an archive, then try to upload again.';
						return next();
					});

					// verify regex match of at least one filename
					if (req.asgn.format.regex && filename.match(regex))
						req.asgn.format.validation_pass = true;

					files++;
					fstream = fs.createWriteStream(req.handin_path + '/' + filename);
					file.pipe(fstream);

					fstream.on('close', function () {
						req.files.push(filename);
						--files;
						if (!files && finished)
							return next();
					});
				});

				// too many files?
				req.busboy.on('filesLimit', function() {
					req.err = 'You may only upload a maximum of ' + limits.files + ' files.';
					return next();
				});

				// continue only when all form data received
				req.busboy.on('finish', function(){
					finished = true;
					if (!req.asgn.format.is_file) { // write fields to file if not a file upload asgn
						console.log("writing to stream");
						var fstream = fs.createWriteStream(req.handin_path + '/submission.txt');
						fstream.on('error', function(err) { res.render('error.html', {error: err_fs, user: req.user}); });
						fstream.on('finish', function() { return next(); });
						write_rows.forEach(function(item) { fstream.write(item + '\n'); });
						fstream.end();
					}
					else if (!files)
						return next();
				});

		    	req.pipe(req.busboy);
			});
		});
	});
}