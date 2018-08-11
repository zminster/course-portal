-- assignment format table
-- 	stores information about possible assignment formats
-- 	usage: format validation occurs student-side according to regex
--
-- 	assignment(asgn_id, name, type, pt_value, trimester, honors_possible, description, url)

USE course_portal;

CREATE TABLE course_portal.assignment_format (
	`format_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`description` TEXT,
	`is_file` TINYINT(1),
	`regex` TEXT,
	`validation_help` TEXT,
		PRIMARY KEY (`format_id`),
	UNIQUE INDEX `format_name_UNIQUE` (`name`)
);

-- add requisite format table mods to assignment
ALTER TABLE course_portal.assignment
	ADD COLUMN `format` INT UNSIGNED NOT NULL AFTER `type`;

ALTER TABLE course_portal.assignment
	ADD FOREIGN KEY (`format`) REFERENCES assignment_format(`format_id`);

