( function( $ ) {

tinyMCE.init( {
	mode: "exact",
	theme: "advanced",
	language: "en", 
	skin: "wp_theme",
	editor_css: "/wp-includes/css/editor.css", 
	theme_advanced_toolbar_align:"left",
	theme_advanced_statusbar_location:"bottom",
	theme_advanced_resizing:true,
	theme_advanced_resize_horizontal:false,
	dialog_type:"modal",
	content_css: "/wp-content/themes/vip/kff/inc/fieldmanager/css/fieldmanager-richtext-content.css",
	theme_advanced_toolbar_location: "top",
	theme_advanced_buttons1: "bold,italic,strikethrough,|,bullist,numlist,blockquote,|,justifyleft,justifycenter,justifyright,|,link,unlink,|,wp_fullscreen,|,formatselect,underline,justifyfull,forecolor,|,pastetext,pasteword,removeformat,|,charmap,|,outdent,indent,|,undo,redo,|,wp_page,",
	theme_advanced_buttons2: "",
	plugins: "inlinepopups,spellchecker,tabfocus,paste,fullscreen,wordpress,wpeditimage,wpgallery,wplink,wpdialogs,-table,wpfullscreen",
	style_formats:[{"title":"Custom Paragraph Style","selector":"p","classes":"kff-style1"}],
	height: "250",
	width: "100%",
} );

$( document ).on( "click", '.fm-richtext', function() {
	$this = $( this );
	tinyMCE.execCommand( "mceAddControl", false, $this.attr( 'id' ) );
} );

} ) ( jQuery );