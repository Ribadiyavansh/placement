-- Migration script to rename certificate_url to certificate_file
-- Run this script to update your existing database

ALTER TABLE `resume_certifications` 
CHANGE COLUMN `certificate_url` `certificate_file` VARCHAR(255) DEFAULT NULL;
