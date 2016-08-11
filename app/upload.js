//app/upload.js

/* Configuration */
var maxSize 	= 1000000;	/* per-handin upload limit (bytes) */
var handinDir 	= '/course/csp/handin/';

/* Handin upload */
var multer		= require('multer');
var fs 			= require('fs');
var mkdirp		= require('mkdirp');
var exec 		= require('child_process').exec;
var storage = multer.diskStorage({
	// send uploads to correct handin dir thorugh multer
	destination: function (req, file, callback) {
		console.log(req.body);
		console.log(req.body);
		if (!req.body.collab) {
			callback("NOCOLLABERR", null);
		}
		else {
			var handin_path = handinDir + req.params.asgn_id + '/' + req.user.username;
			mkdirp(handin_path, {mode: 0770}, function(err, made) {
				if (!err) {	// delete any existing files
					exec('rm -rf ' + handin_path + '/*', function (err, stdout, stderr) {
						if (!err)
							callback(null, handin_path);
						else
							callback(err, null);
						});
				} else
					callback(err, null);
			});
		}
	},
	// keep student's original filenames
	filename: function (req, file, callback) {
		callback(null, file.originalname);
	}
});

module.exports = multer({
	storage: storage,
	limits: { fileSize:maxSize }
}).array('handin');