<?php
/**
 * Settings Tab Interface.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

/**
 * Interface for settings tabs.
 */
interface TabInterface {

	/**
	 * Get the tab slug (used in ?tab= and as panel id).
	 *
	 * @return string
	 */
	public function id(): string;

	/**
	 * Get the human tab label.
	 *
	 * @return string
	 */
	public function label(): string;

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void;

	/**
	 * Option keys handled by this tab, mapped to their sanitize type.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array;
}
