<?php
namespace AffWP\Visit\CLI;

use \WP_CLI\Utils;

/**
 * A 'generate' WP-CLI sub-command for 'wp affwp visit'.
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
	 * : How many visits to generate PER affiliate. Default: 100
	 *
	 * [--affiliate_id=<ID|id_list>]
	 * : The affiliate ID or a comma-separated list of affiliate IDs to associate visits with.
	 *
	 * [--referral_id=<ID|id_list>]
	 * : The referral ID or a comma-separated list of referral IDs to associate visits with.
	 *
	 * [--with_referral=<bool>]
	 * : Whether to associate a referral with each generated visit. Default false.
	 *
	 * [--referrer=<URL>]
	 * : URL to set as the referrer for generated visits. Default is empty (direct).
	 *
	 * [--visit_url=<URL>]
	 * : URL to set as the visit URL. Default is the site.url/cli.
	 *
	 * [--format=<format>]
	 * : Accepted values: progress, ids. Default: ids.
	 *
	 * ## EXAMPLES
	 *
	 *     # Add meta to every generated user
	 *     wp user generate --format=ids | xargs -0 -d ' ' -I % wp user meta add % foo bar
	 */
	public function __invoke( $args, $assoc_args ) {
		$defaults = array(
			'count'         => 10,
			'affiliate_id'  => 0,
			'referral_id'   => 0,
			'with_referral' => 'no',
			'visit_url'     => site_url( 'cli' ),
			'referrer'      => '',
		);

		$assoc_args = array_merge( $defaults, $assoc_args );

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'progress' );

		$affiliate_ids = wp_parse_id_list( $assoc_args['affiliate_id'] );
		$referral_ids  = wp_parse_id_list( $assoc_args['referral_ids'] );

		$notify    = false;
		$visits    = array();
		$statuses  = array( 'paid', 'unpaid', 'pending', 'rejected' );

		foreach ( $affiliate_ids as $affiliate_id ) {

			if ( 'progress' === $format ) {
				$unconverted_prompt = sprintf( 'Generating %d unconverted visit(s) for affiliate #%d',
					$assoc_args['count'],
					$affiliate_id
				);

				$converted_prompt = sprintf( 'Generating %d converted visit(s) for affiliate #%d',
					$assoc_args['count'],
					$affiliate_id
				);

				$notify = \WP_CLI\Utils\make_progress_bar(
					'yes' === $assoc_args['with_referral'] ? $converted_prompt : $unconverted_prompt,
					$assoc_args['count']
				);
			}

			$args = array(
				'affiliate_id' => $affiliate_id,
				'url'          => $assoc_args['visit_url'],
				'referrer'     => $assoc_args['referrer']
			);

			if ( 'yes' === $assoc_args['with_referral'] ) {
				$args['referral_id'] = affwp_add_referral( array(
					'affiliate_id' => $affiliate_id,
					'amount'       => $this->random_float( 0, 20 ),
					'status'       => $statuses[ rand( 0, 3 ) ],
				) );
			}

			for ( $i = 1; $i <= $assoc_args['count']; $i++ ) {
				$visit_id = affiliate_wp()->visits->add( $args );

				$visits[ $affiliate_id ][] = $visit_id;

				// Update the referral with the visit ID if there is one.
				if ( ! empty( $args['referral_id'] ) ) {
					$referral = affwp_get_referral( $args['referral_id'] )->set( 'visit_id', $visit_id, true );
				}

				if ( 'progress' === $format ) {
					$notify->tick();
				}
			}
		}

		if ( 'progress' === $format ) {
			$notify->finish();
		} elseif ( 'ids' === $format ) {
			\WP_CLI::line( implode( $visits, ' ' ) );
		}
	}

	/**
	 * Generates a random float sanitized to the decimal count set in AffWP.
	 *
	 * @access protected
	 * @since  1.0
	 *
	 * @param int|float $min Minimum number.
	 * @param int|float $max Maximum number.
	 * @return float Random, sanitized float.
	 */
	protected function random_float( $min = 0, $max = 10 ) {
		$value = $min + mt_rand() / mt_getrandmax() * ( $max - $min );

		return floatval( affwp_sanitize_amount( $value ) );
	}

}
