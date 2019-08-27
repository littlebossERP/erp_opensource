/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	config.scayt_contextCommands = 'off';
	config.toolbar = 'Full';
	config.fontSize_sizes = '1px;2px;3px;4px;5px;6px;7px;8px;9px;10px;11px;12px;13px;14px;15px;16px;17px;18px;19px;20px;21px;22px;23px;24px;25px;26px;27px;28px;29px;30px;31px;32px;33px;34px;35px;36px;37px;38px;39px;40px;41px;42px;43px;44px;45px;46px;47px;48px;49px;50px;51px;52px;53px;54px;55px;56px;57px;58px;59px;60px;61px;62px;63px;64px;65px;66px;67px;68px;69px;70px;71px;72px;';
config.toolbar_Full =
[
	{ name: 'document', items : [ 'Source' ] },
	{ name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock' ] },
	{ name: 'basicstyles', items : [ 'NumberedList','BulletedList','-','Bold','Italic','Underline','-','RemoveFormat' ] },
	// { name: 'insert', items : [ 'Image','Flash','Table','HorizontalRule'] },
	{ name: 'styles', items : [ 'Font','FontSize' ] },
	{ name: 'colors', items : [ 'TextColor','BGColor' ] },
	{ name: 'tools', items : [ 'Maximize','backgrounds'] }
];
// config.allowedContent= true;
};
