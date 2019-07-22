<?php
/**
 * Plugin Name: AffiliateWP WP-CLI Generate Commands
 * Description: Adds generate sub-commands for affiliates, creatives, payouts, referrals, and visits.
 * Plugin URI: https://affiliatewp.com
 * Author: Sandhills Development, LLC
 * Author URI: https://sandhillsdev.com
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements 'generate' WP-CLI sub-commands for all core objects.
 *
 * @since 1.0
 */
final class AffiliateWP_Generate_Sub_Commands {

	/**
	 * Plugin instance.
	 *
	 * @access private
	 * @since  1.0
	 * @var AffiliateWP_Generate_Sub_Commands
	 * @static
	 */
	private static $instance;

	/**
	 * Path to the home folder for this plugin.
	 *
	 * @access public
	 * @since  1.0
	 * @var    string
	 * @static
	 */
	public static $plugin_dir;

	/**
	 * URL to the home folder for this plugin.
	 *
	 * @access public
	 * @since  1.0
	 * @var    string
	 * @static
	 */
	public static $plugin_url;

	/**
	 * Plugin version.
	 *
	 * @access private
	 * @since  1.0
	 * @var    string
	 * @static
	 */
	private static $version;

	/**
	 * Main AffiliateWP_Generate_Sub_Commands instance.
	 *
	 * Insures that only one instance of AffiliateWP_Generate_Sub_Commands exists in memory at
	 * any one time. Also prevents needing to define globals all over the place.
	 *
	 * @access public
	 * @since  1.0
	 * @static
	 * @staticvar array $instance
	 *
	 * @return AffiliateWP_Generate_Sub_Commands The one true AffiliateWP_Generate_Sub_Commands instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof AffiliateWP_Generate_Sub_Commands ) ) {
			self::$instance = new AffiliateWP_Generate_Sub_Commands;

			self::$plugin_dir = plugin_dir_path( __FILE__ );
			self::$plugin_url = plugin_dir_url( __FILE__ );
			self::$version    = '1.0';

			self::$instance->includes();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Brings in required files.
	 *
	 * @access private
	 * @since  1.0
	 */
	private function includes() {
		require_once self::$plugin_dir . 'includes/commands/class-affiliate-generate.php';
		require_once self::$plugin_dir . 'includes/commands/class-creative-generate.php';
		require_once self::$plugin_dir . 'includes/commands/class-referral-generate.php';
		require_once self::$plugin_dir . 'includes/commands/class-visit-generate.php';

		// Third-parties.
		require_once self::$plugin_dir . 'includes/commands/class-wp-affiliate-generate.php';
	}

	/**
	 * Registers the 'generate' sub-commands with WP-CLI.
	 *
	 * @access private
	 * @since  1.0
	 */
	private function init() {
		\WP_CLI::add_command( 'affwp affiliate generate', 'AffWP\Affiliate\CLI\Generate_Sub_Command' );
		\WP_CLI::add_command( 'affwp creative generate',  'AffWP\Creative\CLI\Generate_Sub_Command'  );
		\WP_CLI::add_command( 'affwp referral generate',  'AffWP\Referral\CLI\Generate_Sub_Command'  );
		\WP_CLI::add_command( 'affwp visit generate',     'AffWP\Visit\CLI\Generate_Sub_Command'     );

		// Third-parties.
		\WP_CLI::add_command( 'affwp wp-affiliate generate', 'AffWP\WP_Affiliate\CLI\Generate_Sub_Command' );
	}
}

/**
 * Serves as the main function responsible for loading the plugin.
 *
 * @since 1.0
 *
 * @return AffiliateWP_Generate_Sub_Commands
 */
function affiliate_wp_generate() {

	if ( ! class_exists( 'Affiliate_WP' ) ) {

		if ( ! class_exists( 'AffiliateWP_Activation' ) ) {
			require_once 'includes/class-activation.php';
		}

		$activation = new AffiliateWP_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();

	} elseif ( defined( 'WP_CLI' ) && WP_CLI ) {
		return AffiliateWP_Generate_Sub_Commands::instance();
	}

}
add_action( 'plugins_loaded', 'affiliate_wp_generate', 100 );
