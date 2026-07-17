<?php
/**
 * Front-end withdrawal form handler.
 *
 * Thin dispatcher: validates the submission (nonce, honeypot) and routes each
 * step to the StepProcessor. The flow is stateless — identify -> select -> confirm.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Frontend;

/**
 * Processes withdrawal form submissions and returns a view descriptor.
 */
final class FormHandler {

	/**
	 * Process the request and return the view to render.
	 *
	 * @return array{view: string, data: array<string, mixed>}
	 */
	public function handle(): array {
		if ( ! FormRequest::is_submission() ) {
			return [
				'view' => 'identify',
				'data' => [],
			];
		}

		if ( ! FormRequest::verify_nonce() ) {
			return [
				'view' => 'identify',
				'data' => [ 'error' => __( 'A munkamenet lejárt. Kérjük, próbálja újra.', 'elallas-for-woo' ) ],
			];
		}

		if ( FormRequest::is_bot() ) {
			return [
				'view' => 'identify',
				'data' => [],
			];
		}

		$processor = new StepProcessor();

		return match ( FormRequest::step() ) {
			'identify' => $processor->identify(),
			'select'   => $processor->select(),
			'confirm'  => $processor->confirm(),
			default    => [
				'view' => 'identify',
				'data' => [],
			],
		};
	}
}
