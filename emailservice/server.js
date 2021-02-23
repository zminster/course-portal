require('dotenv').config({path: __dirname + '../.env'});
const mysql = require('mysql2');
const fs = require('fs');
const path = require('path');
const nodemail = require('nodemailer');
const {
	uniqueNamesGenerator,
	adjectives,
	animals,
	names
} = require('unique-names-generator');

//connect to db
const connection = mysql.createConnection({
	host: process.env.HOST,
	database: process.env.DATABASE,
	password: process.env.PASSWORD,
	user: process.env.DB_USER,
	insecureAuth: true
});

connection.connect((err) => {
	if (err) console.log(err);
});

	let randString = uniqueNamesGenerator({
		dictionaries: [adjectives, animals, names],
		style: 'lowerCase'
	});
	console.log("running query", process.env.HOST, process.env.DB_USER);
	//grab information, and send all the people who are missing assignments
	let transporter = nodemail.createTransport({
		sendmail: true,
		newline: 'unix',
		path: 'usr/sbin/sendmail'
	});
	let date = new Date().getTime();
	connection.query("SELECT grades.uid, grades.asgn_id, grades.extension, membership.class_pd, assignment_meta.date_due FROM grades INNER JOIN membership ON grades.uid = membership.uid " +
		"INNER JOIN assignment_meta ON grades.asgn_id = assignment_meta.asgn_id AND membership.class_pd = assignment_meta.class_pd WHERE handed_in=0 AND chomped=0", async (err, gradeRows) => {
			if (err) console.log("GRADE SELECTION error on uid, asgn_id, and extension", err);
			if (gradeRows) {
				//start looking at each student at a time
				gradeRows.forEach(async (item, index) => {
					//now each time that item is the same need to check for that specific asgn_id
					//check which class the student is in
					//now that you know class period you can check against the specific assignments
					//first check the due date of the specific project
					let udate = new Date(item.date_due).getTime();
					//take due date and compare it to current time
					//run through a multitude a comparators of each portion of data in date_due
					if (date - udate > 0) {
						//send a message after finding which assignment it is
						connection.query("SELECT assignment.name, assignment.url, user_meta.first_name, user_meta.last_name, user_meta.email FROM assignment, user_meta WHERE assignment.asgn_id=? AND user_meta.uid=?", [item.asgn_id, item.uid], (err, assignmentRow) => {
							if (err) console.log("selection from assignment for name and url", err);
							//fill out the text file <--must be named late.txt
							res.json(assignmentRow);
							if (index < 20) {
								console.log("send email");
								fs.readFile(path.join(__dirname, "assignmentFiles", "late1.txt"), function(err, template) {
									if (err) console.log(err);
									let dateTime = new Date(udate).toString().substring(0, 15);
									let returnEmail = template.toString();
									returnEmail = returnEmail.replace("{{SPECIMEN_NAMEF}}", userInfoRow[0].first_name);
									returnEmail = returnEmail.replace("{{SPECIMEN_NAMEL}}", userInfoRow[0].last_name);
									returnEmail = returnEmail.replace("{{ASSIGNMENT_NAME}}", assignmentRow[0].name);
									returnEmail = returnEmail.replace("{{DUE_DATE}}", dateTime ? dateTime : "");
									returnEmail = returnEmail.replace("{{URL}}", assignmentRow[0].url ? assignmentRow[0].url : "");
									console.log("EMAIL SEND TO:", userInfoRow[0].email);
									transporter.sendMail({
										from: '"CSP" ' + randString + '@cs.stab.org',
										to: userInfoRow[0].email,
										subject: 'Missing Assignment: ' + assignmentRow[0].name,
										html: "<pre>" + returnEmail + "</pre>"
									}, (err, info) => {
										console.error();
									});
								});
							}
						});
					} else {
						connection.query("SELECT assignment.name, assignment.url, assignment_type.weight, user_meta.first_name," +
							" user_meta.last_name, user_meta.email" +
							" FROM assignment, user_meta INNER JOIN " +
							"assignment_type ON assignment.type = assignment_type.type", (err, assignmentRow) => {
								if (err) console.log("selection from assignment for name and url", err);
								console.log(assignmentRow);
							});
					}
				});
			}
		});
