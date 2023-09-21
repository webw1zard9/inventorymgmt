<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        
        DROP PROCEDURE IF EXISTS `sync_batch_location_aggregate`;
CREATE PROCEDURE `sync_batch_location_aggregate`()
BEGIN

DELETE FROM `batch_location_aggregate` where (`batch_id`, `location_id`) NOT IN (

SELECT
		`batches`.`id` AS `batch_id`,
		`batch_location`.`location_id` AS `location_id`
	FROM
		`batches`
	LEFT JOIN `batch_location` ON `batches`.`id` = `batch_location`.`batch_id`
	
	GROUP BY
		`batches`.`id`,
		`batch_location`.`location_id`
		);

INSERT INTO `batch_location_aggregate` (
	`batch_id`, 
	`location_id`,
	`onhand_inventory`,
	`onhand_cost`,
	`available_inventory`,
	`available_cost`,
	`pending_inventory`,
	`pending_cost`,
	`fulfilled_inventory`,
	`fulfilled_cost`,
	`sold_inventory`,
	`sold_cost`,
	`reconciled_inventory`,
	`reconciled_cost`,
	`approved_inventory`,
	`waiting_approval_inventory`,
	`location_unit_price`,
	`created_at`,
	`updated_at`) 
	
	(SELECT
		`batches`.`id` AS `batch_id`,
		`batch_location`.`location_id` AS `location_id`,
		IFNULL(`approved_batches`.`approved_inventory` + COALESCE(`pending_batches_at_locations`.`pending_inventory`, 0), 0) AS `onhand_inventory`,
		IFNULL((`approved_batches`.`approved_inventory` * `approved_batches`.`location_unit_price`) + COALESCE(`pending_batches_at_locations`.`pending_cost`, 0), 0) AS `onhand_cost`,
		
		IFNULL(`approved_batches`.`approved_inventory`, 0) AS `available_inventory`,
		IFNULL(`approved_batches`.`approved_inventory` * `approved_batches`.`location_unit_price`, 0) AS `available_cost`,
		
		IFNULL(`pending_batches_at_locations`.`pending_inventory`, 0) AS `pending_inventory`,
		IFNULL(`pending_batches_at_locations`.`pending_cost`, 0) AS `pending_cost`,
		
		IFNULL(`fulfilled_batches_at_locations`.`fulfilled_inventory`, 0) AS `fulfilled_inventory`,
		IFNULL(`fulfilled_batches_at_locations`.`fulfilled_cost`, 0) AS `fulfilled_cost`,
		
		IFNULL(`sold_batches_at_locations`.`sold_inventory`, 0) AS `sold_inventory`,
		IFNULL(`sold_batches_at_locations`.`sold_cost`, 0) AS `sold_cost`,

        IFNULL(`reconciled_batches`.`reconciled_inventory`, 0) AS `reconciled_inventory`,
	    IFNULL(`reconciled_batches`.`reconciled_cost`, 0) AS `reconciled_cost`,
	
		IFNULL(`approved_batches`.`approved_inventory`, 0) AS `approved_inventory`,
		IFNULL(`waiting_approval_batches`.`waiting_approval_inventory`, 0) AS `waiting_approval_inventory`,
		IFNULL(`approved_batches`.`location_unit_price`, 0) AS `location_unit_price`,
		NOW() AS `updated_at`,
		NOW() AS `created_at`
	FROM
		`batches`
	LEFT JOIN `batch_location` ON `batches`.`id` = `batch_location`.`batch_id`
	INNER JOIN `locations` ON `batch_location`.`location_id` = `locations`.`id`
	
	LEFT JOIN (
		SELECT
			`batches`.`id` AS `batch_id`,
			`batch_location`.`location_id`,
			(COALESCE(SUM(`batch_location`.`quantity`), 0) * - 1) - COALESCE(SUM(`order_details`.`units_fulfilled`), 0) AS `pending_inventory`,
			(COALESCE(SUM(`batch_location`.`quantity` * `batch_location`.`unit_price`), 0) * - 1) - COALESCE(SUM(`order_details`.`units_fulfilled` * `order_details`.`unit_cost`), 0) AS `pending_cost`
		FROM
			`orders`
			INNER JOIN `order_details` ON `orders`.`id` = `order_details`.`sale_order_id`
			INNER JOIN `batch_location` ON `order_details`.`id` = `batch_location`.`order_detail_id`
			INNER JOIN `batches` ON `batch_location`.`batch_id` = `batches`.`id`
		WHERE
			`orders`.`status` in('hold', 'ready to pack')
			AND `orders`.`deleted_at` IS NULL
			AND `orders`.`type` in('sale')
		GROUP BY
			`batches`.`id`,
			`batch_location`.`location_id`) AS `pending_batches_at_locations` ON `batches`.`id` = `pending_batches_at_locations`.`batch_id`
		AND `batch_location`.`location_id` = `pending_batches_at_locations`.`location_id`

	LEFT JOIN (
		SELECT
			`batches`.`id` AS `batch_id`,
			`batch_location`.`location_id`,
			COALESCE(SUM(`order_details`.`units_fulfilled`), 0) AS `fulfilled_inventory`,
			COALESCE(SUM(`order_details`.`units_fulfilled` * `order_details`.`unit_cost`), 0) AS `fulfilled_cost`
		FROM
			`orders`
			INNER JOIN `order_details` ON `orders`.`id` = `order_details`.`sale_order_id`
			INNER JOIN `batch_location` ON `order_details`.`id` = `batch_location`.`order_detail_id`
			INNER JOIN `batches` ON `batch_location`.`batch_id` = `batches`.`id`
		WHERE
			`orders`.`status` != 'delivered'
			AND `orders`.`deleted_at` IS NULL
			AND `orders`.`type` in('sale')
		GROUP BY
			`batches`.`id`,
			`batch_location`.`location_id`) AS `fulfilled_batches_at_locations` ON `batches`.`id` = `fulfilled_batches_at_locations`.`batch_id`
		AND `batch_location`.`location_id` = `fulfilled_batches_at_locations`.`location_id`

	LEFT JOIN (
		SELECT
			`batches`.`id` AS `batch_id`,
			`batch_location`.`location_id`,
			COALESCE(SUM(`order_details`.`units_accepted`), 0) AS `sold_inventory`,
			COALESCE(SUM(`order_details`.`units_accepted` * `order_details`.`unit_cost`), 0) AS `sold_cost`
		FROM
			`orders`
			INNER JOIN `order_details` ON `orders`.`id` = `order_details`.`sale_order_id`
			INNER JOIN `batch_location` ON `order_details`.`id` = `batch_location`.`order_detail_id`
			INNER JOIN `batches` ON `batch_location`.`batch_id` = `batches`.`id`
		WHERE
			`orders`.`status` = 'delivered'
			AND `orders`.`deleted_at` IS NULL
			AND `orders`.`type` in('sale')
		GROUP BY
			`batches`.`id`,
			`batch_location`.`location_id`) AS `sold_batches_at_locations` ON `batches`.`id` = `sold_batches_at_locations`.`batch_id`
		AND `batch_location`.`location_id` = `sold_batches_at_locations`.`location_id`

	LEFT JOIN (
		SELECT
			`batch_id`,
			`location_id`,
			COALESCE(SUM(`quantity`), 0) AS `approved_inventory`,
			IFNULL(SUM(`batch_location`.`quantity` * `batch_location`.`unit_price`) / NULLIF(SUM(`batch_location`.`quantity`), 0), 0) AS `location_unit_price`
		FROM
			`batch_location`
		WHERE
			`approved` = 1
		GROUP BY
			`batch_id`,
			`location_id`) AS `approved_batches` ON `batches`.`id` = `approved_batches`.`batch_id`
		AND `batch_location`.`location_id` = `approved_batches`.`location_id`
		
	LEFT JOIN (
		SELECT
			`batch_id`,
			`location_id`,
			COALESCE(SUM(`quantity`), 0) AS `waiting_approval_inventory`
		FROM
			`batch_location`
		WHERE
			`approved` = 0
		GROUP BY
			`batch_id`,
			`location_id`) AS `waiting_approval_batches` ON `batches`.`id` = `waiting_approval_batches`.`batch_id`
		AND `batch_location`.`location_id` = `waiting_approval_batches`.`location_id`

	LEFT JOIN (
		SELECT
            `batches`.`id` AS `batch_id`,
            `locations`.`id` AS `location_id`,
            COALESCE(SUM(`transfer_logs`.`quantity_transferred`), 0) AS `reconciled_inventory`,
            COALESCE(SUM(`transfer_logs`.`quantity_transferred` * `transfer_logs`.`unit_cost`), 0) AS `reconciled_cost`
        FROM
            `batches`
        CROSS JOIN `locations`
        LEFT JOIN `transfer_logs` ON `batches`.`id` = `transfer_logs`.`batch_id` 
                                 AND `locations`.`id` = `transfer_logs`.`location_id`
        GROUP BY
            `batches`.`id`,
            `locations`.`id`) AS `reconciled_batches` ON `batches`.`id` = `reconciled_batches`.`batch_id`
							AND `batch_location`.`location_id` = `reconciled_batches`.`location_id`

		WHERE
			`batches`.`id` = @batch_id
	GROUP BY
		`batches`.`id`,
		`batch_location`.`location_id`) 
		
		ON DUPLICATE KEY UPDATE
		 
		`batch_id` = VALUES(`batch_id`),
`location_id` = VALUES(`location_id`),
`onhand_inventory` = VALUES(`onhand_inventory`),
`onhand_cost` = VALUES(`onhand_cost`),

`available_inventory` = VALUES(`available_inventory`),
`available_cost` = VALUES(`available_cost`),

`pending_inventory` = VALUES(`pending_inventory`),
`pending_cost` = VALUES(`pending_cost`),

`fulfilled_inventory` = VALUES(`fulfilled_inventory`),
`fulfilled_cost` = VALUES(`fulfilled_cost`),

`sold_inventory` = VALUES(`sold_inventory`),
`sold_cost` = VALUES(`sold_cost`),

`reconciled_inventory` = VALUES(`reconciled_inventory`),
`reconciled_cost` = VALUES(`reconciled_cost`),

`approved_inventory` = VALUES(`approved_inventory`),
`waiting_approval_inventory` = VALUES(`waiting_approval_inventory`),
`location_unit_price` = VALUES(`location_unit_price`),
`updated_at` = NOW();

END
        
        ");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sync_batch_location_aggregate');
    }
};
