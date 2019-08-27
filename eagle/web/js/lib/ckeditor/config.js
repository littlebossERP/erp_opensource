/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	config.forcePasteAsPlainText =false;
	config.pasteFromWordKeepsStructure = false;
	config.pasteFromWordRemoveStyle = false;
	config.pasteFromWordRemoveFontStyles = false;
//	config.removePlugins='elementspath';
//	config.format_div = { element : "div", attributes : { class : "normalDiv" } };
//	config.format_p = { element : "p", attributes : { class : "normalPara" } }; 
//	config.fullPage = true; 
	config.allowedContent=true;
	config.EnableSourceXHTML=true;
	config.FormatSource=false;
};
