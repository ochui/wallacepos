-- FreePOS Database Installation Schema
-- This file creates the basic database structure for FreePOS

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Table structure for table `auth`
CREATE TABLE `auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) NOT NULL,
  `password` varchar(255) NOT NULL,
  `uuid` varchar(45) DEFAULT NULL,
  `admin` int(1) NOT NULL DEFAULT '0',
  `permissions` text,
  `disabled` int(1) NOT NULL DEFAULT '0',
  `token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Default admin user (admin/admin)
INSERT INTO `auth` (`id`, `username`, `password`, `uuid`, `admin`, `permissions`, `disabled`) VALUES
(1, 'admin', '$2y$10$BUKJnOhjFJxnrRE4FQAzu.eo8Nk8Ynk0F4UZl.QqYVVqOyjd/HGvW', NULL, 2, NULL, 0),
(2, 'staff', '$2y$10$BUKJnOhjFJxnrRE4FQAzu.eo8Nk8Ynk0F4UZl.QqYVVqOyjd/HGvW', NULL, 0, NULL, 1);

-- Table structure for table `config`
CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `data` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `customers`
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `suburb` varchar(255) DEFAULT NULL,
  `postcode` varchar(10) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `googleid` varchar(255) DEFAULT NULL,
  `dt` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `customer_contacts`
CREATE TABLE `customer_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerid` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `receivesinv` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `customerid` (`customerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `locations`
CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Default location
INSERT INTO `locations` (`id`, `name`) VALUES (1, 'Main Location');

-- Table structure for table `devices`
CREATE TABLE `devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `locationid` int(11) NOT NULL DEFAULT '1',
  `data` longtext,
  PRIMARY KEY (`id`),
  KEY `locationid` (`locationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `device_map`
CREATE TABLE `device_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deviceid` int(11) NOT NULL,
  `type` varchar(45) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `deviceid` (`deviceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `stored_categories`
CREATE TABLE `stored_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `dt` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `stored_suppliers`
CREATE TABLE `stored_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `dt` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `stored_items`
CREATE TABLE `stored_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` longtext,
  `supplierid` int(11) DEFAULT NULL,
  `categoryid` int(11) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `supplierid` (`supplierid`),
  KEY `categoryid` (`categoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `sales`
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(45) DEFAULT NULL,
  `type` varchar(45) NOT NULL DEFAULT 'sale',
  `channel` varchar(45) NOT NULL DEFAULT 'pos',
  `data` longtext,
  `userid` int(11) NOT NULL,
  `deviceid` int(11) NOT NULL,
  `locationid` int(11) NOT NULL,
  `custid` int(11) DEFAULT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `rounding` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` int(1) NOT NULL DEFAULT '1',
  `processdt` bigint(20) NOT NULL,
  `duedt` datetime DEFAULT NULL,
  `dt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `deviceid` (`deviceid`),
  KEY `locationid` (`locationid`),
  KEY `custid` (`custid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `sale_items`
CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `saleid` int(11) NOT NULL,
  `storeditemid` int(11) DEFAULT NULL,
  `saleitemid` varchar(45) DEFAULT NULL,
  `qty` decimal(10,3) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `taxid` int(11) DEFAULT NULL,
  `tax` longtext,
  `tax_incl` int(1) NOT NULL DEFAULT '0',
  `tax_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit_original` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `refundqty` decimal(10,3) NOT NULL DEFAULT '0.000',
  PRIMARY KEY (`id`),
  KEY `saleid` (`saleid`),
  KEY `storeditemid` (`storeditemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `sale_payments`
CREATE TABLE `sale_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `saleid` int(11) NOT NULL,
  `method` varchar(45) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `processdt` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `saleid` (`saleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `sale_voids`
CREATE TABLE `sale_voids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `saleid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `deviceid` int(11) NOT NULL,
  `locationid` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `method` varchar(45) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `items` longtext,
  `void` int(1) NOT NULL DEFAULT '0',
  `processdt` bigint(20) NOT NULL,
  `dt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `saleid` (`saleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `sale_history`
CREATE TABLE `sale_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `saleid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `type` varchar(45) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `dt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `saleid` (`saleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `stock_levels`
CREATE TABLE `stock_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `storeditemid` int(11) NOT NULL,
  `locationid` int(11) NOT NULL,
  `stocklevel` decimal(10,3) NOT NULL DEFAULT '0.000',
  `dt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_location` (`storeditemid`,`locationid`),
  KEY `storeditemid` (`storeditemid`),
  KEY `locationid` (`locationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `stock_history`
CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `storeditemid` int(11) NOT NULL,
  `locationid` int(11) NOT NULL,
  `auxid` int(11) DEFAULT NULL,
  `auxdir` varchar(45) DEFAULT NULL,
  `type` varchar(45) NOT NULL,
  `amount` decimal(10,3) NOT NULL,
  `dt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `storeditemid` (`storeditemid`),
  KEY `locationid` (`locationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `tax_rules`
CREATE TABLE `tax_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `tax_items`
CREATE TABLE `tax_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `altname` varchar(255) DEFAULT NULL,
  `type` varchar(45) NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `multiplier` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Default tax item (10% GST)
INSERT INTO `tax_items` (`id`, `name`, `altname`, `type`, `value`, `multiplier`) VALUES
(1, 'GST', 'GST', 'percentage', 10.00, 0);

-- Default tax rule
INSERT INTO `tax_rules` (`id`, `data`) VALUES
(1, '{"id":1,"name":"Standard","inclusive":true,"items":[{"id":1,"name":"GST","altname":"GST","type":"percentage","value":10,"multiplier":0}]}');