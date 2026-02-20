-- Run this once in phpMyAdmin or MySQL CLI against the kdpatt database

CREATE TABLE IF NOT EXISTS `tutmapping` (
    `id`          INT          NOT NULL AUTO_INCREMENT,
    `faculty`     VARCHAR(50)  NOT NULL COMMENT 'faculty.id',
    `term`        VARCHAR(20)  NOT NULL,
    `sem`         VARCHAR(10)  NOT NULL,
    `subject`     VARCHAR(100) NOT NULL,
    `tutBatch`    VARCHAR(30)  NOT NULL,
    `slot`        VARCHAR(50)  NOT NULL,
    `start_date`  DATE         NOT NULL,
    `end_date`    DATE         NOT NULL,
    `repeat_days` VARCHAR(20)  NOT NULL COMMENT 'comma-separated day numbers: 0=Sun,1=Mon,...,6=Sat',
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
