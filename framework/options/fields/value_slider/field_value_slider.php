<?php
class Radium_Options_value_slider extends Radium_Options{	
	
	/**
	 * Field Constructor.
	 *
	 * Required - must call the parent constructor, then assign field and value to vars, and obviously call the render field function
	 *
	 * @since Radium_Options 1.0
	*/
	function __construct($field = array(), $value ='', $parent){
		
		parent::__construct($parent->sections, $parent->args, $parent->extra_tabs);
		$this->field = $field;
		$this->value = $value;
		//$this->render();
		
	}//function
	
	
	
	/**
	 * Field Render Function.
	 *
	 * Takes the vars and outputs the HTML for the field in the settings
	 *
	 * @since Radium_Options 1.0
	*/
	function render(){
		
		$class = (isset($this->field['class'])) ? $this->field['class'] : 'value-slider';
  		
		echo '<div id="'.$this->field['id'].'" class="radium-opts-value-slider '.$class.'" data-name="'.$this->args['opt_name'].'['.$this->field['id'].']" data-min="'.$this->field['min_value'].'" data-max="'.$this->field['max_value'].'" data-value="'.$this->value.'" data-unit="'.$this->field['unit'].'"></div><div class="clear"></div>';
		
		echo (isset($this->field['desc']) && !empty($this->field['desc']))?' <span class="description">'.$this->field['desc'].'</span>':'';
		
	}//function
	
	
	/**
	 * Enqueue Function.
	 *
	 * If this field requires any scripts, or css define this function and register/enqueue the scripts/css
	 *
	 * @since Radium_Options 1.0
	*/
	function enqueue(){
		
		wp_enqueue_script(
			'radium-opts-field-value-slider-js', RADIUM_OPTIONS_URL.'fields/value_slider/field_value_slider.js', array( 'jquery', 'jquery-ui-slider' ), time(), true
		);
		
		wp_enqueue_style('radium-opts-jquery-ui-css');
  		
	}//function
	
}//class
?>