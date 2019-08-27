<?php
// 区别最后生成提交给ebay的html还是编辑风格模板的html
$finalHtml = true;

//php中声明全局路径变量BASE_URL,HOST_INFO
if(defined('BASE_URL') === FALSE)
	define('BASE_URL', '/');
if(defined('HOST_INFO') === FALSE)
	define('HOST_INFO', \Yii::$app->params['hostInfo']);
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style type='text/css'>
	.realnotdis {
		display: none;
	}

	html,body,div,span,applet,object,iframe,h1,h2,h3,h4,h5,h6,ul,p,blockquote,pre,a,abbr,acronym,address,big,cite,code,del,dfn,em,img,ins,kbd,q,s,samp,small,strike,strong,sub,sup,tt,var,b,u,i,center,dl,dt,dd,ol,fieldset,form,label,legend,caption,tbody,tfoot,thead,article,aside,canvas,details,embed,figure,figcaption,footer,header,hgroup,menu,nav,output,ruby,section,summary,time,mark,audio,video
	{
		margin: 0;
		border: 0;
	}

	/* HTML5 display-role reset for older browsers */
	article,aside,details,figcaption,figure,footer,header,hgroup,menu,nav,section
	{
		display: block;
	}

	body {
		line-height: 1;
	}

	blockquote,q {
		quotes: none;
	}

	blockquote:before,blockquote:after,q:before,q:after {
		content: '';
		content: none;
	}

	a img {
		border: none;
	}

	#subbody {
		margin: 0 auto;
		position: relative;
	}
	.editor {
		min-height: 40px;
	}
</style>

<script type='text/javascript'>
	function displayPage() {
		var current = this.parentNode.getAttribute('data-current');
		document.getElementById('tabHeader_' + current).removeAttribute('class');
		document.getElementById("tabHeader_" + current).setAttribute("class","tabHeaderi");
		document.getElementById('tabpage_' + current).style.display='none';
		var ident = this.id.split('_')[1];
		this.setAttribute('class','tabActiveHeader');
		document.getElementById('tabpage_' + ident).style.display='block';
		this.parentNode.setAttribute('data-current',ident);
	}
	window.onload = function ()
	{
//var container = document.getElementById('tabContainer');
		var container = document.getElementById('policy_html');
		var tabcon = document.getElementById('tabscontent');
		var navitem = document.getElementById('tabHeader_1');
		var ident = navitem.id.split('_')[1];
		navitem.parentNode.setAttribute('data-current',ident);
		navitem.setAttribute('class','tabActiveHeader');
		var pages = tabcon.getElementsByTagName('div');
		for (var i = 1; i < pages.length; i++) {
			pages.item(i).style.display='none';
		};
		var tabs = container.getElementsByTagName('li');
		for (var i = 0; i < tabs.length; i++) {
			tabs[i].onclick=displayPage;
		}
		if(document.getElementById('policy_box1_text') != null){
			document.getElementById('policy_box1_text').style.display='block';
		}
		if(document.getElementById('policy_box2_text') != null){
			document.getElementById('policy_box2_text').style.display='block';
		}
		if(document.getElementById('policy_box3_text') != null){
			document.getElementById('policy_box3_text').style.display='block';
		}
		if(document.getElementById('policy_box4_text') != null){
			document.getElementById('policy_box4_text').style.display='block';
		}
		if(document.getElementById('policy_box5_text') != null){
			document.getElementById('policy_box5_text').style.display='block';
		}
	}
</script>
<script type='text/javascript'>
	var FX = {
		easing: {
			linear: function(progress) {
				return progress;
			},
			quadratic: function(progress) {
				return Math.pow(progress, 2);
			},
			swing: function(progress) {
				return 0.5 - Math.cos(progress * Math.PI) / 2;
			},
			circ: function(progress) {
				return 1 - Math.sin(Math.acos(progress));
			},
			back: function(progress, x) {
				return Math.pow(progress, 2) * ((x + 1) * progress - x);
			},
			bounce: function(progress) {
				for (var a = 0, b = 1, result; 1; a += b, b /= 2) {
					if (progress >= (7 - 4 * a) / 11) {
						return -Math.pow((11 - 6 * a - 11 * progress) / 4, 2) + Math.pow(b, 2);
					}
				}
			},
			elastic: function(progress, x) {
				return Math.pow(2, 10 * (progress - 1)) * Math.cos(20 * Math.PI * x / 3 * progress);
			}
		},
		animate: function(options) {
			var start = new Date;
			var id=this.animateID= setInterval(function() {
				var timePassed = new Date - start;
				var progress = timePassed / options.duration;
				if (progress > 1) {
					progress = 1;
				}
				options.progress = progress;
				var delta = options.delta(progress);
				options.step(delta);
				if (progress == 1) {
					clearInterval(id);
					options.complete();
				}
			}, options.delay || 10);
		},
		fadeIn: function(element, options) {
			var to = 0;
			this.animate({
				duration: options.duration,
				delta: function(progress) {
					progress = this.progress;
					return FX.easing.swing(progress);
				},
				complete: options.complete,
				step: function(delta) {
					var browserName=navigator.appName;
					var ieo = (to + delta)*100;
					if (browserName=='Microsoft Internet Explorer') {
						element.style.filter = 'alpha(opacity='+ieo+')';
					}else{
						element.style.opacity = to + delta;
					}
				}
			});
		}
	};
	window.FX = FX;
	function changeImages(src){
		if(FX.animateID){
			clearInterval(FX.animateID);
		}
		var browserName=navigator.appName;
		document.getElementById('linkId').href = src;
		document.getElementById('sample_Bigimg').src = src;
		FX.fadeIn(document.getElementById('sample_Bigimg'), {
			duration: 500,
			complete: function() {
			}
		});
	}
	function resizeImg(img) {
		var wwi = document.getElementById('mobilebox').offsetWidth - 35;
		img.width = wwi;
		return false;
	}
</script>
<style type='text/css'>
	.poster_pic {
		max-width: 800px;
	}

	.ItemSpecificstableALL tr td:first-child,.AttributestableALL tr td:first-child
	{
		min-width: 110px;
	}

	.ItemSpecificstableALL tr td:last-child,.AttributestableALL tr td:last-child
	{
		min-width: 110px;
	}

	.hotitemtitle {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		width: 100px;
		height: 15px;
	}

	.hotitemli {
		margin: auto;
		margin-top: 2px;
		font-size: 12px
	}

	.hotitemli div,.hotitemli p {
		font-size: 12px
	}

	.hotitemli td {
		vertical-align: middle;
		padding: 2PX;
	}

	#smail_pic_box {
		margin-left: 4px;
	}

	.abright {
		display: inline-block;
		float: right;
		text-align: left;
		margin: 2px;
	}

	.ableft {
		display: inline-block;
		float: left;
		text-align: left;
		margin: 2px;
	}

	.abcenter {
		display: block;
		width: 100%;
		margin: auto;
		text-align: center;
		margin: 2px;
	}

	.dbbox {
		margin-bottom: 10px;
		border: 1px solid transparent;
		display: inline-block;
		overflow: hidden;
		vertical-align: top;
		border-radius: 10px;
		transition: box-shadow .25s, min-height .35s;
		-moz-transition: box-shadow .25s, min-height .35s;
		-webkit-transition: box-shadow .25s, min-height .35s;
		transition-delay: box-shadow .75s;
		-moz-transition-delay: box-shadow .75s;
		-webkit-transition-delay: box-shadow;
	}

	.dbbox:hover {
		border: 1px solid #f0f0f0;
		border-radius: 10px;
		box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
		z-index: 5;
	}

	#Next_to_Product_Photo {
		margin: auto;
		text-align: center;
	}

	.cbp {
		display: inline-block;
		vertical-align: top;
		margin-right: 5px;
	}

	#Below_All_Product_Photo,#desc_html,#Below_All_Product_Posters,#above_product_Photo,.MM_h_desc,#feedback_html,#poster_html,#tabContainer,#policy_html
	{
		width: 800px;
		margin: auto;
		text-align: center;
		word-break: break-word;
	}
	.mm5 {
		margin: 3px
	}

	.needpadding {
		padding-top: 5px;
		padding-bottom: 5px;
	}

	#left1080 {
		padding-left: 3px;
	}

	#right1080 {
		max-width: 880px;
	}

	#Attributestable {
		margin-top: 6px;
	}

	#Attributestable td:first-child {
		text-align: left;
	}

	#Attributestable td {
		padding: 3px;
		border: 1px solid #b2b2b2;
	}

	#ItemSpecificstable {
		margin-top: 6px;
	}

	#ItemSpecificstable td:first-child {
		text-align: left;
	}

	#ItemSpecificstable td {
		padding: 3px;
		border: px;
	}

	.navitemc {
		margin: 3px;
	}

	.mcenter {
		width: 100%;
		margin: auto;
	}

	.outer {
		width: 100%;
		text-align: center;
	}

	.inner {
		display: inline-block;
	}

	#logo {
		clear: both;
		text-align: center;
	}

	.navitemitem {
		color: #7f7f7f
	}

	.top3px {
		padding-top: 2px;
	}

	#smail_pic_box {

	}

	.table_d {
		width: 100%;
	}

	.m_p {
		max-width: 786px;
	}

	#mobilebox .m_p {
		width: 100%;
		max-width: 786px;
	}

	.mobpovinfo img {
		display: none;
	}

	#mobilefooter,#mobilebox {
		display: none
	}

	#mobilebox {
		width: 0
	}

	#sample2_graphic_setting_Shop_Name_Banner {
		position: relative;
	}

	.shopsubnameaddon {
		position: absolute;
		font-family: Orbitron !important;
		font-size: 26px;
		color: #999999;
		top: 95px;
		left: 25px;
	}

	.shopnameaddon {
		position: absolute;
		font-family: Impact !important;
		font-size: 75px;
		color: #4c4c4c;
		top: 10px;
		left: 25px;
	}

	.mpicbanner .shopnameaddon {
		font-size: 37.5px;
	}

	.settop {
		height: 35px;
	}

	.topsuname {
		font-weight: bold;
	}

	.catbutton {
		cursor: pointer;
		margin: 3px;
		border-radius: 2px;
		BORDER: #555 1px solid;
		text-shadow: 0 1px 1px rgba(0, 0, 0, .3);
		box-shadow: 0 1px 2px rgba(0, 0, 0, .2);
		FONT-SIZE: 10px;
		min-width: 4em;
		height: 20px;
		COLOR: #FFFFFF;
		background-color: #333333;
		font-family: Arial;
	}

	.catbutton:hover {
		background-color: #7f7f7f;
	}

	.layoutborder {
		background-color: #ffffff;
		top: -3px;
		margin-bottom: -3px;
		position: relative;
		z-index: 0;
		border-radius: 5px;
		text-align: left;
	}

	.layoutborder {
		border: 1px solid #dddddd;
	}

	.gboxout {
		background-color: #cccccc;
		font-family: Squada One !important;
		font-size: 17px;
		color: #333333;
		position: relative;
		z-index: 1;
		padding-left: 10px;
		padding-top: 2px;
		padding-bottom: 2px;
		border-top-right-radius: 5px;
		border-top-left-radius: 5px;
	}

	.gbox {
		font-family: Arial !important;
		font-size: 12px;
		color: #7f7f7f !important;
	}

	.Cattop ul li:hover a {
		color: #333333;
		text-decoration: none;
	}

	.widthset850 {
		max-width: 850px
	}

	.widthset160 {
		max-width: 160px
	}

	#floata {
		float: right;
	}

	#c_box {
		clear: both;
	}

	.soout {
		display: none;
		position: fixed;
		width: 180px;
		height: 320px;
		background: rgba(255, 255, 255, 0.9);
		padding: 10px;
		flort: left;
		border-radius: 10px;
		border: 1px solid #9c9c9c;
	}

	.soout img {
		height: 180px !important;
		width: 180px !important;
		border: 1px solid #9c9c9c;
	}

	.soout .hotitemtitle {
		overflow: visible !important;
		width: 170px !important;
		white-space: normal;
		height: inherit;
		max-height: 110px;
	}

	#subbody {
		border-radius: 10px;
		box-shadow: 4px 4px 3px rgba(158, 158, 158, 0.5);
		line-height: 1.5;
		width: 850px;
		background-color: #ffffff;
		border: 10px solid #666666;
	}

	#subbody #big_pic div,#subbody #smail_pic_box div,.imgdiv,.product_photo_need_hide,.product_photo_need_hide div
	{
		line-height: 0;
	}

	#smail_pic_box img {
		margin: 2px;
	}

	#mobilebox {
		background-color: #ffffff;
	<!--border: 5px solid #666666;-->
		padding: 5px 5px 5px 5px;
	}

	.overf {
		overflow: hidden;
		position: relative;
	}

	#menubar {
		height: 25px;
	}

	.catnbwidthset {
		max-width: 800px;
	}

	.menurow {
		height: 100%;
		float: left;
		width: 152px;
		position: relative;
		font-size: 12px;
		color: #f7f7f6;
		font-family: Arial;
	}

	.menurow a {
		position: relative;
		top: 20%;
	}

	.menurow a:visited,.menurow a:hover {
		text-decoration: none;
	}

	.menuright {
		border-right: #f7f7f6 1px solid;
	}

	#menudisplay {
		top: -4px;
		left: -3px;
		position: relative;
		height: 25px;
		width: 850px;
		background-image: url(<?php echo HOST_INFO.BASE_URL ;?>/images/ebay/template/webbar5.png);
	}

	.overf {
		overflow: hidden;
	}

	#big_smail_pic {
		clear: both;
		padding-left: 10px;
		padding-top: 10px
	}

	.navpic {
		padding-top: 2px;
		padding-bottom: 2px;
	}

	#feedback_html {
		text-align: left;
		border-radius: 10px;
		box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
	}

	.mobpovinfo {
		background-color: #ffffff;
	}

	.policy_box {
		background-color: #ffffff;
		border-radius: 10px;
		box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
		border: 2px solid #7f7f7f;
	}

	#poster_html {
		text-align: center;
		clear: both;
		width: 800px;
	}

	#policy_box1 {
		text-align: left;
		margin-top: 5px;
	}

	#policy_box2 {
		text-align: left;
		margin-top: 5px;
	}

	#policy_box3 {
		text-align: left;
		margin-top: 5px;
	}

	#policy_box4 {
		text-align: left;
		margin-top: 5px;
	}

	#policy_box5 {
		text-align: left;
		margin-top: 5px;
		display: none;
	}

	.poster_pic {
		padding-top: 3px;
	}

	#rmpic {
		position: relative;
	}

	#rmpic_l {
		position: absolute;
		left: 0px;
		top: 50%;
		filter: alpha(opacity = 40);
		opacity: 0.4;
		z-index: 9999;
		display: none;
	}

	#rmpic_r {
		position: absolute;
		right: 0px;
		top: 50%;
		filter: alpha(opacity = 40);
		opacity: 0.4;
		z-index: 9999;
		display: none;
	}

	.poster_pic img {
		max-width: 786px;
	}

	.desc_details ul {
		padding: 0px;
		margin-left: 20px;
		list-style: square;
	}
	.desc_word {word-wrap: break-word;}
	.descdiv {
		margin: 6px 0px 0px 0px;
		padding: 0px;
	}

	.descdiv ul {
		padding: initial;
	}

	#linkId {
		display: inline-block;
	}

	#tabscontent {
		text-align: left;
	}

	#desc {
		margin-top: 30PX;
	}

	.desc_box {
		padding-left: 5px !important;
	}

	.margin {
		margin-top: 5px;
		overflow: hidden;
	}

	.mousedown {
		cursor: pointer;
	}

	.toptwopx {
		padding-top: 4px;
	}

	.m_pic_r {
		max-width: 1100px;
	}

	.subtitle {
		line-height: 1.5;
		padding-top: 30px;
		padding: 10px;
		text-align: center;
		color: #4c4c4c;
		font-family: Impact !important;
		font-size: 25PX;
	}

	#mobile_subtitle {
		text-align: center;
		color: #4c4c4c;
		font-family: Impact !important;
		font-size: 25PX;
	}

	.tabpage {
		padding: 2px 2px 2px 2px;
		border: 2px;
		border-color: #7f7f7f;
		border-style: solid;
		border-bottom-left-radius: 10px;
		border-bottom-right-radius: 10px;
	}

	#tabscontent {
		margin-bottom: 10px
	}

	.w180 {
		font-size: 8px;
	}

	#smail_pic_box {
		text-align: left;
	}

	#gallery {
		box-shadow: 3px 3px 5px rgba(0, 0, 0, 0.1);
	}

	.smail_pic {
		box-shadow: 3px 3px 5px rgba(0, 0, 0, 0.1);
		display: inline-block;
		border: 1px solid #9c9c9c;
	}

	.smail_pic:hover {
		box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
	}

	#tabContainer {
		background-color: #ffffff;
		width: 800px;
		border-radius: 10px;
		box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
	}

	.pbp {
		margin-bottom: 3px;
		border-radius: 10px;
		border: 1px solid transparent;
		vertical-align: top;
		border-radius: 10px;
		width: 180px;
		overflow: hidden;
	}

	.pbp:hover {

	}

	#tabscontent {
		clear: both;
	}

	#tabs>ul {
		margin: 0;
		padding: 0;
		font: 1em;
		list-style: none;
	}

	#tabs>ul>li {
		min-height: 12px;
		box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
		margin: 0 2px 0 0;
		padding: 7px 10px;
		display: block;
		float: left;
		color: #ffffff;
		-moz-user-select: none;
		user-select: none;
		border-top-left-radius: 4px;
		border-top-right-radius: 4px;
		border-bottom-right-radius: 0px;
		border-bottom-left-radius: 0px;
		background: #b2b2b2; /* old browsers */
		width: 178px;
		font-size: 12PX;
		font-family: Arial;
	}

	.hotitemli>tbody>tr {
		border-bottom: 1px dashed #E5E5E5;
	}

	.hotitemli>tbody>tr:last-child {
		border-bottom: 0px dashed #E5E5E5;
	}

	.hotitemli>tr {
		border-bottom: 1px dashed #E5E5E5;
	}

	.hotitemli>tr:last-child {
		border-bottom: 0px dashed #E5E5E5;
	}

	#tabs>ul>li:hover {
		cursor: pointer;
	}

	.mobpovh {
		cursor: pointer;
		background-color: #b2b2b2;
		border-top-left-radius: 15px;
		font-size: 18px;
		padding: 10px;
		margin-top: 5px;
		color: #ffffff;
	}

	.mobpovinfo {
		padding: 5px;
		overflow: hidden;
	}

	.imgapx2 {

	}

	.user_edit ul,.user_edit dl {
		*margin-right: 0px;
		padding: 0 40px;
		list-style: square;
	}

	.user_edit ol {
		*margin-right: 0px;
		padding: 0 40px;
		list-style: decimal;
	}

	.user_edit table,.user_edit td,.user_edit tr {
		border: double;
	}

	#Store-Search ul {
		list-style: none;
	}


	<?php
        // policy tab font , background
        $tabHeaderiClass = "";
        $tabActiveHeaderClass = "";
        if(!empty($allItem['eb_tp_tab_Header_selected'])){
            if(stripos($allItem['eb_tp_tab_Header_selected'] , '#') === false )
                $allItem['eb_tp_tab_Header_selected'] = '#'.$allItem['eb_tp_tab_Header_selected'];
            $tabActiveHeaderClass .= "background-color: ".$allItem['eb_tp_tab_Header_selected'].";";
        }else{
            $tabActiveHeaderClass .= "background-color: #7f7f7f;";
        }


        if(!empty($allItem['eb_tp_tab_Header_color'])){
            if(stripos($allItem['eb_tp_tab_Header_color'] , '#') === false )
                $allItem['eb_tp_tab_Header_color'] = '#'.$allItem['eb_tp_tab_Header_color'];
            $tabHeaderiClass .= "background-color: ".$allItem['eb_tp_tab_Header_color'].";";
        }else{
            $tabHeaderiClass .= "background-color: #b2b2b2;";
        }

        if(!empty($allItem['eb_tp_tab_Header_font'])){
            if(stripos($allItem['eb_tp_tab_Header_font'] , '#') === false )
                $allItem['eb_tp_tab_Header_font'] = '#'.$allItem['eb_tp_tab_Header_font'];
            $tabActiveHeaderClass .= "color: ".$allItem['eb_tp_tab_Header_font'].";";
            $tabHeaderiClass .= "color: ".$allItem['eb_tp_tab_Header_font'].";";
        }else{
            $tabActiveHeaderClass .= "color: #ffffff;";
            $tabHeaderiClass .= "color: #ffffff;";
        }
        if(!empty($allItem['eb_tp_tab_Font_style'])){
            $tabActiveHeaderClass .= "font-family: ".$allItem['eb_tp_tab_Font_style'].";";
            $tabHeaderiClass .= "font-family: ".$allItem['eb_tp_tab_Font_style'].";";
        }else{
            $tabActiveHeaderClass .= "font-family: Arial;";
            $tabHeaderiClass .= "font-family: Arial;";
        }
        if(!empty($allItem['eb_tp_tab_Font_size'])){
            $tabActiveHeaderClass .= "font-size: ".$allItem['eb_tp_tab_Font_size']."px;";
            $tabHeaderiClass .= "font-size: ".$allItem['eb_tp_tab_Font_size']."px;";
        }else{
            $tabActiveHeaderClass .= "font-size: 12px;";
            $tabHeaderiClass .= "font-size: 12px;";
        }

        $tabHeaderiClass .= "cursor: pointer;";
        $tabActiveHeaderClass .= "cursor: pointer;";

    ?>

	#tabs>ul>li.tabActiveHeader {<?php echo $tabActiveHeaderClass;?>}

	#tabs>ul>li.tabHeaderi{<?php echo $tabHeaderiClass;?>}

	@font-face {
		font-family: 'Orbitron';
		font-style: normal;
		font-weight: 400;
		src: local('Orbitron-Light'), local('Orbitron-Regular'),
		url(http://themes.googleusercontent.com/static/fonts/orbitron/v3/94ug0rEgQO_WuI_xKJMFc_esZW2xOQ-xsNqO47m55DA.woff)
		format('woff');
	}

	@font-face {
		font-family: 'Squada One';
		font-style: normal;
		font-weight: 400;
		src: local('Squada One'), local('SquadaOne-Regular'),
		url(http://themes.googleusercontent.com/static/fonts/squadaone/v2/DIbfqh10Zkwc_Qd08Y0saRsxEYwM7FgeyaSgU71cLG0.woff)
		format('woff');
	}

	.showbtn {
		display: none;
	}

	#Zoom-Icon {
		position: absolute;
		bottom: 0px;
		padding: 0;
		background-image: url(<?php echo HOST_INFO.BASE_URL ;?>/images/ebay/template/zoom.gif);
		background-repeat: no-repeat;
		height: 25px;
		width: 25px;
		font-size: 0.75em;
		color: #A6A6A6;
		z-index: 2;
		cursor: pointer;
		right: 0px;
	}

	#gallery {
		position: relative;
	}

	.mpicbox {
		width: 100%;
		-webkit-overflow-scrolling: touch;
		overflow-y: hidden;
		overflow-x: scroll;
		margin-top: 1px;
	}

	.transparent {
		opacity: .2;
		-moz-opacity: 0.2
	}

	.nonOpaque {
		opacity: 1;
		-moz-opacity: 1
	}

	#zDIV_slideShow a {
		color: #000;
		background-color: #fff
	}

	#zDIV_slideShow {
		top: 200px !important;
		display: none;
		position: fixed;
		left: 0;
		width: 100%;
		height: 100%;
		background-position: 50% 50%;
		background-repeat: no-repeat;
		text-align: center;
		margin: 0;
		z-index: 10 /* IE Mac */
	}

	#zA_close {
		background: transparent !important
	}

	#zA_close img {
		border: 0 !important;
		position: absolute;
	}

	#zIMG {
		padding: 3px;
		background: #fff;
		border: 2px solid #000
	}

	.Cat_gbox {
		padding: 5px;
	}

	strong {
		font-weight: 900;
	}

	.ebaybtn {
		text-decoration: none;
		bottom: 2px;
		right: 2px;
		position: fixed;
		background: #808A0E;
		display: inline-block;
		text-align: center;
		padding: 10px 5%;
		color: #fff;
		margin-right: 10px;
		text-decoration: none;
		font-weight: bold;
		line-height: 1;
		font-family: 'Helvetica Neue', 'Helvetica', Arial, Verdana, sans-serif !important;
		cursor: pointer;
		border: none;
		outline: none;
		-moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
		-webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
		box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
		text-shadow: 0 -1px 1px rgba(0, 0, 0, 0.28);
		border: 1px solid #808A0E;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px;
		font-size: 18px;
		z-index: 5;
	}

	.ebaybtnbuy {
		background: #3525F3;
		right: 120px;
	}

	.showbtn {
		text-decoration: none;
		bottom: 2px;
		left: 2px;
		position: fixed;
		background: red;
		display: inline-block;
		text-align: center;
		padding: 11px 5%;
		color: #fff;
		text-decoration: none;
		font-weight: bold;
		line-height: 1;
		font-family: 'Helvetica Neue', 'Helvetica', Arial, Verdana, sans-serif
		!important;
		cursor: pointer;
		border: none;
		outline: none;
		-moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
		-webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
		box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
		text-shadow: 0 -1px 1px rgba(0, 0, 0, 0.28);
		border: 1px solid red;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px;
		font-size: 18px;
		z-index: 5;
	}

	#mobilefooter {
		bottom: 0;
		left: 0px;
		position: fixed;
		background-color: rgba(0, 0, 0, 0.3);
		width: 100%;
		height: 35px;
		z-index: 4;
		padding: 5px
	}

	#mobilefooter a {

	}

	.catline {
		cursor: pointer;
	}

	.cathide {
		display: none;
	}

	#popupbox {
		display: none;
		position: fixed;
		background: none;
		width: 100%;
		height: 100%;
		z-index: 999;
		overflow: hidden;
		top: 0px;
		left: 0px;
	}

	#oimg {
		position: absolute;
		z-index: -1;
		top: 0;
		left: 0;
		background: #000000;
		width: 100%;
		height: 100%;
		filter: alpha(opacity = 50);
		opacity: .5;
	}

	#ct2:hover #oimg {
		opacity: 1;
	}

	#ct2 {
		position: relative;
		display: inline-block;
	}

	#popup_img {
		max-width: 1000px;
	}

	#popup_c {
		position: absolute;
		right: 0px;
		top: 0px;
		cursor: pointer;
	}

	#Bwidht {
		vertical-align: top;
	}

	.mpicbanner .shopsubnameaddon {
		left: 2px;
	}

	.mpicbanner .shopsubnameaddon {
		top: inherit;
	}

	.mpicbanner .shopsubnameaddon {
		bottom: 8px;
	}

	.mpicbanner .shopnameaddon {
		top: 15px;
	}

	.mpicbanner .shopnameaddon {
		left: 2px;
	}
</style>
<div id='popupbox'>
	<div id='oimg'></div>
	<center>
		<div id='ct2'><a id='popup_img_a' href='' target='_blank'><img
					id='popup_img' src=''></a> <a id='popup_c'><img class='popup_c_img'
																	src='<?php echo HOST_INFO.BASE_URL."/images/ebay/template/close_button.png" ;?>'></a></div>
	</center>
</div>


<style>
	.gbox {
		text-decoration: none;
	}

	.gboxout {
		padding: 5px 5px 5px 5px;
		clear: both;
	}

	.Cat-List {
		position: relative;
		padding: 3px;
		background-color: #FFFFFF;;
		width: 170px;
	}

	.Cat-List:hover {
		background-color: #FFFFFF;;
	}

	.Cat-List a:hover {
		color: #333333
	}

	.Cattop {
		list-style: none;
	}

	.lv1a {
		font-family: Arial !important;
		font-size: 12px;
		color: #7f7f7f;
		width: 160px;
		text-decoration: none;
	}

	.lv2a {
		font-family: Arial !important;
		font-size: 12px;
		color: #7f7f7f;
		width: 160px;
		font-weight: normal;
		text-decoration: none;
	}

	#menudisplay {
		width: 1080px
	}

	.widthset850 {
		max-width: 1080px
	}
</style>

<style type='text/css' media='screen'>
	#Store-Search {

	}

	#promotion_html {
		margin: 0 auto;
		padding-top: 3px
	}

	#big_pic {
		float: left;
	}

	#logo {
		clear: both;
		text-align: center;
	}

	.m_desc_details {
		width: 100%;
	}

	#desc_header {
		font-size: 20px;
	}

	#Bwidht {
		/*width: 410px;*/
	}

	#policy_html {
		/* margin: 10px 0px 0px 0px;*/
		width: 800px;
		border-radius: 10px;
		box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
		word-wrap: break-word;
	}

	#policy_left {
		float: left;
		width: 400PX;
	}

	#policy_right {
		float: left;
		width: 90PX;
	}

	.policy_box {
		border-radius: 10px;
	}

	#feedback_img {
		text-align: center;
	}

	.margin {
		margin: 5px 5px 5px 5px;
	}

	#subbody {
		width: 1080px;
	}

	#right1080 {
		vertical-align: top;
	}

	#left1080 {
		width: 180px;
		vertical-align: top;
		background:
	}
	.menu{
		display: none;
	}
	.layout_left .menu{
		display: block;
		width:180px;
		vertical-align: top;
		float: left;
		margin-right: 40px;
		height: 100%;
	}
	.layout_left .main{
		float: left;
	}
	.layout_right .menu{
		display: block;
		width:180px;
		vertical-align: top;
		float: right;
		margin-right: 40px;
		height: 100%;
	}
	.layout_right .main{
		float: left;
	}
	.shutdown{
		display: none;
	}
	.active{
		display: block;
	}
	.clearfix:before,.clearfix:after{
		content:"";
		display:table;
	}
	.clearfix:after{clear:both;}
	.clearfix{
		*zoom:1;/*IE/7/6*/
	}
</style>


<meta name='viewport'
	  content='width=device-width,minimum-scale=0.1,maximum-scale=10,user-scalable=1' />
<style type='text/css'>
	#showbtnm {
		display: none;
	}

	body>table {
		table-layout: fixed;
	}

	.showbtn {
		display: none;
		font-family: sans-serif;
		color: white;
		font-size: 15px;
		float: left
	}

	.ebaybtn {
		display: none;
	}

	@media screen and (max-width: 640px) {
		#mobilefooter {
			/* display: block !important */
		}
		#mobilebox {
			display: block;
			width: auto !important;
			max-width: 640px;
		}
		#subbody {
			display: none;
		}
		.ebaybtn {
			/* display: block; */
		}
		#logo {
			clear: both;
			text-align: center;
			width: 120PX;
			margin: 0 auto
		}
		.showbtn {
			display: block;
		}
	}

	.popup_c_img {
		width: 40px;
	}
</style>
<?php
$mobilephoneDisplay = '';
if(empty($allItem['eb_tp_mobile_true'])){
	$mobilephoneDisplay = 'style="display: none;"';
}

$mobileboxStyle = 'style=';
if(!empty($allItem['tb_eb_CFI_style_master_BP'])){
	if($allItem['tb_eb_CFI_style_master_BP'] == 'Pattern'){
		$mobileboxStyle .= "background-image: url(".$allItem['tb_eb_CFI_style_master_background_Pattern'].");";
	}else{
		if(stripos($allItem['eb_tp_clr_Mainbody_background'] , '#') === false )
			$allItem['eb_tp_clr_Mainbody_background'] = '#'.$allItem['eb_tp_clr_Mainbody_background'];
		$mobileboxStyle .= "background-color:".$allItem['eb_tp_clr_Mainbody_background'].";";
	}
}

$mobileboxStyle .= "'";
?>
<div id='mobilephone'<?php echo $mobilephoneDisplay;?> >
	<div id='mobilebox' <?php echo $mobileboxStyle;?>>
		<?php

		$m_content_type = array(
			"Shop Name banner"=>"shop_name_banner",
			"Hori. Product Photo"=>"product_photo",
			"Description"=>"description",
			"Item Specifics"=>"item_specifics",
			"Poster"=>"poster",
			"Policy"=>"policy"
		);

		$msortableMap = array();
		if(!empty($msortable)){
			$index = 0;
			foreach ($msortable as $value){
				if($value['name'] == 'm_content_type_id')
					$index++;
				$msortableMap[$index][$value['name']] = $value['value'];
			}
			$m_content_sort = array();
			foreach ($msortableMap as $value){
				$m_content_sort[$value['m_content_displayorder']] = $m_content_type[$value['m_content_type_id']];
			}
		}

		?>
		<?php if(!empty($m_content_sort)):?>
			<?php foreach ($m_content_sort as $index=>$contentType):?>
				<?php echo $this->renderFile($fileRoot.'mobileContentTypeHtml'.$fileExt, array("finalHtml"=>$finalHtml,"allItem"=>$allItem,"contentType"=>$contentType,"itemInfo"=>$itemInfo)); ?>
			<?php endforeach;?>
		<?php endif;?>
	</div>
</div>
<?php
// subbody background:

$subbodyStyle = "style='";

$subBorderSetting = array();
if(!empty($allItem['tb_eb_CFI_style_master_body_size'])){
	$subBorderSetting['size'] = $allItem['tb_eb_CFI_style_master_body_size']."px";
}else{
	$subBorderSetting['size'] = "0px";
}
if(!empty($allItem['tb_eb_CFI_style_master_body_border_style'])){
	$subBorderSetting['style'] = $allItem['tb_eb_CFI_style_master_body_border_style'];
}
if(!empty($allItem['eb_tp_clr_Mainbody_border'])){
	if(stripos($allItem['eb_tp_clr_Mainbody_border'] , '#') === false)
		$allItem['eb_tp_clr_Mainbody_border'] = '#'.$allItem['eb_tp_clr_Mainbody_border'];
	$subBorderSetting['color'] = $allItem['eb_tp_clr_Mainbody_border'];
}

$subbodyStyle .= "border: ".implode(" ", $subBorderSetting).";";


if(!empty($allItem['tb_eb_CFI_style_master_BP'])){
	if($allItem['tb_eb_CFI_style_master_BP'] == 'Pattern'){
		$subbodyStyle .= "background-image: url(".$allItem['tb_eb_CFI_style_master_background_Pattern'].");";
	}else{
		if(stripos($allItem['eb_tp_clr_Mainbody_background'] , '#') === false )
			$allItem['eb_tp_clr_Mainbody_background'] = '#'.$allItem['eb_tp_clr_Mainbody_background'];
		$subbodyStyle .= "background-color:".$allItem['eb_tp_clr_Mainbody_background'].";";
	}
}

$subbodyStyle .= "'";
?>
<div id='subbody' name='bo1' class='C_S_A3' <?php echo $subbodyStyle;?> >
	<div id='header_html' >
		<center>
			<div class='overf'>
				<center>
					<?php if(!empty($allItem['shop_name_text'])):?>
						<?php
						$shopNameStyle = "style='";
						if(!empty($allItem['shop_name_text_size'])){
							$shopNameStyle .= "font-size: ".$allItem['shop_name_text_size'].";";
						}
						if(!empty($allItem['shop_name_text_style'])){
							$shopNameStyle .= "font-family:".$allItem['shop_name_text_style'].";";
						}
						if(!empty($allItem['shop_name_text_color'])){
							if(stripos($allItem['shop_name_text_color'] , '#') === false )
								$allItem['shop_name_text_color'] = '#'.$allItem['shop_name_text_color'];

							$shopNameStyle .= "color:".$allItem['shop_name_text_color'].";";
						}
						if(!empty($allItem['shop_name_text_left'])){
							$shopNameStyle .= "left:".$allItem['shop_name_text_left']."px;";
						}
						if(!empty($allItem['shop_name_text_top'])){
							$shopNameStyle .= "top:".$allItem['shop_name_text_top']."px;";
						}
						$shopNameStyle .= "'";
						?>
						<p class='shopnameaddon' <?php echo $shopNameStyle;?>>
							<?php echo $allItem['shop_name_text'];?>
						</p>
					<?php else:?>
						<p class='shopnameaddon'></p>
					<?php endif;?>

					<?php if(!empty($allItem['shop_name_sub_text'])):?>
						<?php
						$shopNameSubStyle = "style='";
						if(!empty($allItem['shop_name_sub_text_size'])){
							$shopNameSubStyle .= "font-size: ".$allItem['shop_name_sub_text_size']."px;";
						}
						if(!empty($allItem['shop_name_sub_text_style'])){
							$shopNameSubStyle .= "font-family:".$allItem['shop_name_sub_text_style'].";";
						}
						if(!empty($allItem['shop_name_sub_text_color'])){
							if(stripos($allItem['shop_name_sub_text_color'] , '#') === false )
								$allItem['shop_name_sub_text_color'] = '#'.$allItem['shop_name_sub_text_color'];

							$shopNameSubStyle .= "color:".$allItem['shop_name_sub_text_color'].";";
						}
						if(!empty($allItem['shop_name_sub_text_left'])){
							$shopNameSubStyle .= "left:".$allItem['shop_name_sub_text_left']."px;";
						}
						if(!empty($allItem['shop_name_sub_text_top'])){
							$shopNameSubStyle .= "top:".$allItem['shop_name_sub_text_top']."px;";
						}
						$shopNameSubStyle .= "'";
						?>
						<p class='shopsubnameaddon' <?php echo $shopNameSubStyle;?>>
							<?php echo $allItem['shop_name_sub_text'];?>
						</p>
					<?php else:?>
						<p class='shopsubnameaddon'></p>
					<?php endif;?>
					<?php if(!empty($allItem['graphic_setting_Shop_Name_Banner'])):?>
						<img alt='' border='0' class='widthset850'
							 id='usegraphic_setting_Shop_Name_Banner'
							 src='<?php echo $allItem['graphic_setting_Shop_Name_Banner'];?>'>
					<?php else:?>
						<img alt='' border='0' class='widthset850'
							 id='usegraphic_setting_Shop_Name_Banner'
							 src='http://1e60194d2ecb9cce3358-6c3816948ff1e081218428d1ffca5b0d.r1.cf4.rackcdn.com/953bba0926fce846ed3064a7ea3ffbf4_plain_color_white_pure.jpg'>
					<?php endif;?>
				</center>
			</div>
		</center>
	</div>

<center>
	<table>
		<tr>
			<td>
				<center>
					<?php
					$menudisplayStyle = "";
					if("yes" != strtolower($allItem['tb_shop_master_Setting_menu_On'])){
						$menudisplayStyle = "style='display: none;'";
					}
					else{
						$menudisplayStyle = "style='display: block; ";
						if(!empty($allItem['tb_shop_master_Setting_menu_bar'])){
							$menudisplayStyle .= "background-image: url(".$allItem['tb_shop_master_Setting_menu_bar']."); ";
						}
						$menudisplayStyle .= "'";
					}
					?>
					<div id='menudisplay' <?php echo $menudisplayStyle;?>>
						<?php echo $this->renderFile($fileRoot.'menuBar'.$fileExt, array("finalHtml"=>$finalHtml,"allItem"=>$allItem,)); ?>
					</div>

				</center>
			</td>
		</tr>
	</table>
</center>

<div  id="layout_type" class="<?php if(empty($switchType)){echo 'layout_left';}else{ echo $switchType;}?> clearfix">
	<div>
		<div class='menu'>
			<?php echo $this->renderFile($fileRoot.'sideBar'.$fileExt, array("finalHtml"=>$finalHtml,"fileRoot"=>$fileRoot,"fileExt"=>$fileExt,'allItem'=>$allItem,"sortable"=>$sortable,"itemInfo"=>$itemInfo,"newListItem"=>$newListItem));?>
		</div>
		<?php
		$d_content_type = array(
			"Basic Descriptions"=>"basic_descriptions",
			"Item Specifics"=>"item_specifics",
			"Action button"=>"action_button",
			"EagleGallery"=>"eagleGallery",
		);

		$groupDsortableInfo = array();
		if(!empty($dsortable)){
			$index = 0;
			$max = 0;
			foreach ($dsortable as $value){
				if("d_content_type_id" == $value['name'])
					$index++;
				$groupDsortableInfo[$index][$value['name']] = $value['value'];
			}
			$reSortDContent = array();
			$contentTypeDContentMap = array();

			foreach ($groupDsortableInfo as $dContent){
				$max++;
				$reSortDContent[$dContent['d_content_displayorder']] = $dContent;
				$contentTypeDContentMap[$d_content_type[$dContent['d_content_type_id']]][$dContent['d_content_displayorder']] = $dContent;
			}
//			var_dump($contentTypeDContentMap);
			$displayDsortable = array();
			foreach ($reSortDContent as $dContent){
				$displayDsortable[strtolower($dContent['d_content_pos'])][] = $d_content_type[$dContent['d_content_type_id']];
			}

		}
		?>
		<div id='main' class="main">
			<div id="promotion_html">
				<?php
				$noticeBannerStyle = "";
				if(!empty($allItem['Notice_Banner_ONNOFF']) && "off" == strtolower($allItem['Notice_Banner_ONNOFF'])){
					$noticeBannerStyle .= "style='display: none;'";
				}
				?>

				<div <?php echo $noticeBannerStyle;?> id="sample2_graphic_setting_Notice_Banner">
					<center>
						<?php if(!empty($allItem['graphic_setting_Notice_Banner'])):?>
							<img class="sampleimg catnbwidthset"
								 id="sample_graphic_setting_Notice_Banner"
								 src="<?php echo $allItem['graphic_setting_Notice_Banner'] ;?>">
						<?php endif;?>
					</center>
				</div>
			</div>
			<div id='desc_html'>
				<div id="above_product_Photo" class="desc_word ">
					<?php
					if(isset($displayDsortable) && isset($displayDsortable['above_product_photo'])){
						foreach ($displayDsortable['above_product_photo'] as $d_content_type){
							foreach($contentTypeDContentMap[$d_content_type] as $dcontent){
								if(is_array($dcontent) && $dcontent['d_content_pos']=='above_product_Photo'){
									echo $this->renderFile($fileRoot.'itemPartialHtml'.$fileExt, array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$dcontent));
								}

							}
							break;
						}
					}
					?>
				</div>
				<?php



				$subtitleStyle = "style='";

				if(!empty($allItem['Title_ONNOFF']) && "off" == strtolower($allItem['Title_ONNOFF']))
					$subtitleStyle = "style='display: none;'";
				if(!empty($allItem['eb_tp_clr_Title'])){
					if(stripos($allItem['eb_tp_clr_Title'] , '#') === false )
						$allItem['eb_tp_clr_Title'] = '#'.$allItem['eb_tp_clr_Title'];
					$subtitleStyle .= "color: ".$allItem['eb_tp_clr_Title'].";";
				}else{
					$subtitleStyle .= "color: #ffffff;";
				}
				if(!empty($allItem['eb_tp_font_Title'])){
					$subtitleStyle .= "font-family: ".$allItem['eb_tp_font_Title'].";";
				}else{
					$subtitleStyle .= "font-family: Arial;";
				}
				if(!empty($allItem['eb_tp_Title_Size'])){
					$subtitleStyle .= "font-size: ".$allItem['eb_tp_Title_Size']."px;";
				}else{
					$subtitleStyle .= "font-size: 12px;";
				}
				$subtitleStyle .= "'";

				?>
				<div class='subtitle' <?php echo $subtitleStyle;?> ><?php echo $itemInfo['title'];?></div>
				<div id='desc' name='desc'>
					<?php if(isset($productType)){ $data =  $productType;}else{$data = 'product_layout_left';} ?>
					<div id="product_layout_left" class="shutdown <?php if($data == 'product_layout_left'){echo "active";}?> clearfix">
						<div>
							<div>
								<div id="Bwidht" class="product_photo_need_hide" style="width: 410px; <?php if($data == 'product_layout_left'){echo 'float:left;';}?>">
									<?php if(!empty($itemInfo['imagesUrlArr'][0])):?>
										<div>
											<div class="bigimage">
												<div id="gallery"><a href="<?php echo $itemInfo['imagesUrlArr'][0]; ?>" id="linkId">
														<img alt="" border="0" id="sample_Bigimg" width="400PX" src="<?php echo$itemInfo['imagesUrlArr'][0]; ?>" class="img_border" name="mainimage">
														<div id="Zoom-Icon"></div>
													</a></div>
											</div>
										</div>
										<div id="smail_pic_box">
											<?php foreach ($itemInfo['imagesUrlArr'] as $index=>$source):?>

												<div class="smail_pic smail_pic<?php echo $index;?>"><a class="mousedown" id="MD<?php echo $index;?>"
																										onmousedown="changeImages('<?php echo $source;?>')">
														<img alt="" class="sm<?php echo $index;?>" width="70px" src="<?php echo $source;?>">
													</a>
												</div>
											<?php endforeach;?>
										</div>
									<?php endif;?>
								</div>
								<div style="vertical-align: top;">
									<div>
										<div id="Next_to_Product_Photo">
											<?php
											if(isset($displayDsortable) && isset($displayDsortable['next_to_product_photo'])){
												foreach ($displayDsortable['next_to_product_photo'] as $d_content_type){
													foreach($contentTypeDContentMap[$d_content_type] as $dcontent){
														if(is_array($dcontent) && $dcontent['d_content_pos']=='Next_to_Product_Photo'){
															echo $this->renderFile($fileRoot.'itemPartialHtml'.$fileExt, array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$dcontent));
														}

													}
													break;
												}
											}
											?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div id="product_layout_right" class="product_layout_right shutdown <?php if($data == 'product_layout_right'){echo "active";}?> clearfix">
						<div>
							<div>
								<div id="Bwidht" class="product_photo_need_hide clearfix">
									<?php if(!empty($itemInfo['imagesUrlArr'])):?>
										<div id="big_pic">
											<div class="bigimage">
												<div id="gallery"><a href="<?php echo $itemInfo['imagesUrlArr'][0]; ?>" id="linkId">
														<img alt="" border="0" id="sample_Bigimg" width="400PX" src="<?php echo$itemInfo['imagesUrlArr'][0]; ?>" class="img_border" name="mainimage">
														<div id="Zoom-Icon"></div>
													</a></div>
											</div>
										</div>
										<div id="smail_pic_box" >
											<?php foreach ($itemInfo['imagesUrlArr'] as $index=>$source):?>

												<div class="smail_pic smail_pic<?php echo $index;?>"><a class="mousedown" id="MD<?php echo $index;?>"
																										onmousedown="changeImages('<?php echo $source;?>')">
														<img alt="" class="sm<?php echo $index;?>" width="70px" src="<?php echo $source;?>">
													</a>
												</div>
											<?php endforeach;?>
										</div>
									<?php endif;?>
								</div>
								<div style="vertical-align: top;">

								</div>
							</div>
							<div class="desc_word" id="h_desc" >
								<div id="Next_to_Product_Photo" class="desc_word product_layout_right" ><br>
									<?php
									if(isset($displayDsortable) && isset($displayDsortable['next_to_product_photo'])){
										foreach ($displayDsortable['next_to_product_photo'] as $d_content_type){
											foreach($contentTypeDContentMap[$d_content_type] as $dcontent){
												if(is_array($dcontent) && $dcontent['d_content_pos']=='Next_to_Product_Photo'){
													echo $this->renderFile($fileRoot.'itemPartialHtml'.$fileExt, array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$dcontent));
												}

											}
											break;
//											echo $this->renderFile($fileRoot.'itemPartialHtml'.$fileExt, array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$contentTypeDContentMap[$d_content_type],"type"=>"Next_to_Product_Photo"));
										}
									}
									?>
								</div>
							</div>
						</div>
					</div>
					<div id="product_layout_center" class="product_layout_center shutdown <?php if($data == 'product_layout_center'){echo "active";}?> clearfix">
						<div id="Bwidht" class="product_photo_need_hide clearfix">
							<?php if(!empty($itemInfo['imagesUrlArr'])):?>
								<div id="big_pic">
									<div class="bigimage">
										<div id="gallery">
											<img alt="" border="0" id="sample_Bigimg" width="400PX" src="<?php echo$itemInfo['imagesUrlArr'][0]; ?>" class="img_border" name="mainimage">
										</div>
									</div>
								</div>

							<?php endif;?>
						</div>
						<div class="desc_word" id="h_desc" >
							<div id="Next_to_Product_Photo" class="desc_word product_layout_right" ><br>
								<?php
								if(isset($displayDsortable) && isset($displayDsortable['next_to_product_photo'])){
									foreach ($displayDsortable['next_to_product_photo'] as $d_content_type){
										foreach($contentTypeDContentMap[$d_content_type] as $dcontent){
											if(is_array($dcontent) && $dcontent['d_content_pos']=='Next_to_Product_Photo'){
												echo $this->renderFile($fileRoot.'itemPartialHtml'.$fileExt, array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$dcontent));
											}

										}
										break;
									}
								}
								?>
							</div>
						</div>
						<div >
							<?php if(!empty($itemInfo['imagesUrlArr'])):?>
								<?php foreach ($itemInfo['imagesUrlArr'] as $index=>$source):?>
									<div class="bigimage" style="padding-top: 10px;">
										<img alt="" class="sm<?php echo $index;?>" width="400px" src="<?php echo $source;?>">
									</div>
								<?php endforeach;?>
							<?php endif;?>
						</div>
					</div>
					<div id='Below_All_Product_Photo' class='desc_word '>
						<?php
						$index= 0;
						if(isset($displayDsortable) && isset($displayDsortable['below_all_product_photo'])){
							foreach ($displayDsortable['below_all_product_photo'] as $d_content_type){
								foreach($contentTypeDContentMap[$d_content_type] as $dcontent){
									if(is_array($dcontent) && $dcontent['d_content_pos'] === "Below_All_Product_Photo" ){
										echo $this->renderFile($fileRoot.'itemPartialHtml'.$fileExt, array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$dcontent));
									}
								}
								break;
							}
						}
						?>
					</div>
				</div>
			</div>

			<div id='poster_html'>
			<div class='poster_pic'>
				<div class='disnone'></div>
				<br>
				<div id='Below_All_Product_Posters' class='desc_word '>
					<?php
					if(isset($displayDsortable) && isset($displayDsortable['below_all_product_posters'])){
						foreach ($displayDsortable['below_all_product_posters'] as $d_content_type){
							foreach($contentTypeDContentMap[$d_content_type] as $dcontent){

								if(is_array($dcontent) && $dcontent['d_content_pos']=='Below_All_Product_Posters'){
									echo $this->renderFile($fileRoot.'itemPartialHtml'.$fileExt, array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$dcontent));
								}

							}
							break;
						}
					}
					?>
				</div>
			</div>
		</div>

		<br>
		<?php
		$policyInfo = array();
		$policyNum = 0;
		for($i = 1; $i <= 5; $i++ ){
			if(!empty($allItem['sh_ch_info_Policy'.$i.'_header']) && !empty($allItem['sh_ch_info_Policy'.$i])){
				$policyNum++;
				$policyInfo[$policyNum]['policy_header'] = (!empty($allItem['sh_ch_info_Policy'.$i.'_header'])?$allItem['sh_ch_info_Policy'.$i.'_header']:"Policy".$policyNum." header");
				$policyInfo[$policyNum]['policy'] = (!empty($allItem['sh_ch_info_Policy'.$i])?$allItem['sh_ch_info_Policy'.$i]:"");
			}
		}
		$policyTabWidth = floor(800 / $policyNum) - 22;
		$policyTabWidth = "width: ".$policyTabWidth."px";


		// policy background ,tabscontent border
		$policyBackStyle = "style='";

		if(!empty($allItem['tb_eb_CFI_style_policy_BP'])){
			if($allItem['tb_eb_CFI_style_policy_BP'] == 'Pattern'){
				$policyBackStyle .= "background-image: url('".$allItem['eb_tp_policy_Pattern']."')";
			}else{
				if(!strripos($allItem['eb_tp_clr_infobox_background'],"#"))
					$allItem['eb_tp_clr_infobox_background'] = "#".$allItem['eb_tp_clr_infobox_background'];
				$policyBackStyle .= "background-color: ".$allItem['eb_tp_clr_infobox_background'].";";
			}

		}
		$policyBackStyle .= "'";

		$policyTabContentStyle = "style='";
		$tabContentBorderSetting = array();
		if(!empty($allItem['eb_tp_clr_infobox_border_size'])){
			$tabContentBorderSetting['size'] = $allItem['eb_tp_clr_infobox_border_size']."px";
		}else{
			$tabContentBorderSetting['size'] = "0px";
		}
		if(!empty($allItem['eb_tp_clr_infobox_border_style'])){
			$tabContentBorderSetting['style'] = $allItem['eb_tp_clr_infobox_border_style'];
		}
		if(!empty($allItem['eb_tp_clr_infobox_border'])){
			if(stripos($allItem['eb_tp_clr_infobox_border'] , '#') === false )
				$allItem['eb_tp_clr_infobox_border'] = '#'.$allItem['eb_tp_clr_infobox_border'];
			$tabContentBorderSetting['color'] = $allItem['eb_tp_clr_infobox_border'];
		}

		$policyTabContentStyle .= "border: ".implode(" ", $tabContentBorderSetting).";";
		$policyTabContentStyle .= "border-top: 0px;";
		$policyTabContentStyle .= "'";

		?>
		<?php if(!empty($policyInfo)):?>
			<div id='policy_html' <?php echo $policyBackStyle;?> >
				<div id="tabs">
					<ul>
						<?php foreach ($policyInfo as $index=>$value):?>
							<li id="tabHeader_<?php echo $index;?>" class="tabHeaderi" style="<?php echo $policyTabWidth;?>" >
								<p id="tabHeader<?php echo $index;?>">
									<?php echo $value['policy_header']?>
								</p>
							</li>
						<?php endforeach;?>
					</ul>
				</div>
				<div id="tabscontent">
					<?php foreach ($policyInfo as $index=>$value):?>
						<div class="tabpage" id="tabpage_<?php echo $index;?>"  <?php echo $policyTabContentStyle;?>>
							<div id="policy_box<?php echo $index;?>_text" class="ckinline editor">

								<?php echo $value['policy']?>
							</div></div>
					<?php endforeach;?>

				</div>
			</div>
		<?php endif;?>

		<div id='feedback_html'>
			<div class='margin'>
				<div id='policy_bot_text' name='policy_bot_text' class='editor'>
					<?php  if(!empty($allItem['sh_ch_info_Policybot'])):?>
						<?php echo $allItem['sh_ch_info_Policybot'];?>
					<?php endif;?>
				</div>
			</div>
		</div>
		</div>
		<br>
		</td>
		</tr>
		</table>
	</div>
	<div id='logo'>
	</div>
	<div id='mobilefooter'>
		<a href='#' class='showbtn' id='showbtnd' onclick='showdesktop();'>Desktop</a>
		<a href='#' id='showbtnm' class='showbtn' onclick='showmobile();'>Mobile</a>
		<a id='btn_buy' class='ebaybtn ebaybtnbuy' title=''>Buy</a>
		<a id='btn_bin' class='ebaybtn' title=''>Watch</a></div>
</div>
</div>
	<script type='text/javascript'>

		popup_c = document.getElementById('popupbox');
		popup_c.onclick = hide_popup;
		popup('linkId');

		<?php
        if(isset($itemInfo) && !empty($itemInfo['imagesUrlArr'])){
            foreach ($itemInfo['imagesUrlArr'] as $index=>$prodLink){
                echo "popup('mbp".$index."');";
            }
        }
        ?>
		function popup(id) {
			if(document.getElementById(id) != null){
				popup_a = document.getElementById(id);
				popup_a.onclick = show_popup;
			}
			return false;
		}
		function hide_popup() {
			document.getElementById('popupbox').style.display='none';
			return false;
		}

		function show_popup() {
			popup_i_a = document.getElementById('popup_img_a');
			popup_i = document.getElementById('popup_img');
			popup_i.style.width = 'auto';
			popup_i.style.height = 'auto';
			popup_i.src = this.href;
			popup_i_a.href = this.href;
			if(popup_i.width > window.innerWidth)
			{
				popup_i.style.width = window.innerWidth - 10 +'px';
				document.getElementById('ct2').style.left = '0px';
			}
			if(popup_i.height > window.innerHeight)
			{
				popup_i.style.height = window.innerHeight - 30 +'px';
				document.getElementById('ct2').style.top = '5px';
			}else{
				if(window.innerHeight / 2 - popup_i.height / 2 < 400 && window.innerHeight / 2 - popup_i.height / 2 > 0){
					document.getElementById('ct2').style.top = window.innerHeight / 2 - popup_i.height / 2 + 'px';
				}else{
					document.getElementById('ct2').style.top = '5px';
				}
			}

			;

			document.getElementById('popupbox').style.display='block';
			return false;
		}
		function showmobile() {
			document.getElementById('subbody').style.display = 'none';
			document.getElementById('mobilebox').style.display = 'block';
			document.getElementById('showbtnm').style.display = 'none';
			document.getElementById('showbtnd').style.display = 'block';
		}
		function showdesktop() {
			document.getElementById('subbody').style.display = 'block';
			document.getElementById('mobilebox').style.display = 'none';
			document.getElementById('showbtnm').style.display = 'block';
			document.getElementById('showbtnd').style.display = 'none';
		}

		function gobuy(oj) {
			var fside = findsite();
			var itm = fside['itm'];
			var site = fside['site'];
			var ebay_site = fside['ebay_site'];

			var buy_url='http://offer.'+ebay_site+site+'/ws/eBayISAPI.dll?BinConfirm&item='+itm ;
			document.getElementById(oj).href=buy_url;
		}
		function goBid(oj) {
			var fside = findsite();
			var itm = fside['itm'];
			var site = fside['site'];
			var ebay_site = fside['ebay_site'];

			var buy_url='http://offer.'+ebay_site+site+'/ws/eBayISAPI.dll?MakeBid&item='+itm ;
			document.getElementById(oj).href=buy_url;
		}
		function create_bin() {
			var fside = findsite();
			var itm = fside['itm'];
			var site = fside['site'];
			var ebay_site = fside['ebay_site'];
			bin = document.getElementById('btn_bin');
			buy = document.getElementById('btn_buy');
			if(bin&&itm){
				var bin_url='http://cgi1.'+ebay_site+site+'/ws/eBayISAPI.dll?MakeTrack&item='+itm;
				var buy_url='http://offer.'+ebay_site+site+'/ws/eBayISAPI.dll?BinConfirm&item='+itm;
				bin.href=bin_url;
				buy.href=buy_url;
			}else{
				if(bin){ bin.style.display = 'none';}
				if(buy){buy.style.display = 'none';}
			}
		}
		function findsite(){
			var itm, ebay_site, url = location.href,
				site = location.hostname.split(/\.ebay\.|\.ebaydesc\./i)[1],
				res = url.match(/item=\d+/),
				is_sandbox=location.hostname.match(/\.sandbox\./i);
			if(is_sandbox){
				ebay_site='sandbox.ebay.';
			}else{
				ebay_site='ebay.';
			}
			if (!res){res = url.match(/\/\d+/)};
			if (!res){res = url.match(/\d{12}/)};
			if (res){itm = res[0].match(/\d+/)};
			var IDs = new Array();
			IDs['ebay_site'] = ebay_site;
			IDs['site'] = site;
			IDs['itm'] = itm;
			return IDs;
		}
		function create_qr() {
			var qr_code_img = document.getElementById('qr_code_img');
			var qr_code_a = document.getElementById('qr_code_a');
			var fside = findsite();
			var itm = fside['itm'];
			var site = fside['site'];
			var ebay_site = fside['ebay_site'];
			if(qr_code_img != null){
				if (location.hostname.match(/\.ebaydesc\./i)) {
					var code_url='https://chart.googleapis.com/chart?chs=173x173&cht=qr&chl=http%3A%2F%2Fwww.'+ebay_site+site+'/itm/'+itm+'&choe=UTF-8';
					var code_url400='https://chart.googleapis.com/chart?chs=400x400&cht=qr&chl=http%3A%2F%2Fwww.'+ebay_site+site+'/itm/'+itm+'&choe=UTF-8';
				}else{
					var code_url='https://chart.googleapis.com/chart?chs=173x173&cht=qr&chl=http%3A%2F%2Fwww.'+ebay_site+site+'/itm/'+itm+'&choe=UTF-8';
					var code_url400='https://chart.googleapis.com/chart?chs=400x400&cht=qr&chl=http%3A%2F%2Fwww.'+ebay_site+site+'/itm/'+itm+'&choe=UTF-8';
				}
				qr_code_a.href=code_url400;
				qr_code_img.src=code_url;
				popup('qr_code_a');
			}
		}
		function showme(id, activeHeader) {
			if("mobpovah" == activeHeader.className){
				activeHeader.removeAttribute('class');
				activeHeader.setAttribute("class","mobpovh");
			}else{
				activeHeader.removeAttribute('class');
				activeHeader.setAttribute("class","mobpovah");
			}
			var divid = document.getElementById(id);
			if (divid.style.display == 'block') {

				divid.style.display = 'none'; }
			else {

				divid.style.display = 'block';
			}
			return false;
		}
		function showme1(id, linkid) {
			var divid = document.getElementById(id);
			var toggleLink = document.getElementById(linkid);
			if (divid.style.display == 'block') {
				toggleLink.innerHTML = '&#9658;';
				divid.style.display = 'none';
			}else{
				toggleLink.innerHTML = '&#9660;';
				divid.style.display = 'block';
			}
			return false;
		}
		function showme2(id, linkid) {
			var divid = document.getElementById(id);
			var toggleLink = document.getElementById(linkid);
			if (divid.style.display == 'block') {
				toggleLink.innerHTML = '&#9655;';
				divid.style.display = 'none'; }
			else {
				toggleLink.innerHTML = '&#9661;';
				divid.style.display = 'block';
			}
			return false;
		}
		// mobile view btn
		create_bin();
		create_qr();
		var isiPad = navigator.userAgent.match(/iPad/i) != null;
		if(isiPad){
			if(document.getElementById('menu')){
				document.getElementById('menu').style.display='none';
			}
			chipad('subbody','850');
			chipad('menudisplay','850');
			chipad('usegraphic_setting_Shop_Name_Banner','850');
			chipad('policy_html','800');
			chipad('tabContainer','800');
			chipad('feedback_html','800');
			chipad('policy_box1','800');
			chipad('policy_box2','800');
			chipad('policy_box3','800');
			chipad('policy_box4','800');
			chipad('policy_box5','800');
		}
		function chipad(id,num) {
			if(document.getElementById(id)){
				document.getElementById(id).style.width = num+'px';
			}
			return false;
		}
		// item specific's btn id
		// goBid('acta149853');
		// gobuy('actb149853');
	</script>