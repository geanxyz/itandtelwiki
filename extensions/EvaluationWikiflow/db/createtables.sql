--
-- MySQL
--
CREATE TABLE `ew_waiting` (
	`eww_id` int unsigned NOT NULL auto_increment,
	`eww_page_id` int unsigned NOT NULL,
	`eww_namespace_id` int NOT NULL,
	`eww_page_title` varchar(255) character set latin1 collate latin1_bin NOT NULL,
	`eww_revision_id` int unsigned NOT NULL,
	`eww_user_id` int unsigned NOT NULL,
	`eww_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL,
	`eww_pending` boolean default true,
	`eww_timestamp` varbinary(14) NOT NULL,
	PRIMARY KEY (`eww_page_id`,`eww_revision_id`),
	UNIQUE KEY `eww_id` (`eww_id`),
	KEY `eww_timestamp` (`eww_timestamp`),
	KEY `page_pending` (`eww_page_id`,`eww_pending`),
	KEY `user_page` (`eww_user_id`,`eww_page_id`),
	KEY `pending_timestamp` (`eww_pending`,`eww_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 MAX_ROWS=10000000 AVG_ROW_LENGTH=1024 AUTO_INCREMENT=1 ;

CREATE TABLE `ew_assigned` (
	`ewa_id` int unsigned NOT NULL auto_increment,
	`ewa_waiting_id` int unsigned NOT NULL DEFAULT '0',
	`ewa_page_id` int unsigned NOT NULL,
	`ewa_namespace_id` int NOT NULL,
	`ewa_page_title` varchar(255) character set latin1 collate latin1_bin NOT NULL,
	`ewa_revision_id` int NOT NULL,
	`ewa_reviewer_id` int unsigned NOT NULL,
	`ewa_reviewer_text` varchar(255) character set latin1 collate latin1_bin NOT NULL,
	`ewa_manager_id` int unsigned NOT NULL,
	`ewa_manager_text` varchar(255) character set latin1 collate latin1_bin NOT NULL,
	`ewa_pending` boolean default true,
	`ewa_timestamp` varbinary(14) NOT NULL,
	PRIMARY KEY (`ewa_id`),
	KEY `ewa_timestamp` (`ewa_timestamp`),
	KEY `page_reviewer` (`ewa_page_id`,`ewa_reviewer_id`),
	KEY `reviewer_pending` (`ewa_reviewer_id`,`ewa_pending`),
	KEY `page_pending` (`ewa_page_id`,`ewa_pending`),
	KEY `pending_timestamp` (`ewa_pending`,`ewa_timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 MAX_ROWS=10000000 AVG_ROW_LENGTH=1024 AUTO_INCREMENT=1 ;

CREATE TABLE `ew_review` (
	`ewr_id` int unsigned NOT NULL auto_increment,
	`ewr_waiting_id` int unsigned NOT NULL DEFAULT '0',
	`ewr_assigned_id` int unsigned NOT NULL DEFAULT '0',
	`ewr_review_id` int unsigned NOT NULL,
	`ewr_about_id` int unsigned NOT NULL,
	`ewr_aboutrevision_id` int NOT NULL,
	`ewr_reviewer_id` int unsigned NOT NULL,
	`ewr_reviewer_text` varchar(255) character set latin1 collate latin1_bin NOT NULL,
	`ewr_timestamp` varbinary(14) NOT NULL,
	PRIMARY KEY (`ewr_id`),
	KEY `ewr_timestamp` (`ewr_timestamp`),
	KEY `review_reviewer` (`ewr_review_id`,`ewr_reviewer_id`),
	KEY `about_revision` (`ewr_about_id`,`ewr_aboutrevision_id`),
	KEY `about_timestamp` (`ewr_about_id`,`ewr_timestamp`),
	KEY `waiting_assigned_review` (`ewr_waiting_id`,`ewr_assigned_id`,`ewr_review_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 MAX_ROWS=10000000 AVG_ROW_LENGTH=1024 AUTO_INCREMENT=1 ;

CREATE TABLE `ew_certified` (
	`ewc_id` int unsigned NOT NULL auto_increment,
	`ewc_old_namespace_id` int NOT NULL,
	`ewc_new_namespace_id` int NOT NULL,
	`ewc_old_page_id` int unsigned NOT NULL,
	`ewc_new_page_id` int unsigned NOT NULL,
	`ewc_old_revision_id` int NOT NULL,
	`ewc_new_revision_id` int NOT NULL,
	`ewc_old_page_title` varchar(255) character set latin1 collate latin1_bin NOT NULL,
	`ewc_new_page_title` varchar(255) character set latin1 collate latin1_bin NOT NULL,
	`ewc_manager_id` int unsigned NOT NULL,
	`ewc_manager_text` varchar(255) character set latin1 collate latin1_bin NOT NULL,
	`ewc_timestamp` varbinary(14) NOT NULL,
	UNIQUE KEY ( `ewc_id` ),
	UNIQUE KEY ( `ewc_old_page_id` ),
	KEY `ewc_timestamp` ( `ewc_timestamp` ),
	KEY `page_manager` ( `ewc_old_page_id` , `ewc_new_page_id`, `ewc_manager_id` ),
	KEY `old_new_revision` ( `ewc_old_revision_id` , `ewc_new_revision_id` )
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 MAX_ROWS=10000000 AVG_ROW_LENGTH=1024 AUTO_INCREMENT=1 ;