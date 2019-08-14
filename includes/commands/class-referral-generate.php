<?php
namespace AffWP\Referral\CLI;

use \WP_CLI\Utils;

/**
 * A 'generate' WP-CLI sub-command for 'wp affwp referral'.
 *
 * @since 1.0
 */
class Generate_Sub_Command {

	/**
	 * Generate referrals.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many referrals PER affiliate to generate. Default: 10
	 *
	 * [--affiliate_id=<ID|id_list>]
	 * : The affiliate ID or a comma-separated list of affiliate IDs to associate referrals with.
	 *
	 * [--status=<referral_status>]
	 * : The referral status to give generated referrals. If ommitted, statuses will be random.
	 *
	 * [--date=<date>]
	 * : The date to assign generated referrals. If omitted, today's date will be used.
	 * Accepts "year", "past", or "future", or any strtotime date format.
	 * Set this to "year" to set each referral date in consecutive days starting with Jan 1st.
	 * Set this to "past" to set each referral date in reverse consecutive days, starting with yesterday.
	 * Set this to "future" to set each referral date in consecutive days, starting with tomorrow's date.
	 *
	 * [--format=<format>]
	 * : Accepted values: progress, ids. Default: ids.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate 3 referrals each for affiliate IDs 1, 2, and 3.
	 *     wp affwp referral generate --count=3 --affiliate_id=1,2,3
	 */
	public function __invoke( $args, $assoc_args ) {
		$defaults = array(
			'count'        => 10,
			'affiliate_id' => 0,
		);

		$assoc_args = array_merge( $defaults, $assoc_args );

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'progress' );
		$status = \WP_CLI\Utils\get_flag_value( $assoc_args, 'status',         '' );

		if ( empty( $assoc_args['affiliate_id'] ) ) {
			\WP_CLI::error( 'At least one affiliate ID must be specified via --affiliate_id to generate referrals against.' );
		}

		$affiliate_ids = wp_parse_id_list( $assoc_args['affiliate_id'] );

		$notify    = false;
		$statuses  = array( 'paid', 'unpaid', 'pending', 'rejected' );
		$referrals = array();

		foreach ( $affiliate_ids as $affiliate_id ) {

			if ( 'progress' === $format ) {
				$notify = \WP_CLI\Utils\make_progress_bar(
					sprintf( 'Generating %d referral(s) for affiliate #%d',
						$assoc_args['count'],
						$affiliate_id
					),
					$assoc_args['count']
				);
			}

			for ( $i = 1; $i <= $assoc_args['count']; $i++ ) {
				if ( empty( $status ) ) {
					$status = $statuses[ rand( 0, 3 ) ];
				}

				if ( $assoc_args['date'] === 'year' ) {
					$date = sprintf( 'January 01 + %s day', $i - 1 );
				} elseif ( $assoc_args['date'] === 'past' ) {
					$date = sprintf( 'today - %s day', $i );
				} elseif ( $assoc_args['date'] === 'future' ) {
					$date = sprintf( 'today + %s day', $i );
				} else {
					$date = $assoc_args['date'];
				}


				$referrals[ $affiliate_id ][] = affwp_add_referral( array(
					'affiliate_id' => $affiliate_id,
					'amount'       => $this->random_float( 0, 20 ),
					'status'       => $status,
					'campaign'     => $campaign,
					'date'         => $date,
				) );

				if ( 'progress' === $format ) {
					$notify->tick();
				}
			}
		}

		if ( 'progress' === $format ) {
			$notify->finish();
		} elseif ( 'ids' === $format ) {
			\WP_CLI::line( implode( $referrals, ' ' ) );
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
