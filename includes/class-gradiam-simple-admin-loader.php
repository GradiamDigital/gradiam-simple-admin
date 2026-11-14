<?php
/**
 * Register all actions and filters for the plugin.
 *
 * Maintains a list of all hooks registered throughout the plugin and
 * registers them with the WordPress API when run() is called.
 *
 * @link       https://gradiamdigital.cz
 * @since      1.0.0
 * @package    Gradiam_Simple_Admin
 * @subpackage Gradiam_Simple_Admin/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook loader.
 */
class Gradiam_Simple_Admin_Loader {

	/**
	 * Actions to register with WordPress.
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Filters to register with WordPress.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Add a new action to the collection.
	 *
	 * @param string $hook          WordPress action name.
	 * @param object $component     Instance on which the callback is defined.
	 * @param string $callback      Method name on $component.
	 * @param int    $priority      Optional. Default 10.
	 * @param int    $accepted_args Optional. Default 1.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection.
	 *
	 * @param string $hook          WordPress filter name.
	 * @param object $component     Instance on which the callback is defined.
	 * @param string $callback      Method name on $component.
	 * @param int    $priority      Optional. Default 10.
	 * @param int    $accepted_args Optional. Default 1.
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a hook to a collection.
	 *
	 * @param array  $hooks         Collection (actions or filters).
	 * @param string $hook          Hook name.
	 * @param object $component     Instance.
	 * @param string $callback      Method name.
	 * @param int    $priority      Priority.
	 * @param int    $accepted_args Number of arguments.
	 * @return array
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register the filters and actions with WordPress.
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		// Nothing reads the collections after registration.
		$this->actions = array();
		$this->filters = array();
	}
}
