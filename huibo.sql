DROP TABLE IF EXISTS `page_record`;
CREATE TABLE `page_record` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `ower_id` int(11) DEFAULT NULL COMMENT '商家ID',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `share_user_id` int(11) DEFAULT NULL COMMENT '分享人ID',
  `model` varchar(30) NOT NULL DEFAULT '' COMMENT '页面标识',
  `content_id` int(11) NOT NULL DEFAULT '0' COMMENT '内容ID',
  `url` varchar(100) NOT NULL DEFAULT '' COMMENT '页面链接',
  `is_deal` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否成交 1是 0否',
  `order_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '订单类型',
  `entry_time` int(11) NOT NULL DEFAULT '0' COMMENT '进入时间',
  `leave_time` int(11) NOT NULL DEFAULT '0' COMMENT '离开时间',
  `stay_time` int(11) NOT NULL DEFAULT '0' COMMENT '停留时间 单位秒',
  PRIMARY KEY (`id`),
  KEY `idx_ower_user`(`ower_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='页面记录表';