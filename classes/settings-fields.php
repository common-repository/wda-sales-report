<?php

/*-------------------------------------------
*  Exit if accessed directly
*-------------------------------------------*/
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WDASR_Settings_Field' ) ) {
	class WDASR_Settings_Field {
		public $args;
		private $input_value;
		
		public function __construct () {}
	
	
		/*-------------------------------------------
		*  Rendering Settings Form
		*-------------------------------------------*/
		public function render_form ( $args ) {
			$this->args = $args;
			$this->input_value = get_option( $this->args['option_name'], '' );

			$html = '
			<label>
				{INPUT}
				<p class="description" id="{ID}-description">{DESCRIPTION}</p>
			</label>';
	
	
			/*----- String Translation -----*/
			$html = strtr( $html, [
				'{INPUT}'		=> $this->render_input( $this->args ),
				'{ID}'          => esc_attr( $this->args['option_name'] ),
				'{DESCRIPTION}' => $this->args['description'],
			] );
		
			return $html;
		}
	
	
		/*-------------------------------------------
		*  Rendering Input Field
		*-------------------------------------------*/
		private function render_input ( $args ) {
			$html = '';
	
			switch ( $args['type'] ) {
				case 'select':
					$html .= '<select id="{ID}" name="{NAME}">';
			
					$html .= '</select>';
					break;
				
				default:
					$html .= '<input id="{ID}" name="{NAME}" type="{TYPE}" value="{VALUE}" {CHECKED} {CUSTOM}/>';
					break;
			}
	
	
			/*----- String Translation -----*/
			$html = strtr( $html, [
				'{ID}'          => esc_attr( $args['option_name'] ),
				'{NAME}'        => esc_attr( $args['option_name'] ),
				'{TYPE}'        => esc_attr( $args['type'] ),
				'{VALUE}'       => $args['type'] !== 'checkbox' ? esc_attr( $this->input_value ) : 'yes',
				'{CHECKED}'     => $this->input_value && $args['type'] == 'checkbox' ? checked( 'yes', esc_attr( $this->input_value ), false) : '',
				'{CUSTOM}'		=> array_key_exists( 'custom_arg', $args ) ? esc_attr( $args['custom_arg'] ) : ''
			] );
	
			return $html;
		}
	}
}