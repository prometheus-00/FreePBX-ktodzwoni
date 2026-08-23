USE asterisk;

CREATE TABLE IF NOT EXISTS live_calls (
    uniqueid varchar(64) NOT NULL,
    linkedid varchar(64) DEFAULT NULL,
    channel varchar(255) DEFAULT NULL,
    number varchar(32) NOT NULL,
    callerid varchar(255) DEFAULT NULL,
    started_at datetime NOT NULL,
    PRIMARY KEY (uniqueid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS call_history (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    uniqueid varchar(64) NOT NULL,
    number varchar(32) NOT NULL,
    callerid varchar(255) DEFAULT NULL,
    display_name varchar(255) DEFAULT NULL,
    first_called_at datetime NOT NULL,
    last_called_at datetime NOT NULL,
    call_count int(10) unsigned NOT NULL DEFAULT 1,
    note text DEFAULT NULL,
    sentiment enum('positive','neutral','negative') DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniqueid (uniqueid),
    KEY number (number),
    KEY last_called_at (last_called_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS odebractelefon_cache (
    number varchar(32) NOT NULL,
    callerid varchar(255) DEFAULT NULL,
    rating varchar(32) DEFAULT NULL,
    main_category varchar(255) DEFAULT NULL,
    positive int(11) NOT NULL DEFAULT 0,
    negative int(11) NOT NULL DEFAULT 0,
    neutral int(11) NOT NULL DEFAULT 0,
    total int(11) NOT NULL DEFAULT 0,
    categories text DEFAULT NULL,
    has_data tinyint(1) NOT NULL DEFAULT 0,
    checked_at datetime NOT NULL,
    PRIMARY KEY (number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
