<?php 
use eagle\models\EbayCategory;
use common\helpers\Helper_Siteinfo;
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\listing\helpers\MubanHelper;
?>
<?php 
$BasePath=Yii::getAlias('@web');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns:fb="http://www.facebook.com/2008/fbml" xmlns:og="http://opengraphprotocol.org/schema/">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script type="text/javascript">var plst=new Date().getTime();</script>
<title><?php echo str_replace('"','&quot;',$data['itemtitle'])?></title>
<script type="text/javascript" src="<?php echo $BasePath.'/js/lib/jquery-1.8.3.min.js'?>"></script>
<script type="text/javascript">
function vi_tabs_0(){
    $("#vi_tabs_0_td").addClass("tb-act");
    $("#vi_tabs_1_td").removeClass("tb-act");
    $("#vi_tabs_0_cnt").attr("class","tb-cntOn");
    $("#vi_tabs_1_cnt").attr("class","tb-cntOff");
}
function vi_tabs_1(){
    $("#vi_tabs_1_td").addClass("tb-act");
    $("#vi_tabs_0_td").removeClass("tb-act");
    $("#vi_tabs_1_cnt").attr("class","tb-cntOn");
    $("#vi_tabs_0_cnt").attr("class","tb-cntOff");
}
</script>
<meta name="google-site-verification" content="02-SEP-10">
<meta name="y_key" content="8kHr3jd3Z43q1ovwo0KVgo_NZKIEMjthBxti8m8fYTg">
<meta property="og:type" content="product">
<meta property="og:site_name" content="eBay">
<!--[if lt IE 7]><style>* html .vi-c_0 { width: 940px; width: expression((document.documentElement && document.documentElement.clientHeight) ? ( (document.documentElement.clientWidth < 940) ? "940px" : ( (document.documentElement.clientWidth > 1200) ? "1200px" : "auto") ) : ( (document.body.clientWidth < 940) ? "940px" : "auto") ); }</style><![endif]-->
<script>var pageHasRtmPlacements = true;</script>
<link rel="stylesheet" type="text/css" href="<?php echo $BasePath.'/css/listing/ebay/4crcpvxbne14xgwg3h3o4xbfm.css'?>">
<link rel="stylesheet" type="text/css" href="<?php echo $BasePath.'/css/listing/ebay/ki0rvpytjaztrb3a14vwphj4n.css'?>"?>
<!--[if lt IE 8]><style>.bn-b input{position:relative;left:-3px;padding:0 14px;width:1%}.bn-b b,.bn-b a{left:-3px}.psb-S input,.ssb-S input,.trsb-S input{padding:0 9px 0 10px}</style><![endif]-->
<!--[if IE 6]><style>.bn-b input{overflow:visible;width:0}.bn-b,.bn-b input,.bn-b a,.bn-b b{background-image:url(http://p.ebaystatic.com/aw/pics/cmp/ds2/sprButtons.gif)}</style><![endif]-->
<!--[if IE 8]><style>.bn-b input{padding:0 14px 2px}.psb-S input,.ssb-S input,.trsb-S input{padding:2px 9px 3px 10px}.psb-bp input{background-position:1px -479px}.pb-bp input{background-position:1px -171px}.trsb-bp input{background-position:1px -1711px}.ssb-bp input{background-position:1px -1095px}.trb-bp input{background-position:1px -1403px}</style><![endif]-->
<!--[if lt IE 8]><style>.act-icon{padding-top:2px}.atc-btn{padding-right:25px!important;margin-right:0!important}.trsTop{margin-top:-3px!important;margin-left:0!important}.s-content{margin:0 0 0 3px}.s-content-eu td{padding-bottom:0}.sl-eu{margin-bottom:10px}.s-gray-eu{margin-bottom:10px}.trsTopPanel{margin-top:0}.trsBtmPanel{position:fixed}.vi-pla-ec-span{margin-top:-2px}.vi-pla-ec-span-down{margin-top:2px}</style><![endif]-->
</head>
<body style="text-align: left;" itemscope="itemscope" itemtype="http://schema.org/Product" id="body">
<?php 
$select_site=$select_site;
$values=$data;
?>
<div></div>
<div id="vi-container" class="vi-c_0">
<div id="vi-top"><!--[if lt IE 7 ]><div id='gnheader' class='gh-w ie6'><![endif]--><!--[if IE 7]><div id='gnheader' class='gh-w ie7'><![endif]--><!--[if (gt IE 7)|!(IE)]><!-->
<div id="gnheader" class="gh-w"><!--<![endif]--><a href="#mainContent" rel="nofollow" class="g-hdn">Skip to main content</a>
<div>
<div class="gh-eb">
	<div class="gh-emn">
		<div class="gh-hid"></div>
		<div class="gh-mn">
			<span class="gh-fst"><a id="MyEbay" href="javascript:" _sp="m570.l2919">My eBay</a></span>
			<a id="Sell" href="javascript:" _sp="m570.l1528">Sell</a>
			<a id="Community" href="javascript:" _sp="m570.l1540">Community</a>
			<span class="gh-nho"></span><a id="Help" href="javascript:" _sp="m570.l1545">Customer Support</a>
			<span class="gh-nho"><span id="GH_Cart" class="gh-sc"><a href="javascript:"><img src="<?php echo $BasePath.'/css/listing/ebay/iconCart000.gif';?>" alt="Your shopping cart" border="0" height="24" width="31">Cart</a></span></span>
		</div>
	</div>
	<form id="headerSearch" name="headerSearch" method="get" action="">
	<input name="_from" value="R40" type="hidden">
	<input name="_trksid" value="m570.l2736" type="hidden">
	<span class="gh-esb"><label for="_nkw" class="g-hdn">Enter your search keyword</label><input class="gh-txt" name="_nkw" id="_nkw" type="text"><a><input value="Go" class="gh-go" type="button"></a></span>
	</form>
</div>
<div class="gh-log">
	<span class="gh-lg"><a id="EbayLogo" href="javascript:" _sp="m570.l2586"><img src="<?php echo $BasePath.'/css/listing/ebay/logoEbay_x45.png';?>" alt="eBay" border="0" height="45" width="110"></a></span>
	<span class="gh-wrap"><span class="gh-shim"></span><span class="greeting gh-ui"><!-- BEGIN: GREETING:SIGNEDOUT -->Welcome! <a href="javascript:" >Sign in</a> or <a href="javascript:" id="registerLink" _sp="m570.l2621" rel="nofollow">register</a>.<!-- END: GREETING:SIGNEDOUT --><span
    id="bta"></span></span><span class="coupon"></span></span></div>
<div></div>
</div>
<div class="gh-cl"></div>
<div>
<div class="gh-col"><b class="gh-c1"></b><b class="gh-c2"></b><b
    class="gh-c3"></b><b class="gh-c4"></b><b class="gh-c5"></b><b
    class="gh-c6"></b><b class="gh-c7"></b>
<div class="gh-clr"></div>
</div>
<div id="headerWrapper" class="gh-hbw">
<div class="gh-hb">
<div class="gh-mn"><a id="BrowseCategories" href="javascript:"
    _sp="m570.l1620">CATEGORIES</a><a id="chevron0" href="javascript:"
    class="gh-ai"><b>&nbsp;</b></a><a id="EbayElectronics"
    title="Your shopping destination for the best selection and value in electronics and accessories"
    href="javascript:" _sp="m570.l2959">ELECTRONICS</a><span id="11450_sp"><a
    title="Your new destination for Clothing, Shoes &amp; Accessories on eBay."
    href="javascript:" _sp="m570.l2624">FASHION</a></span><a id="6000_sp"
    title="Buy and sell cars, trucks, vehicle parts, and accessories."
    href="javascript:" _sp="m570.l2597">MOTORS</a><a id="EbayTickets"
    title="Tickets – Sports, Concerts, Theater and More on eBay"
    href="javascript:" _sp="m570.l1624">TICKETS</a><a id="172382_sp"
    title="Great items, deep discounts, and free shipping!"
    href="javascript:" _sp="m570.l2625">DEALS</a><a id="EbayClassifieds"
    href="javascript:" _sp="m570.l2626">CLASSIFIEDS</a></div>
</div>
<div class="gh-lbh1">
<div class="gh-rtm" style="display: inline-block; display: block;">
<div id="rtm_html_876"></div>
</div>
</div>
<div class="gh-lbh2">
<div class="gh-rtm" style="display: inline-block; display: block;">
<div id="rtm_html_912"></div>
</div>
</div>
<div class="gh-lbh3">
<div class="gh-rtm" style="display: inline-block; display: block;">
<div id="rtm_html_433"></div>
</div>
</div>
<div class="gh-clr"></div>
</div>
<img src="<?php echo $BasePath.'/css/listing/ebay/a.gif';?>" alt="" height="1"
    width="1"><script type="text/javascript">var svrGMT = 1322663314959;</script></div>
</div>

<div class="vi-cmb">
<div class="vi-ih-header">
<table class="vi-ih-area_nav" border="0" cellpadding="0" cellspacing="0">
    <tbody>
        <tr>
            <td valign="top"><span id="ngviback" class="sbt"><a
                href="javascript:" title="Click to Go Back to home page"><img
                src="<?php echo $BasePath.'/css/listing/ebay/iconLtArrow_20x20.gif';?>"
                alt="Click to Go Back to home page" align="middle" border="0"
                height="20" width="20">Back to home page</a></span></td>
            <td class="vi-ih-pipe-cell" valign="top">&nbsp;|&nbsp;</td>
            <td valign="top">
            <table style="margin-top: 0px;">
                <tbody>
                    <tr>
                        <td></td>
                        <td valign="top">
                        <div>
                        <div class="bbc-in bbc bbc-nav"><!--ebay分类--> <b class="g-hdn">Bread
                        Crumb Link</b> <a href="javascript:">
<?php $ec=EbayCategory::find()->where('siteid = :siteid and categoryid = :categoryid',array(':siteid'=>$data['siteid'],':categoryid'=>$data['primarycategory']))->one();?>
<?php if (empty($ec)):?>
	<span class="red">NULL</span>
<?php else:?>
	<?php echo EbayCategory::getPath($ec,$ec->name,$data['siteid'])?>
<?php endif;?>
</a></div>
                        </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            </td>
        </tr>
    </tbody>
</table>
</div>
</div>
<div style="clear: both;">
<div class="vi-tm-pbt10">
<div id="blueStripComp" class="vi-tm-tpd"></div>
</div>
</div>
<div class="z_5" style="clear: both">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tbody>
        <tr>
            <td align="right" nowrap="nowrap" width="100%"><span><span
                class="watchlinkSpan" id="linkTopAct"><img
                src="<?php echo $BasePath.'/css/listing/ebay/s.gif';?>" alt="" width="5"><a
                rel="nofollow" title="" id="WtchItm" href="javascript:">Add to Watch
            list</a></span></span><span><span id="dwnArr"></span><span id="upArr"
                class="z_1"></span></span><img
                src="<?php echo $BasePath.'/css/listing/ebay/s.gif';?>" alt="" width="3"></td>
        </tr>
    </tbody>
</table>
<div>
<div>
<div>
<div id="errorDiv" class="watchouterdiv1_5" style="display: none">You
have reached your maximum guest watch list limit of 10 items.<br>
Please remove some items from your watch list in <a href="javascript:">My
eBay</a> if you want to add more.</div>
<div id="masterDiv" class="watchItem watchOuterDiv"
    style="display: none">
<div class="guestLine">This item has been added to your guest watch list
in <a href="javascript:">My eBay</a>.</div>
<div id="middleDiv" class="watchInfo"></div>
</div>
</div>
</div>
</div>
</div>
</div>
<table id="vi-tTbl" border="0" cellpadding="0" cellspacing="0">
    <tbody>
        <tr>
            <td colspan="1" rowspan="1" id="vi-tTblC1" class="vi-tTblC1_0">
            <div>
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tbody>
                    <tr>
                        <td class="ipics-cell-0">



                        <div class="vi-ipic1"><span class="shig" id="freeShippingIcon"><span
                            id="spclOffers"></span></span><span itemprop="image"
                            content="http://coomao-goodsimg.s3.amazonaws.com/349/101209/163942-780531a6dd.JPG"></span>
                        <form name="ssFrm"
                            action=""
                            target="ssFrmWin" method="post"><input type="hidden" name="ssr"
                            value="1"><input type="hidden" name="iurls" value=""><input
                            type="hidden" name="dtid" id="dtid" value="0"><input
                            type="hidden" name="vs" value="1"><input type="hidden" name="sh"
                            value="1"><input type="hidden" name="title"
                            value="Blue Aluminum mini 4GB MP3 fitness function Sporty Clip"></form>
                        <div>
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tbody>
                                <tr>
                                    <td class="vs_w-a">
                                    <div class="ict-w1" id="vv4-42">
                                    <div class="ic-w300 ic-cntr">
                                    <div class="ic-w300 ic-m" id="vv4-42_idiv">
                                    <center><span></span> <script type="text/javascript">
                                    var imgmain='<?=@$data['imgurl'][0] ?>';
                                    </script> <img id="mainimg" height="300" width="300" alt=""
                                        style="width: 300px; height: 225px;"
                                        src="<?=@$data['imgurl'][0] ?>"></center>
                                    </div>
                                    <a id="vv4-42_a" class="ic-cp" href="javascript:;"></a>
                                    <div id="vv4-42_bdiv" class="ic-p ic-b1"
                                        style="height: 298px; width: 298px;">
                                    <div id="vv4-42_t" class="ic-thr" style="display: none;"><span></span></div>
                                    <div id="vv4-42_e" class="ic-err" style="display: none;"><span>Image
                                    not available</span></div>
                                    </div>
                                    </div>
                                    <div class="tbr-c" id="vv4-42_TB">
                                    <ul class="tbr-w">
                                        <li title="Show larger and alternate views" class="tbr-l"><a
                                            id="vv4-42_TB_0"
                                            onclick="window.open(imgmain,'img','resizable=yes');"
                                            href="javascript:"><span class="ict-enl">Enlarge</span></a></li>
                                    </ul>
                                    </div>
                                    </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td id="vv4-42_sp" class="vs_w-spr">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>

                                    <table border="0" cellpadding="0" cellspacing="0" width="100%"
                                        height="100%" class="tg-tb tg-clp" id="vv4-42_tbl">
                                        <tbody>
                                            <tr id="t_r_vv4-42_0">
                                              <?php $imgshownumtd=0; ?>
                                              <?php
                                                         $variation=empty($values['variation'])?NULL:json_decode($data['variation']);
                                                        if(!empty($variation)){
                                                         $variationval=$variation->Variation;
                                                         $variationpic=$variation->Pictures;
                                              ?> 
                                              <?php if(!empty($data['imgurl'])):?>
                                              <?php foreach ($data['imgurl'] as $v):?>
                                                <td id="pic" width="16%" height="42px"><img class="picclass"
                                                    onclick="$('.picclass').css('border-width','0px');$(this).css('border-width','3px');imgmain=$(this).attr('src');<?php foreach ($variation->Variation[0]->VariationSpecifics->NameValueList as $select):?>$('#Attributessel_<?php echo $select->Name;?>').val('-1');<?php endforeach;?>Attributeschan('-1');"
                                                    onmouseover="$('#mainimg').attr('src',$(this).attr('src'));"
                                                    onmouseout="$('#mainimg').attr('src',imgmain);"
                                                    src="<?=$v ?>" alt="" id="t_ivv4-42_0"
                                                    style="width: 45px; height: 35px; border: 0px solid #666666;"></td>
                                              <?php $imgshownumtd++;if($imgshownumtd%6==0) echo "</tr><tr>"?>
                                              <?php endforeach;?>
                                              <?php endif;?>
                                                <?php $i=0;foreach ($variationpic as $pic):?>
                                                <td id="t_c_vv4-42_1" width="16%"><img class="picclass"
                                                    onclick="$('.picclass').css('border-width','0px');$(this).css('border-width','3px');imgmain=$(this).attr('src');<?php foreach ($variation->Variation[$i]->VariationSpecifics->NameValueList as $select):?>$('#Attributessel_<?php echo $select->Name;?>').val('<?php echo $select->Value;?>');<?php endforeach;?>Attributeschan('<?=$i?>');"
                                                    onmouseover="$('#mainimg').attr('src',$(this).attr('src'));"
                                                    onmouseout="$('#mainimg').attr('src',imgmain);"
                                                    src="<?=$pic->VariationSpecificPictureSet->PictureURL[0] ?>"
                                                    alt="" id="pic<?=$i; ?>"
                                                    style="border: 0px solid #666666; width: 45px; height: 35px;"></td>
                                                <?php $imgshownumtd++;if($imgshownumtd%6==0) echo "</tr><tr>"?>
                                                <?php $i++;endforeach;}?>
                                            </tr>
                                        </tbody>
                                    </table>
                                    </div>
                                    </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                        <div><img src="http://q.ebaystatic.com/aw/pics/s.gif" height="1"
                            width="302px" alt=""></div>
                        </div>



                        <div class="vi-pbh">
                        <center><a class="vi-slt" rel="nofollow" href="javascript:"><b></b>Sell
                        one like this</a></center>
                        </div>
                        </td>
                        <td id="isclmn" class="isumv1_5-cell">
                        <form name="v4-26" id="v4-26" method="post" class="vi-is1-s4"
                            action="">
                        <table class="vi-is1" border="0" cellpadding="0" cellspacing="0">
                            <tbody>
                                <tr>
                                    <td colspan="4">
                                    <div><!--刊登标题--> <b id="mainContent">
                                    <h1 itemprop="name" class="vi-is1-titleH1"><?php echo str_replace('"','&quot;',$data['itemtitle'])?></h1>
                                    </b>

                                    <div class="vi-is1-pt5">
                                    <div id="fbifr" style="display: none; height: 25px"><!--                                    <iframe-->
                                    <!--                                        class="vi-pla-vAm vi-is1-cls vi-is1-pt5"--> <!--                                        title="Facebook like - opens in a new window or tab"-->
                                    <!--                                        allowtransparency="true"--> <!--                                        style="border: medium none; overflow: hidden; width: 90px; height: 25px;"-->
                                    <!--                                        src="http://www.facebook.com/plugins/like.php?href=http://cgi.sandbox.ebay.com/-/110094544830&amp;show_faces=false&amp;width=90&amp;action=like&amp;height=25&amp;colorscheme=light&amp;layout=button_count&amp;font=arial"-->
                                    <!--                                        frameborder="0" scrolling="no"></iframe>--></div>
                                    </div>
                                    </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" height="10"></td>
                                </tr>
                                <tr>
                                    <th class="vi-is1-lbl">Item condition:</th>
                                    <td colspan="3" class="vi-is1-clr"><span
                                        class="vi-is1-condText">New with tags</span></td>
                                </tr>
                                <tr>
                                    <td colspan="4" height="10"></td>
                                </tr>
                                <tr>
                                    <th class="vi-is1-lbl"><?php if($data['listingtype']=='Chinese'){echo 'Time left';}else{echo 'Ended';} ?>:</th>
                                    <td colspan="3" class="vi-is1-clr"><span class="vi-is1-dt">
                                    <?php 
                                         if($listdate_all[$data['listingduration']]=='GTC'){
                                            echo 'Without limiting the time';
                                         }
                                         else
                                         { 
                                            if($data['listingtype']=='Chinese'){
                                                echo $listdate_all[$data['listingduration']]."d (".date(" M d Y H:i:s",(time()+(int)$listdate_all[$data['listingduration']]*86400)).")";
                                            }
                                            else
                                            {
                                                echo date(" M d Y H:i:s",(time()+$listdate_all[$data['listingduration']]*86400));
                                            } 
                                         }
                                         ?></span></td>
                                </tr>
                                <tr>
                                    <td colspan="4" height="10"></td>
                                </tr>
                                <?php
                                  $selltype=array('Chinese'=>'拍卖','FixedPriceItem'=>'一口价');
                                  if($data['listingtype']=='Chinese'):?>
                                <tr>
                                    <th class="vi-is1-lbl">Bid history:</th>
                                    <td colspan="3" class="vi-is1-clr">
                                    <div><span class="vi-is1-s6"><span><a href="javascript:"
                                        rel="nofollow"><span id="v4-27">0</span> <span>bids</span></a></span></span><span
                                        id="v4-28" class="vi-is1-tet vi-is1-rf vi-is1-dspl">[<a
                                        href="javascript:">Refresh&nbsp;bidhistory</a>]</span></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" height="10"></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="vi-is1-solid" height="10"></td>
                                </tr>
                                <tr>
                                    <th class="vi-is1-lblp vi-is1-solidBg">Starting bid:</th>
                                    <td class="vi-is1-solid vi-is1-tbll"><span><span id="v4-29"
                                        class="vi-is1-prcp">
                                        <?php echo Helper_Siteinfo::getSiteCurrency($select_site->siteid).' '.$data['startprice'] ?>
             </span></span></td>
                                    <td colspan="2" class="vi-is1-solid vi-is1-tblb"></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="vi-is1-solid" height="10"></td>
                                </tr>
                                <tr>
                                    <th class="vi-is1-lblp vi-is1-solidBg"><label for="v4-30">Your
                                    max bid:</label></th>
                                    <td class="vi-is1-solid vi-is1-tbll">
                                    <table class="vi-is1-prcp" border="0" cellpadding="0"
                                        cellspacing="0">
                                        <tbody>
                                            <tr>
                                                <td class="vi-is1-prcs vi-is1-cur"><?=Helper_Siteinfo::getSiteCurrency($select_site->siteid) ?></td>
                                                <td>
                                                <div><input size="8" maxlength="10" name="maxbid" id="v4-30"
                                                    class="vi-is1-tet vi-is1-mb" type="text"></div>
                                                </td>
                                            </tr>
                                            <tr></tr>
                                        </tbody>
                                    </table>
                                    </td>
                                    <td colspan="2" class="vi-is1-solid vi-is1-tblb">
                                    <div><b id="v4-7" class="bn-w bn-pad psb-S"><i>Place bid</i><span
                                        id="spn_v4-7" class="bn-b psb-b psb-S"><input id="but_v4-7"
                                        name="" value="Place bid" title="" type="button"><b
                                        id="txt_v4-7">Place bid</b></span></b><label class="g-hdn"
                                        for="v4-31">Place bid</label><input name="hiddenText"
                                        style="display: none;" id="v4-31" type="text"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="vi-is1-lblp vi-is1-solidBg"></th>
                                    <td colspan="3" class="vi-is1-solid"><span id="v4-32"
                                        class="vi-c-fsmt">(Enter <?=Helper_Siteinfo::getSiteCurrency($select_site->siteid).' '.$values['startprice'] ?> or more)</span></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="vi-is1-solid" height="10"></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="vi-is1-solid_bevel" height="10">
                                    <div class="vi-is1-bevel"></div>
                                    </td>
                                </tr>
                                <?php endif;?>
                                <?php if(!empty($variation)): ?>
                                <tr>
                                    <td colspan="4" class="vi-is1-solid" height="10">
                                        </td>
                                </tr>
                                <?php if (!is_array($variation->VariationSpecificsSet->NameValueList)):?>
                                    <?php $variation->VariationSpecificsSet->NameValueList=array($variation->VariationSpecificsSet->NameValueList);?>
                                <?php endif;?>
                                <?php foreach ($variation->VariationSpecificsSet->NameValueList as $k):?>
                                <tr>
                                    <th class="vi-is1-lbl vi-is1-solidBg"><label for="63"><?php echo $k->Name;?>:</label></th>
                                    <td colspan="3" class="vi-is1-solid">
                                    <div>
                                    <div><select id="Attributessel_<?php echo $k->Name;?>"
                                        onchange="changepic($(this).val(),'<?php echo $k->Name?>');"
                                        class="vi-is1-jsSelect">
                                        <option value="-1">- Select -</option>
                                        <?php if (!is_array($k->Value)):?>
                                        <?php $k->Value=array($k->Value);?>
                                        <?php endif;?>
                                        <?php $i=1; foreach ($k->Value[0] as $v_key => $v):?>
                                        <option value="<?php echo $v;?>" style="color: black;"><?php echo $v;?></option>
                                        <?php $i++;endforeach;?>

                                    </select></div>
                                    </div>
                                    <input type="hidden" id="change_<?php echo $k->Name;?>" value="">
                                    </td>
                                </tr>
                                <?php endforeach;?>
                                <?php endif;?>
                                
                                <tr>
                                    <td colspan="4" class="vi-is1-solid" height="10">
                                        </td>
                                </tr>
                                
                                <tr>
                                    <th class="vi-is1-lbl vi-is1-solidBg"><label for="v4-30qtyId">Quantity:</label></th>
                                    <td colspan="3" class="vi-is1-solid">
                                    <div>
                                    <div class="vi-is1-s11">
                                    <div id="eiv4-30qtyId" class="vi-is1-errorIcon"
                                        style="display: none;"><img
                                        src="http://q.ebaystatic.com/aw/pics/s.gif" height="1"
                                        width="1" class="vi-err-img" alt="Error icon"></div>
                                    <span id="dv4-30qtyId" class="vi-is1-dt">
                                    <div class="vi-is1-qtyDiv"><input value="1" size="4"
                                        id="v4-30qtyId" class="vi-is1-s10"></div>
                                    <span class="vi-is1-qtyDiv">
                                    <table>
                                        <tbody>
                                            <tr>
                                                <td>
                                                
                                                <script type="text/javascript">
                                                function Attributeschan(val){
                                                    <?php $quansum=empty($values['quantity'])?0:$values['quantity'];if(!empty($variation)): ?>
                                                    switch(val)
                                                    {
                                                    <?php $quansum=0; $i=0; foreach ($variationval as $v):?>

                                                    case '<?=$i ?>':
                                                         $('#attrquan').text(<?=$v->Quantity ?>);
                                                         $('#valpri').text(<?=$v->StartPrice ?>);
                                                         break;
                                                    <?php $quansum+=$v->Quantity;$i++;endforeach;?>
                                                    case '-1':
                                                        $('#attrquan').text(<?=$quansum ?>);
                                                        $('#valpri').text(<?=$values['buyitnowprice'] ?>);
                                                        break;
                                                     default: break;

                                                    }
                                                    <?php endif;?>
                                                    return true;
                                                    


                                                }
                                                </script>
                                                
                                                
                                                <span id="attrquan"><?php echo $quansum ?></span> available</td>
                                                
                                                
                                                
                                                
                                            </tr>
                                        </tbody>
                                    </table>
                                    </span></span><span></span></div>
                                    <div id="emv4-30qtyId" aria-live="assertive" role="alert"
                                        class="vi-is1-errorMsg" style="display: none;">Please enter a
                                    quantity of $quantity$ or less</div>
                                    <span id="emsv4-30qtyId" aria-live="assertive" role="alert"
                                        class="vi-is1-errorMsg" style="display: none;">Please enter a
                                    quantity of 1</span>
                                    <div id="mav4-30qtyId" aria-live="assertive" role="alert"
                                        class="vi-is1-errorMsg" style="display: none;">Purchases are
                                    limited to $quantity$ per buyer</div>
                                    <div id="mlv4-30qtyId" aria-live="assertive" role="alert"
                                        class="vi-is1-errorMsg" style="display: none;">Please enter a
                                    quantity of $quantity$ or less</div>
                                    <div id="cev4-30qtyId" aria-live="assertive" role="alert"
                                        class="vi-is1-errorMsg" style="display: none;">Please enter a
                                    lower number</div>
                                    <div id="atv4-30qtyId" aria-live="assertive" role="alert"
                                        class="vi-is1-errorMsg" style="display: none;">Please enter
                                    quantity of 1 or more</div>
                                    </div>
                                    </td>
                                </tr>


                                <tr>
                                    <th class="vi-is1-lblp vi-is1-solidBg">Price:</th>
                                    <td class="vi-is1-solid vi-is1-tbll"><span class="vi-is1-prcp"
                                        itemprop="offers" itemscope="itemscope"
                                        itemtype="http://schema.org/Offer"><span id="v4-33"
                                        itemprop="price"><?php echo Helper_Siteinfo::getSiteCurrency($select_site->siteid).' '?></span><span id="valpri"><?=$values['buyitnowprice'] ?></span><span
                                        itemprop="availability" content="http://schema.org/OnlineOnly"></span><span
                                        itemprop="priceCurrency" content="USD"></span></span></td>
                                    <td colspan="2" class="vi-is1-solid vi-is1-tblb">
                                    <div style="display: <?php if($values['listingtype']!='Chinese') echo 'none';?>;" ><b id="v4-26binLnk" class="bn-w bn-pad psb-S"><i>Buy It
                                    Now</i><span id="spn_v4-26binLnk" class="bn-b psb-b psb-S"><a
                                        id="but_v4-26binLnk" href="javascript:" title="">Buy It Now</a><b
                                        style="display: none;" id="txt_v4-26binLnk">Buy It Now</b></span></b></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="vi-is1-solid" height="10"></td>
                                </tr>
                                <tr>
                                    <th class="vi-is1-lblp vi-is1-solidBg"></th>
                                    <td class="vi-is1-solid vi-is1-tbll"></td>
                                    <td colspan="2" class="vi-is1-solid vi-is1-tblb">
                                    <div style="display: <?php if($values['listingtype']!='Chinese') echo 'none';?>;"><b id="v4-27atcLnk" class="bn-w bn-pad psb-S"><i>Add to
                                    cart</i><span id="spn_v4-27atcLnk" class="bn-b psb-bo psb-S"><a
                                        id="but_v4-27atcLnk" href="javascript:" title="">Add to cart<span
                                        class="act-icon">&nbsp;&nbsp;&nbsp;&nbsp;</span></a><b
                                        style="display: none;" id="txt_v4-27atcLnk">Add to cart</b></span></b></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="vi-is1-solid" height="10"></td>
                                </tr>
                                <tr id="watchItemMiddleRow">
                                    <td class="vi-is1-solid vi-is1-lbl vi-is1-pdlt" colspan="2">
                                    <div id="-97_wshdv" class="ul-dd g-xs" style="float: right">
                                    <div id="-97_wsh" class="vi-is1-wtl" style="cursor: pointer"><a
                                        class="vi-is1-wshAnc" style="text-decoration: none;"><span
                                        class="vi-is1-strImg"></span><span id="-97_wshl"
                                        class="vi-is1-atlTxt">Add to Wish list</span></a></div>
                                    </div>
                                    </td>
                                    <td class="vi-is1-solid vi-is1-tblb">
                                    <div>
                                    <div>
                                    <div id="dd_addToList" class="ul-dd g-xs">
                                    <div id="ddv_addToList"><span id="ttlsp_addToList"
                                        class="ul-tl"><a href="javascript:" id="-99_ttl_addToList"
                                        sw="true" w="t" i="-99" n="Watch list">Add to Watch list</a></span><span
                                        class="ul-di"><a href="javascript:" id="img_addToList"></a></span></div>
                                    <div id="pnl_addToList" style="display: none;" class="ul-pn"><a
                                        href="javascript:" id="s_addToList" class="g-hdn">Start of
                                    panel</a>
                                    <ul id="ul_addToList" class="ul-it">
                                        <li><a href="javascript:" id="-99_ita_addToList" sw="true"
                                            w="t" i="-99" n="Watch list">Add to Watch list</a></li>
                                        <li><a href="javascript:" id="-97_ita_addToList" d="t" i="-97"
                                            wsh="t" n="Wish list">Add to Wish list</a></li>
                                    </ul>
                                    <div class="ul-sp"></div>
                                    <div id="ftdv_addToList"><a ds="false" class="ul-ft"
                                        href="javascript:" id="s_ita_addToList" nw="true" i="s"
                                        n="addNew">Sign in for more lists</a></div>
                                    <a href="javascript:" id="e_addToList" class="g-hdn">End of
                                    panel</a></div>
                                    </div>
                                    <div id="nLstOlyOly_Outer" class="g-hdn"
                                        style="visibility: hidden; width: 330px">
                                    <div id="cnnLstOly">
                                    <div>
                                    <div>
                                    <div class="al-ttl"><label for="nLstTxt">Add to a new list</label></div>
                                    <div class="al-te" id="al_me"><b class="al-ei"></b><b
                                        class="al-et" id="al_et">Please enter a valid name</b><span
                                        id="al_te" style="width: 90%"></span></div>
                                    <input id="nLstTxt" name="nLstTxt" size="40"
                                        value="Type a new list name here" type="text">
                                    <div class="al-it">(Separate multiple list names with a comma.)</div>
                                    <div class="al-be" id="al_be"></div>
                                    <div class="al-br">
                                    <div class="al-fl"><input name="ad_btn" id="ad_btn" value="Add"
                                        type="button"><span class="al-c"><a href="javascript:"
                                        id="v4-34">Cancel</a></span></div>
                                    </div>
                                    </div>
                                    </div>
                                    <a id="nLstOly_stA" href="javascript:" class="g-hdn">Start of
                                    Layer</a><a id="nLstOly_enA" href="javascript:" class="g-hdn">End
                                    of Layer</a></div>
                                    </div>
                                    </div>
                                    </div>
                                    </td>
                                </tr>
                                <tr id="">
                                    <td class="vi-is1-solid"></td>
                                    <td colspan="3" class="vi-is1-solid">
                                    <div class="vi-is1-stMsg" id="statusmsg" role="alert"
                                        aria-live="assertive"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="vi-is1-solid" height="10"></td>
                                </tr>
                                <tr id="sllrOffrRowId" style="display: none;">
                                    <td colspan="4">
                                    <div>
                                    <div class="vi-is1-sllrOffr"><span><a href="#config">Special
                                    offer available</a> on this and additional items!</span></div>
                                    </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" height="10"></td>
                                </tr>
                                <tr>
                                    <th class="vi-is1-lbl">Shipping:</th>
                                    <td colspan="3" class="vi-is1-clr"><span id="fshippingCost"
                                        class="vi-is1-sh-srvcCost vi-is1-hideElem vi-is1-showElem">
                                        <?php 
                                      if(!empty($values['shippingdetails']['ShippingServiceOptions'])){
                                        foreach ($values['shippingdetails']['ShippingServiceOptions'] as $v) {
                                            if(empty($v['ShippingService']))continue; 
                                            $servicecost=(float)($v['ShippingServiceCost']); 
                                            echo empty($servicecost)?'Free shipping':Helper_Siteinfo::getSiteCurrency($select_site->siteid).' '.$servicecost; 
                                            break;  
                                        }
                                      
                                      } 
                                      ?>
                                        </span><span><span> </span></span><span class="sh-nowrap"><a
                                        href="javascript:" id="changeLocLink"
                                        class="vi-tl vi-is1-shpl vi-c-fsmt vi-is1-hideDisc"><span>See
                                    more services</span>&nbsp;<span class="vi-pla-sI vi-pla-iD"></span></a></span><input
                                        id="chngLocPnlJSId" value="Js-chngLoc" type="hidden"><b>&nbsp;</b><span
                                        class="sh-nowrap"><wbr>
                                    <a href="javascript:" id="seeDcnt"
                                        class="vi-tl vi-is1-shpl vi-c-fsmt vi-is1-hideDisc"><span>See
                                    <b class="g-hdn">shipping</b> discounts</span>&nbsp;<span
                                        class="vi-pla-sI vi-pla-iD vi-is1-hideDiv"></span></a></span>
                                    <div id="disPnlOly_Outer" class="g-hdn"
                                        style="visibility: hidden; width: 175px">
                                    <div id="cndisPnl">
                                    <div>
                                    <div id="discountsMessaging">
                                    <table class="vi-is1-s9" width="99%">
                                        <tbody>
                                            <tr>
                                                <td style="word-wrap: break-word;">
                                                <div>
                                                <div id="discount_msg" class="sh-discPnl"></div>
                                                </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    </div>
                                    </div>
                                    <a id="disPnl_stA" href="javascript:" class="g-hdn">Start of
                                    Layer</a><a id="disPnl_enA" href="javascript:" class="g-hdn">End
                                    of Layer</a></div>
                                    </div>
                                    <b>&nbsp;|&nbsp;</b><span class="vi-is1-rePol"> <span
                                        class="vi-is1-wrp" id="v4-36"><a rel="nofollow"
                                        class="vi-is1-rePol"></a><a onClick="vi_tabs_1();"
                                        href="#shId">See all <b class="g-hdn">shipping</b> details</a></span></span>
                                    <div><span id="cbtViHolder" style="display: none"></span></div>
                                    </td>
                                </tr>
                                <tr id="delspcr">
                                    <td colspan="4" height="10"></td>
                                </tr>
                                <tr id="delrw">
                                    <th class="vi-is1-lbl">Delivery:</th>
                                    <td colspan="3" class="vi-is1-clr"><span style="float: none">
                                    <div>
                                    <div id="fdeliveryTime">
                                    <div>
                                    <div>
                                    <div role="alert" class="sh-TblCnt">
                                    <?php 
                                      if(is_array($values['shippingdetails']['ShippingServiceOptions'])){
                                        foreach ($values['shippingdetails']['ShippingServiceOptions'] as $v) {
                                            if(empty($v['ShippingService']))continue; 
                                            echo empty($v['ShippingService'])?'':$shippingserviceall['shippingservice'][($v['ShippingService'])]; 
                                            break;  
                                        }
                                      
                                      } 
                                      ?>
                                    
                                    <div role="alert" class="sh-DlvryDtl"></div>
                                    </div>
                                    </div>
                                    </div>
                                    </div>
                                    </div>
                                    </span></td>
                                </tr>
                                <tr>
                                    <td colspan="4" height="10"></td>
                                </tr>
                                <tr>
                                    <th class="vi-is1-lbl">Returns:</th>
                                    <td colspan="3" class="vi-is1-clr">
                                    <div class="vi-is1-s6">
                                    <table id="miyId" border="0" cellpadding="0" cellspacing="0">
                                        <tbody>
                                            <tr>
                                                <td class="vi-rpd-miyContent">No returns or exchanges, but
                                                item is covered by <a href="javascript:">eBay Buyer
                                                Protection<b class="g-hdn">- opens in a new window or tab</b></a>.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" height="10"></td>
                                </tr>
                                <tr>
                                    <td colspan="4">
                                    <div id="v4-37" class="vi-bg-cpn vi-bg-cm">
                                    <div class="vi-bg-oh vi-bg-cpn">
                                    <div class="vi-bg-crp vi-bg-sh vi-bg-logo"></div>
                                    <div class="vi-bg-bgn vi-bg-cpn vi-bg-oh vi-bg-l65 vi-bg-m65"></div>
                                    </div>
                                    <div class="vi-bg-il vi-bg-bdrh vi-bg-crp">
                                    <div class="vi-bg-txt"></div>
                                    <div class="vi-bg-stxt"></div>
                                    <span class="g-btn"><a href="javascript:">Learn more<b
                                        class="g-hdn">about eBay Buyer Protection - opens in a new
                                    window or tab</b></a></span></div>
                                    </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </form>
                        <div>
                        <div id="chngLocOly_Outer" class="g-hdn"
                            style="visibility: hidden; width: 334px">
                        <div id="cnchngLoc">
                        <div>
                        <div>
                        <div class="vi-shp">
                        <div>
                        <div id="clOverLayPanelDiv" class="sh-InpFld"><label
                            for="clcountry" class="sh-Cntry">Country:</label>
                        <div id="countryDiv">
                        <div class="sh-CntrySlctr">
<!--              运输地址选择-->
                        
                        <?php
//                       Q::control('dropdownlist','listingtype',array(
//                     "items"=>viewnation,
//                     'value'=>$values['listingtype']
//                     ))->display()
                       ?>
                        
                        
                        
                        
                        
                        </div>
                        </div>
                        <div id="clZipCodeDiv">
                        <div id="zipCodeDiv">
                        <div class="sh-TxtBxAln"><label id="clZipCodeTextDiv"
                            for="clzipCode" class="sh-TxtStyl sh-zipLeftAlign sh-hideElement">ZIP
                        Code:</label>
                        <div class="sh-zipSpanPanel">
                        <div id="clZipArrowimg" class="sh-hideElement"></div>
                        <input id="clzipCode" size="12" name="zipCode"
                            class="sh-TxtCnt sh-hideElement sh-enblBox sh-TxtCnt"
                            disabled="disabled" type="text">
                        <div class="sh-RateBtn"><input id="clGetRates" name="getRates"
                            value="Get Rates" class="sh-BtnTxt sh-BtnTxt" type="button"></div>
                        </div>
                        </div>
                        </div>
                        </div>
                        </div>
                        <div>
                        <div id="srvcDetails" class="sh-SrvcDtls">
                        <div>Service and other details:</div>
                        </div>
                        <div id="shippingServices" class="sh-DTbl">
                        <div class="dt" id="v4-35">
                        <div class="dt-dtbl">
                        <table id="v4-35_tab_0" border="0" cellpadding="0" cellspacing="0"
                            width="100%">
                            <thead>
                                <tr class="dt-tblHdr">
                                    <th scope="col" id="v4-35_tab_0_srtHCol_0"
                                        class="dt-colCnt dt-rowSeptr dt-colHdr dt-alignLft"
                                        colspan="1" width="0%">
                                    <div class="sh-SrvcHdr">Service</div>
                                    </th>
                                    <th scope="col" id="v4-35_tab_0_srtHCol_1"
                                        class="dt-colCnt dt-rowSeptr dt-colHdr dt-alignLft"
                                        colspan="1" width="0%">
                                    <div class="sh-SrvcHdr">Estimated delivery*</div>
                                    </th>
                                    <th scope="col" id="v4-35_tab_0_srtHCol_2"
                                        class="dt-colCnt dt-rowSeptr dt-colHdr dt-alignLft"
                                        colspan="1" width="0%">
                                    <div class="sh-SrvcHdr sh_Prcpad">Price</div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td scope="row" id="v4-35_tab_0_srtCol_0_0"
                                        class="dt-colCnt dt-alignLft sh-RowBrdr" rowspan="1"
                                        colspan="1">
                                    <div><span class="sh-ShipDtls">Expedited Shipping</span></div>
                                    </td>
                                    <td id="v4-35_tab_0_srtCol_0_1"
                                        class="dt-colCnt dt-alignLft sh-RowBrdr" rowspan="1"
                                        colspan="1">
                                    <div class="sh-ShipDtls">
                                    <div>Between <span id="Mon. Dec. 5 and Tue. Dec. 6">Mon. Dec. 5
                                    and Tue. Dec. 6</span></div>
                                    </div>
                                    </td>
                                    <td id="v4-35_tab_0_srtCol_0_2"
                                        class="dt-colCnt dt-alignLft sh-RowBrdr" rowspan="1"
                                        colspan="1">
                                    <div class="sh-ShipDtls sh_Prcpad">$10.00</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                        </div>
                        </div>
                        <div id="instrTextPanel" class="sh-instrText sh-transitTime">*Estimated
                        delivery dates include seller's handling time, and will depend on
                        shipping service selected and receipt of cleared payment. Delivery
                        times may vary, especially during peak periods.</div>
                        </div>
                        </div>
                        </div>
                        </div>
                        </div>
                        <a id="chngLoc_stA" href="javascript:" class="g-hdn">Start of
                        Layer</a><a id="chngLoc_enA" href="javascript:" class="g-hdn">End
                        of Layer</a></div>
                        </div>
                        </div>
                        <div id="hldolpOly_Outer" class="g-hdn"
                            style="visibility: hidden; width: 250px">
                        <div id="cnhldolp">
                        <div>
                        <div id="hldolpcnt"><a href="javascript:">Estimated delivery dates
                        <b class="g-hdn">- opens in a new window or tab</b></a> include
                        seller's handling time, and will depend on shipping service
                        selected and receipt of <a href="javascript:">cleared payment<b
                            class="g-hdn">- opens in a new window or tab</b></a>. Delivery
                        times may vary, especially during peak periods.</div>
                        </div>
                        <a id="hldolp_stA" href="javascript:" class="g-hdn">Start of Layer</a><a
                            id="hldolp_enA" href="javascript:" class="g-hdn">End of Layer</a></div>
                        </div>
                        <div id="v4-38Oly_Outer" class="g-hdn"
                            style="visibility: hidden; width: 250px">
                        <div id="cnv4-38">
                        <div>A reserve price is the minimum price the seller will accept.
                        This price is hidden from bidders. To win, a bidder must have the
                        highest bid and have met or exceeded the reserve price.</div>
                        <a id="v4-38_stA" href="javascript:" class="g-hdn">Start of Layer</a><a
                            id="v4-38_enA" href="javascript:" class="g-hdn">End of Layer</a></div>
                        </div>
                        <div id="viCbtBelowThreShipPanelOly_Outer" class="g-hdn"
                            style="visibility: hidden; width: 320px">
                        <div id="cnviCbtBelowThreShipPanel">
                        <div>
                        <div><b>International Shipping</b> - items may be subject to
                        customs processing depending on the item's declared value.<b
                            class="vi-cbt-empt-line"></b>Sellers set the item's declared
                        value and must comply with customs declaration laws.<b
                            class="vi-cbt-empt-line"></b>Visit the eBay's page on <a
                            href="javascript:">international trade here<b class="g-hdn">-
                        opens in a new window or tab</b></a>.</div>
                        </div>
                        <a id="viCbtBelowThreShipPanel_stA" href="javascript:"
                            class="g-hdn">Start of Layer</a><a
                            id="viCbtBelowThreShipPanel_enA" href="javascript:" class="g-hdn">End
                        of Layer</a></div>
                        </div>
                        <div id="viCbtAboveThreShipPanelOly_Outer" class="g-hdn"
                            style="visibility: hidden; width: 320px">
                        <div id="cnviCbtAboveThreShipPanel">
                        <div>
                        <div><b>International Shipping</b> - items may be subject to
                        customs processing depending on the item's declared value.<b
                            class="vi-cbt-empt-line"></b>Sellers set the item's declared
                        value and must comply with customs declaration laws.<b
                            class="vi-cbt-empt-line"></b><span class="vi-cbt-info-icon"></span><span
                            style="display: inline-block; width: 92%;"><b>As the buyer, you
                        should be aware of possible:</b> <b style="display: block"></b>- <b>delays</b>
                        from customs inspection. <b style="display: block"></b>- <b>import
                        duties</b> and taxes which buyers must pay.<b
                            style="display: block"></b>- <b>brokerage fees</b> payable at the
                        point of delivery.</span><b style="display: block"></b>Your
                        country's customs office can offer more details, or visit the
                        eBay's page on <a href="javascript:">international trade<b
                            class="g-hdn">- opens in a new window or tab</b></a>.</div>
                        </div>
                        <a id="viCbtAboveThreShipPanel_stA" href="javascript:"
                            class="g-hdn">Start of Layer</a><a
                            id="viCbtAboveThreShipPanel_enA" href="javascript:" class="g-hdn">End
                        of Layer</a></div>
                        </div>
                        <div>
                        <div class="vi-is1-s1"></div>
                        </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
            <div>
            <div id="jit_cmpOly_Outer" class="g-hdn"
                style="visibility: hidden; width: 220px; display: none;"></div>
            </div>
            </td>
            <td colspan="1" rowspan="1" id="vi-tTblS" class="vi-tTblS">&nbsp;</td>
            <td colspan="1" rowspan="1" id="vi-tTblC2" class="vi-tTblC2_0">
            <div>
            <div>
            <div class="cr-w cr-bt c-gy-bdr">
            <div class="cr-cnt">
            <div>
            <table class="s-content" cellpadding="0" cellspacing="0">
                <tbody>
                    <tr>
                        <td>
                        <h2 class="sit">Seller info</h2>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <div class="s-details">
                        <div class="mbg"><a title="Member id testuser" href="javascript:"><b
                            class="g-hdn">Member id </b><b><span class="mbg-nw"><?=$values['selleruserid'] ?></span></b></a>
                        <span class="mbg-l"> ( <a class="mbg-fb"
                            title="Feedback Score Of 0" href="javascript:"><b class="g-hdn">Feedback
                        Score Of</b> 0</a> ) </span> <span class="mbg-l"></span></div>
                        <br>
                        </div>
                        <div class="sRlBor"></div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <div class="bdg sl">
                        <div class="s-f-da"><a href="javascript:" rel="nofollow">Save this
                        seller</a></div>
                        <div class="s-f-da"><span class="s-f-da"><a href="javascript:">See
                        other items <b class="g-hdn">from this seller</b></a></span></div>
                        </div>
                        </td>
                    </tr>
                    <tr></tr>
                    <tr>
                        <td>
                        <div class="s-gray"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
            </div>
            </div>
            <div class="spc1"></div>
            </div>
            </div>
            <div>
            <div class="c-gy-bdr cr-brd cr-bt">
            <div><span id="ec_span" class="vi-pla-ec-span-down"><a id="ii_arr"
                href="javascript:" class="vi-pla-sI vi-pla-bD"></a></span>
            <h3 id="ec_title" class="eciIt">Other item info</h3>
            </div>
            <div id="ii_lyr">
            <div>
            <div class="z_b">
            <table class="sp1" summary="Other item info" cellpadding="3">
                <tbody>
                    <tr>
                        <td class="inf_lab" align="right" valign="top" width="1%">Item
                        number:</td>
                        <td itemprop="productID" valign="top"></td>
                    </tr>
                    <tr>
                        <td class="inf_lab" align="right" valign="top">Item location:</td>
                        <td valign="top">  <?=$data['location']?> , <?=$locationarr[$data['country']] ?></td>
                    </tr>
                    <tr>
                        <td class="inf_lab" align="right" valign="top">Ships to:</td>
                        <td valign="top">
                         <?php
                         $shipstostr='';
                         if(!empty($viewshipsto))
                         {
                            $shipsto=$select_site['site'];
                            if(is_array($viewshipsto))
                            {
                                foreach ($viewshipsto as $v) {
                                    $shipsto.=(" , ".$v);
                                }
                            }
                            else
                            {
                                $shipsto=$viewshipsto;
                            }
                                $shipstostr=$shipsto;

                         }
                         else 
                         {
                             $shipstostr=$select_site['site'];
                         }
                         echo $shipstostr;
                         
                         
                         
                         ?>
                         </td>
                    </tr>
                    <tr>
                        <td class="inf_lab" align="right" valign="top">Payments:</td>
                        <td valign="top">
                        <div>
                        <div>
                        <div class="on_pay">
                        <div id="payDet1" style="color: #000;">Paypal<span> <span
                            class="g-nav pyOp"><a rel="nofollow"></a><a id="a_payId"
                            href="#payId" onClick="vi_tabs_1();">See <b class="g-hdn">payment</b>
                        details</a></span></span></div>
                        </div>
                        </div>
                        </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
            </div>
            </div>
            </div>
            </div>
            <div class="vi-title"><a href="javascript:" rel="nofollow"
                class="vi-pla-vAb"><span></span>Print <b class="g-hdn">this item -
            opens in a new window or tab</b></a><span class="vi-pla-dl g-hlp">|</span><a
                href="javascript:" rel="nofollow" class="vi-pla-vAb vi-pla-nw"><span></span>Report
            item</a></div>
            <div></div>
            </td>
        </tr>
    </tbody>
</table>
<div
    style="left: 0; top: 0; width: 100%; height: 132px; position: absolute; text-align: center; font-size: 120px; color: #4F4F4F; z-index: 1000; background-color: #DFDFDF; opacity: 0.7; filter: Alpha(Opacity =         70)">Preview</div>
<div id="vi-content" class="vi-mdtt" style="clear: both;">
<div id="VisualPreviewContent"></div>
<div class="vi-cmb" id="rtm_html_1595" style="height: 100%; width: 100%"></div>
<div id="vi-desc"></div>
<div class="vi-cd">
<div id="vi_tabs">
<div>
<div class="tb-tw tb-gr tb-rrsc">
<table id="vi_tabs_wrp" class="tb tb-nw" cellpadding="0" cellspacing="0"
    width="100%">
    <tbody>
        <tr role="tablist">
            <td class="tb-act" id="vi_tabs_0_td" width="1"><a role="tab"
                class="tb-a" href="javascript:;" onClick="vi_tabs_0();"
                id="vi_tabs_0"><span class="tb-txt">
            <h2 class="vi-tab-hdr g-m g-m0">Description</h2>
            </span></a></td>
            <td id="vi_tabs_1_td" width="1"><a role="tab"
            	class="tb-a" href="javascript:;" onClick="vi_tabs_1();"
                id="vi_tabs_1"><span class="tb-txt">
            <h2 class="vi-tab-hdr g-m g-m0">Shipping and payments</h2>
            </span></a></td>
            <td class="tb-rrs">
            <div class="tb-rs">
            <div class="vi-pla-c1 vi-pla-mr10"><span
                class="vi-pla-shr vi-pla-p0 g-dft"><span class="vi-pla-lbc">Share: <b
                class="g-hdn">this item</b></span><a
                title="Email to a friend - opens in a new window or tab"
                href="javascript:" class="vi-pla-sI vi-pla-iE2"></a>&nbsp;<a
                title="Share on Facebook - opens in a new window or tab"
                href="javascript:" class="vi-pla-sI vi-pla-iF"></a>&nbsp;<a
                title="Share on Twitter - opens in a new window or tab"
                href="javascript:" class="vi-pla-sI vi-pla-iT"></a>&nbsp;</span></div>
            </div>
            </td>
        </tr>
    </tbody>
</table>
<input id="vi_tabs_hid" value="0" type="hidden"></div>
<div class="tb-cw">
<div id="vi_tabs_0_cnt" class="tb-cntOn" role="tabpanel">
<div>
<div class="vi-cd"><span style="float: left;" class="vi-br">Seller
assumes all responsibility for this listing.</span></div>
<table width="100%">
    <tbody>
        <tr>
            <td class="storeDescTd" valign="top">
            <div>
            <div id="ngvi_desc_div" class="d-pad">
            <div>
            <div><!-- begin -->
            <table align="center" sytle="border-spacing:0px;width:100%">
                <tbody>
                    <tr>
                        <td>
                        <div id="EBdescription" style="width:1024px;">
                        <?php echo MubanHelper::buildDescription($data['selleruserid'],$data,$data['itemtitle'],$data['itemdescription'],$data['itemdescription_listing']);?>
                </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <!---  end  --></div>
            </div>
            
            </td>
        </tr>
    </tbody>
</table>
<div align="center" >
<?php if ($data['hitcounter']=='BasicStyle') echo '<a style="padding:3px;;background: black;color:#5F8F8F;text-decoration: none;">BasicStyle</a>'; ?>
<?php if ($data['hitcounter']=='RetroStyle') echo '<a style="padding:3px;background: black;color:lime;text-decoration: none;">RetroStyle</a>'; ?>
</div>


<div>
<div>
<div class="vi-qa-main_qa">
<div class="pnltbl">
<div id="v4-46">
<table class="r3 c gy-br" cellpadding="0" cellspacing="0">
    <thead id="v4-46h" class="gy">
        <tr>
            <td>
            <div class="r3_hm"
                style="border-width: 1px 1px 0; padding: 0; height: 5px; font-size: 0; overflow: hidden;"></div>
            </td>
        </tr>
        <tr id="v4-46_h">
            <td class="r3_hm" id="v4-46_hm_0">
            <h4 class="vi-qa-m0 vi-ds2-subt">Questions and answers about this
            item</h4>
            </td>
        </tr>
    </thead>
    <tbody>
        <tr id="v4-46_c">
            <td id="v4-46cm" class="r3_c c-sgf">
            <div class="r3_cm po" id="v4-46_ct">
            <div>
            <div class="dt" id="v4-47">
            <div class="dt-dtbl">
            <table id="v4-47_tab_0" border="0" cellpadding="0" cellspacing="0"
                width="100%">
                <tbody>
                    <tr>
                        <td class="dt-spTd dt-bgColorTrans" width="1%">&nbsp;</td>
                        <td scope="row" id="v4-47_tab_0_srtCol_0_0"
                            class="dt-colCnt dt-alignLft" rowspan="1" colspan="1"><span>No
                        questions or answers have been posted about this item.</span></td>
                        <td class="dt-spTd dt-bgColorTrans" width="1%">&nbsp;</td>
                    </tr>
                </tbody>
            </table>
            </div>
            </div>
            </div>
            </div>
            <div class="r3_fm r3_s" id="v4-46_f">
            <div class="vi-qa-asq-brdr">
            <div class="vi-qa-ngvi-qa-ask"><span class="vi-qa-qa-ask-span"><a
                href="javascript:" class="al">Ask a question</a></span></div>
            </div>
            </div>
            <div class="r3_hm"
                style="border-width: 0pt 0px 1px; padding: 0px; height: 4px; font-size: 0; overflow: hidden"></div>
            </td>
        </tr>
    </tbody>
</table>
</div>
</div>
</div>
</div>
<div></div>
</div>
</div>
</div>
<div id="vi_tabs_1_cnt" class="tb-cntOff" role="tabpanel">
<div><span class="vi-br">Seller assumes all responsibility for this
listing.</span>
<div class="vi-shp">
<div class="cr-w cr-bt c-gy-bdr">
<div class="cr-cnt">
<div>
<div class="shippingSection_BottomVI vi-shp" id="shipNHadling">
<div id="shId">
<h3 class="g-m0 head vi-ds2-subt"
    style="font-size: 16px; font-weight: bold; color: #333">Shipping and
handling</h3>
</div>
<div>
<div>
<div id="discounts"></div>
<div class="sh-ItemLoc">Item location:  <?=$data['location']?> , <?=$locationarr[$data['country']] ?></div>
<div class="sh-ShipSecTop">
<div class="sh-ShipTo">
<div class="sh-ShipLoc">Shipping to: <?=$shipstostr ?></div>
</div>
<div class="sh-CalcShip">
<div class="sh_calcShipPad">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tbody>
        <tr>
            <td nowrap="nowrap" width="40%">
            <div id="qtyArrowImg" class="sh-hideArrow"></div>
            <div class="sh-InlCnt"><label for="shCountry" class="sh-ShipDtl">Change
            country:</label>
            <div id="shipToCountry" class="sh-InlCnt">
            <?php echo Html::dropDownList('viewnation', $select_site->site, array_merge(array($select_site->site=>$select_site->site),empty($viewnation)?array():$viewnation))?>
            </div>
            </div>
            </td>
            <td width="60%">
            <div id="zipArrowImg" class="sh-hideArrow"></div>
            <div id="shZipCode" aria-live="assertive" class="sh-InlCnt"><span><label
                id="shZipCodeTextDiv" for="shPostalCode"
                class="sh-ShipDtl sh-hideElement">ZIP Code:</label>
            <div class="sh-ZipAln"><input id="shPostalCode" size="12"
                name="zipCode" class="sh-TxtCnt sh-hideElement sh-enblBox"
                disabled="disabled" type="text"></div>
            </span></div>
            <div class="sh-RateBtn sh-InlCnt"><input value="Get Rates"
                id="getrates" name="getRates" class=""
                type="button"></div>
              
            <?php if(is_array($data['shippingdetails']['InternationalShippingServiceOption'])):?>
            <script type="text/javascript">
            var inshipping=<?php echo json_encode($data['shippingdetails']['InternationalShippingServiceOption']) ?>;
            var shipping=<?php echo json_encode($shippingarea) ?>;
            var ishippingservice=<?php echo json_encode($shippingserviceall['ishippingservice']) ?>;
            $('#getrates').click(function (){
                var shippnation=$('#viewnation :selected');
                if(shippnation.val()=='<?php echo $select_site->site ?>')
                {
                    $('#inshippingtbody').hide();
                    $('#shippingtbody').show();
                    return;
                }
                $.post('<?=Url::to(['item/muban/ajaxgetarea'])?>',"nation="+shippnation.val(),function (data){showshipping(data,shippnation);},'text');

                });
            </script>
            <script type="text/javascript">
            //去除物流号码并传递给函数makeshipping完成show();
            function showshipping(area,shippnation){
                nation=shippnation.val();
//              alert(area);
//              alert(nation);
//                alert(shipping['nationarray'][nation].length);
//                alert(shipping['areaarray'][area]);
                //国家所包含的物流
                var shippnumall=new Array();
                //在国家里找
                if(typeof(shipping['nationarray'][nation])=='object')
                {
                 for(i=0;i<shipping['nationarray'][nation].length;i++)
                 {
                     var isexist=false;
                     for(j=0;j<shippnumall.length;j++)
                     {
                         //去重复
                         if(shippnumall[j]==shipping['nationarray'][nation][i])
                         {
                            isexist=true;
                            break;
                         }
                     }
                     if(isexist)continue;
                     shippnumall.push(shipping['nationarray'][nation][i]);
                    
                 }
                }
                
                //在区域里找
                if(typeof(shipping['areaarray'][area])=='object')
                {
                 for(i=0;i<shipping['areaarray'][area].length;i++)
                 {
                     var isexist=false;
                     for(j=0;j<shippnumall.length;j++)
                     {
                        //去重复
                         if(shippnumall[j]==shipping['areaarray'][area][i])
                         {
                            isexist=true;
                            break;
                         } 
                     }
                     if(isexist)continue;
                     shippnumall.push(shipping['areaarray'][area][i]);
                    
                 }
                }
                //把属于全世界的取出来
                if(typeof(shipping['areaarray']['Worldwide'])=='object')
                {
                 for(i=0;i<shipping['areaarray']['Worldwide'].length;i++)
                 {
                     var isexist=false;
                     for(j=0;j<shippnumall.length;j++)
                     {
                        //去重复
                         if(shippnumall[j]==shipping['areaarray']['Worldwide'][i])
                         {
                            isexist=true;
                            break;
                         } 
                     }
                     if(isexist)continue;
                     shippnumall.push(shipping['areaarray']['Worldwide'][i]);
                    
                 }
                }
                
                
                makeshipping(shippnumall,shippnation.text());
            }
            function makeshipping(shippnumall,shiptext){
                //生成html代码
//                alert(shippnumall);
                var $inshippinghtml='';
                for(i=0;i<shippnumall.length;i++)
                {
                    $inshippinghtml+='<tr><td class=\"dt-colCnt dt-alignLft\"><div class=\"sh-TblCnt\">';
                    $inshippinghtml+=parseFloat(inshipping[shippnumall[i]]['ShippingServiceCost'])==0?'Free shipping':inshipping[shippnumall[i]]['ShippingServiceCost'];
                    $inshippinghtml+='</div></td><td class=\"dt-colCnt dt-alignLft\"><div class=\"sh-TblCnt\">';
                    $inshippinghtml+=shiptext;
                    $inshippinghtml+='</div></td><td class=\"dt-colCnt dt-alignLft\"><div class=\"sh-TblCnt\">';
                    $inshippinghtml+=inshipping[shippnumall[i]]['ShippingService'];
                    $inshippinghtml+='</div></td><td class=\"dt-colCnt dt-alignLft\"><div class=\"sh-TblCnt\">';
                    $inshippinghtml+=ishippingservice[inshipping[shippnumall[i]]['ShippingService']];
                    $inshippinghtml+='</div></td></tr>';
                }
                
                $('#shippingtbody').hide();
                $('#inshippingtbody').html($inshippinghtml).show();
//                alert($inshippinghtml);

            }
            
            </script>
            <?php endif;?>
            </td>
        </tr>
        <tr>
            <td width="40%">
            <div id="shQtyError" class="sh-hideElement"></div>
            </td>
            <td valign="top" width="60%">
            <div id="shZipError" class="sh-hideElement"></div>
            </td>
        </tr>
    </tbody>
</table>
<div><input id="hiddenCountry" name="hiddenCountry" type="hidden"><input
    id="hiddenZipCode" name="hiddenZipCode" type="hidden"></div>
</div>
</div>
</div>
<div class="sh_shipTblAln_">&nbsp;</div>
<div id="shippingSection" aria-live="assertive" class="sh-DTbl">
<div class="dt" id="v4-48">
<div class="dt-dtbl">
<table id="v4-48_tab_0" border="0" cellpadding="0" cellspacing="0"
    width="100%">
    <thead>
        <tr class="dt-tblHdr">
            <th scope="col" id="v4-48_tab_0_srtHCol_0"
                class="dt-colCnt dt-rowSeptr dt-colHdr dt-alignLft" colspan="1"
                width="0%">
            <div class="sh-TblHdr-new" role="alert">Shipping and handling</div>
            </th>
            <th scope="col" id="v4-48_tab_0_srtHCol_1"
                class="dt-colCnt dt-rowSeptr dt-colHdr dt-alignLft" colspan="1"
                width="0%">
            <div class="sh-TblHdr-new" role="alert">To</div>
            </th>
            <th scope="col" id="v4-48_tab_0_srtHCol_2"
                class="dt-colCnt dt-rowSeptr dt-colHdr dt-alignLft" colspan="1"
                width="0%">
            <div class="sh-TblHdr-new" role="alert">Service</div>
            </th>
            <th scope="col" id="v4-48_tab_0_srtHCol_3"
                class="dt-colCnt dt-rowSeptr dt-colHdr dt-alignLft" colspan="1"
                width="0%">
            <div class="sh-TblHdr-new" role="alert">Estimated delivery*</div>
            </th>
        </tr>
    </thead>
    <tbody  id='inshippingtbody' style="display: none">
    </tbody>
    <tbody  id='shippingtbody'>
        <!--    境内-->
    <?php if(!empty($data['shippingdetails']['ShippingServiceOptions'])): ?>
    <?php foreach ($data['shippingdetails']['ShippingServiceOptions'] as $v) : if(empty($v['ShippingService']))continue; ?>
        <tr>
            <td scope="row" id="v4-48_tab_0_srtCol_0_0"
                class="dt-colCnt dt-alignLft" rowspan="1" colspan="1">
            <div aria-live="assertive" role="alert" class="sh-TblCnt">
            <div role="alert"><?php $servicecost=(float)($v['ShippingServiceCost']);  ?><?php echo empty($servicecost)?'Free shipping':Helper_Siteinfo::getSiteCurrency($select_site->siteid).' '.$servicecost ?></div>
            <div role="alert"></div>
            </div>
            </td>
            <td id="v4-48_tab_0_srtCol_0_1" class="dt-colCnt dt-alignLft"
                rowspan="1" colspan="1">
            <div aria-live="assertive" role="alert" class="sh-TblCnt"><?=$select_site['site'] ?></div>
            </td>
            <td id="v4-48_tab_0_srtCol_0_2" class="dt-colCnt dt-alignLft"
                rowspan="1" colspan="1">
            <div aria-live="assertive" role="alert" class="sh-TblCnt">
            <div>
            <div role="alert"><?=$v['ShippingService']  ?></div>
            </div>
            </div>
            </td>
            <td id="v4-48_tab_0_srtCol_0_3" class="dt-colCnt dt-alignLft"
                rowspan="1" colspan="1">
            <div aria-live="assertive" role="alert">
            <div>
            <div id="fdeliveryTime">
            <div>
            <div>
            <div role="alert" class="sh-TblCnt"><?php echo empty($v['ShippingService'])?'':$shippingserviceall['shippingservice'][($v['ShippingService'])] ?></span>
            <div role="alert" class="sh-DlvryDtl"></div>
            </div>
            </div>
            </div>
            </div>
            </div>
            </div>
            </td>
        </tr>
        <?php endforeach;?>
        <?php endif;?>
        
        
    </tbody>
</table>
</div>
</div>
</div>
<div id="instrTextTable" aria-live="assertive"
    class="sh_TopSptr sh-instrText sh-transitTime">* <a href="javascript:">Estimated
delivery dates <b class="g-hdn">- opens in a new window or tab</b></a>
include seller's handling time, and will depend on shipping service
selected and receipt of <a href="javascript:">cleared payment <b
    class="g-hdn">- opens in a new window or tab</b></a>. Delivery times
may vary, especially during peak periods.</div>
</div>
</div>
</div>
<div class="vi-shp">
<div id="dom_handling_timeId">
<div class="sh-DTbl">
<div class="dt" id="v4-49">
<div class="dt-dtbl">
<table id="v4-49_tab_0" border="0" cellpadding="0" cellspacing="0"
    width="100%">
    <thead>
        <tr class="dt-tblHdr">
            <th scope="col" id="v4-49_tab_0_srtHCol_0"
                class="dt-colCnt dt-rowSeptr dt-colHdr dt-alignLft" colspan="1"
                width="33%">
            <div class="sh-TblHdr-new">Domestic handling time</div>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td scope="row" id="v4-49_tab_0_srtCol_0_0"
                class="dt-colCnt dt-alignLft" rowspan="1" colspan="1">
            <div class="sh-TblCnt">Will usually ship within 2 business days of <a
                href="javascript:">receiving cleared payment<b class="g-hdn">- opens
            in a new window or tab</b></a>.<span id="shphandlingspan"></span></div>
            </td>
        </tr>
    </tbody>
</table>
</div>
</div>
</div>
<div class="sh-InpFld"></div>
<div></div>
</div>
</div>
</div>
</div>
</div>
</div>
<div>
<div class="cr-w cr-bt c-gy-bdr">
<div class="cr-cnt">
<div id="rpdId">
<h3 class="vi-rpd-rpdTitle_ vi-ds2-subt">Return policy</h3>
<table class="vi-rpd-tbllyt" border="0" cellpadding="5" cellspacing="0"
    width="100%">
    <tbody>
        <tr>
            <td class="vi-rpd-rpdContent">No returns or exchanges, but item is
            covered by <a href="javascript:">eBay Buyer Protection<b
                class="g-hdn">- opens in a new window or tab</b></a>.</td>
        </tr>
    </tbody>
</table>
</div>
</div>
</div>
</div>
<div class="vi-pd">
<div>
<div class="cr-w cr-bt c-gy-bdr">
<div class="cr-cnt">
<div id="payId" class="pay-cont">
<h3 class="pay-hdr vi-ds2-subt">Payment details</h3>
<div class="pay-data">
<div class="dt" id="v4-50">
<div class="dt-dtbl">
<table id="v4-50_tab_0" border="0" cellpadding="0" cellspacing="0"
    width="100%">
    <thead>
        <tr class="dt-tblHdr">
            <th scope="col" id="v4-50_tab_0_srtHCol_0"
                class="dt-colCnt dt-rowSeptr dt-colHdr dt-alignLft" colspan="1"
                width="33%">Payment method</th>
            <th scope="col" id="v4-50_tab_0_srtHCol_1"
                class="dt-colCnt dt-rowSeptr dt-colHdr dt-alignLft" colspan="1"
                width="33%">Preferred / Accepted</th>
            <th scope="col" id="v4-50_tab_0_srtHCol_2"
                class="dt-colCnt dt-rowSeptr dt-colHdr dt-alignLft" colspan="1"
                width="33%"><b>&nbsp;</b></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td scope="row" id="v4-50_tab_0_srtCol_0_0"
                class="dt-colCnt dt-alignLft" rowspan="1" colspan="1">
            <div id="payDet1">
            <div><img src="<?php echo $BasePath.'/css/listing/ebay/imgEcheck.gif';?>"
                alt="Credit or debit card through Paypal"
                title="Credit or debit card through Paypal"></div>
            </div>
            </td>
            <td id="v4-50_tab_0_srtCol_0_1" class="dt-colCnt dt-alignLft"
                rowspan="1" colspan="1">
            <div id="payPref1">Accepted</div>
            </td>
            <td id="v4-50_tab_0_srtCol_0_2" class="dt-colCnt dt-alignLft"
                rowspan="1" colspan="1"><b>&nbsp;</b></td>
        </tr>
    </tbody>
</table>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
<script>if (typeof(oGaugeInfo) !== 'undefined') { st = oGaugeInfo.iST; eT = new Date().getTime() - st; oGaugeInfo.sUrl = oGaugeInfo.sUrl+'&atf='+ eT;}</script><b
    style="display: none"><b id="wcItm_1">You can add ##n## more item.</b><b
    id="wcItm_2">You can add ##n## more items.</b><b id="wcItm_3">You’re
the first person to watch this item. Don’t let it get away!</b><b
    id="wcItm_4">people are watching this item. Place a bid and beat them
to buy this item!</b><b id="wcItm_5">##n## item can still be added to
your watch list.</b><b id="wcItm_6">##n## items can still be added to
your watch list.</b><b id="wcItm_7">person is watching this item. Place
a bid and improve your chances to buy this item.</b><b id="wcItm_8">(##i##
item)</b><b id="wcItm_9">(##i## items)</b></b></div>
<div id="vi-bottom">
<div>
<div class="dt-tp dt-laB"></div>
</div>
<div class="vi-btb-blinks"><span id="v4-51"><a href="javascript:"
    class="vi-btb-Lt">Back to home page</a></span><a href="javascript:"
    id="_rtop" class="vi-btb-Rt">Return to top</a></div>
<div class="vi-mcmt pvw_m_sep"></div>
<div class="vi-cmb" id="rtm_html_973" style="height: 100%; width: 100%"></div>
<div class="vi-cmb" id="rtm_html_974" style="height: 100%; width: 100%"></div>
<div>
<div style="padding-bottom: 0px; margin-top: 15px;" class="RtmStyle"><span
    class="RtmStyle1">
<div id="rtm_html_825" style="height: 115; width: 300"></div>
</span><span>
<div></div>
</span><span class="RtmStyle3">
<div id="rtm_html_829" style="height: 115; width: 300"></div>
</span></div>
</div>
<div class="vi-cmb" id="rtm_html_813" style="height: 100%; width: 940"></div>
<div>
<div class="standard-text">
<div id="internal" class="pipelinecolor"><a href="javascript:">Popular
Searches</a> | <a href="javascript:">eBay Pulse</a> | <a
    href="javascript:">eBay Reviews</a> | <a href="javascript:">eBay Stores</a>
| <a href="javascript:">Half.com</a> | <a href="javascript:">Global
Buying Hub</a> | <a href="javascript:">Austria</a> | <a
    href="javascript:">France</a> | <a href="javascript:">Germany</a> | <a
    href="javascript:">Italy</a> | <a href="javascript:">Spain</a> | <a
    href="javascript:">United Kingdom</a> | <a href="javascript:">Australia</a></div>
<div id="external" class="pipelinecolor"><a href="javascript:">Kijiji</a>
| <a href="javascript:">Paypal</a> | <a href="javascript:">ProStores</a>
| <a href="javascript:">Apartments for Rent</a> | <a href="javascript:">Shopping.com</a>
| <a href="javascript:">Skype</a> | <a href="javascript:">Tickets</a></div>
</div>
</div>
<div class="coreFooterLinks" id="glbfooter">
<div>
<div id="rtm_html_1650"></div>
<div id="rtm_html_1651"></div>
</div>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tbody>
        <tr>
            <td class="g-pipe"><img src="<?php echo $BasePath.'/css/listing/ebay/s.gif';?>"
                alt="" height="10" width="1"><br>
            <a href="javascript:" _sp="m571.l2602">About eBay</a> | <a
                href="javascript:" _sp="m571.l2616">Security Center</a> | <a
                href="javascript:" _sp="m571.l2603">Buyer Tools</a> | <a
                href="javascript:" _sp="m571.l2604">Policies</a> | <a
                href="javascript:" _sp="m571.l2605">Stores</a> | <a
                href="javascript:" _sp="m571.l2898">eBay Wish list</a> | <a
                href="javascript:" _sp="m571.l1625">Site Map</a> | <a
                href="javascript:" _sp="m571.l2606">eBay official time</a> | <a
                href="javascript:" _sp="m571.l1617">Preview new features</a> | <a
                href="javascript:" id="gh-surveyLink" target="eBaySurvey"
                _sp="m571.l2628" rel="nofollow">Tell us what you think</a>
            <form
                action=""
                id="gh-surveyForm" method="post" target="eBaySurvey"
                class="gh-hdn g-hdn"><input name="domContent"></form>
            </td>
        </tr>
        <tr>
            <td height="5"></td>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#dddddd" height="1"></td>
        </tr>
        <tr>
            <td height="10"></td>
        </tr>
        <tr class="g-hlp" valign="top">
            <td class="g-nav coreFooterLegalNotice">Copyright © 1995-2011 eBay
            Inc. All Rights Reserved. Designated trademarks and brands are the
            property of their respective owners. Use of this Web site constitutes
            acceptance of the eBay <a href="javascript:" _sp="m571.l2612">User
            Agreement</a> and <a href="javascript:" _sp="m571.l2613">Privacy
            Policy</a>.<br>
            <img src="<?php echo $BasePath.'/css/listing/ebay/s.gif';?>" alt="" height="20"></td>
        </tr>
    </tbody>
</table>
<div id="cobrandFooter"></div>
</div>

<div style="margin-top: 5px"></div>
<div class="vi-cmb" id="rtm_html_283" style="height: 1; width: 100%"></div>
<div id="tacodaVIWatching" class="z_4">
<div class="vi-cmb" id="rtm_html_280" style="height: 1; width: 100%"></div>
</div>
</div>
</div>
<div
    style="left: 0; width: 100%; height: 230px; margin-top: -230px; position: absolute; text-align: center; font-size: 200px; color: #4F4F4F; z-index: 1000; background-color: #DFDFDF; opacity: 0.7; filter: Alpha(Opacity =         70)">Preview</div>
<!--vo.rp73(0ba5237,RcmdId ViewItemNext,RlogId p4vkoaj%60btbkbel%7Dehq%60%3C%3Dsm%2Bpu56*5%60d7712-133f4e0861f-0xf3-->

<script type="text/javascript"
    src="<?php echo $BasePath.'/js/lib/jquery-1.8.3.min.js'?>"></script>
<script type="text/javascript">
function changepic(val,name){
    $('#change_'+name).val(val);
<?php if (isset($variation->Variation)):?>
<?php foreach ($variation->Variation as $key=>$value):$array=array();$array2=array();?>
<?php foreach ($value->VariationSpecifics->NameValueList as $k):?>
<?php $array[]="$('#change_".$k->Name."').val()=='".$k->Value."'";?>
<?php $array2[]="$('#change_".$k->Name."').val()=='-1'";?>
<?php $string=implode('&&',$array);?>
<?php $string2=implode('&&',$array2);?>
<?php endforeach;?>
    if(<?php echo $string;?>){
    	 $('#pic<?php echo $key;?>').trigger('mouseover').trigger('click');
    }
    if(<?php echo $string2;?>){
    	$('#t_ivv4-42_0').trigger('mouseover').trigger('click');
    }       
<?php unset($array);unset($array2);?>
<?php endforeach;?>
<?php endif;?>
};
</script>
<div class="gh-ovr" id="gbh_ovl">
<div class="gh-iovr"></div>
</div>
</body>
</html>