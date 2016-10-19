<?php
namespace AffWP\Affiliate\CLI;

use \WP_CLI\Utils;

/**
 * A 'generate' WP-CLI sub-command for 'wp affwp affiliate'.
 *
 * @since 1.0
 */
class Generate_Sub_Command {

	/**
	 * Generate affiliates.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many users to generate. Default: 10.
	 *
	 * [--status=<status>]
	 * : Status to assign generated affiliates.
	 *
	 * [--rate=<float>]
	 * : Affiliate rate. Ignored if not specified.
	 *
	 * [--rate_type=<type>]
	 * : Affiliate rate type. Ignored if not specified.
	 *
	 * [--visits=<number>]
	 * : The number of visits to generate PER affiliate. Use this with caution!
	 *
	 * [--referrals=<number>]
	 * : The number of referrals to generate PER affiliate. Use this with caution!
	 *
	 * [--format=<format>]
	 * : Accepted values: progress, ids. Default: ids.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate 3 affiliates (and users) and give them each two generated, converted visits.
	 *     wp affwp affiliate generate --count=3 --visits=2
	 */
	public function __invoke( $args, $assoc_args ) {
		$defaults = array(
			'count'     => 10,
			'rate'      => '',
			'rate_type' => '',
			'visits'    => 0,
			'referrals' => 0,
		);

		$assoc_args = array_merge( $defaults, $assoc_args );

		$role = get_option('default_role');

		$user_count = count_users();
		$total      = $user_count['total_users'];
		$limit      = $assoc_args['count'] + $total;

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'progress' );

		//
		// Users
		//

		$notify = false;
		if ( 'progress' === $format ) {
			$notify = \WP_CLI\Utils\make_progress_bar(
				sprintf( 'Generating %d user(s) for affiliates', $assoc_args['count'] ),
				$assoc_args['count']
			);
		}

		$user_ids = array();

		$mid = rand( 100, 10000 );

		for ( $i = $total; $i < $limit; $i++ ) {
			$login = sprintf( 'affwp_user_%d_%d', $mid, $i );
			$name = "AffWP User $i";

			$user_ids[] = wp_insert_user( array(
				'user_login'   => $login,
				'user_pass'    => $login,
				'nickname'     => $name,
				'display_name' => $name,
				'role'         => $role
			) );

			if ( 'progress' === $format ) {
				$notify->tick();
			}
		}

		if ( 'progress' === $format ) {
			$notify->finish();
		}

		unset( $notify );

		//
		// Affiliates
		//

		$rate      = empty( $assoc_args['rate'] ) ? '' : sanitize_text_field( $assoc_args['rate'] );
		$rate_type = empty( $assoc_args['rate_type'] ) ? '' : sanitize_text_field( $assoc_args['rate_type'] );
		$status    = empty( $assoc_args['status'] ) ? 'active' : sanitize_text_field( $assoc_args['status'] );

		if ( 'progress' === $format ) {
			$notify = \WP_CLI\Utils\make_progress_bar(
				sprintf( 'Generating %d affiliate(s)', $assoc_args['count'] ),
				$assoc_args['count']
			);
		}

		foreach ( $user_ids as $user_id ) {

			$args = array(
				'status'    => $status,
				'user_id'   => $user_id,
				'rate'      => $rate,
				'rate_type' => $rate_type,
			);

			$affiliate_ids[] = affiliate_wp()->affiliates->add( $args );

			if ( 'progress' === $format ) {
				$notify->tick();
			}
		}

		if ( 'progress' === $format ) {
			$notify->finish();
		} elseif ( 'ids' === $format ) {
			\WP_CLI::line( implode( $affiliate_ids, ' ' ) );
		}

		//
		// Referrals / Visits
		//

		$referrals_count = (int) $assoc_args['referrals'];
		$visits_count    = (int) $assoc_args['visits'];

		foreach ( $affiliate_ids as $affiliate_id ) {
			// Referrals.
			if ( 0 !== $referrals_count ) {
				\WP_CLI::run_command( array( 'affwp', 'referral', 'generate' ), array(
					'count'        => $referrals_count,
					'affiliate_id' => $affiliate_id,
				) );
			}

			// Visits.
			if ( 0 !== $visits_count ) {
				\WP_CLI::run_command( array( 'affwp', 'visit', 'generate' ), array(
					'count'         => $visits_count,
					'affiliate_id'  => $affiliate_id,
					'with_referral' => 0 !== $referrals_count ? 'no' : 'yes',
				) );
			}
		}

	}

}
