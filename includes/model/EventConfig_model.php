<?php

use Engelsystem\Database\DB;

/**
 * Get event config.
 *
 * @return array|null
 */
function EventConfig()
{
    return DB::selectOne('SELECT * FROM `EventConfig` LIMIT 1');
}

/**
 * Update event config.
 *
 * @param string $event_name
 * @param int    $buildup_start_date
 * @param int    $event_start_date
 * @param int    $event_end_date
 * @param int    $teardown_end_date
 * @param string $event_welcome_msg
 * @return int Rows updated
 */
function EventConfig_update(
    $event_name,
    $buildup_start_date,
    $event_start_date,
    $event_end_date,
    $teardown_end_date,
    $event_welcome_msg
) {
    if (EventConfig() == null) {
        return DB::insert('
              INSERT INTO `EventConfig` (
                  `event_name`,
                  `buildup_start_date`,
                  `event_start_date`,
                  `event_end_date`,
                  `teardown_end_date`,
                  `event_welcome_msg`
              )
              VALUES (?, ?, ?, ?, ?, ?)
            ',
            [
                $event_name,
                $buildup_start_date,
                $event_start_date,
                $event_end_date,
                $teardown_end_date,
                $event_welcome_msg
            ]
        );
    }

    return DB::update('
          UPDATE `EventConfig` SET
          `event_name` = ?,
          `buildup_start_date` = ?,
          `event_start_date` = ?,
          `event_end_date` = ?,
          `teardown_end_date` = ?,
          `event_welcome_msg` = ?
        ',
        [
            $event_name,
            $buildup_start_date,
            $event_start_date,
            $event_end_date,
            $teardown_end_date,
            $event_welcome_msg,
        ]
    );
}
