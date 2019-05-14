/*
Navicat MySQL Data Transfer

Source Server         : 127.0.0.1
Source Server Version : 50505
Source Host           : 127.0.0.1:3306
Source Database       : test

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2019-05-14 11:12:46
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for contract
-- ----------------------------
DROP TABLE IF EXISTS `contract`;
CREATE TABLE `contract` (
  `contract_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `launch_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发起时间',
  `flow_id` varchar(50) NOT NULL DEFAULT '' COMMENT '流程审批id',
  `contract_no` varchar(100) NOT NULL DEFAULT '' COMMENT '合同编号',
  `erp_contract_no` varchar(100) NOT NULL DEFAULT '' COMMENT 'ERP合同编号',
  `customer` varchar(50) NOT NULL DEFAULT '' COMMENT '客户单位',
  `final_customer` varchar(50) NOT NULL DEFAULT '' COMMENT '调整后客户单位',
  `agency` varchar(50) NOT NULL DEFAULT '' COMMENT '4A代理公司',
  `agency_type` varchar(20) NOT NULL DEFAULT '' COMMENT '代理政策类型',
  `mian_brand` varchar(100) NOT NULL DEFAULT '' COMMENT '主品牌',
  `brand` varchar(100) NOT NULL DEFAULT '' COMMENT '品牌',
  `brand_type` varchar(50) NOT NULL DEFAULT '' COMMENT '品牌行业分类',
  `is_new` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否新品 0：否 1：是',
  `channel` varchar(50) NOT NULL DEFAULT '' COMMENT '渠道组',
  `channel_manager` varchar(50) NOT NULL DEFAULT '' COMMENT '渠道经理',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '合同开始时间',
  `end_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '合同结束时间',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单价',
  `put_volume` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '投放量',
  `amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '合同金额',
  `final_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '合同结算金额',
  `balance_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '结算余额',
  `balance` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '可用结算余额',
  `ad_type` varchar(50) NOT NULL DEFAULT '' COMMENT '广告类型',
  `item_level` varchar(50) NOT NULL COMMENT '本土项目级别',
  `local_new` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本土新品奖励%',
  `local_fund` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本土按时垫资奖励%',
  `local_special` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本土特殊比例%',
  `agency_base` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A基础比例%',
  `agency_fund` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A按时垫资奖励%',
  `agency_special` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A特殊比例%',
  `agency_fee` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A集团代理服务费比例%',
  `content` text NOT NULL COMMENT '合作内容',
  `payment` text NOT NULL COMMENT '付款约定',
  `remark` text NOT NULL COMMENT '备注',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `total_receipt_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '到款总计',
  `mortgage_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '冲抵金额 结算余额冲抵其他合同总额',
  `charge_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '充抵金额 其他合同余额冲抵总额',
  `overdue_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '逾期金额',
  `agency_fee_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '累计代理服务费',
  `duty_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '累计权责',
  `is_erp` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否录入ERP 0:否 1：是',
  PRIMARY KEY (`contract_id`),
  UNIQUE KEY `idx_contract_no` (`contract_no`) USING BTREE,
  KEY `idx_flow_id` (`flow_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='合同基本信息表';

-- ----------------------------
-- Table structure for contract_direct
-- ----------------------------
DROP TABLE IF EXISTS `contract_direct`;
CREATE TABLE `contract_direct` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `contract_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '合同id',
  `direct_group` varchar(50) NOT NULL DEFAULT '' COMMENT '直客组',
  `direct_manager` varchar(50) NOT NULL DEFAULT '' COMMENT '直客经理',
  `rate` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '拆分比例',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_contract_id` (`contract_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='合同直客信息表';

-- ----------------------------
-- Table structure for contract_duty
-- ----------------------------
DROP TABLE IF EXISTS `contract_duty`;
CREATE TABLE `contract_duty` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `contract_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '合同id',
  `duty_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '权责总额',
  `duty1` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '1期权责',
  `duty2` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '2期权责',
  `duty3` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '3期权责',
  `duty4` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4期权责',
  `duty5` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '5期权责',
  `duty6` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '6期权责',
  `duty7` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '7期权责',
  `duty8` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '8期权责',
  `duty9` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '9期权责',
  `duty10` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '10期权责',
  `duty11` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '11期权责',
  `duty12` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '12期权责',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `_contract_id` (`contract_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for contract_expect
-- ----------------------------
DROP TABLE IF EXISTS `contract_expect`;
CREATE TABLE `contract_expect` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `contract_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '合同id',
  `expect_date` date NOT NULL COMMENT '应收日期',
  `expect_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '应收金额',
  `is_return` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否已返点 0：否 1：是',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_contract_id_date` (`contract_id`,`expect_date`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='合同应收信息表';

-- ----------------------------
-- Table structure for contract_receipt
-- ----------------------------
DROP TABLE IF EXISTS `contract_receipt`;
CREATE TABLE `contract_receipt` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `contract_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '合同id',
  `expect_date` date NOT NULL COMMENT '应收日期',
  `expect_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '应收金额',
  `receipt_date` date NOT NULL COMMENT '到账日期',
  `receipt_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '到账金额',
  `receipt_type` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '到账类型 1：现金 2：冲抵',
  `contract_no` varchar(100) NOT NULL DEFAULT '' COMMENT '冲抵合同编号',
  `is_return` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否已返点 0:否 1：是',
  `local_new` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本土新品奖励比例%',
  `local_new_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本土新品奖励',
  `local_fund` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本土按时垫资奖励比例%',
  `local_fund_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本土按时垫资奖励',
  `local_special` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本土特殊比例%',
  `local_special_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本土特殊返点金额',
  `agency_base` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A基础比例%',
  `agency_base_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A基础返点金额',
  `agency_fund` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A按时垫资奖励比例%',
  `agency_fund_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A按时垫资奖励',
  `agency_special` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A特殊比例%',
  `agency_special_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A特殊返点金额',
  `agency_fee` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A集团代理服务费比例%',
  `agency_fee_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '4A集团代理服务费金额',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_contract_id_date` (`contract_id`,`expect_date`,`receipt_date`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='合同到账信息表';
