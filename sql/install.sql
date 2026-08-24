USE asterisk;

CREATE TABLE IF NOT EXISTS live_calls (
 uniqueid varchar(64) NOT NULL, linkedid varchar(64) DEFAULT NULL, channel varchar(255) NOT NULL, number varchar(32) NOT NULL, callerid varchar(255) DEFAULT NULL, started_at datetime NOT NULL, PRIMARY KEY(uniqueid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS call_history (
 id bigint unsigned NOT NULL AUTO_INCREMENT, uniqueid varchar(64) NOT NULL, number varchar(32) NOT NULL, callerid varchar(255) DEFAULT NULL, display_name varchar(255) DEFAULT NULL, note text DEFAULT NULL, sentiment enum('positive','negative','neutral') DEFAULT NULL, cisco_directory tinyint(1) NOT NULL DEFAULT 0, first_called_at datetime NOT NULL, last_called_at datetime NOT NULL, call_count int unsigned NOT NULL DEFAULT 1, PRIMARY KEY(id), UNIQUE KEY uq_number(number), KEY idx_last_called(last_called_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
