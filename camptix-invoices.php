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
			global $camptix_invoice_custom_error;
			$camptix_invoice_custom_error = false;
			add_filter( 'camptix_setup_sections', array( __CLASS__, 'invoice_settings_tab' ) );
			add_action( 'camptix_menu_setup_controls', array( __CLASS__, 'invoice_settings' ) );
			add_filter( 'camptix_validate_options', array( __CLASS__, 'validate_options' ), 10, 2 );
			add_action( 'camptix_payment_result', array( __CLASS__, 'maybe_create_invoice' ), 10, 3 );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_assets' ) );
			add_filter( 'camptix_checkout_attendee_info', array( __CLASS__, 'attendee_info' ) );
			add_action( 'camptix_notices', array( __CLASS__, 'error_flag' ), 0 );
			add_filter( 'camptix_form_register_complete_attendee_object', array( __CLASS__, 'attendee_object' ), 10, 2 );
			add_action( 'camptix_checkout_update_post_meta', array( __CLASS__, 'add_meta_invoice_on_attendee' ), 10, 2 );
			add_filter( 'camptix_metabox_attendee_info_additional_rows', array( __CLASS__, 'add_invoice_meta_on_attendee_metabox' ), 10, 2 );
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
			add_settings_field( 'invoice-current-number', 'Prochaine facture', array( __CLASS__, 'current_number_callback' ), 'camptix_options', 'invoice', array(
				'id'    => 'invoice-current-number',
				'value' => isset( $opt['invoice-current-number'] ) ? $opt['invoice-current-number'] : 1,
				'yearly' => isset( $opt['invoice-new-year-reset'] ) ? $opt['invoice-new-year-reset'] : false
			) );
			add_settings_field( 'invoice-logo', __( 'Logo' ), array( __CLASS__, 'type_file_callback' ), 'camptix_options', 'invoice', array(
				'id'    => 'invoice-logo',
				'value' => ! empty( $opt['invoice-logo'] ) ? $opt['invoice-logo'] : '',
			) );
			$camptix->add_settings_field_helper( 'invoice-company', __( 'Adresse de l’organisme' ), 'field_textarea' ,'invoice');
			$camptix->add_settings_field_helper( 'invoice-cgv', __( 'CGV' ), 'field_textarea' ,'invoice');
			$camptix->add_settings_field_helper( 'invoice-thankyou', __( 'Mot en dessous du total' ), 'field_textarea' ,'invoice');
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
		 * Input type file
		 */
		static function type_file_callback( $args ) {
			wp_enqueue_media();
			wp_enqueue_script( 'admin-camptix-invoices' );
			wp_localize_script( 'admin-camptix-invoices', 'camptixInvoiceBackVars', array(
				'selectText'  => __( 'Sélectionner un logo a télécharger' ),
				'selectImage' => __( 'Choisir ce logo' ),
			) );

			vprintf( '<div class="camptix-media"><div class="camptix-invoice-logo-preview-wrapper" data-imagewrapper>
				%4$s
			</div>
			<input data-set type="button" class="button button-secondary" value="%3$s" />
			<input data-unset type="button" class="button button-secondary" value="%5$s"%6$s/>
			<input type="hidden" name=camptix_options[%1$s] data-field="image_attachment" value="%2$s"></div>', array(
				esc_attr( $args['id'] ),
				esc_attr( $args['value'] ),
				esc_attr__( 'Choisir un logo' ),
				! empty( $args['value'] ) ? wp_get_attachment_image( $args['value'], 'thumbnail', '', array() ) : '',
				esc_attr__( 'Retirer le logo' ),
				empty( $args['value'] ) ? ' style="display:none;"' : '',
			) );
		}

		/**
		 * Validate our custom options
		 */
		static function validate_options( $output, $input ) {
			if ( isset( $input['invoice-new-year-reset'] ) ) {
				$output['invoice-new-year-reset'] = (int) $input['invoice-new-year-reset'];
			}
			if ( ! empty( $input['invoice-current-number'] ) ) {
				$output['invoice-current-number'] = (int) $input['invoice-current-number'];
			}
			if ( isset( $input['invoice-logo'] ) ) {
				$output['invoice-logo'] = (int) $input['invoice-logo'];
			}
			if ( isset( $input['invoice-company'] ) ) {
				$output['invoice-company'] = sanitize_text_field( $input['invoice-company'] );
			}
			if ( isset( $input['invoice-cgv'] ) ) {
				$output['invoice-cgv'] = sanitize_textarea_field( $input['invoice-cgv'] );
			}
			if ( isset( $input['invoice-thankyou'] ) ) {
				$output['invoice-thankyou'] = sanitize_textarea_field( $input['invoice-thankyou'] );
			}
			return $output;
		}

		/**
		 * Listen payment result to create invoice
		 */
		static function maybe_create_invoice( $payment_token, $result, $data ) {
			if ( 2 !== $result ) {
				return;
			}

			$attendees = get_posts( array(
				'posts_per_page' => -1,
				'post_type' => 'tix_attendee',
				'post_status' => 'any',
				'meta_query' => array(
					array(
						'key' => 'tix_payment_token',
						'compare' => '=',
						'value' => $payment_token,
						'type' => 'CHAR',
					),
				),
			) );
			if ( ! $attendees ) {
				return;
			}
			if ( $metas = get_post_meta( $attendees[0]->ID, 'invoice_metas', true ) ) {
				$order = get_post_meta( $attendees[0]->ID, 'tix_order', true );
				CampTix_Addon_Invoices::create_invoice( $attendees[0], $order, $metas );
			}
		}

		/**
		 * Get, increment and return invoice number
		 * @todo can be refactorized
		 */
		static function create_invoice_number() {
			$opt = get_option( 'camptix_options' );
			$current = ! empty( $opt['invoice-current-number'] ) ? intval( $opt['invoice-current-number'] ) : 1;
			$year = date( 'Y' );

			if ( ! empty( $opt['invoice-new-year-reset'] ) ) {
				if ( $opt['invoice-current-year'] != $year ) {
					$opt['invoice-current-number'] = 1;
					$current = 1;
				}
				$current = sprintf( '%s-%s', $year, $current );
			}
			
			$opt['invoice-current-year'] = $year;
			$opt['invoice-current-number']++;
			update_option( 'camptix_options', $opt );
			return $current;
		}

		/**
		 * Create invoice
		 * @todo Faire une liaison entre la facture et les participants
		 */
		static function create_invoice( $attendee, $order, $metas ) {
			$number = CampTix_Addon_Invoices::create_invoice_number();
			$arr = array(
				'post_type'   => 'tix_invoice',
				'post_status' => 'publish',
				'post_title'  => sprintf( __( 'Facture n°%1$s la commande %2$s du %3$s' ), $number, get_post_meta( $attendee->ID, 'tix_transaction_id', true ), get_the_time( 'd/m/Y', $attendee ) ),
				'post_name'   => sprintf( 'invoice-%s', $number ),
			);
			$invoice = wp_insert_post( $arr );
			if ( ! $invoice || is_wp_error( $invoice ) ) {
				return;
			}
			update_post_meta( $invoice, 'invoice_number', $number );
			update_post_meta( $invoice, 'invoice_metas', $metas );
			update_post_meta( $invoice, 'original_order', $order );
		}

		/**
		 * Enqueue assets
		 * @todo enqueue only on [camptix] shortcode
		 */
		static function enqueue_assets() {
			wp_register_script( 'camptix-invoices', plugins_url( 'camptix-invoices.js', __FILE__ ), array( 'jquery' ), true );
			wp_enqueue_script( 'camptix-invoices' );
			wp_localize_script( 'camptix-invoices', 'camptixInvoicesVars', array(
				'invoiceDetailsForm' => home_url( '/wp-json/camptix-invoices/v1/invoice-form' ),
			) );
			wp_register_style( 'camptix-invoices-css', plugins_url( 'camptix-invoices.css', __FILE__ ) );
			wp_enqueue_style( 'camptix-invoices-css' );
		}

		/**
		 * Register assets on admin side
		 */
		static function admin_enqueue_assets() {
			wp_register_script( 'admin-camptix-invoices', plugins_url( 'camptix-invoices-back.js', __FILE__ ), array( 'jquery' ), true );
		}

		/**
		 * Attendee invoice information
		 * (also check for missing invoice infos)
		 */
		static function attendee_info( $attendee_info ) {
			global $camptix;
			if ( ! empty( $_POST['camptix-need-invoice'] ) ) {
				if ( empty( $_POST['invoice-email'] )
				  || empty( $_POST['invoice-name'] )
				  || empty( $_POST['invoice-address'] )
				  || ! is_email( $_POST['invoice-email'] )
				) {
					$camptix->error_flag( 'fuck' );
				} else {
					$attendee_info['invoice-email'] = sanitize_email( $_POST['invoice-email'] );
					$attendee_info['invoice-name'] = sanitize_text_field( $_POST['invoice-name'] );
					$attendee_info['invoice-address'] = sanitize_textarea_field( $_POST['invoice-address'] );
				}
			}
			return $attendee_info;
		}

		/**
		 * Define custom attributes for an attendee object
		 */
		static function attendee_object( $attendee, $attendee_info ) {
			if ( ! empty( $attendee_info['invoice-email'] ) ) {
				$attendee->invoice = array(
					'email'   => $attendee_info['invoice-email'],
					'name'    => $attendee_info['invoice-name'],
					'address' => $attendee_info['invoice-address'],
				);
			}
			return $attendee;
		}

		/**
		 * Add Invoice meta on an attendee post
		 */
		static function add_meta_invoice_on_attendee( $post_id, $attendee ) {
			if ( ! empty( $attendee->invoice ) ) {
				update_post_meta( $post_id, 'invoice_metas', $attendee->invoice );
				global $camptix;
				$camptix->log( __( 'Le participant a demandé une facture.'), $post_id, $attendee->invoice );
			}
		}

		/**
		 * My custom errors flags
		 */
		static function error_flag() {
			
			global $camptix;
			/**
			 * Hack
			 */
			$rp = new ReflectionProperty( 'CampTix_Plugin', 'error_flags' );
			$rp->setAccessible( true );
			$error_flags = $rp->getValue( $camptix );
			if ( ! empty( $error_flags['fuck'] ) ) {
				$camptix->error( 'Vous avez demandé une facture, il faut donc complèter les champs requis.' );
			}
		}

		/**
		 * Display invoice meta on attendee admin page
		 */
		static function add_invoice_meta_on_attendee_metabox( $rows, $post ) {
			$invoice_meta = get_post_meta( $post->ID, 'invoice_metas', true );
			if ( ! empty( $invoice_meta ) ) {
				$rows[] = array( __( 'A demandé une facture' ), __( 'Oui' ) );
				$rows[] = array( __( 'Destinataire de la facture' ), $invoice_meta['name'] );
				$rows[] = array( __( 'Facture à envoyer à' ), $invoice_meta['email'] );
				$rows[] = array( __( 'Adresse du client' ), $invoice_meta['address'] );
			} else {
				$rows[] = array( __( 'A demandé une facture' ), __( 'Non' ) );				
			}
			return $rows;
		}
	}
	camptix_register_addon( 'CampTix_Addon_Invoices' );

	add_action( 'init', 'register_tix_invoice' );
}

/**
 * Register invoice CPT
 */
function register_tix_invoice() {
	register_post_type( 'tix_invoice', array(
		'label'        => __( 'Factures' ),
		'labels' => array(
			'name' => __( 'Factures' ),
		),
		'public'       => true,
		'show_ui'      => true,
		'show_in_menu' => 'edit.php?post_type=tix_ticket',
	) );
}

/**
 * Register REST API endpoint to serve invoice details form
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'camptix-invoices/v1', '/invoice-form', array(
		'methods'  => 'GET',
		'callback' => 'camptix_invoice_form',
	) );
} );

function camptix_invoice_form() {
	$fields = array();
	$fields['main' ]  = '<input type="checkbox" value="1" name="camptix-need-invoice" id="camptix-need-invoice"/> <label for="camptix-need-invoice">' . __( 'J’ai besoin d’une facture' ) . '</label>';
	$fields['hidden'][] = '<td class="tix-left"><label for="invoice-email">' . __( 'Email pour recevoir la facture' ) . ' <span class="tix-required-star">*</span></label></td>
		<td class="tix-right"><input type="text" name="invoice-email" id="invoice-email" pattern="^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$"></td>';
	$fields['hidden'][] = '<td class="tix-left"><label for="invoice-name">' . __( 'Nom de facturation' ) . ' <span class="tix-required-star">*</span></label></td>
		<td class="tix-right"><input type="text" name="invoice-name" id="invoice-name"></td>';
	$fields['hidden'][] = '<td class="tix-left"><label for="invoice-address">' . __( 'Adresse de facturation' ) . ' <span class="tix-required-star">*</span></label></td>
		<td class="tix-right"><textarea name="invoice-address" id="invoice-address" rows="2"></textarea></td>';
	$fields = apply_filters( 'camptix-invoices/invoice-details-form-fields', $fields );
	$fields_formatted = $fields['main'] . '<table class="camptix-invoice-details tix_tickets_table tix_invoice_table"><tbody><tr>' . implode( '</tr><tr>', $fields[ 'hidden'] ) . '</tr></tbody></table>';
	$form = apply_filters( 'camptix-invoice/invoice-details-form', '<div class="camptix-invoice-toggle-wrapper">' . $fields_formatted . '</div>', $fields );
	wp_send_json( array( 'form' => $form ) );
}
