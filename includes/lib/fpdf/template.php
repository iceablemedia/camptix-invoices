<?php
/**
 * This file defines the default styles for an invoice template.
 *
 * @package FPDF
 */

// Company details.
$pdf->template['header']['fontSize']   = 11;
$pdf->template['header']['lineHeight'] = 5;
$pdf->template['header']['margin']     = array( 40, 0, 0, 10 );

// Page number.
$pdf->template['infoPage']['margin'] = array( 5, 5, 0, 120 );
$pdf->template['infoPage']['align']  = 'R';

// Invoice number.
$pdf->template['infoFacture']['margin']   = array( 10, 5, 0, 120 );
$pdf->template['infoFacture']['fontSize'] = 15;
$pdf->template['infoFacture']['align']    = 'R';
$pdf->template['infoFacture']['fontFace'] = 'B';

// Date.
$pdf->template['infoDate']['margin'] = array( 15, 5, 0, 120 );
$pdf->template['infoDate']['align']  = 'R';

// Client.
$pdf->template['client']['margin'] = array( 30, 0, 0, 120 );

// Footer.
$pdf->template['footer']['fontSize']        = 9;
$pdf->template['footer']['align']           = 'C';
$pdf->template['footer']['color']           = array(
	'r' => 100,
	'g' => 100,
	'b' => 100,
);
$pdf->template['footer']['backgroundColor'] = array(
	'r' => 245,
	'g' => 245,
	'b' => 245,
);
$pdf->template['footer']['margin']          = array( 265, 10, 5, 10 );
$pdf->template['footer']['padding']         = array( 4, 5, 0, 5 );

// Product header.
$pdf->template['productHead']['fontFace']        = 'B';
$pdf->template['productHead']['margin']          = array( 20, 0, 0, 10 );
$pdf->template['productHead']['padding']         = array( 0, 4, 0, 4 );
$pdf->template['productHead']['backgroundColor'] = array(
	'r' => 50,
	'g' => 50,
	'b' => 50,
);
$pdf->template['productHead']['color']           = array(
	'r' => 230,
	'g' => 230,
	'b' => 230,
);

// Product list.
$pdf->template['product']['backgroundColor']  = array(
	'r' => 235,
	'g' => 235,
	'b' => 235,
);
$pdf->template['product']['backgroundColor2'] = array(
	'r' => 245,
	'g' => 245,
	'b' => 245,
);
$pdf->template['product']['color']            = array(
	'r' => 0,
	'g' => 0,
	'b' => 0,
);
$pdf->template['product']['color2']           = array(
	'r' => 20,
	'g' => 20,
	'b' => 20,
);
$pdf->template['product']['margin']           = array( 1, 0, 0, 10 );
$pdf->template['product']['padding']          = array( 1, 4, 1, 4 );

// Heading of totals.
$pdf->template['totalHead']['lineHeight'] = 0;
$pdf->template['totalHead']['margin']     = array( 10, 0, 0, 0 );

// List of totals.
$pdf->template['total']['margin'] = array( 1, 0, 1, 130 );

// Custom elements.
