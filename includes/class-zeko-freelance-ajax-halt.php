<?php
/**
 * Zeko Freelance front-end AJAX handlers.
 *
 * Handles project create/edit/delete and bookmarking from the public
 * templates. All handlers require a logged-in user and verify the shared
 * freelance nonce.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thrown by Zeko_Freelance_Ajax::respond() when the ajax exit is disabled
 * (test mode). Lets callers capture the JSON payload instead of terminating
 * the request the way wp_send_json() would.
 *
 * @package Zeko_Freelance
 */
class Zeko_Freelance_Ajax_Halt extends \Exception {
}
