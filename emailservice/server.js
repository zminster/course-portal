const mysql = require('mysql2');
const fs = require('fs');
const util = require('util');
const path = require('path');
require('dotenv').config({
    path: path.resolve(__dirname, '../.env')
});
const nodemail = require('nodemailer');
const {
    uniqueNamesGenerator,
    adjectives,
    animals,
    names
} = require('unique-names-generator');

//connect to db
const connection = mysql.createConnection({
    host: process.env.DB_HOST,
    database: process.env.DB_NAME,
    password: process.env.DB_PASS,
    user: process.env.DB_USER,
    insecureAuth: true
});

connection.connect((err) => {
    if (err) console.log(err);
});

const db = util.promisify(connection.query).bind(connection);

let randString = uniqueNamesGenerator({
    dictionaries: [adjectives, animals, names],
    style: 'lowerCase'
});

//grab information, and send all the people who are missing assignments
let transporter = nodemail.createTransport({
    sendmail: true,
    newline: 'unix',
    path: '/usr/sbin/sendmail'
});
let date = new Date().getTime();
connection.query("SELECT user_meta.first_name, user_meta.last_name, assignment.name, grades.asgn_id, assignment_meta.date_due, assignment.url, user_meta.email FROM grades INNER JOIN membership ON grades.uid = membership.uid LEFT JOIN assignment ON grades.asgn_id = assignment.asgn_id INNER JOIN assignment_meta ON grades.asgn_id = assignment_meta.asgn_id AND membership.class_pd = assignment_meta.class_pd LEFT JOIN user ON grades.uid = user.uid LEFT JOIN user_role ON user.role = user_role.rid LEFT JOIN user_meta ON user.uid = user_meta.uid WHERE handed_in=0 AND chomped=0 AND displayed=1 AND user_role.handin_enabled = 1 AND user_role.reporting_enabled = 1 AND grades.nreq = 0 AND date_due < NOW();", async(err, gradeRows) => {
    if (err) { console.error("GRADE SELECTION error on uid, asgn_id, and extension", err); return; }
    if (!gradeRows.length) return;
    //start looking at each student at a time
    //now each time that item is the same need to check for that specific asgn_id
    //check which class the student is in
    //now that you know class period you can check against the specific assignments
    //first check the due date of the specific project
    //send a message after finding which assignment it is
    //fill out the text file <--must be named late.txt
    gradeRows.forEach((assignment) => {
        fs.readFile(path.join(__dirname, "assignmentFiles", "late1.txt"), function(err, template) {
            if (err) { console.error(err); return; }
            let dateTime = new Date(assignment.date_due).toString().substring(0, 15);
            let returnEmail = template.toString();
            returnEmail = returnEmail.replace(/{{SPECIMEN_NAMEF}}/g, assignment.first_name);
            returnEmail = returnEmail.replace(/{{SPECIMEN_NAMEL}}/g, assignment.last_name);
            returnEmail = returnEmail.replace(/{{ASSIGNMENT_NAME}}/g, assignment.name);
            returnEmail = returnEmail.replace(/{{DUE_DATE}}/g, dateTime ? dateTime : "");
            returnEmail = returnEmail.replace(/{{URL}}/g, assignment.url ? assignment.url : "");
            returnEmail = returnEmail.replace(/{{CP_URL}}/g, 'https://' + process.env.COURSE_CODE + '.cs.stab.org/handin/' + assignment.asgn_id);
            returnEmail = returnEmail.replace(/{{COURSE_CODE}}/g, process.env.COURSE_CODE.toUpperCase());
            console.log("EMAIL SEND TO:", assignment.email);
            transporter.sendMail({
                from: '"CS Mailboy+"' + process.env.COURSE_CODE.toUpperCase() +  '_' + randString + '@cs.stab.org>',
                to: assignment.email,
                replyTo: 'zminster@stab.org',
                subject: 'Missing Assignment: ' + assignment.name,
                html: "<pre>" + returnEmail + "</pre>"
            }, (err, info) => {
                if (err) console.error(err);
                console.log(info);
            });
        });
    });
    /*connection.query("SELECT assignment.name, assignment.url, assignment_type.weight, user_meta.first_name," +
    	" user_meta.last_name, user_meta.email" +
    	" FROM assignment LEFT JOIN assignment_type ON assignment.type = assignment_type.type_id" +
    	" CROSS JOIN user_meta", (err, assignmentRow) => {
    		if (err) console.log("selection from assignment for name and url", err);
    		console.log(assignmentRow);
    	});*/
    connection.close();
});