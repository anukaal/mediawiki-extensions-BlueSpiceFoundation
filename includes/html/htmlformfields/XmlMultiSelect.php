<?php

class XmlMultiSelect extends XmlSelect {

	public function addOption( $name, $value = false ) {
		$value = ( $value !== false ) ? $value : $name;

		$this->options[] = [ $name => $value ];
	}

	public static function formatOptions( $options, $default = false ) {
		$data = '';

		if ( !is_array( $default ) ) {
			$default = [];
		}

		foreach ( $options as $label => $value ) {
			if ( is_array( $value ) ) {
				$contents = self::formatOptions( $value, $default );
				$data .= Html::rawElement( 'optgroup', [ 'label' => $label ], $contents ) . "\n";
			} else {
				$data .= Xml::option( $label, $value, ( array_search( $value, $default ) !== false ) ) . "\n";
			}
		}

		return $data;
	}

	/**
	 * @return string
	 */
	public function getHTML() {
		$contents = '';
		foreach ( $this->options as $options ) {
			$contents .= self::formatOptions( $options, $this->default );
		}
		return Xml::tags( 'select', $this->attributes, rtrim( $contents ) );
	}

}
