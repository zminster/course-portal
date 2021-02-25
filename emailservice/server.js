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

const db = util.promisify(connection.query);

let randString = uniqueNamesGenerator({
    dictionaries: [adjectives, animals, names],
    style: 'lowerCase'
});
console.log("running query", process.env.DB_HOST, process.env.DB_USER);
//grab information, and send all the people who are missing assignments
let transporter = nodemail.createTransport({
    sendmail: true,
    newline: 'unix',
    path: '/usr/sbin/sendmail'
});
let date = new Date().getTime();
connection.query("SELECT grades.uid, grades.asgn_id, grades.extension, membership.class_pd, assignment_meta.date_due FROM grades INNER JOIN membership ON grades.uid = membership.uid " +
    "INNER JOIN assignment_meta ON grades.asgn_id = assignment_meta.asgn_id AND membership.class_pd = assignment_meta.class_pd LEFT JOIN user ON grades.uid = user.uid LEFT JOIN user_role ON user.role = user_role.rid  WHERE handed_in=0 AND chomped=0 AND displayed=1 AND user_role.handin_enabled = 1 AND user_role.reporting_enabled = 1 AND grades.nreq = 0;", async(err, gradeRows) => {
        if (err) console.error("GRADE SELECTION error on uid, asgn_id, and extension", err);
        if (!gradeRows.length) return;
        //start looking at each student at a time
        let emails = gradeRows.map((item, index) => {
            //now each time that item is the same need to check for that specific asgn_id
            //check which class the student is in
            //now that you know class period you can check against the specific assignments
            //first check the due date of the specific project
            return new Promise((resolve, reject) => {
                let udate = new Date(item.date_due).getTime();
                //take due date and compare it to current time
                //run through a multitude a comparators of each portion of data in date_due
                if (!item.length) resolve("DON'T SEND EMAIL");
                if (date - udate < 0) resolve("end early");
                //send a message after finding which assignment it is
                db("SELECT assignment.name, assignment.url, assignment.asgn_id, user_meta.first_name, user_meta.last_name, user_meta.email FROM assignment CROSS JOIN user_meta WHERE assignment.asgn_id=? AND user_meta.uid=? AND assignment.trimester = (SELECT value_int FROM system_settings WHERE name='current_trimester')", [item.asgn_id, item.uid])
                    .then((assignmentRow) => {
                        console.log(item.asgn_id, item.uid, item.class_pd);
                        console.log("UNDEFINED?", assignmentRow);
                        if (!assignmentRow) reject("no assignment");
                        //fill out the text file <--must be named late.txt
                        console.log("send email");
                        fs.readFile(path.join(__dirname, "assignmentFiles", "late1.txt"), function(err, template) {
                            if (err) reject(err);
                            let dateTime = new Date(udate).toString().substring(0, 15);
                            let returnEmail = template.toString();
                            console.log("TEST FOR NAME", assignmentRow[0], assignmentRow[0].first_name);
                            returnEmail = returnEmail.replace("{{SPECIMEN_NAMEF}}", assignmentRow[0].first_name);
                            returnEmail = returnEmail.replace("{{SPECIMEN_NAMEL}}", assignmentRow[0].last_name);
                            returnEmail = returnEmail.replace("{{ASSIGNMENT_NAME}}", assignmentRow[0].name);
                            returnEmail = returnEmail.replace("{{DUE_DATE}}", dateTime ? dateTime : "");
                            returnEmail = returnEmail.replace("{{URL}}", assignmentRow[0].url ? assignmentRow[0].url : "");
                            console.log("EMAIL SEND TO:", assignmentRow[0].email);
                            transporter.sendMail({
                                from: '"CS Mailboy+"<CSP' + randString + '@cs.stab.org>',
                                to: "chall22@students.stab.org", // assignmentRow[0].email,
                                subject: 'Missing Assignment: ' + assignmentRow[0].name,
                                html: "<pre>" + returnEmail + "</pre>"
                            }, (err, info) => {
                                if (err) reject(err);
                                resolve(info);
                            });
                        });
                    })
                    .catch((err) => {
                        console.error(err);
                        reject(err);
                    });
                /*connection.query("SELECT assignment.name, assignment.url, assignment_type.weight, user_meta.first_name," +
                	" user_meta.last_name, user_meta.email" +
                	" FROM assignment LEFT JOIN assignment_type ON assignment.type = assignment_type.type_id" +
                	" CROSS JOIN user_meta", (err, assignmentRow) => {
                		if (err) console.log("selection from assignment for name and url", err);
                		console.log(assignmentRow);
                	});*/
            });
        });
        Promise.all(emails).then((emails) => {
            connection.close();
            console.log(emails);
            return emails;
        }).catch((err) => {
            console.error(err);
            return;
        });
    });