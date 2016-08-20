DROP DATABASE IF EXISTS course_portal;

CREATE DATABASE course_portal;

USE course_portal;

-- user
-- 	stores information necessary to authorize student users
-- 
-- 	user(uid, username, password)
CREATE TABLE course_portal.user (
    `uid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(20) NOT NULL,
    `password` CHAR(60) NOT NULL,
    `change_flag` TINYINT(1), -- password reset flag
        PRIMARY KEY (`uid`),
    UNIQUE INDEX `uid_UNIQUE` (`uid` ASC),
    UNIQUE INDEX `username_UNIQUE` (`username` ASC)
);

-- class
-- 	provides a reference to every existing class period
-- 	future usage: any extra data about each class
-- 
-- 	class(class_pd)
CREATE TABLE course_portal.class (
    `class_pd` INT UNSIGNED NOT NULL,
    `amnesty` TINYINT(1),
        PRIMARY KEY (`class_pd`)
);

-- user_meta
-- 	stores meta information about each user
-- 
-- 	user_meta(uid, name, year, email)
CREATE TABLE course_portal.user_meta (
    `uid` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `year` INT UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL,
        PRIMARY KEY (`uid`),
    FOREIGN KEY (`uid`) REFERENCES user(`uid`)
);

-- membership table
-- 	stores membership of students in classes
-- 	used for figuring out due dates, etc for users
-- 
-- 	membership(uid, class_pd)
CREATE TABLE course_portal.membership (
    `uid` INT UNSIGNED NOT NULL,
    `class_pd` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`uid`),
   FOREIGN KEY (`uid`) REFERENCES user(`uid`),
   FOREIGN KEY (`class_pd`) REFERENCES class(`class_pd`)
);

-- assignment type table
--  stores weights/names of assignment categories
--  used to calculate students' overall grades
-- 
--  assignment(type_id, name, weight)
CREATE TABLE course_portal.assignment_type (
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
CREATE TABLE course_portal.assignment (
    `asgn_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `type` INT UNSIGNED NOT NULL,
    `pt_value` INT UNSIGNED NOT NULL,
    `trimester` INT UNSIGNED NOT NULL,
    `honors_possible` TINYINT(1),
    `description` TEXT,
    `url` TEXT,
        PRIMARY KEY (`asgn_id`),
    FOREIGN KEY (`type`) REFERENCES assignment_type(`type_id`),
    UNIQUE INDEX `asgn_name_UNIQUE` (`name`)
);

-- assignment_meta table
-- 	stores per-class meta information about each assignment
-- 
-- 	assignment_meta(asgn_id, class_pd, date_out, date_due, displayed, can_handin, info_changed)
CREATE TABLE course_portal.assignment_meta (
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
CREATE TABLE course_portal.grades (
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
CREATE TABLE course_portal.lesson (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `trimester` INT UNSIGNED NOT NULL,
    `topic` VARCHAR(767) NOT NULL,
    `slide_url` TEXT NOT NULL,
    `extra_url` TEXT NOT NULL,
        PRIMARY KEY (`id`),
    UNIQUE INDEX `lesson_topic_UNIQUE` (`topic`)
);

-- lesson_meta table
--  stores per-class meta information about each lesson in lesson table
-- 
-- lesson_meta(id, class_pd, date, visible)
CREATE TABLE course_portal.lesson_meta (
    `id` INT UNSIGNED NOT NULL,
    `class_pd` INT UNSIGNED NOT NULL,
    `release_date` DATE NOT NULL,
    `visible` TINYINT(1) NOT NULL,
        FOREIGN KEY (`id`) REFERENCES lesson(`id`)
);