ALTER TABLE `hw_terms`
ADD COLUMN `type`  tinyint NULL DEFAULT 1 AFTER `listorder`;

-- 20170621
ALTER TABLE `hw_posts` CHANGE `author_id` `user_id` BIGINT(20) UNSIGNED NULL DEFAULT '0' COMMENT '发表者id';
-- 20170621 文章原作者
ALTER TABLE `hw_posts` ADD `author` VARCHAR(255) NULL AFTER `user_id`;
-- 20170707
ALTER TABLE `hw_oauth_user` CHANGE `access_token` `access_token` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

-- 20170713
ALTER TABLE `hw_nav` ADD `sublabel` VARCHAR(255) NULL AFTER `label`;

-- 201718
ALTER TABLE `hw_posts` ADD `post_file_path` VARCHAR(255) NOT NULL AFTER `post_type`;

-- 20170720
ALTER TABLE `hw_posts` ADD `post_tag` VARCHAR(255) NULL DEFAULT '' AFTER `post_title`;

-- 20170724
ALTER TABLE `hw_user_notification` ADD `from_uid` INT NULL DEFAULT '0' COMMENT '为0时是系统消息' AFTER `uid`;
ALTER TABLE `hw_user_notification` ADD `createtime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `is_unread`;
ALTER TABLE `hw_posts` CHANGE `post_file_path` `post_file_path` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

-- 20170803
ALTER TABLE `hw_terms` ADD `sublabel` VARCHAR(100) NULL AFTER `slug`;
UPDATE hw_terms t,hw_nav n SET t.sublabel=n.sublabel WHERE t.`name`=n.label;

-- 20170809
ALTER TABLE `hw_comments` ADD `comment_dislike` INT(11) NULL DEFAULT '0' AFTER `comment_like`;

-- 20170811
ALTER TABLE `hw_terms` ADD `icon` VARCHAR(255) NULL AFTER `sublabel`;
UPDATE hw_terms t,hw_nav n SET t.icon=n.icon WHERE t.`name`=n.label;

-- 20170815
ALTER TABLE `hw_posts` ADD `serial_number` INT NOT NULL DEFAULT '0' AFTER `smeta`;

-- 20170821
ALTER TABLE `hw_posts_images` ADD `file_name` VARCHAR(100) NULL AFTER `user_id`;

-- 20170829
ALTER TABLE `hw_posts` ADD `is_ad` INT(2) NULL DEFAULT '0' COMMENT '0不是广告，1是广告' AFTER `recommended`;

-- 20170911
ALTER TABLE `hw_users` ADD `accept_month_email_push_status` TINYINT(2) NULL DEFAULT '0' AFTER `mobile`, ADD `accept_activity_email_push_status` TINYINT(2) NULL DEFAULT '0' AFTER `accept_month_email_push_status`;

-- 20170914
ALTER TABLE `hw_posts` ADD `author_id` INT NULL DEFAULT '0' AFTER `author`;

-- 20170919
ALTER TABLE `hw_posts` ADD `publisher` VARCHAR(100) NULL AFTER `is_ad`;

-- 20170927
ALTER TABLE `hw_posts` ADD `post_source_date` DATETIME NULL AFTER `post_source`;

-- 20171011
ALTER TABLE `hw_terms` ADD `cover_icon` VARCHAR(255) NULL AFTER `icon`;

-- 20171106
ALTER TABLE `hw_user_notification` ADD `thumb` VARCHAR(255) NULL DEFAULT '' AFTER `title`;