<?php
$pageTitle = "FL - REPORTY-Sloty";

require_once "checkLogin.php";
require_once "../header.php";
require_once "../php/class.settings.php";


if (isset($_GET["shift"])) {
    $shift = $_GET["shift"];    
} else {
    $shift = 0;
}

?>

<div class="container-fluid" id="administrace-rezervaci">    
    <?php include "menu.php" ?>                               
    <?php

        $query = " (
            SELECT
                `al`.`displayName` AS `displayName`,
                        `al`.`orderRank` AS `orderRank`,
                (
                SELECT
                    COUNT(`pat`.`id`)
                FROM
                    `fyziolandc`.`personAvailabilityTimetable` `pat`
                WHERE
                    `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY AND ! EXISTS(
                    SELECT
                        `r`.`id`
                    FROM
                        `fyziolandc`.`reservations` `r`
                    WHERE
                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                        ) = `pat`.`time`
                    LIMIT 1
                )) AS `availableSlots1W`,(
                    SELECT
                        COUNT(`pat`.`id`)
                    FROM
                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                    WHERE
                        `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY AND ! EXISTS(
                        SELECT
                            `r`.`id`
                        FROM
                            `fyziolandc`.`reservations` `r`
                        WHERE
                            `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                            ) = `pat`.`time`
                        LIMIT 1
                    )) AS `availableSlots1W2W`,(
                        SELECT
                            COUNT(`pat`.`id`)
                        FROM
                            `fyziolandc`.`personAvailabilityTimetable` `pat`
                        WHERE
                            `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY AND ! EXISTS(
                            SELECT
                                `r`.`id`
                            FROM
                                `fyziolandc`.`reservations` `r`
                            WHERE
                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                ) = `pat`.`time`
                            LIMIT 1
                        )) AS `availableSlots2W3W`,(
                            SELECT
                                COUNT(`pat`.`id`)
                            FROM
                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                            WHERE
                                `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND ! EXISTS(
                                SELECT
                                    `r`.`id`
                                FROM
                                    `fyziolandc`.`reservations` `r`
                                WHERE
                                    `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                        CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                    ) = `pat`.`time`
                                LIMIT 1
                            )) AS `availableSlots3W4W`,(
                                SELECT
                                    COUNT(`pat`.`id`)
                                FROM
                                    `fyziolandc`.`personAvailabilityTimetable` `pat`
                                WHERE
                                    `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND ! EXISTS(
                                    SELECT
                                        `r`.`id`
                                    FROM
                                        `fyziolandc`.`reservations` `r`
                                    WHERE
                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                        ) = `pat`.`time`
                                    LIMIT 1
                                )) AS `availableSlots1M`,(
                                    SELECT
                                        COUNT(`pat`.`id`)
                                    FROM
                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                    WHERE
                                        `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY AND ! EXISTS(
                                        SELECT
                                            `r`.`id`
                                        FROM
                                            `fyziolandc`.`reservations` `r`
                                        WHERE
                                            `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                            ) = `pat`.`time`
                                        LIMIT 1
                                    )) AS `availableSlots4W5W`,(
                                        SELECT
                                            COUNT(`pat`.`id`)
                                        FROM
                                            `fyziolandc`.`personAvailabilityTimetable` `pat`
                                        WHERE
                                            `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY AND ! EXISTS(
                                            SELECT
                                                `r`.`id`
                                            FROM
                                                `fyziolandc`.`reservations` `r`
                                            WHERE
                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                ) = `pat`.`time`
                                            LIMIT 1
                                        )) AS `availableSlots5W6W`,(
                                            SELECT
                                                COUNT(`pat`.`id`)
                                            FROM
                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                            WHERE
                                                `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY AND ! EXISTS(
                                                SELECT
                                                    `r`.`id`
                                                FROM
                                                    `fyziolandc`.`reservations` `r`
                                                WHERE
                                                    `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                        CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                    ) = `pat`.`time`
                                                LIMIT 1
                                            )) AS `availableSlots6W7W`,(
                                                SELECT
                                                    COUNT(`pat`.`id`)
                                                FROM
                                                    `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                WHERE
                                                    `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots7W8W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY AND ! EXISTS(
                                                        SELECT
                                                            `r`.`id`
                                                        FROM
                                                            `fyziolandc`.`reservations` `r`
                                                        WHERE
                                                            `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                            ) = `pat`.`time`
                                                        LIMIT 1
                                                    )) AS `availableSlots2M`
                                                    FROM
                                                        `fyziolandc`.`adminLogin` `al`
                                                    WHERE
                                                        `al`.`active` = 1 AND `al`.`indiv` = 1)
                                                    UNION
                                                        (
                                                        SELECT
                                                            'ERGO ALL' AS `ERGO ALL`,
                                                                                                                '99' as orderRank,
                                                            (
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY  AND CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots1W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots1W2W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots2W3W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots3W4W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots1M`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots4W5W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots5W6W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots6W7W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots7W8W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots2M`
                                                        FROM
                                                            `fyziolandc`.`adminLogin` `al`
                                                        WHERE
                                                            `al`.`active` = 1 AND `al`.`indiv` = 1 AND `al`.`isErgo` = 1)
                                                        UNION
                                                        (
                                                        SELECT
                                                            'FYZIO ALL' AS `FYZIO ALL`,
                                                            '5' as orderRank,
                                                            (
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY  AND CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots1W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots1W2W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots2W3W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots3W4W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots1M`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots4W5W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots5W6W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots6W7W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots7W8W`,(
                                                            SELECT
                                                                COUNT(`pat`.`id`)
                                                            FROM
                                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                            WHERE
                                                                `pat`.`person` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY AND ! EXISTS(
                                                            SELECT
                                                                `r`.`id`
                                                            FROM
                                                                `fyziolandc`.`reservations` `r`
                                                            WHERE
                                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                                ) = `pat`.`time`
                                                            LIMIT 1
                                                        )) AS `availableSlots2M`
                                                        FROM
                                                            `fyziolandc`.`adminLogin` `al`
                                                        WHERE
                                                            `al`.`active` = 1 AND `al`.`indiv` = 1 AND `al`.`isFyzio` = 1)
                                                        ORDER BY
                                                            orderRank ASC";

        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":shift", $shift, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);
        ?>                         
            
        <div class="col-lg-10 col-lg-offset-1">
            <h2 class="text-center">
                Počet VOLNÝCH slotů
            </h2>                                       
            <div class="row">
                <div class="col-lg-1 col-lg-offset-3" style = "margin-bottom: 10px; ">                
                    <button type="button" class="btn btn-secondary"  style="margin-top: 0px; margin-bottom: 0px;  margin-left:0px; width: 40%;text-align: center;" id="shiftMinus7"><</button>			
                    <button type="button" class="btn btn-secondary"  style="margin-top: 0px; margin-bottom: 0px;  margin-left:0px; width: 40%;text-align: center;" id="shiftPlus7">></button>                        
		</div>
                <div class="col-lg-1" style = "margin-bottom: 10px; ">                
                    <input type="text" class="form-control" name="datum" id="datum" style="width: 100px;" value="" >
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-lg-offset-3">
                    <table class="table table-bordered table-hover table-striped" id="TableVolneVstupy" style="horizontal-align: middle">
                        <thead>
                            <tr>
                                <th class="text-center" style="vertical-align: middle;">Pracovník</th>                                    
                                <th class="text-center" style="vertical-align: middle;">1W</th>
                                <th class="text-center" style="vertical-align: middle;">2W</th>
                                <th class="text-center" style="vertical-align: middle;">3W</th>
                                <th class="text-center" style="vertical-align: middle;">4W</th>
                                <th style="font-weight: bold; font-size:120%;" class="text-center" style="vertical-align: middle;">1M</th>
                                <th class="text-center" style="vertical-align: middle;">5W</th>
                                <th class="text-center" style="vertical-align: middle;">6W</th>
                                <th class="text-center" style="vertical-align: middle;">7W</th>
                                <th class="text-center" style="vertical-align: middle;">8W</th>
                                <th style="font-weight: bold; font-size:120%;" class="text-center" style="vertical-align: middle;">2M</th>   
                                <th class="text-center" style="vertical-align: middle;">Pořadí</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($results) > 0): ?>
                                <?php foreach ($results as $result): ?>
                                    <?php if ($result->displayName == 'ERGO ALL' || $result->displayName == 'FYZIO ALL'): ?>
                                        <tr data-id="<?= $result->id ?>" style="background-color: gray; color: white; font-weight: bold;">
                                    <?php else: ?>
                                         <tr data-id="<?= $result->id ?>">   
                                    <?php endif; ?>
                                        <td class="text-left"><?= $result->displayName ?></td>                                            
                                        <td class="text-center" data-order="<?= $result->availableSlots1W ?>"><?= number_format($result->availableSlots1W, 0, ",", " ") ?></td>
                                        <td class="text-center" data-order="<?= $result->availableSlots1W2W ?>"><?= number_format($result->availableSlots1W2W, 0, ",", " ") ?></td>
                                        <td class="text-center" data-order="<?= $result->availableSlots2W3W ?>"><?= number_format($result->availableSlots2W3W, 0, ",", " ") ?></td>
                                        <td class="text-center" data-order="<?= $result->availableSlots3W4W ?>"><?= number_format($result->availableSlots3W4W, 0, ",", " ") ?></td>
                                        <td style="font-weight: bold; font-size:120%;" class="text-center" data-order="<?= $result->availableSlots1M ?>"><?= number_format($result->availableSlots1M, 0, ",", " ") ?></td>
                                        <td class="text-center" data-order="<?= $result->availableSlots4W5W ?>"><?= number_format($result->availableSlots4W5W, 0, ",", " ") ?></td>
                                        <td class="text-center" data-order="<?= $result->availableSlots5W6W ?>"><?= number_format($result->availableSlots5W6W, 0, ",", " ") ?></td>
                                        <td class="text-center" data-order="<?= $result->availableSlots6W7W ?>"><?= number_format($result->availableSlots6W7W, 0, ",", " ") ?></td>
                                        <td class="text-center" data-order="<?= $result->availableSlots7W8W ?>"><?= number_format($result->availableSlots7W8W, 0, ",", " ") ?></td>                                    
                                        <td style="font-weight: bold; font-size:120%;" class="text-center" data-order="<?= $result->availableSlots2M ?>"><?= number_format($result->availableSlots2M, 0, ",", " ") ?></td>                                    
                                        <td style="color: gray; font-size:50%;" class="text-center"><?= $result->orderRank ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                    <tr><td colspan="11" class="text-center">Dotaz selhal.</td></tr>    
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>    
        
        <?php
        
            
            $query = "  (
            SELECT
                `al`.`displayName` AS `displayName`,
                `al`.`orderRank` AS `orderRank`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY) AS `usedSlots1W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY) AS `usedSlots1W2W`,        
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY) AS `usedSlots2W3W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY) AS `usedSlots3W4W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY) AS `usedSlots1M`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY) AS `usedSlots4W5W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY) AS `usedSlots5W6W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY) AS `usedSlots6W7W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY) AS `usedSlots7W8W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY) AS `usedSlots2M`

            FROM
                `adminLogin` `al`
            WHERE
                `al`.`active` = 1 AND `al`.`indiv` = 1
            ORDER BY
                `al`.`displayName`)

            UNION

            (SELECT
                'ERGO ALL' AS `displayName`,
                '98' as orderRank,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY) AS `usedSlots1W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY) AS `usedSlots1W2W`,        
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY) AS `usedSlots2W3W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            )AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY) AS `usedSlots3W4W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY) AS `usedSlots1M`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY) AS `usedSlots4W5W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY) AS `usedSlots5W6W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY) AS `usedSlots6W7W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY) AS `usedSlots7W8W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            )AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY) AS `usedSlots2M`

            FROM
                `adminLogin` `al`
            WHERE
                `al`.`active` = 1 AND `al`.`indiv` = 1
            )

        UNION

            (SELECT
                'FYZIO ALL' AS `displayName`,
                '5' as orderRank,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY) AS `usedSlots1W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY) AS `usedSlots1W2W`,        
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY) AS `usedSlots2W3W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            )AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY) AS `usedSlots3W4W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY) AS `usedSlots1M`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY) AS `usedSlots4W5W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY) AS `usedSlots5W6W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY) AS `usedSlots6W7W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY) AS `usedSlots7W8W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            )AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 + :shift DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 + :shift DAY) AS `usedSlots2M`

            FROM
                `adminLogin` `al`
            WHERE
                `al`.`active` = 1 AND `al`.`indiv` = 1
            ) ORDER BY orderRank

        ";

            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":shift", $shift, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);
            ?>
        
            <div class="col-lg-10 col-lg-offset-1">
                <h2 class="text-center">
                    Počet OBSAZENÝCH slotů
                </h2>                        

                <div class="row">
                    <div class="col-lg-6 col-lg-offset-3">
                        <table class="table table-bordered table-hover table-striped" id="tableObsazeneSloty" style="horizontal-align: middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="vertical-align: middle;">Pracovník</th>                                    
                                    <th class="text-center" style="vertical-align: middle;">1W</th>
                                    <th class="text-center" style="vertical-align: middle;">2W</th>
                                    <th class="text-center" style="vertical-align: middle;">3W</th>
                                    <th class="text-center" style="vertical-align: middle;">4W</th>
                                    <th style="font-weight: bold; font-size:120%;" class="text-center" style="vertical-align: middle;">1M</th>
                                    <th class="text-center" style="vertical-align: middle;">5W</th>
                                    <th class="text-center" style="vertical-align: middle;">6W</th>
                                    <th class="text-center" style="vertical-align: middle;">7W</th>
                                    <th class="text-center" style="vertical-align: middle;">8W</th>
                                    <th style="font-weight: bold; font-size:120%;" class="text-center" style="vertical-align: middle;">2M</th>   
                                    <th class="text-center" style="vertical-align: middle;">Pořadí</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($results) > 0): ?>
                                    <?php foreach ($results as $result): ?>
                                        <?php if ($result->displayName == 'ERGO ALL' || $result->displayName == 'FYZIO ALL'): ?>
                                            <tr data-id="<?= $result->id ?>" style="background-color: gray; color: white; font-weight: bold;">
                                        <?php else: ?>
                                             <tr data-id="<?= $result->id ?>">   
                                        <?php endif; ?>
                                            <td class="text-left"><?= $result->displayName ?></td>                                            
                                            <td class="text-center" data-order="<?= $result->usedSlots1W ?>"><?= number_format($result->usedSlots1W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots1W2W ?>"><?= number_format($result->usedSlots1W2W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots2W3W ?>"><?= number_format($result->usedSlots2W3W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots3W4W ?>"><?= number_format($result->usedSlots3W4W, 0, ",", " ") ?></td>
                                            <td style="font-weight: bold; font-size:120%;" class="text-center" data-order="<?= $result->usedSlots1M ?>"><?= number_format($result->usedSlots1M, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots4W5W ?>"><?= number_format($result->usedSlots4W5W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots5W6W ?>"><?= number_format($result->usedSlots5W6W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots6W7W ?>"><?= number_format($result->usedSlots6W7W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots7W8W ?>"><?= number_format($result->usedSlots7W8W, 0, ",", " ") ?></td>                                    
                                            <td style="font-weight: bold; font-size:120%;" class="text-center" data-order="<?= $result->usedSlots2M ?>"><?= number_format($result->usedSlots2M, 0, ",", " ") ?></td>                                    
                                            <td style="color: gray; font-size:50%;" class="text-center"><?= $result->orderRank ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                        <tr><td colspan="11" class="text-center">Dotaz selhal.</td></tr>    
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>        
    <script>
        $(document).ready(function () {
            const noveDatum = new Date();
            var shift =  Number("<?= $shift?>");
            
            noveDatum.setDate(noveDatum.getDate() + shift); 
            
            const d = noveDatum.getDate().toString().padStart(2, '0');
            const m = (noveDatum.getMonth() + 1).toString().padStart(2, '0'); // měsíce začínají od 0
            const y = noveDatum.getFullYear();
            
            
            $("#datum").val(d+'. '+m+'. '+y);
            
            $("#shiftMinus7").click(function(event) {   
                var shift =  Number("<?= $shift?>");
                shift = shift - 7;
                document.location = "reports?shift=" + shift;
            });
            $("#shiftPlus7").click(function(event) {   
                var shift =  Number("<?= $shift?>");
                
                shift = shift + 7;
                document.location = "reports?shift=" + shift;
            });
        });
            
    </script>
</div>
</body>
</html>