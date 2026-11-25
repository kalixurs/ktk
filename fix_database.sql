-- ONLY needed if you already imported an OLDER copy of "online_enroll (2).sql".
-- A fresh import of the updated "online_enroll (2).sql" already includes all of this.
-- Run in phpMyAdmin with the `online_enroll` database selected.
ALTER TABLE `usertbl` MODIFY `username` VARCHAR(50) NOT NULL;
ALTER TABLE `usertbl` MODIFY `password` VARCHAR(255) NOT NULL;   -- was VARCHAR(11): too small for a password hash
ALTER TABLE `usertbl` MODIFY `fullname` VARCHAR(150) NOT NULL;
ALTER TABLE `studenttbl` MODIFY `studnum` VARCHAR(20) NOT NULL;
ALTER TABLE `studenttbl` MODIFY `contact` VARCHAR(20) NOT NULL;
ALTER TABLE `studenttbl` MODIFY `emailaddress` VARCHAR(100) NOT NULL;
ALTER TABLE `usertbl` ADD UNIQUE KEY `uniq_username` (`username`);
ALTER TABLE `studenttbl` ADD COLUMN `suspended_until` DATE NULL, ADD COLUMN `suspend_reason` VARCHAR(255) NOT NULL DEFAULT '';  -- (also added automatically by conn.php)
