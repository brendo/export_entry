<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');

	class contentExtensionexport_entryAjaxEntries extends AjaxPage {
		protected $_driver;

		public function view() {
			$this->_driver = $this->_Parent->ExtensionManager->create('export_entry');

			$this->target = $_GET['section'];
			$this->fieldID = $_GET['field'];

			if(!$this->validate()) {
				$this->_status = self::STATUS_BAD;
				$this->generate();
				return false;
			}

			$sectionManager = new SectionManager($this->_Parent);
			$entryManager = new EntryManager($this->_Parent);

			$section = $sectionManager->fetch($this->target);
			$fields = $section->fetchFields();

			/*	Get all the fields from the target section
			**	Loop them for the section Link field
			*/
			foreach($fields as $field) {
				if($field->get("id") == $this->fieldID) {
					if($field->get('type') == "bilink") {
						$linked_section_id = $field->get("linked_section_id");
						$linked_field_id = $field->get("linked_field_id");
					} else if($field->get('type') == "selectbox_link") {
						$linked_section_id = Symphony::Database()->fetchVar("parent_section_id",0,"SELECT parent_section_id 
							FROM `tbl_sections_association` 
							WHERE `child_section_field_id` = {$field->get("id")}
							LIMIT 1");
						$linked_field_id = $field->get("related_field_id");
					}
				}
			}

			/*	Got the linked field, now get the other end of the
			**	link. Use the first Visible column as the output handle
			*/
			$linked_field_id = $this->_driver->fetchVisibleFieldID($linked_section_id);

			/*	Foreach entry in the linked section, display the first
			**	column to be selected
			*/
			$entries = $entryManager->fetch(null,$linked_section_id);
			foreach($entries as $entry) {
				$data = current($entryManager->fetch($entry->get('id'),$linked_section_id));
				$values = $data->getData($linked_field_id);

				$el = new XMLElement("entry", General::sanitize($values['value']));
				$el->setAttribute('id', $entry->get('id'));

				$this->_Result->appendChild($el);
			}
		}

		public function validate() {
			return (is_numeric($this->target) && is_numeric($this->fieldID));
		}
	}
?>
