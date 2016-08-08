//app/upload.js

/* Configuration */
var maxSize 	= 1000000;	/* per-handin upload limit (bytes) */
var handinDir 	= '/course/csp/handin/';

/* Handin upload */
var multer		= require('multer');
var fs 			= require('fs');
var exec 		= require('child_process').exec;
var storage = multer.diskStorage({
	// send uploads to correct handin dir thorugh multer
	destination: function (req, file, callback) {
		var handin_path = handinDir + req.params.asgn_id + '/' + req.user.username;
		fs.lstat(handin_path, function(err, stats) {
			if (!err && stats.isDirectory()) {	// if there's already a handin, boot it out
				exec('rm -rf ' + handin_path + '/*', function (err, stdout, stderr) {
					if (!err)
						callback(null, handin_path);
					else
						callback(err, null);
					});
			} else if (!err) {
				fs.mkdir(handin_path);
				callback(null, handin_path);
			} else
				callback(err, null);
		});
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