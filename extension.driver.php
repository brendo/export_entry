<?php

	require_once(TOOLKIT . '/class.entrymanager.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');

	Class Extension_export_entry extends Extension {

		protected $_Parent = null;
		protected static $section_visible = array();

		public function about(){
			return array('name' => 'Export Entry',
						 'version' => '0.4.2',
						 'release-date' => '2010-08-30',
						 'author' => array('name' => 'Brendan Abbott',
										   'website' => 'http://www.bloodbone.ws',
										   'email' => 'brendan@bloodbone.ws'),
						'description' => 'Allows you to select entries to export to CSV',
						'dependencies' => array(
							'asdc' => 'http://github.com/pointybeard/asdc',
							'databasemanipulator' => 'http://github.com/yourheropaul/databasemanipulator')
				 		);
		}

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'initaliseAdminPageHead'
				)
			);
		}

		public function fetchNavigation() {
			return array(
				array(
					'location'	=> 200,
					'name'	=> 'Exporter',
					'link'	=> '/export/'
				)
			);
		}

		public function initaliseAdminPageHead($context) {
			$page = $context['parent']->Page;

			if ($page instanceof contentExtensionexport_entryExport) {
				$page->addScriptToHead(URL . '/extensions/export_entry/assets/ee.ajaxify.js',100100992);
				$page->addStylesheetToHead(URL . '/extensions/export_entry/assets/ee.default.css','screen', 100100992);
			}
	    }

		## http://au.php.net/manual/en/function.str-getcsv.php#88353
		function str_putcsv($array, $delimiter = ',', $enclosure = '"', $terminator = "\n") {

	        # First convert associative array to numeric indexed array
	        foreach ($array as $key => $value) $workArray[] = $value;
	        $returnString = '';                 # Initialize return string
	        $arraySize = count($workArray);     # Get size of array

	        for ($i=0; $i<$arraySize; $i++) {
	            # Nested array, process nest item
	            if (is_array($workArray[$i])) {
	                $returnString .= $this->str_putcsv($workArray[$i], $delimiter, $enclosure, $terminator);
	            } else {
	                switch (gettype($workArray[$i])) {
	                    # Manually set some strings
	                    case "NULL":     $_spFormat = ''; break;
	                    case "boolean":  $_spFormat = ($workArray[$i] == true) ? 'true': 'false'; break;
	                    # Make sure sprintf has a good datatype to work with
	                    case "integer":  $_spFormat = '%i'; break;
	                    case "double":   $_spFormat = '%0.2f'; break;
	                    case "string":   $_spFormat = '%s'; break;
	                    # Unknown or invalid items for a csv - note: the datatype of array is already handled above, assuming the data is nested
	                    case "object":
	                    case "resource":
	                    default:         $_spFormat = ''; break;
	                }
	                $returnString .= sprintf('%2$s'.$_spFormat.'%2$s', $workArray[$i], $enclosure);
					$returnString .= ($i < ($arraySize-1)) ? $delimiter : $terminator;
	            }
	        }
	        # Done the workload, return the output information
	        return $returnString;
	    }

		function fetchVisibleFieldID($section) {
			if(isset(Extension_export_entry::$section_visible[$section])) {
				return Extension_export_entry::$section_visible[$section];
			}

			$sectionManager = new SectionManager($this->_Parent);

			$linked_section = $sectionManager->fetch($section);
			$li_field = current($linked_section->fetchVisibleColumns());

			Extension_export_entry::$section_visible[$section] = $li_field->get('id');

			return Extension_export_entry::$section_visible[$section];
		}
	}

?>
