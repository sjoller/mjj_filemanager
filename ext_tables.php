<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	t3lib_extMgm::addModulePath('web_txmjjfilemanagerM1', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
		
	t3lib_extMgm::addModule('web', 'txmjjfilemanagerM1', 'bottom', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
}
?>