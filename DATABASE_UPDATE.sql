-- =====================================================================
-- DATABASE_UPDATE.sql  -  adds enrollment applications, applicant accounts,
-- student status (suspend / expel) to the EXISTING `online_enroll` database.
--
-- HOW TO RUN (phpMyAdmin): select the `online_enroll` database -> Import -> choose this file -> Go.
-- Run it ONCE. Nothing is deleted: all existing users, students, subjects and enrollments are kept.
-- (Running it a second time will show "Duplicate column" errors; that is harmless.)
-- =====================================================================
USE `online_enroll`;

-- 1) Use InnoDB so that foreign keys and transactions work (data is kept).
ALTER TABLE `usertbl`          ENGINE = InnoDB;
ALTER TABLE `studenttbl`       ENGINE = InnoDB;
ALTER TABLE `student_subject`  ENGINE = InnoDB;

-- 2) studenttbl: student status columns (Active / Suspended / Expelled) + unique student number
ALTER TABLE `studenttbl`
  ADD COLUMN `status`          VARCHAR(10)  NOT NULL DEFAULT 'Active' COMMENT 'Active or Expelled (Suspended is worked out from suspended_until)',
  ADD COLUMN `suspended_until` DATE         NULL COMMENT 'last day of the suspension; the student is active again after this date',
  ADD COLUMN `suspend_reason`  VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN `expelled_at`     DATE         NULL,
  ADD COLUMN `expel_reason`    VARCHAR(255) NOT NULL DEFAULT '',
  ADD UNIQUE KEY `uniq_studnum` (`studnum`),
  ADD KEY `idx_account` (`accountID`);

-- 3) NEW TABLE: applications  (one row per enrollment application)
--    account_id -> usertbl.ID   (login created by the applicant; user_type = 'applicant' until approved)
--    student_id -> studenttbl.ID (filled in when the registrar approves)
--    reviewed_by-> usertbl.ID   (the registrar/admin who decided)
CREATE TABLE IF NOT EXISTS `applications` (
  `ID`              INT(11) NOT NULL AUTO_INCREMENT,
  `ref_no`          VARCHAR(20)  NOT NULL,
  `account_id`      INT(11)      NULL,
  `student_id`      INT(11)      NULL,
  `status`          ENUM('Pending','Under Review','Approved','Declined') NOT NULL DEFAULT 'Pending',
  `remarks`         VARCHAR(500) NOT NULL DEFAULT '',
  `fname`           VARCHAR(100) NOT NULL,
  `minitial`        VARCHAR(10)  NOT NULL DEFAULT '',
  `lname`           VARCHAR(100) NOT NULL,
  `gender`          VARCHAR(11)  NOT NULL,
  `birthdate`       DATE         NOT NULL,
  `birthplace`      VARCHAR(100) NOT NULL,
  `civilstatus`     VARCHAR(20)  NOT NULL,
  `course`          VARCHAR(50)  NOT NULL,
  `yearlvl`         VARCHAR(50)  NOT NULL,
  `contact`         VARCHAR(20)  NOT NULL,
  `email`           VARCHAR(100) NOT NULL,
  `street`          VARCHAR(150) NOT NULL,
  `barangay`        VARCHAR(100) NOT NULL,
  `city`            VARCHAR(100) NOT NULL,
  `province`        VARCHAR(100) NOT NULL,
  `zip`             VARCHAR(10)  NOT NULL DEFAULT '',
  `fathername`      VARCHAR(100) NOT NULL DEFAULT '',
  `mothername`      VARCHAR(100) NOT NULL DEFAULT '',
  `guardianname`    VARCHAR(100) NOT NULL,
  `guardianrelation` VARCHAR(50) NOT NULL,
  `guardiancontact` VARCHAR(20)  NOT NULL,
  `elemschool`      VARCHAR(100) NOT NULL,
  `elemyear`        VARCHAR(4)   NOT NULL,
  `hsschool`        VARCHAR(100) NOT NULL,
  `hsyear`          VARCHAR(4)   NOT NULL,
  `lastschool`      VARCHAR(100) NOT NULL DEFAULT '',
  `lastyear`        VARCHAR(4)   NOT NULL DEFAULT '',
  `lastaddress`     VARCHAR(150) NOT NULL DEFAULT '',
  `requirements`    VARCHAR(255) NOT NULL DEFAULT '',
  `submitted_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reviewed_by`     INT(11)      NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uniq_ref_no`  (`ref_no`),
  UNIQUE KEY `uniq_account` (`account_id`),
  KEY `idx_status_date` (`status`, `submitted_at`),
  KEY `idx_name_birth`  (`lname`, `fname`, `birthdate`),
  CONSTRAINT `fk_app_account`  FOREIGN KEY (`account_id`)  REFERENCES `usertbl`    (`ID`) ON DELETE SET NULL,
  CONSTRAINT `fk_app_student`  FOREIGN KEY (`student_id`)  REFERENCES `studenttbl` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `fk_app_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `usertbl`    (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
