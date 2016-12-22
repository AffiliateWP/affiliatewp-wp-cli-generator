<?php
namespace AffWP\WP_Affiliate\CLI;

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
	 * [--skip_users]
	 * : Whether to skip creating user accounts. Default is to create user accounts.
	 *
	 * [--status=<status>]
	 * : Status to assign generated affiliates. Default 'approved'.
	 *
	 * [--rate=<float>]
	 * : Affiliate rate. Ignored if not specified.
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

		if ( ! function_exists( 'wp_aff_create_affilate_using_array_data' ) ) {
			requice
			\WP_CLI::error( 'WP Affiliate must be active to proceed.' );
		}

		$defaults = array(
			'count' => 10,
			'rate'  => '20',
		);

		$assoc_args = array_merge( $defaults, $assoc_args );

		$role = get_option('default_role');

		$user_count = count_users();
		$total      = $user_count['total_users'];
		$limit      = $assoc_args['count'] + $total;

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'progress' );
		$mid    = rand( 1000, 100000 );

		//
		// Users
		//

		if ( empty( $assoc_args['skip_users'] ) ) {

			$notify = false;
			if ( 'progress' === $format ) {
				$notify = \WP_CLI\Utils\make_progress_bar(
					sprintf( 'Generating %d user(s) for affiliates', $assoc_args['count'] ),
					$assoc_args['count']
				);
			}

			$user_ids = array();

			for ( $i = $total; $i < $limit; $i++ ) {
				$login = sprintf( 'wp_affiliate_user_%d_%d', $mid, $i );
				$name = "AffWP User $i";

				$user_ids[] = wp_insert_user( array(
					'user_login'   => $login,
					'user_pass'    => $login,
					'user_email'   => sprintf( '%1$s@affwp.dev', $login ),
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
		}

		//
		// Affiliates
		//

		$rate   = empty( $assoc_args['rate'] ) ? '' : sanitize_text_field( $assoc_args['rate'] );
		$status = empty( $assoc_args['status'] ) ? 'approved' : sanitize_text_field( $assoc_args['status'] );

		if ( 'progress' === $format ) {
			$notify = \WP_CLI\Utils\make_progress_bar(
				sprintf( 'Generating %d WP Affiliate account(s)', $assoc_args['count'] ),
				$assoc_args['count']
			);
		}

		$affiliate_ids = array();

		if ( empty( $assoc_args['skip_users'] ) ) {
			foreach ( $user_ids as $user_id ) {
				$user_info  = get_userdata( $user_id );
				$first_name = 'WP Affiliate';
				$last_name  = "User $i";
				$email      = sprintf( '%1$s@affwp.dev', $user_info->user_login );

				$args = array(
					'refid'           => $user_info->user_login,
					'pass'            => generate_random_password( 24 ),
					'email'           => $email,
					'firstname'       => $first_name,
					'lastname'        => $last_name,
					'date'            => date( 'Y-m-d' ),
					'commissionlevel' => $rate,
					'paypalemail'     => $email,
					'referrer'        => '',
					'account_status'  => $status,
				);

				$affiliate_ids[] = wp_aff_create_affilate_using_array_data( $args );

				if ( 'progress' === $format ) {
					$notify->tick();
				}
			}
		} else {

			for ( $i = 1; $i <= $assoc_args['count']; $i++ ) {
				$user_login = sprintf( 'wp_affiliate_user_%d_%d', $mid, $i );
				$first_name = 'WP Affiliate';
				$last_name  = "User $i";
				$email      = sprintf( '%1$s@affwp.dev', $user_login );

				$args = array(
					'refid'           => $user_login,
					'pass'            => generate_random_password( 24 ),
					'email'           => $email,
					'firstname'       => $first_name,
					'lastname'        => $last_name,
					'date'            => date( 'Y-m-d' ),
					'commissionlevel' => $rate,
					'paypalemail'     => $email,
					'referrer'        => '',
					'account_status'  => $status,
				);

				$affiliate_ids[] = wp_aff_create_affilate_using_array_data( $args );

				if ( 'progress' === $format ) {
					$notify->tick();
				}
			}
		}

		if ( 'progress' === $format ) {
			$notify->finish();
		} elseif ( 'ids' === $format ) {
			\WP_CLI::line( implode( $affiliate_ids, ' ' ) );
		}

	}

}
