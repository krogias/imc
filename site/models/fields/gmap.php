<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */


defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

/**
 * Supports an HTML select list of categories
 */
class JFormFieldGmap extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'Gmap';
	protected $latitudefield;
	protected $longitudefield;
	protected $width;
	protected $height;
	protected $lat;
	protected $lng;
	protected $zoom;
	protected $icon = '';
	
	protected $mapOnly = false;
	
	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   3.2
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'latitudefield':
			case 'longitudefield':
			case 'width':
			case 'height':
			case 'lat':
			case 'lng':
			case 'zoom':			
			case 'icon':			
			case 'mapOnly':			
				return $this->$name;
		}

		return parent::__get($name);
	}


/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param   string  $name   The property name for which to the the value.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'latitudefield':
			case 'longitudefield':
			case 'width':
			case 'height':
			case 'lat':
			case 'lng':
			case 'zoom':
			case 'icon':
			case 'mapOnly':
				$this->$name = (string) $value;
				break;
			default:
				parent::__set($name, $value);
		}
	}

	public function showField($lat, $lng, $zoom = 14) 
	{
		$this->element['disabled'] = true;
		$this->element['latitudefield'] = 'latitudefield';
		$this->element['longitudefield'] = 'longitudefield';
		$this->lat = $lat;
		$this->lng = $lng;
		$this->zoom = $zoom;
		return $this->getInput();
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{
		$disabled = false;
		if(isset($this->element['disabled'])){
			$disabled = $this->element['disabled'];
		}
		JFactory::getDocument()->addStyleSheet(JURI::root(true).'/components/com_imc/models/fields/gmap/css/gmap.css');
		
		//(isset($this->element['api_key']) ? $this->element['api_key'] : '');
		if(!isset($this->element['latitudefield']))
			return '<strong>GMap field argument `latitudefield` is not set</strong>';
		if(!isset($this->element['longitudefield']))
			return '<strong>GMap field argument `longitudefield` is not set</strong>';

		$params = JComponentHelper::getParams('com_imc');
		$api_key = $params->get('api_key');

		if($api_key == '')
			echo '<span style="color: red; font-weight:bold;">'.JText::_('COM_IMC_JFIELD_GMAP_MISSING_KEY').'</span>';
		

		//get google maps default options if no value is set (e.g. new record)
		$lat        = (isset($this->lat) ? $this->lat : $params->get('latitude') );
		$lng        = (isset($this->lng) ? $this->lng : $params->get('longitude') );
		$zoom       = (isset($this->zoom) ? $this->zoom : $params->get('zoom') );
		//If field is used on params just set some default values...
		if(!$zoom) $zoom = 14;
		if(!$lat)  $lat  = '40.626449';
		if(!$lng)  $lng  = '22.948426';

		$scrollwheel = ($params->get('scrollwheel') == 1 ? true : false);
		$lockaddressbtn = ($params->get('lockaddressbtn') == 1 ? true : false);
		$language   = $params->get('maplanguage');
		$hiddenterm = $params->get('hiddenterm');
		$boundaries = $params->get('boundaries', null);


		if($api_key != '')
			JFactory::getDocument()->addScript('https://maps.googleapis.com/maps/api/js?key='.$api_key.'&language='.$language.'&libraries=geometry');
		else
			JFactory::getDocument()->addScript('https://maps.googleapis.com/maps/api/js?language='.$language.'&libraries=geometry');

		$borders = array();
		if(!is_null($boundaries))
		{
			$arPolygons = explode(";", $boundaries);

			foreach ($arPolygons as $poly) {
				$polygon = str_replace("\r", "", $poly);

				$bounds = array();
				$arBoundaries = explode("\n", $polygon);
				foreach ($arBoundaries as $bnd)
				{
					if(strlen($bnd) > 1) {
						$latLng = explode(',', $bnd);
						array_push($bounds, array('lng' => (double)$latLng[0], 'lat' => (double)$latLng[1]));
					}
				}

				array_push($borders, $bounds);
			}
			$boundaries = json_encode($borders);
		}

		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/gmap/js/gmap.js');


		//set js variables
		$itemId   = (isset($this->element['userstate']) ? JFactory::getApplication()->getUserState($this->element['userstate']) : JRequest::getVar('id', 0));
		if($itemId == '')
			$itemId = 0;

		$script = array();
		if($disabled)
			$script[] = "var disabled=".$disabled.";";
		else
			$script[] = "var disabled=false;";

		$script[] = "var itemId=".$itemId.";";
		$script[] = "var Lat='".$lat."';";
		$script[] = "var Lng='".$lng."';";
		$script[] = "var latfield='jform_".$this->element['latitudefield']."';";
		$script[] = "var lngfield='jform_".$this->element['longitudefield']."';";
		$script[] = "var addrfield='".$this->id."';";
		$script[] = "var zoom=".$zoom.";";
		$script[] = "var scrollwheel='".$scrollwheel."';";
		$script[] = "var icon='".$this->icon."';";
		$script[] = "var language='".$language."';";
		$script[] = "var hiddenterm='".$hiddenterm."';";
		if(!is_null($boundaries))
		{
			$script[] = "var boundaries=JSON.parse('" . $boundaries . "');";
		}
		$script[] = "var info='".addslashes(JText::_('COM_IMC_DRAG_MARKER'))."';";
		$script[] = "var info_unlock='".JText::_('COM_IMC_UNLOCK_ADDRESS')."';";
		$script[] = "var notfound='".JText::_('COM_IMC_ADDRESS_NOT_FOUND')."';";
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		//initialize map
		$script = array();
		$script[] = "";
		$script[] = "google.maps.event.addDomListener(window, 'load', initialize);";
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		//style
		$style = array();
		$style[] = (isset($this->element['width']) ? 'width:'.$this->element['width'].';' : '');
		$style[] = (isset($this->element['height']) ? 'height:'.$this->element['height'].';' : '');

		//set html
		$html = array();

		if($this->mapOnly){
			$html[] = '<div style="'.implode("", $style).'display:block;clear:both;">';
			$html[] = '	<div id="imc-map-canvas"></div>';
			$html[] = '</div>';
			return implode("\n", $html);
		}

        $html[] = '<div style="'.implode("", $style).'display:table-cell;clear:both;padding-bottom: 10px;">';
		if(!$disabled) {
			$html[] = '		<button id="searchaddress" class="btn btn-mini" type="button"><i class="icon-search icon-white"></i> '. JText::_('COM_IMC_CUSTOM_FIELD_LOCATE_ADDRESS') . '</button>';
			if($lockaddressbtn) {
				$html[] = '		<button id="lockaddress" class="btn btn-mini" type="button"><i class="icon-lock"></i> ' . JText::_('COM_IMC_CUSTOM_FIELD_LOCK_ADDRESS') . '</button>';
			}
			$html[] = '		<button id="locateposition" style="float:right;" class="btn btn-mini" type="button"><i class="icon-screenshot"></i> '. JText::_('COM_IMC_CUSTOM_FIELD_LOCATE_POSITION') . '</button>';
		}
		$html[] = ' <textarea placeholder="'.JText::_('COM_IMC_FORM_LBL_ISSUE_ADDRESS').'" '. ($disabled ? "disabled=\"\"" : "").' class="imc-gmap-textarea validate-boundaries" rows="3" cols="75" id="' . $this->id . '" name="' . $this->name . '">'.htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8').'</textarea>';
        $html[] = '	<div id="imc-map-canvas"></div>';
		$html[] = '</div>';

		$html[] = '<!-- Modal -->';
		$html[] = '<div id="IMC_searchModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="searchModalLabel" aria-hidden="true">';
		$html[] = '	<div class="modal-dialog modal-sm">';
		$html[] = '		<div class="modal-content">';
		$html[] = '			<div class="modal-header">';
		$html[] = '				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>';
		$html[] = '				<h3 id="searchModalLabel">Search Results</h3>';
		$html[] = '			</div>';
		$html[] = '			<div class="modal-body">';
		$html[] = '				<p id="searchBody">One fine body…</p>';
		$html[] = '			</div>';
		$html[] = '			<div class="modal-footer">';
		$html[] = '				<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>';
		$html[] = '			</div>';
		$html[] = '		</div>';
		$html[] = '	</div>';
		$html[] = '</div>';

		return implode("\n", $html);
	}
}
