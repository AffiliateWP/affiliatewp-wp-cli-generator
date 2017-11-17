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
	 * Generate visits.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many visits to generate PER affiliate. Default: 100
	 *
	 * [--status=<status>]
	 * : Referral status. Accepts. 'paid', 'unpaid', 'pending', 'rejected', or 'random'.
	 * If random, one of the four statuses will be chosen at random. Default 'unpaid'.
	 *
	 * [--affiliate_id=<ID|id_list>]
	 * : The affiliate ID or a comma-separated list of affiliate IDs to associate visits with.
	 *
	 * [--referral_id=<ID|id_list>]
	 * : The referral ID or a comma-separated list of referral IDs to associate visits with.
	 *
	 * [--with_referral=<yes|no>]
	 * : Whether to associate a referral with each generated visit. Default 'no'.
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
	 *     # Generate 2 visits for affiliate ID 20, each with a referral (converted).
	 *     wp affwp visit generate --count=2 --affiliate_id=20 --with_referral=yes
	 */
	public function __invoke( $args, $assoc_args ) {
		$defaults = array(
			'count'         => 10,
			'status'        => 'unpaid',
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

		if ( empty( $affiliate_ids ) ) {
			\WP_CLI::error( 'At least one affiliate ID must be specified via --affiliate_id to generate visits against.' );
		}

		$notify    = false;
		$visits    = array();
		$statuses  = array( 'paid', 'unpaid', 'pending', 'rejected' );

		foreach ( $affiliate_ids as $affiliate_id ) {

			if ( 'progress' === $format ) {
				if ( 'yes' === $assoc_args['with_referral'] ) {
					$message = 'Generating %d converted visit(s) for affiliate #%d';
				} else {
					$message = 'Generating %d unconverted visit(s) for affiliate #%d';
				}

				$notify = \WP_CLI\Utils\make_progress_bar(
					sprintf( $message, $assoc_args['count'], $affiliate_id ),
					$assoc_args['count']
				);
			}

			$args = array(
				'affiliate_id' => $affiliate_id,
				'url'          => $assoc_args['visit_url'],
				'referrer'     => $assoc_args['referrer']
			);

			for ( $i = 1; $i <= $assoc_args['count']; $i++ ) {
				if ( 'yes' === $assoc_args['with_referral'] ) {
					if ( 'random' === $assoc_args['status'] ) {
						$status = $statuses[ rand( 0, 3 ) ];
					} else {
						$status = $assoc_args['status'];
					}

					$args['referral_id'] = affwp_add_referral( array(
						'affiliate_id' => $affiliate_id,
						'amount'       => $this->random_float( 0, 20 ),
						'status'       => $status,
					) );
				}

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
