<?php
namespace AffWP\Creative\CLI;

use \WP_CLI\Utils;

/**
 * A 'generate' WP-CLI sub-command for 'wp affwp creative'.
 *
 * @since 1.0
 */
class Generate_Sub_Command {

	/**
	 * Generate users.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many creatives to generate. Default: 1.
	 *
	 * [--creative_url=<URL>]
	 * : URL to set as the creative URL. Default is the site.url/cli.
	 *
	 * [--text=<string>]
	 * : String to use for the creative 'text' field. Default is the name of the site.
	 *
	 * [--format=<format>]
	 * : Accepted values: progress, ids. Default: ids.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate 1 creative and give it a URL of http://affiliatewp.com
	 *     wp affwp creative generate --creative_url=http://affiliatewp.com
	 */
	public function __invoke( $args, $assoc_args ) {
		$defaults = array(
			'count'        => 1,
			'creative_url' => site_url( 'cli' ),
			'text'         => get_bloginfo( 'name' ),
		);

		$assoc_args = array_merge( $defaults, $assoc_args );

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'progress' );

		$notify = false;
		if ( 'progress' === $format ) {
			$notify = \WP_CLI\Utils\make_progress_bar(
				sprintf( 'Generating %d creative(s)',
					$assoc_args['count']
				),
				$assoc_args['count']
			);
		}

		$creatives = array();

		for ( $i = 1; $i <= $assoc_args['count']; $i++ ) {

			$creatives[] = affwp_add_creative( array(
				'name'        => 'Generated Creative',
				'description' => 'WP-CLI generated creative.',
				'url'         => $assoc_args['creative_url'],
				'text'        => $assoc_args['text'],
				'status'      => 'active',
			) );

			if ( 'progress' === $format ) {
				$notify->tick();
			}
		}

		if ( 'progress' === $format ) {
			$notify->finish();
		} elseif ( 'ids' === $format ) {
			\WP_CLI::line( implode( $creatives, ' ' ) );
		}
	}

}
