<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(EXTENSIONS . '/databasemanipulator/lib/class.databasemanipulator.php');

	class contentExtensionexport_entryExport extends AdministrationPage {
		protected $_driver;

		public function checkExtensions($name = null) {
			$load = array();
			$about = $this->_driver->about();
			foreach($about['dependancies'] as $name => $git) {
				if($this->checkExtension($name) !== EXTENSION_ENABLED) {
					$load[] = sprintf('%s is not enabled. Download it @ <a href="%2$s">%2$s</a>', $name, $git, $git);
				};
			}

			return $load;
		}

		public function checkExtension($name) {
			$extensionManager = $this->_Parent->ExtensionManager;
			return $extensionManager->fetchStatus($name);
		}

		public function __viewIndex() {
			$this->_driver = $this->_Parent->ExtensionManager->create('export_entry');

			$this->setPageType('form');
			$this->Form->setAttribute('enctype', 'multipart/form-data');
			$this->setTitle('Symphony &ndash; Export Entry');

			$this->appendSubheading('Export');

		// Settings --------------------------------------------------------

			$container = new XMLElement('fieldset');
			$container->setAttribute('class', 'settings');

			$load = $this->checkExtensions();
			if(count($load) !== 0) {
				$container->appendChild(
						new XMLElement("p", "Some of the dependancies needed by this extension were not met.")
					);
				$list = new XMLElement('ul');
				foreach($load as $ext) {
					$list->appendChild(
						new XMLElement("li", $ext)
					);
				}
				$container->appendChild($list);
				$this->Form->appendChild($container);
				return;
			}

			$container->appendChild(
				new XMLElement('legend', 'Select the export <code>section</code>')
			);

			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$this->__viewIndexSectionName($group);

			/*
			**	Check if the bilink is installed
			*/

			if($this->checkExtension("bilinkfield") == EXTENSION_ENABLED) {
				$this->__viewIndexSectionLinks($group);
				$this->__viewIndexLinkedEntries($group);
			}

			$container->appendChild($group);
			$this->Form->appendChild($container);

		//---------------------------------------------------------------------

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');

			$attr = array('accesskey' => 's');
			$div->appendChild(Widget::Input('action[save]', 'Export', 'submit', $attr));

			$this->Form->appendChild($div);
		}

	/*-------------------------------------------------------------------------
		Sections:
	-------------------------------------------------------------------------*/
		public function __viewIndexSectionName($context) {
			$sectionManager = new SectionManager($this->_Parent);

			/*	Label	*/
			$label = Widget::Label(__('Section'));

			/*	Fetch sections & populate a dropdown	*/
			$sections = $sectionManager->fetch(NULL, 'ASC', 'name');
			$options = array();

			if(is_array($sections) && !empty($sections)){
				foreach($sections as $s) {
					$options[] = array(
						$s->get('id'),
						($fields['target'] == $s->get('id')),
						$s->get('name')
					);
				}
			}

			$label->appendChild(Widget::Select('fields[target]', $options, array('id' => 'context')));

			$context->appendChild($label);

		}

		public function __viewIndexSectionLinks($context) {
			$sectionManager = new SectionManager($this->_Parent);

			/*	Label	*/
			$label = Widget::Label(__('Available Filters'));

			$options = null;

			$label->appendChild(new XMLElement("span",__('Ignore if you do not wish to filter the exported entries')));
			$label->appendChild(Widget::Select('fields[linked-section]', $options, array('id' => 'linked-section')));

			$context->appendChild($label);
		}

		public function __viewIndexLinkedEntries($context) {
			$sectionManager = new SectionManager($this->_Parent);

			/*	Label	*/
			$label = Widget::Label(__('Filter by Linked Entry'));

			$options = null;

			$label->appendChild(Widget::Select('fields[linked-entry]', $options, array('id' => 'linked-entry')));

			$context->appendChild($label);
		}

		public function __actionIndex() {
			if (empty($this->_driver)) {
				$this->_driver = $this->_Parent->ExtensionManager->create('export_entry');
			}

			if (@isset($_POST['action']['save'])) {
				$this->export($_POST['fields']);
			}
		}

		/*-------------------------------------------------------------------------*/

		/*-------------------------------------------------------------------------*/
		public function export($post) {
			DatabaseManipulator::associateParent($this->_Parent);

			if($post['linked-section'] and $post['linked-entry']) {
				$filter = array($post['linked-section'] => $post['linked-entry']);
			} else {
				$filter = null;
			}

			$entries = DatabaseManipulator::getEntries(
				$post['target'],
				'*',
				$filter
			);

			/*	CSV Header
			**	--------------
			**	Build the header from the fields, but we'll only take fields that have a 'value' or a 'file'
			*/
			$header = $data = array();
			$export = array('value','file');

			$header_entry = array_values(current($entries));

			foreach($header_entry[1] as $name => $entry) {
				foreach($export as $field_type) {
					if(array_key_exists($field_type, $entry)) {
						$header[] = $name;
					}
				}
			}
			$output = $this->_driver->str_putcsv($header);

			/*	Data
			**	-----------
			**	Get the data, filtering by the actual header vars
			**	If it's 'file', append the root so that the csv will have the full link to the files
			*/
			foreach($entries as $k => $v) {
				foreach($v['fields'] as $name => $entry) {
					if(in_array($name, $header)) {
						if(array_key_exists("value", $entry)) {
							$data[$k][] = $entry['value'];
						} else {
							$data[$k][] = URL . "/workspace" . $entry['file'];
						}
					}
				}
			}
			$output .= $this->_driver->str_putcsv($data);

			/* We got our CSV, so lets output it, but we'll exit, because we don't want any Symphony output*/
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=export_entry_' . DateTimeObj::get('Y-m-d') . '.csv');

			echo $output;
			exit;
		}
	}
?>