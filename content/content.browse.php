<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentExtensionFilebrowserBrowse extends AdministrationPage{

		function __construct(){
			parent::__construct();
			$this->setTitle('Symphony &ndash; File Browser');
		}
	
		function action(){
			
			$checked = @array_keys($_POST['items']);
			
			if(!isset($_POST['action']['apply']) || empty($checked)) return;
			
			$FileManager = Symphony::ExtensionManager()->create('filbrowser');		
			
			switch($_POST['with-selected']){
				
				case 'delete':
				
					$path = DOCROOT . $FileManager->getStartLocation();
					
					foreach($checked as $rel_file){
						$abs_file = $path . '/' . ltrim($rel_file, '/');

						if(!is_dir($abs_file) && file_exists($abs_file)) General::deleteFile($abs_file);
						elseif(is_dir($abs_file)){
							
							if(!@rmdir($abs_file))
								$this->pageAlert(
									__(
										'%s could not be deleted as is still contains files.',
										array('<code>'.$rel_file.'</code>')
									),
									AdministrationPage::PAGE_ALERT_ERROR
								);
						}
						
					}
					
					break;
					
				case 'archive':
				
					$path = (is_array($this->_context) && !empty($this->_context) ? '/' . implode('/', $this->_context) . '/' : NULL);
					$filename = $FileManager->createArchive($checked, $path);
					
					break;
					
					
			}
		}
	
		function view(){

			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/filebrowser/assets/styles.css', 'screen', 70);

			$FileManager = Symphony::ExtensionManager()->create('filebrowser');

			$path = DOCROOT . $FileManager->getStartLocation() . (is_array($this->_context) && !empty($this->_context) ? '/' . implode('/', $this->_context) . '/' : NULL);
			$actions = $this->Context;
			if(is_writable($path)) {
				// Build file/dir creation menu
				
				
				$create_menu = new XMLElement('ul');
				$create_menu->setAttribute('class', 'actions');
			
				$li = new XMLElement('li');
				$li->appendChild(Widget::Anchor('New Directory', extension_filebrowser::baseURL() . 'new/directory/' . (is_array($this->_context) && !empty($this->_context) ? implode('/', $this->_context) . '/' : NULL), 'New Directory', 'button create'));
				$create_menu->appendChild($li);
			
				$li = new XMLElement('li');
				$li->appendChild(Widget::Anchor('New File', extension_filebrowser::baseURL() . 'new/file/' . (is_array($this->_context) && !empty($this->_context) ? implode('/', $this->_context) . '/' : NULL), 'New File', 'button create'));
				$create_menu->appendChild($li);
			
				$li = new XMLElement('li');
				$li->appendChild(Widget::Anchor('Upload File', extension_filebrowser::baseURL() . 'new/upload/' . (is_array($this->_context) && !empty($this->_context) ? implode('/', $this->_context) . '/' : NULL), 'Upload File', 'button create'));
				$create_menu->appendChild($li);
			}
			else {
				$create_menu = new XMLElement('p','This directory is not writable');
				$create_menu->setAttribute('class','actions');
			}

			$this->setPageType('table');
			
			$crumbs = $this->_context;
			
			$ArrayObject = new ArrayObject($crumbs);
			$Iterator = $ArrayObject->getIterator();
			
			$crumburl = NULL;
			$result = array();
			$length = sizeOf($ArrayObject);
			
			//$link = new XMLElement('a',Widget::Anchor(ltrim('/',$FileManager->getStartLocation()), URL . '/' ));
			//array_push($result, $link);
			
			while($Iterator->valid()){
			
				$key = $Iterator->key();
				//var_dump($key);
				$crumburl .= $Iterator->current() . '/';

				if($key != $length-1){
					$link = new XMLElement('a',Widget::Anchor(ucfirst($Iterator->current()), URL . '/symphony/extension/filebrowser/browse/' . $crumburl)->generate());
				}
				else{
					$link = new XMLElement('h2',ucfirst($Iterator->current()));
					}
					
				$Iterator->next();
				array_push($result, $link);
				
			}
			
			//var_dump($result);
			//$breadcrumbs = (is_array($this->_context) ? $FileManager->buildBreadCrumbs($this->_context) : NULL);
			//$this->appendSubheading(trim($FileManager->getStartLocationLink()));
			$this->insertBreadcrumbs($result);
			//$nav->appendChild($navitems);
			$actions->appendChild($create_menu);

			$Iterator = new DirectoryIterator($path);

			$aTableHead = array(

				array('Name', 'col'),
				array('Size', 'col'),
				array('Permissions', 'col'),
				array('Modified', 'col'),
				array('Available Actions', 'col'),			

			);	

			$aTableBody = array();

			if(iterator_count($Iterator) <= 0){

				$aTableBody = array(
									Widget::TableRow(array(Widget::TableData(__('None Found.'), 'inactive', NULL, count($aTableHead))))
								);
			}

			else{

				foreach($Iterator as $file){
					if($row = $FileManager->buildTableRow($file, ($path != DOCROOT . $FileManager->getStartLocation()))) $aTableBody[] = $row;
				}
			
			}
			
			sort($aTableBody);
			
			$table = Widget::Table(
								Widget::TableHead($aTableHead), 
								NULL, 
								Widget::TableBody($aTableBody)
						);

			$this->Form->appendChild($table);

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');
			$fieldsetContainer = new XMLElement('div');
			$options = array(
				array(NULL, false, 'With Selected...'),
				array('archive', false, 'Archive'),
				array('delete', false, 'Delete')									
			);

			$fieldsetContainer->appendChild(Widget::Apply($options));
			$tableActions->appendChild($fieldsetContainer);
			$this->Form->appendChild($tableActions);

		}
	}
	
?>
