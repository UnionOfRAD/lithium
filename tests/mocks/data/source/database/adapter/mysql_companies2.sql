CREATE TABLE IF NOT EXISTS `companies2` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `active` tinyint(1),
  `name` varchar(255),
  `created` datetime,
  `modified` datetime
);
