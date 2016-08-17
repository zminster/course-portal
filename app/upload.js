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

// middleware: prepares filesystem and dumps files encoded in form
module.exports = function (req, res, next) {
	req.handin_path = handinDir + req.params.asgn_id + '/' + req.user.username;
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
				var files = 0, finished = false;

				// field processing - add to req.body
				req.busboy.on('field', function(fieldname, val) {
					req.body[fieldname] = val;
				});

				// file processing - route streams properly and add to req.files
				req.busboy.on('file', function(fieldname, file, filename, encoding, mimetype) {
					file.on('limit', function () {
						req.err = 'You may only upload files of maximum size ' + (limits.fileSize / 1024 / 1000) + 'MB.\
						Reduce file size or put files in an archive, then try to upload again.';
						next();
					});

					files++;
					fstream = fs.createWriteStream(req.handin_path + '/' + filename);
					file.pipe(fstream);

					fstream.on('close', function () {
						req.files.push(filename);
						--files;
						if (!files && finished)
							next();
					});
				});

				// too many files?
				req.busboy.on('filesLimit', function() {
					req.err = 'You may only upload a maximum of ' + limits.files + ' files.';
					next();
				});

				// continue only when all form data received
				req.busboy.on('finish', function(){
					finished = true;
				});

		    	req.pipe(req.busboy);
			});
		});
	});
}