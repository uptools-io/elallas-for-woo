<?php
/**
 * Data-retention cleanup (anonymization).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Cron;

use LightweightPlugins\Elallas\Activator;
use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Database\EventRepository;

/**
 * Anonymizes the personal data of cases past the configured retention period.
 *
 * Keeps the case record, items and audit log for accountability, but strips
 * the consumer's personal data (email, IP, user agent, note, source URL).
 */
final class RetentionCleaner {

	/**
	 * Constructor — hooks the daily retention cron.
	 */
	public function __construct() {
		add_action( Activator::CRON_HOOK, [ $this, 'run' ] );
	}

	/**
	 * Run the cleanup.
	 *
	 * @return void
	 */
	public function run(): void {
		$days = (int) Options::get( 'retention_days', 0 );

		if ( $days <= 0 ) {
			return;
		}

		foreach ( CaseRepository::ids_older_than( $days ) as $case_id ) {
			CaseRepository::update(
				$case_id,
				[
					'customer_email_hash'      => '',
					'customer_email_encrypted' => null,
					'ip_hash'                  => '',
					'user_agent_hash'          => '',
					'source_url'               => '',
					'customer_note'            => null,
				]
			);

			EventRepository::log(
				$case_id,
				'anonymized',
				'system',
				null,
				__( 'Az ügy személyes adatai a megőrzési idő lejárta miatt anonimizálva lettek.', 'elallas-for-woo' )
			);
		}
	}
}
