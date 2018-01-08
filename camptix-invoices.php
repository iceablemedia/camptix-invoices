<?php

/**
 * Plugin name: Camptix Invoices
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'CTX_INV_VER', time() );

/**
 * Load invoice addon
 */
add_action( 'camptix_load_addons', 'load_camptix_invoices' );
function load_camptix_invoices() {
	class CampTix_Addon_Invoices extends \CampTix_Addon {
		/**
		 * Init invoice addon
		 */
		function camptix_init() {
			global $camptix;
			add_filter( 'camptix_setup_sections', array( __CLASS__, 'invoice_settings_tab' ) );
			add_action( 'camptix_menu_setup_controls', array( __CLASS__, 'invoice_settings' ) );
			add_filter( 'camptix_validate_options', array( __CLASS__, 'validate_options' ), 10, 2 );
			add_action( 'camptix_payment_result', array( __CLASS__, 'maybe_create_invoice' ), 10, 3 );
			// add_filter( 'camptix_wp_mail_override', array( __CLASS__, 'maybe_attach_invoice' ) );
			// add_filter( 'camptix_email_tickets_template', array( __CLASS__, 'maybe_attach_invoice' ), 10, 2 );
			// add_action( 'camptix_init_email_templates_shortcodes', array( __CLASS__, 'init_invoice_shortcode' ) );
		}

		/**
		 * Add a new tab in camptix settings
		 */
		static function invoice_settings_tab( $sections ) {
			$sections['invoice'] = __( 'Facturation' );
			return $sections;
		}

		/**
		 * Tab content
		 */
		static function invoice_settings( $section ) {
			if ( 'invoice' !== $section ) {
				return false;
			}
			$opt = get_option( 'camptix_options' );
			add_settings_section( 'invoice', __( 'Réglages des factures' ), '__return_false', 'camptix_options' );
			global $camptix;
			$camptix->add_settings_field_helper( 'invoice-new-year-reset', 'Réinitialisation annuelle', 'field_yesno' ,'invoice', 
				sprintf( __( 'Les numéros de facture sont préfixés par l’année, et seront réinitialisés le premier janvier. (ex: %1$s-125)' ), date( 'Y' ) )
			);
			add_settings_field( 'invoice-current-number', 'Prochaine facture', array( __CLASS__, 'current_number' ), 'camptix_options', 'invoice', array(
				'id'    => 'invoice-current-number',
				'value' => isset( $opt['invoice-current-number'] ) ? $opt['invoice-current-number'] : 1,
				'yearly' => isset( $opt['invoice-new-year-reset'] ) ? $opt['invoice-new-year-reset'] : false
			) );
		}

		/**
		 * Next invoice number setting
		 */
		static function current_number_callback( $args ) {
			vprintf( '<p>' . __( 'La prochaine facture portera le numéro' ) . ' %3$s<input type="number" min="1" value="%2$d" name="camptix_options[%1$s]" class="small-text">%4$s</p>', array(
				esc_attr( $args['id'] ),
				esc_attr( $args['value'] ),
				$args['yearly'] ? '<code>' . date( 'Y-' ) : '',
				$args['yearly'] ? '</code>' : '',
			) );
		}

		/**
		 * Validate our custom options
		 */
		static function validate_options( $output, $input ) {
			if ( isset( $input['invoice-new-year-reset'] ) ) {
				$output['invoice-new-year-reset'] = intval( $input['invoice-new-year-reset'] );
			}
			if ( ! empty( $input['invoice-current-number'] ) ) {
				$output['invoice-current-number'] = (int) $input['invoice-current-number'];
			}
			return $output;
		}

		/**
		 * Attach invoice to email
		 * @todo find another way, don't work
		 */
		function maybe_attach_invoice( $type, $attendee ) {
			if ( 'email_template_pending_succeeded' !== $type ) {
				return;
			}
			// global $camptix;
			// $camptix->tmp( 'invoice_url', '<a href=""></a>' );
		}

		/**
		 * Liste payment result to create invoice
		 */
		function maybe_create_invoice( $payment_token, $result, $data ) {
			//
		}
	}
	camptix_register_addon( 'CampTix_Addon_Invoices' );

	add_action( 'init', 'register_tix_invoices' );
}

/**
 * Register invoice CPT
 */
function register_tix_invoices() {
	register_post_type( 'tix_invoices', array(
		'label'        => __( 'Factures' ),
		'labels' => array(
			'name' => __( 'Factures' ),
		),
		'public'       => true,
		'show_ui'      => true,
		'show_in_menu' => 'edit.php?post_type=tix_ticket',
	) );
}
