<?php

function getUpdates25_03_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark - Grove

		//katherine - Grove

		'add_series_settings' => [
			'title' => 'Add Series Search settings to Library Systems',
			'description' => 'Add Series Search settings to Library Systems',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE library ADD COLUMN useSeriesSearchIndex TINYINT(1) DEFAULT 0"
			]
		], //add_series_settings
		'add_series_search_tables' => [
			'title' => 'Add Series Search tables',
			'description' => 'Add Series Search tables',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE series (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					groupedWorkSeriesTitle VARCHAR(500),
					displayName VARCHAR(500),
					author VARCHAR(500),
					description TEXT,
					audience VARCHAR(25),
					isIndexed TINYINT(1) DEFAULT 1,
					deleted TINYINT(1) DEFAULT 0,
					dateUpdated INT(11),
					created INT(11)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE series_member (
    				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    				seriesId INT NOT NULL,
    				isPlaceholder TINYINT(1) DEFAULT 0,
					groupedWorkPermanentId CHAR(40),
					displayName VARCHAR(500),
					author VARCHAR(200),
					description TEXT,
					cover VARCHAR(50),
					volume VARCHAR(50),
					pubDate INT,
					weight INT NOT NULL DEFAULT 0
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE series_indexing_log (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					startTime INT(11) NOT NULL,
					endTime INT(11) DEFAULT NULL,
					lastUpdate INT(11) DEFAULT NULL,
					notes MEDIUMTEXT DEFAULT NULL,
					numSeries INT(11) DEFAULT 0,
					numAdded INT(11) DEFAULT 0,
					numDeleted INT(11) DEFAULT 0,
					numUpdated INT(11) DEFAULT 0,
					numSkipped INT(11) DEFAULT 0,
					numErrors INT(11) DEFAULT 0
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE series_indexing_settings (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					runFullUpdate TINYINT(1) DEFAULT 1,
					lastUpdateOfChangedSeries INT(11) DEFAULT 0,
					lastUpdateOfAllSeries INT(11) DEFAULT 0
    			) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"INSERT INTO series_indexing_settings VALUES (1,1,0,0);",
			]
		], //add_series_tables

		//kirstien - Grove

		// Leo Stoyanov - BWS

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}