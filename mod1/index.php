<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Mads Jensen <knoldesparker@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   66: class tx_mjjfilemanager_module1 extends t3lib_SCbase
 *   78:     function init()
 *  107:     function main()
 *  180:     function printContent()
 *  197:     function getModuleContent()
 *  357:     function getButtons()
 *  394:     function isEditable($file)
 *  415:     function getFileType($file)
 *  445:     function getDirLink($path, $dir)
 *  465:     function getFileLink($path, $file)
 *  486:     function getPermissionLink($string, $path, $item)
 *  511:     function getReadableSize($size, $max = null, $system = 'si', $retstring = '%01.2f %s')
 *  541:     function getIcon($icon)
 *  554:     function getFileIcon($file)
 *  692:     function getButton($btn, $action, $title = '')
 *  724:     function getPermissions($file)
 *
 * TOTAL FUNCTIONS: 15
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


$LANG->includeLLFile('EXT:mjj_filemanager/mod1/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]



/**
 * Module 'Filemanager' for the 'mjj_filemanager' extension.
 *
 * @author	Mads Jensen <knoldesparker@gmail.com>
 * @package	TYPO3
 * @subpackage	tx_mjjfilemanager
 *
 * @TODO	Register own (porper) icons with t3lib_SpriteManager::addSingleIcons
 */
class tx_mjjfilemanager_module1 extends t3lib_SCbase {
	var $hookObjectsArr = array();
	var $datestring;
	var $basedir;
	var $relbasedir;
	var $LANG;
	var $TYPO3_CONF_VARS;
	var $compressAvailable = false;

	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		/*
		if (t3lib_div::_GP('clear_all_cache'))	{
			$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
		}
		*/

		$this->LANG = $LANG;
		$this->TYPO3_CONF_VARS = $TYPO3_CONF_VARS;

		// Initialize hooks objects
		if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['mjj_filemanager']['filemanagerClass'])) {
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['mjj_filemanager']['filemanagerClass'] as $classRef) {
				$this->hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}


	}

	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT;

		$this->datestring = 'd-m-Y H:i:s';
		$this->basedir = $_SERVER['DOCUMENT_ROOT'];
		$this->basedir = str_replace('\\', '/', $this->basedir);
		$this->relbasedir = $_GET['dir'] ? $_GET['dir'] : '';

			// initialize doc
		$this->doc = t3lib_div::makeInstance('template');

		$this->doc->setModuleTemplate('EXT:mjj_filemanager/mod1/template/mod_template.html');

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'preProcessTemplate')) {
				$hookObj->preProcessTemplate($this->doc->moduleTemplate);
			}
		}

		$this->doc->backPath = $BACK_PATH;
		$docHeaderButtons = $this->getButtons();

		$tpl_path = '/'.t3lib_extMgm::siteRelPath('mjj_filemanager').'mod1/template/';
		$tpl_abspath = t3lib_extMgm::extPath('mjj_filemanager').'mod1/template/';

			// CSS
		$this->doc->inDocStyles = 'OMG - WHY DO YOU WRAP CSS IN COMMENTS, Typo3?!?!? -->/*]]>*/</style><style type="text/css">'.str_replace('###TEMPLATEPATH###', $tpl_path, file_get_contents($tpl_abspath.'css/mod_styles.css')).'/*<![CDATA[*/<!--';

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessCSS')) {
				$hookObj->preProcessCSS($this->doc->inDocStyles);
			}
		}

			// JavaScript
		$this->doc->JScode = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js" type="text/javascript"></script>';

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessHeadJS')) {
				$hookObj->preProcessHeadJS($this->doc->JScode);
			}
		}

		$postJS = file_get_contents($tpl_abspath.'script/mod_script.js');

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessPostJS')) {
				$hookObj->postProcessPostJS($postJS);
			}
		}

		$this->doc->postCode = '<script language="javascript" type="text/javascript">'.$postJS.'</script>';

			// Render content:
		$this->getModuleContent();

		$markers['BREADCRUMB'] = $this->renderBreadcrumb();
		$markers['MAGICBAR'] = $this->renderMagicbar();
		$markers['CONTENT'] = $this->content;

			// compile document

				// Build the <body> for the module
		$this->content = $this->doc->startPage($this->LANG->getLL('title'));
		$this->content.= $this->doc->moduleBody(array(), $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();

	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessOutput')) {
				$hookObj->postProcessOutput($this->content);
			}
		}

		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function renderMagicbar() {

		//$basedir = $_GET['dir'] ? str_replace($_SERVER['DOCUMENT_ROOT'], '', $_GET['dir']) : '';

		//$this->basedir = str_replace('\\', '/', $this->basedir);
		
		$dirs = explode('/', $this->basedir);

		$content = '<input type="text" id="magicbar" value="'.$this->LANG->getLL('magicbar-dummytext').' '.$dirs[count($dirs)-2].'" />';
		
		return $content;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function renderBreadcrumb() {

		//$basedir = $_GET['dir'] ? str_replace($_SERVER['DOCUMENT_ROOT'], '', $_GET['dir']) : '';
		
		$bcstart = '
	        <div id="breadcrumb">
	          <div id="breadcrumb-dummy">&nbsp;</div>
		';

		$bcend = '
	          <div class="div-clr">&nbsp;</div>
	        </div>
	        <input type="text" id="breadcrumb-textual" value="'.$this->relbasedir.'" current="'.$this->relbasedir.'" />
		';

		$bc_item = '
            <div class="breadcrumb-item" item="###NUM###" path="###PATH###">
							<div class="label">###NAME###</div>
             	###SUBITEMS###
	            <div class="div-clr">&nbsp;</div>
	          </div>
		';

		$bde = explode('/', $this->relbasedir);

		$bc = '
            <div class="breadcrumb-item" item="0" path="">
							<div class="label">'.$this->getIcon('silk t3-icon-apps t3-icon-pagetree-root', 'Home').'</div>
             	'.$this->getSubdirectories('', $bde[0]).'
	            <div class="div-clr">&nbsp;</div>
	          </div>
		';

		$i = 1;
		$path = '';
		foreach($bde as $k => $v) {
			if ($v != '') {
				$path .= $v.'/';
				$bc .= $bc_item;
				$bc = t3lib_parsehtml::substituteMarker($bc, '###NUM###', $i);
				$bc = t3lib_parsehtml::substituteMarker($bc, '###PATH###', $path);
				$bc = t3lib_parsehtml::substituteMarker($bc, '###NAME###', $v);
				$bc = t3lib_parsehtml::substituteMarker($bc, '###SUBITEMS###', $this->getSubdirectories($path, ($bde[$k + 1] ? $bde[$k + 1] : '')));
	
				$i++;
			}
		}
		
		$content = $bcstart.$bc.$bcend;
		
		return $content;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function getSubdirectories($path, $current = '') {
		$content = '';
		
		$f = scandir($this->basedir.$path);
		if ($f[0] == '.') {
			unset($f[0]);
		}
		if ($f[1] == '..') {
			unset($f[1]);
		}
		
		foreach($f as $k => $v) {
			if (is_dir($this->basedir.$path.'/'.$v)) {
				$content .= '<li class="breadcrumb-subitem'.($current == $v ? ' selected' : '').'">'.$v.'</li>';
			}
		}
		
		if ($content != '') {
			return '<div class="arrow"><ul>'.$content.'</ul></div>';
		}

		return $content;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function getModuleContent() {

		$tmpl = $this->doc->moduleTemplate;

		$content = t3lib_parsehtml::getSubpart($tmpl, '###FILETABLE###');

		$fileTableHeader = t3lib_parsehtml::getSubpart($content, '###FILETABLE_HEADER###');

		$headeritems['filename_label'] = $this->LANG->getLL('filename');
		$headeritems['filesize_label'] = $this->LANG->getLL('size');
		$headeritems['filetype_label'] = $this->LANG->getLL('type');
		$headeritems['filetime_label'] = $this->LANG->getLL('modified');
		$headeritems['filepermissions_label'] = $this->LANG->getLL('permissions');
		$headeritems['fileactions_label'] = $this->LANG->getLL('actions');

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessHeader')) {
				$hookObj->postProcessHeader($headeritems);
			}
		}

		$fileTableHeader = t3lib_parsehtml::substituteMarkerArray($fileTableHeader, $headeritems, '###|###', true);

		$fileTableItem = t3lib_parsehtml::getSubpart($content, '###FILETABLE_ITEM###');

		$content = t3lib_parsehtml::substituteSubpart($content, '###FILETABLE_HEADER###', $fileTableHeader);

		$f = scandir($this->basedir.$this->relbasedir);
		if ($f[0] == '.') {
			unset($f[0]);
		}
		if ($f[1] == '..') {
			unset($f[1]);
		}

		foreach($f as $k => $v) {
			if (is_dir($this->basedir.$this->relbasedir.$v)) {
				$fileid = $this->getFileId($this->basedir.$this->relbasedir.$v);
				$dirs[$v] = array (
					'class' => '',
					'checkbox' => '<input type="checkbox" name="file['.$fileid.']" />',
					'icon' => $this->getFileIcon('directory'),
					'item' => $this->getDirLink($this->relbasedir, $v),
					'size' => '&nbsp;',
					'type' => $this->LANG->getLL('directory'),
					'time' => date($this->datestring, filemtime($this->basedir.$this->relbasedir.$v)),
					'permissions' => $this->getPermissions($this->relbasedir.$v),
					'edit' => $this->getButton('spacer', '', ''),
					'archieve' => $this->compressAvailable ? $this->getButton('silk set1 silk-compress', 'dirArc,'.$fileid, $this->LANG->getLL('archieve')) : '',
					'rename' => $this->getButton('silk set2 silk-textfield_rename', 'rename,'.$fileid, $this->LANG->getLL('rename')),
					'delete' => $this->getButton('silk set1 silk-bin_closed', 'dirDel,'.$fileid, $this->LANG->getLL('delete')),
					'download' => $this->getButton('silk set1gray silk-disk', '', ''),
					'templavoila' => $this->getButton('spacer', '', ''),
				);
			}
			else {
				$htmlarr = array('html', 'htm', 'text/html');
				$fileid = $this->getFileId($this->basedir.$this->relbasedir.$v);
				$files[$v] = array (
					'fileid' => $fileid,
					'class' => '',
					'checkbox' => '<input type="checkbox" name="file['.$fileid.']" />',
					'icon' => $this->getFileIcon($this->relbasedir.$v),
					'item' => $this->getFileLink($this->relbasedir, $v),
					'size' => $this->getReadableSize(filesize($this->basedir.$this->relbasedir.$v), null, 'si', '%s %s'),
					'type' => $this->getFileType($this->relbasedir.$v),
					'time' => date($this->datestring, filemtime($this->basedir.$this->relbasedir.$v)),
					'permissions' => $this->getPermissions($this->relbasedir.$v),
					'edit' => $this->isEditable($this->basedir.$v) ? $this->getButton('silk set2 silk-pencil', 'fileEdit,'.$fileid, $this->LANG->getLL('edit')) : $this->getButton('silk set2gray silk-pencil', '', ''),
					'rename' => $this->getButton('silk set2 silk-textfield_rename', 'rename,'.$fileid, $this->LANG->getLL('rename')),
					'delete' => $this->getButton('silk set1 silk-bin_closed', 'fileDel,'.$fileid, $this->LANG->getLL('delete')),
					'download' => $this->getButton('silk set1 silk-disk', 'fileDown,'.$fileid, $this->LANG->getLL('download')),
					'templavoila' => in_array($filetype, $htmlarr) && t3lib_extMgm::isLoaded('templavoila') ? $this->getButton('templavoila', 'fileTV,'.$fileid, $this->LANG->getLL('templavoila')) : $this->getButton('spacer', '', ''),
				);

				if ($this->compressAvailable) {
					$typearr = array('zip', 'rar', 'tar', 'gz', 'application/x-gzip');
					if (in_array($filetype, $typearr)) {
						$files[$v]['archieve'] = $this->getButton('silk set1 silk-compress', 'fileUnarc,'.$fileid, $this->LANG->getLL('unpack'));
					}
					else {
						$files[$v]['archieve'] = $this->getButton('silk set1gray silk-compress', '', '');
					}
				}
				else {
					$files[$v]['archieve'] = '';
				}
			}
		}

		$ct = 0;
		$op = '';

		if (count($dirs) > 0) {
			ksort($dirs);

			foreach($dirs as $k => $v) {
				$v['class'] = $ct %2 == 0 ? 'bgColor3' : 'bgColor3-20';

				$op .= $fileTableItem;
				foreach ($v as $ke => $va) {
					$op = t3lib_parsehtml::substituteMarker($op, '###'.strtoupper($ke).'###', $va);
				}

				reset($this->hookObjectsArr);
				while (list(,$hookObj) = each($this->hookObjectsArr)) {
					if (method_exists ($hookObj, 'postProcessDirItem')) {
						$hookObj->postProcessDirItem($op);
					}
				}

				$ct++;
			}
		}

		$dircount = $ct;

		$fs = 0;
		if (count($files) > 0) {
			ksort($files);

			foreach($files as $k => $v) {
				$v['class'] = $ct %2 == 0 ? 'bgColor3' : 'bgColor3-20';

				$op .= $fileTableItem;
				foreach ($v as $ke => $va) {
					$op = t3lib_parsehtml::substituteMarker($op, '###'.strtoupper($ke).'###', $va);
				}

				reset($this->hookObjectsArr);
				while (list(,$hookObj) = each($this->hookObjectsArr)) {
					if (method_exists ($hookObj, 'postProcessFileItem')) {
						$hookObj->postProcessFileItem($op);
					}
				}

				$ct++;
				$fs += filesize($this->basedir.$this->relbasedir.$k);
			}
		}

		$content = t3lib_parsehtml::substituteMarker($content, '###FOOTER###', $dircount.' '.$this->LANG->getLL('directories').'<br/>'.($ct - $dircount).' '.$this->LANG->getLL('files').' ('.$this->getReadableSize($fs, null, 'si', '%s %s').')');
		$content = t3lib_parsehtml::substituteSubpart($content, '###FILETABLE_ITEM###', $op);
		$content = t3lib_parsehtml::substituteMarker($content, '###TEMPLATEPATH###', '/'.t3lib_extMgm::siteRelPath('mjj_filemanager').'mod1/template/');

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessTemplate')) {
				$hookObj->postProcessTemplate($op);
			}
		}

		$this->content .= $content;
	}



	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array		All available buttons as an assoc. array
	 */
	function getButtons()	{
		$buttons['copy'] = $this->getButton('silk set2 silk-page_white_copy', 'copy', $this->LANG->getLL('copy'));
		$buttons['paste'] = $this->getButton('silk set2 silk-page_white_paste', 'paste', $this->LANG->getLL('paste'));
		$buttons['cut'] = $this->getButton('silk set1 silk-cut', 'cut', $this->LANG->getLL('cut'));
		$buttons['delete'] = $this->getButton('silk set1 silk-bin_closed', 'delete', $this->LANG->getLL('delete'));
		$buttons['archieve'] = $compressAvailable ? $this->getButton('silk set1 silk-compress', 'archieve', $this->LANG->getLL('archieve')) : $this->getButton('spacer', '', '');

		$buttons['file'] = $this->getButton('silk set2 silk-page_white_add', 'file', $this->LANG->getLL('new-file'));
		$buttons['folder'] = $this->getButton('silk set1 silk-folder_add', 'folder', $this->LANG->getLL('new-directory'));
		$buttons['upload'] = $this->getButton('silk set2 silk-page_white_get', 'upload', $this->LANG->getLL('upload'));

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'getButtons')) {
				$hookObj->getButtons($buttons);
			}
		}

		return $buttons;
	}

	/**
	 * Checks if a file is of an editable type
	 *
	 * @param	string		$file: Filename with relative path prefixed.
	 * @return	boolean		true or false
	 */
	function isEditable($file) {
		$p = pathinfo($this->basedir.$file);

		$type = $p['extension'];

		// TODO: Fetch from $TYPO3_CONF_VARS['EXT']['extConf']['mjj_filemanager']
		// TODO: match using regular expressions
		if (in_array($type, array('txt', 'php', 'css', 'js', 'html', 'htm', 'xml'))) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Get type of file. If the fileinfo php module is loaded, it is utilllized to get a MIME-type. If not return the file extension.
	 *
	 * @param	string		$file: Filename with relative path prefixed.
	 * @return	string		MIME-type or file extension.
	 */
	function getFileType($file) {
		// Check if fileinfo extension is loaded.
		$m = get_loaded_extensions();
		if (in_array('fileinfo', $m)) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$type = finfo_file($finfo, $this->basedir.$file);
		}
		else {
			$p = pathinfo($this->basedir.$file);

			$type = $p['extension'];
		}

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessFileType')) {
				$type = $hookObj->postProcessFileType($file);
			}
		}

		return $type;
	}

	/**
	 * Makes a navigation link for a directory.
	 *
	 * @param	string		$path: Relative path to directory.
	 * @param	string		$dir: Directory name.
	 * @return	string		HTML link.
	 */
	function getDirLink($path, $dir) {
		$content = '<a href="/typo3/mod.php?M=web_txmjjfilemanagerM1&dir='.urlencode($path.$dir.'/').'" target="_self">'.$dir.'</a><input type="text" class="rename-field" value="'.$dir.'" />';

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessDirLink')) {
				$content = $hookObj->postProcessDirLink($path, $dir);
			}
		}

		return $content;
	}

	/**
	 * Makes a link for a file.
	 *
	 * @param	string		$path: Relative path to file.
	 * @param	string		$file: Filename.
	 * @return	string		HTML link.
	 */
	function getFileLink($path, $file) {
		$content = '<a href="/'.$path.$file.'" target="_blank">'.$file.'</a><input type="text" class="rename-field" value="'.$file.'" />';

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessFileLink')) {
				$content = $hookObj->postProcessFileLink($path, $file);
			}
		}

		return $content;
	}

	/**
	 * Makes a link to change permissions on an item.
	 *
	 * @param	string		$string: File permission string. Something like "drwxrwxrwx".
	 * @param	string		$path: Relative path to item.
	 * @param	string		$item: Directory/file name.
	 * @return	string		HTML link.
	 */
	function getPermissionLink($string, $path, $item) {
		$content = '<a href="/'.t3lib_extMgm::extRelPath('mjj_filemanager').'mod1/index.php?permission='.urlencode($path.$item).'" target="_self">'.$string.'</a>';

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessPermissionLink')) {
				$content = $hookObj->postProcessPermissionLink($string, $path, $item);
			}
		}

		return $content;
	}

	/**
	 * Return human readable sizes
	 *
	 * @param	integer		$size: size in bytes
	 * @param	string		$max: maximum unit
	 * @param	string		$system: 'si' for SI, 'bi' for binary prefixes
	 * @param	string		$retstring: return string format
	 * @return	string		Filesize.
	 * @author      Aidan Lister <aidan@php.net>
	 * @version     1.3.0
	 * @link        http://aidanlister.com/2004/04/human-readable-file-sizes/
	 */
	function getReadableSize($size, $max = null, $system = 'si', $retstring = '%01.2f %s') {
	  // Pick units
	  $systems['si']['prefix'] = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	  $systems['si']['size']   = 1000;
	  $systems['bi']['prefix'] = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
	  $systems['bi']['size']   = 1024;
	  $sys = isset($systems[$system]) ? $systems[$system] : $systems['si'];

	  // Max unit to display
	  $depth = count($sys['prefix']) - 1;
	  if ($max && false !== $d = array_search($max, $sys['prefix'])) {
      $depth = $d;
	  }

	  // Loop
	  $i = 0;
	  while ($size >= $sys['size'] && $i < $depth) {
      $size /= $sys['size'];
      $i++;
	  }

	  return sprintf($retstring, $size, $sys['prefix'][$i]);
	}

	/**
	 * Get Typo3 sprite icon.
	 *
	 * @param	string		$icon: Typo3 sprite icon name.
	 * @return	string		HTML for the icon.
	 */
	function getIcon($icon, $title) {
		//$content = t3lib_iconWorks::getSpriteIcon($icon, array(), array());
		$content = '<span class="'.$icon.'" title="'.$title.'">'.$title.'</span>';

		return $content;
	}

	/**
	 * Get Typo3 sprite file icon. Translates eighter MIME-type or file extension into Typo3 file icons.
	 * There's an exception for directories, as these have no MIME-type or extension, so setting $file = 'directory' will give you a directory icon.
	 *
	 * @param	string		$file: Filename with relative path prefixed.
	 * @return	string		HTML for the icon.
	 */
	function getFileIcon($file) {
		switch($file) {
			case 'directory':
				//$content = t3lib_iconWorks::getSpriteIcon('places-folder-closed', array(), array());
				$content = '<span class="silk set1 silk-folder"></span>';
			break;
			default:
				// Check if fileinfo extension is loaded.
				$m = get_loaded_extensions();
				if (in_array('fileinfo', $m)) {
					$finfo = finfo_open(FILEINFO_MIME_TYPE);

					$avail = array(
						'text/x-c' => 'silk set2 silk-page_white_c',
						'text/x-c++' => 'silk set2 silk-page_white_cplusplus',
						'text/x-pascal' => 'silk set2 silk-page_white_text',
						'text/x-lisp' => 'silk set2 silk-page_white_text',
						'text/plain' => 'silk set2 silk-page_white_text',
						'text/x-java' => 'silk set2 silk-page_white_text',
						'text/x-shellscript' => 'silk set2 silk-page_white_tux',
						'application/xml' => 'silk set2 silk-page_white_text',
						'image/gif' => 'silk set2 silk-page_white_picture',
						'image/jpeg' => 'silk set2 silk-page_white_picture',
						'image/png' => 'silk set2 silk-page_white_picture',
						'text/x-php' => 'silk set2 silk-page_white_php',
						'text/html' => 'silk set2 silk-page_white_text',
						'application/x-gzip' => 'silk set2 silk-page_white_compressed',
						'application/x-empty' => 'silk set2 silk-page_white',
						'' => '',
					);

					if (array_key_exists(finfo_file($finfo, $this->basedir.$file), $avail)) {
						//$content = t3lib_iconWorks::getSpriteIcon($avail[finfo_file($finfo, $file)], array(), array());
						//$content = '<span class="'.$avail[finfo_file($finfo, $file)].'"></span>';
						$content = $this->getIcon($avail[finfo_file($finfo, $this->basedir.$file)], '');
					}
					else {
						//$content = t3lib_iconWorks::getSpriteIcon('mimetypes-other-other', array(), array());
						//$content = '<span class="silk set2 silk-page_white"></span>';
						$content = $this->getIcon('silk set2 silk-page_white', '');
					}

				}
				else {
					// guesstimate by file extension...
					$p = pathinfo($this->basedir.$file);

					$avail = array(
						'zip' => 'silk set2 silk-page_white_compressed',
						'gz' => 'silk set2 silk-page_white_compressed',
						'tar' => 'silk set2 silk-page_white_compressed',
						'rar' => 'silk set2 silk-page_white_compressed',
						'7z' => 'silk set2 silk-page_white_compressed',
						'ods' => 'silk set2 silk-page_excel',
						'ots' => 'silk set2 silk-page_excel',
						'sxc' => 'silk set2 silk-page_excel',
						'stc' => 'silk set2 silk-page_excel',
						'stc' => 'silk set2 silk-page_excel',
						'sdc' => 'silk set2 silk-page_excel',
						'slk' => 'silk set2 silk-page_excel',
						'pxl' => 'silk set2 silk-page_excel',
						'uos' => 'silk set2 silk-page_excel',
						'xla' => 'silk set2 silk-page_excel',
						'xls' => 'silk set2 silk-page_excel',
						'xlt' => 'silk set2 silk-page_excel',
						'xlam' => 'silk set2 silk-page_excel',
						'xlsx' => 'silk set2 silk-page_excel',
						'xlsm' => 'silk set2 silk-page_excel',
						'xlsb' => 'silk set2 silk-page_excel',
						'xlsx' => 'silk set2 silk-page_excel',
						'xltx' => 'silk set2 silk-page_excel',
						'xltm' => 'silk set2 silk-page_excel',
						'mp3' => 'silk set2 silk-page_white_cd',
						'wma' => 'silk set2 silk-page_white_cd',
						'wav' => 'silk set2 silk-page_white_cd',
						'pcm' => 'silk set2 silk-page_white_cd',
						'ac3' => 'silk set2 silk-page_white_cd',
						'bmp' => 'silk set2 silk-page_white_picture',
						'png' => 'silk set2 silk-page_white_picture',
						'jpg' => 'silk set2 silk-page_white_picture',
						'jpeg' => 'silk set2 silk-page_white_picture',
						'gif' => 'silk set2 silk-page_white_picture',
						'iff' => 'silk set2 silk-page_white_picture',
						'tiff' => 'silk set2 silk-page_white_picture',
						'avi' => 'silk set1 silk-film',
						'mov' => 'silk set1 silk-film',
						'mkv' => 'silk set1 silk-film',
						'mpg' => 'silk set1 silk-film',
						'mpeg' => 'silk set1 silk-film',
						'wmv' => 'silk set1 silk-film',
						'mp4' => 'silk set1 silk-film',
						'pdf' => 'silk set2 silk-page_white_acrobat',
						'ppa' => 'silk set2 silk-page_white_powerpoint',
						'pps' => 'silk set2 silk-page_white_powerpoint',
						'ppt' => 'silk set2 silk-page_white_powerpoint',
						'pptm' => 'silk set2 silk-page_white_powerpoint',
						'pptx' => 'silk set2 silk-page_white_powerpoint',
						'potm' => 'silk set2 silk-page_white_powerpoint',
						'potx' => 'silk set2 silk-page_white_powerpoint',
						'ppsx' => 'silk set2 silk-page_white_powerpoint',
						'ppam' => 'silk set2 silk-page_white_powerpoint',
						'ppsm' => 'silk set2 silk-page_white_powerpoint',
						'odf' => 'silk set2 silk-page_white_powerpoint',
						'otp' => 'silk set2 silk-page_white_powerpoint',
						'sti' => 'silk set2 silk-page_white_powerpoint',
						'sxi' => 'silk set2 silk-page_white_powerpoint',
						'sda' => 'silk set2 silk-page_white_powerpoint',
						'sdd' => 'silk set2 silk-page_white_powerpoint',
						'oup' => 'silk set2 silk-page_white_powerpoint',
						'css' => 'silk set2 silk-page_white_text',
						'csv' => 'silk set2 silk-page_white_text',
						'htm' => 'silk set2 silk-page_white_text',
						'html' => 'silk set2 silk-page_white_text',
						'js' => 'silk set2 silk-page_white_text',
						'php' => 'silk set2 silk-page_white_php',
						'php3' => 'silk set2 silk-page_white_php',
						'php4' => 'silk set2 silk-page_white_php',
						'php5' => 'silk set2 silk-page_white_php',
						'txt' => 'silk set2 silk-page_white_text',
					);

					if (array_key_exists(strtolower($p['extension']), $avail)) {
						//$content = t3lib_iconWorks::getSpriteIcon($avail[strtolower($p['extension'])], array(), array());
						//$content = '<span class="'.$avail[strtolower($p['extension'])].'"></span>';
						$content = $this->getIcon($avail[strtolower($p['extension'])], '');
					}
					else {
						//$content = t3lib_iconWorks::getSpriteIcon('mimetypes-other-other', array(), array());
						//$content = '<span class="silk set2 silk-page_white"></span>';
						$content = $this->getIcon('silk set2 silk-page_white', '');
					}
				}
			break;
		}

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessMakeIcon')) {
				$content = $hookObj->postProcessMakeIcon($file);
			}
		}

		return $content;
	}

	/**
	 * Get a button for use in the docheader.
	 *
	 * @param	string		$btn: Typo3 sprite icon name.
	 * @param	string		$action: Action name to be called as an argument for the JS onclick function. Can take multiple values seperated by comma - DO NOT USE QUOTES (single or double), values are automaticly quoted.
	 * @param	string		$title: Link title attribute - no double qoutes!
	 * @return	string		HTML for the button.
	 */
	function getButton($btn, $action, $title = '') {

		$ea = explode(',', $action);

		switch ($btn) {
			case 'spacer':
				$content = '<span class="button-spacer"><img src="/clear.gif" width="16" height="16" border="0" /></span>';
			break;
			case 'templavoila':
				$content = '<a href="#" onclick="doAction(\''.implode("','", $ea).'\');" title="'.$title.'"><span class="t3-icon" style="background:transparent;"><img src="/'.t3lib_extMgm::siteRelPath('templavoila').'cm1/cm_icon.gif" width="16" height="14" border="0" /></span></a>';
			break;
			default:
				if ($action == '') {
					$content = $this->getIcon($btn, $title);
				}
				else {
					$content = '<a href="#" onclick="doAction(\''.implode("','", $ea).'\');" title="'.$title.'">'.$this->getIcon($btn, $title).'</a>';
				}
			break;
		}

		reset($this->hookObjectsArr);
		while (list(,$hookObj) = each($this->hookObjectsArr)) {
			if (method_exists ($hookObj, 'postProcessButtonLink')) {
				$content = $hookObj->postProcessButtonLink($btn, $action, $title);
			}
		}

		return $content;
	}

	/**
	 * Gets file permissions and formats it to something readable.
	 *
	 * @param	string		$file: Filename with relative path prefixed.
	 * @return	string		File permissions.
	 */
	function getPermissions($file) {
		$perms = fileperms($this->basedir.$file);
		if (($perms & 0xC000) == 0xC000) {
	    // Socket
	    $info = 's';
		}
		elseif (($perms & 0xA000) == 0xA000) {
	    // Symbolic Link
	    $info = 'l';
		}
		elseif (($perms & 0x8000) == 0x8000) {
	    // Regular
	    $info = '-';
		}
		elseif (($perms & 0x6000) == 0x6000) {
	    // Block special
	    $info = 'b';
		}
		elseif (($perms & 0x4000) == 0x4000) {
	    // Directory
	    $info = 'd';
		}
		elseif (($perms & 0x2000) == 0x2000) {
	    // Character special
	    $info = 'c';
		}
		elseif (($perms & 0x1000) == 0x1000) {
	    // FIFO pipe
	    $info = 'p';
		}
		else {
	    // Unknown
	    $info = 'u';
		}
		// Owner
		$info .= (($perms & 0x0100) ? 'r' : '-');
		$info .= (($perms & 0x0080) ? 'w' : '-');
		$info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
		// Group
		$info .= (($perms & 0x0020) ? 'r' : '-');
		$info .= (($perms & 0x0010) ? 'w' : '-');
		$info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
		// World
		$info .= (($perms & 0x0004) ? 'r' : '-');
		$info .= (($perms & 0x0002) ? 'w' : '-');
		$info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
		return $info;
	}
	
	function getFileId($file) {
		return md5($file);
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mjj_filemanager/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mjj_filemanager/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_mjjfilemanager_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>