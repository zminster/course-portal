const mysql = require('mysql2');
const fs = require('fs');
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
connection.query("SELECT user_meta.advisor_email, CONCAT(user_meta.first_name, ' ', user_meta.last_name) as student_name, assignment.name, assignment_meta.date_due, assignment.url, assignment.pt_value, assignment.asgn_id, assignment_type.name AS category, user_meta.email FROM grades INNER JOIN membership ON grades.uid = membership.uid LEFT JOIN assignment ON grades.asgn_id = assignment.asgn_id INNER JOIN assignment_meta ON grades.asgn_id = assignment_meta.asgn_id AND membership.class_pd = assignment_meta.class_pd LEFT JOIN user ON grades.uid = user.uid LEFT JOIN user_role ON user.role = user_role.rid LEFT JOIN user_meta ON user.uid = user_meta.uid LEFT JOIN assignment_type ON assignment.type = assignment_type.type_id WHERE handed_in=0 AND chomped=0 AND displayed=1 AND user_role.handin_enabled = 1 AND user_role.reporting_enabled = 1 AND grades.nreq = 0 AND date_due < NOW()  AND trimester = (SELECT value_int FROM system_settings WHERE name = 'current_trimester') ORDER BY advisor_email, last_name, first_name, date_due ASC;", async(err, gradeRows) => {
    if (err) { console.error("GRADE SELECTION error on uid, asgn_id, and extension", err); return; }
    if (!gradeRows.length) return;

    // group missing assignments by advisor in giant object
    let advisors = {};
    gradeRows.forEach((assignment) => {
        if (!assignment || !assignment.advisor_email) return;
        if (advisors[assignment.advisor_email])
            if (advisors[assignment.advisor_email][assignment.student_name])
                advisors[assignment.advisor_email][assignment.student_name].push(assignment)
            else
                advisors[assignment.advisor_email][assignment.student_name] = [assignment];
        else
            advisors[assignment.advisor_email] = {[assignment.student_name]: [assignment]};
    });
    
    Object.keys(advisors).forEach((advisor) => {
        let email = advisor;
        advisor = advisors[advisor];
        fs.readFile(path.join(__dirname, "assignmentFiles", "advisor.txt"), function(err, template) {
            if (err) { console.error(err); return; }

            let assignmentDeluge = "";
            Object.keys(advisor).forEach((student) => {
                assignmentDeluge += student + '\n';
                student = advisor[student];
                student.forEach((assignment) => {
                    let due = new Date(assignment.date_due).toString().substring(0, 15);
                    assignmentDeluge += '\t* ' + assignment.name + '\n\t\tDue: ' + due + '\tCategory: ' + assignment.category + '\tPoint Value: ' + assignment.pt_value + '\n';
                });
            });

            let returnEmail = template.toString();
            returnEmail = returnEmail.replace(/{{COURSE_CODE}}/g, process.env.COURSE_CODE.toUpperCase());
            returnEmail = returnEmail.replace(/{{DELUGE}}/g, assignmentDeluge);
            transporter.sendMail({
                from: '"CS Department"<' + process.env.COURSE_CODE.toUpperCase() + '_' + randString + '@cs.stab.org>',
                to: email,
                replyTo: 'zminster@stab.org',
                subject: 'Advisees Missing Assignments In ' + process.env.COURSE_CODE.toUpperCase(),
                html: "<pre>" + returnEmail + "</pre>"
            }, (err, info) => {
                if (err) console.error(err);
                console.log(info);
            });
        });
    });

    connection.close();
});