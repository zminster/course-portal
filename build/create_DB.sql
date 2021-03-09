DROP DATABASE IF EXISTS course_portal;

CREATE DATABASE course_portal DEFAULT CHARACTER SET utf8;

USE course_portal;

-- system
--  stores master system settings as-needed
-- 
--  system(name, value_int, value_str)
CREATE TABLE system_settings (
    `sid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `value_int` INT UNSIGNED,
    `value_str` VARCHAR(255),
        PRIMARY KEY (`sid`),
    UNIQUE INDEX `setting_name_UNIQUE` (`name`)
);

-- default settings
INSERT INTO system_settings (name, value_int) VALUES ("current_trimester", 1);

-- user_role
--  stores information about system roles & permissions
--  can flexibly add columns later to cover additional perms
-- 
--  user_role(rid, name, access_backend, class_membership, reporting_enabled)
CREATE TABLE user_role (
    `rid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `access_backend` TINYINT(1),    -- enables/disables admin access
    `class_membership` TINYINT(1),  -- enables/disables frontend class switching
    `handin_enabled` TINYINT(1),    -- enables/disables turning in & chomping
    `reporting_enabled` TINYINT(1), -- enables/disables affect on grade/asgn reports
        PRIMARY KEY (`rid`)
);

-- establish default roles (can be modified later)
INSERT INTO user_role (name, access_backend, class_membership, handin_enabled, reporting_enabled)
    VALUES ("Instructor", 1, 0, 0, 0);
INSERT INTO user_role (name, access_backend, class_membership, handin_enabled, reporting_enabled)
    VALUES ("Teaching Assistant", 0, 0, 0, 0);
INSERT INTO user_role (name, access_backend, class_membership, handin_enabled, reporting_enabled)
    VALUES ("Auditor", 0, 1, 1, 0);
INSERT INTO user_role (name, access_backend, class_membership, handin_enabled, reporting_enabled)
    VALUES ("Student", 0, 1, 1, 1);

-- user
-- 	stores information necessary to authorize student users
-- 
-- 	user(uid, username, password)
CREATE TABLE user (
    `uid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(20) NOT NULL,
    `password` CHAR(60) NOT NULL,
    `change_flag` TINYINT(1), -- password reset flag
    `role` INT UNSIGNED NOT NULL,   -- assigned role
        PRIMARY KEY (`uid`),
    FOREIGN KEY (`role`) REFERENCES user_role(`rid`),
    UNIQUE INDEX `uid_UNIQUE` (`uid` ASC),
    UNIQUE INDEX `username_UNIQUE` (`username` ASC)
);

-- class
-- 	provides a reference to every existing class period
-- 	future usage: any extra data about each class
-- 
-- 	class(class_pd)
CREATE TABLE class (
    `class_pd` INT UNSIGNED NOT NULL,
    `amnesty` TINYINT(1),
        PRIMARY KEY (`class_pd`)
);

-- user_meta
-- 	stores meta information about each user
-- 
-- 	user_meta(uid, first_name, last_name, year, email)
CREATE TABLE user_meta (
    `uid` INT UNSIGNED NOT NULL,
    `first_name` VARCHAR(255) NOT NULL,
    `last_name` VARCHAR(255) NOT NULL,
    `year` INT UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `advisor_email` VARCHAR(255) NOT NULL,
        PRIMARY KEY (`uid`),
    FOREIGN KEY (`uid`) REFERENCES user(`uid`)
);

-- membership table
-- 	stores membership of students in classes
-- 	used for figuring out due dates, etc for users
-- 
-- 	membership(uid, class_pd)
CREATE TABLE membership (
    `uid` INT UNSIGNED NOT NULL,
    `class_pd` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`uid`),
   FOREIGN KEY (`uid`) REFERENCES user(`uid`),
   FOREIGN KEY (`class_pd`) REFERENCES class(`class_pd`)
);

-- assignment format table
--  stores information about possible assignment formats
--  usage: - format validation occurs student-side according to regex
--         - is_file indicates whether submission is text box or binary
--         - regex is nullable field, value indicates validation should be done
--         - validation_help contains HTML if regex fails
--
--  assignment(asgn_id, name, type, pt_value, trimester, honors_possible, description, url)
CREATE TABLE assignment_format (
    `format_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR (255) NOT NULL,
    `description` TEXT,
    `is_file` TINYINT(1),
    `regex` TEXT,
    `validation_help` TEXT,
        PRIMARY KEY (`format_id`),
    UNIQUE INDEX `format_name_UNIQUE` (`name`)
);

-- assignment type table
--  stores weights/names of assignment categories
--  used to calculate students' overall grades
-- 
--  assignment(type_id, name, weight)
CREATE TABLE assignment_type (
    `type_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `weight` FLOAT,
        PRIMARY KEY (`type_id`)
);

-- assignment table
-- 	stores information about each assignment
-- 	future usage: type corresponds to enum/relation defining asgn types (hw, project, exam, etc)
-- 
-- 	assignment(asgn_id, name, type, pt_value, trimester, honors_possible, description, url)
CREATE TABLE assignment (
    `asgn_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `type` INT UNSIGNED NOT NULL,
    `format` INT UNSIGNED NOT NULL,
    `pt_value` INT UNSIGNED NOT NULL,
    `trimester` INT UNSIGNED NOT NULL,
    `honors_possible` TINYINT(1),
    `description` TEXT,
    `url` TEXT,
        PRIMARY KEY (`asgn_id`),
    FOREIGN KEY (`format`) REFERENCES assignment_format(`format_id`),
    FOREIGN KEY (`type`) REFERENCES assignment_type(`type_id`),
    UNIQUE INDEX `asgn_name_UNIQUE` (`name`)
);

-- assignment_meta table
-- 	stores per-class meta information about each assignment
-- 
-- 	assignment_meta(asgn_id, class_pd, date_out, date_due, displayed, can_handin, info_changed)
CREATE TABLE assignment_meta (
    `asgn_id` INT UNSIGNED NOT NULL,
    `class_pd` INT UNSIGNED NOT NULL,
    `date_out` DATETIME,
    `date_due` DATETIME,
    `displayed` TINYINT(1),
    `can_handin` TINYINT(1),
    `info_changed` TINYINT(1),
    UNIQUE INDEX `ix_perclass` (`asgn_id`, `class_pd`),
    FOREIGN KEY (`asgn_id`) REFERENCES assignment(`asgn_id`),
    FOREIGN KEY (`class_pd`) REFERENCES class(`class_pd`)
);

-- grades table
-- 	stores students' grades on each assignment
-- 
-- 	grades(uid, asgn_id, handed_in, late, chomped, can_view_feedback, score, honors_earned)
CREATE TABLE grades (
    `uid` INT UNSIGNED NOT NULL,
    `asgn_id` INT UNSIGNED NOT NULL,
    `nreq` TINYINT(1) NOT NULL,
    `handed_in` TINYINT(1) NOT NULL,
    `handin_time` DATETIME,
    `extension` INT UNSIGNED,
    `late` TINYINT(1) NOT NULL,
    `chomped` TINYINT(1) NOT NULL,
    `can_view_feedback` TINYINT(1) NOT NULL,
    `score` FLOAT,
    `honors_earned` TINYINT(1),
    UNIQUE INDEX `ix_perstudent` (`uid`, `asgn_id`),
    FOREIGN KEY (`uid`) REFERENCES user(`uid`),
    FOREIGN KEY (`asgn_id`) REFERENCES assignment(`asgn_id`)
);

-- lesson table
--  stores universal information about class lessons
-- 
--  lessons(id, topic, slide_url, extra_url)
CREATE TABLE lesson (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `trimester` INT UNSIGNED NOT NULL,
    `topic` VARCHAR(255) NOT NULL,
    `slide_url` TEXT NOT NULL,
    `extra_url` TEXT NOT NULL,
        PRIMARY KEY (`id`),
    UNIQUE INDEX `lesson_topic_UNIQUE` (`topic`)
);

-- lesson_meta table
--  stores per-class meta information about each lesson in lesson table
-- 
-- lesson_meta(id, class_pd, date, visible)
CREATE TABLE lesson_meta (
    `id` INT UNSIGNED NOT NULL,
    `class_pd` INT UNSIGNED NOT NULL,
    `release_date` DATE NOT NULL,
    `visible` TINYINT(1) NOT NULL,
        FOREIGN KEY (`id`) REFERENCES lesson(`id`)
);