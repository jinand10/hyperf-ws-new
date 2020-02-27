DROP TABLE IF EXISTS `page_record`;
CREATE TABLE `page_record` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `page` varchar(30) NOT NULL DEFAULT '' COMMENT '页面',
  `uid` int(11) DEFAULT NULL COMMENT '用户ID',
  `entry_time` int(11) NOT NULL DEFAULT '0' COMMENT '进入时间',
  `leave_time` int(11) NOT NULL DEFAULT '0' COMMENT '离开时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='页面记录表';
