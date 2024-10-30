<?php
/**
 * Base Controller Class
 */
class Controller {
	/* Properties */
	public $view_base_path = "";
	public $view_folder    = "";

	/**
	 * Constructor of Base Controller to setting View folder for rendering
	 */
	function __construct(){
		$this->view_base_path = CART_CONVERTER_DIR . "/views/{$this->view_folder}/";
	}

	/**
	 * Render data into View
	 * @param  string  $path Path to View need render
	 * @param  array   $data Data of View
	 * @param  boolean $echo Show or return HTML
	 */
	public function render_view( $path, $data = array(), $echo = true ){
		ob_start();
		
		extract( $data );

		include_once( $this->view_base_path . $path );

		$content = ob_get_contents();
		ob_end_clean();

		if( $echo ){
			echo $content;
		} else {
			return $content;
		}
	}
}
