<?php
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
.miandanImgAclick {
    border: 1px solid #eee;
    height: 100px; 
     width: 121px; 
}
.miandanImgAclick.active-mdImg {
    border: 1px solid #73B8EE;
    -webkit-box-shadow: 0 0 8px #73B8EE;
    box-shadow: 0 0 8px #73B8EE;
}
.mTop20 {
    margin-top: 20px;
}
.carousel-inner {
    margin-left:60px;
 	min-height:450px; 
}
.imgCss {
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    padding: 1px;
    margin: auto;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    position: absolute;
}

.carousel-control.left {
    background-image: none;
    right: -36px;
    color: #000;
	width:30px;
	height:30px;
}
.glyphicon-chevron-left, .carousel-control .glyphicon-chevron-right, .carousel-control .icon-next, .carousel-control .icon-prev {
    position: absolute;
    top: 50%;
    z-index: 5;
    display: inline-block;
}
.glyphicon {
    font-family: 'Glyphicons Halflings';
    font-style: normal;
    font-weight: 400;
    line-height: 1;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
.carousel-control.right {
    background-image: none;
    right: -124px;
    color: #000;
	width:30px;
	height:30px;
}
.carousel-control {
    position: absolute;
    top: 50%;
    bottom: 0;
    left: 0;
    width: 15%;
    font-size: 20px;
    color: #fff;
    text-align: center;
    text-shadow: 0 1px 2px rgba(0,0,0,.6);
    filter: alpha(opacity=50);
    opacity: .5;
}
</style>
<div class="modal-body tab-content col-xs-12" style="width:1000px;">
			<!-- 默认区 -->
			<div class="col-xs-6 miandanBigImg_<?php echo $type;?>">
						<div id='BigImg' class="col-xs-12 p0" style="height:454px;">
							<img src="<?php echo empty($sysLabel['CarrierTemplateHighcopy'][0]['template_img'])?'':$sysLabel['CarrierTemplateHighcopy'][0]['template_img']; ?>" data="<?php echo empty($sysLabel['CarrierTemplateHighcopy'][0]['id'])?'':$sysLabel['CarrierTemplateHighcopy'][0]['id']; ?>" class="imgCss">
							<input type='hidden' value='<?php echo empty($sysLabel['CarrierTemplateHighcopy'][0]['additional_print_options'])?"":$sysLabel['CarrierTemplateHighcopy'][0]['additional_print_options']; ?>' id='addshow'>
						</div>
						<div id='BigImgSpan' class="col-xs-12 text-center p0" style="height:40px;">
							<span><?php echo empty($sysLabel['CarrierTemplateHighcopy'][0]['template_name'])?'':$sysLabel['CarrierTemplateHighcopy'][0]['template_name']; ?></span>
						</div>
			</div>
			
			<!-- 选择区 -->
			<div class="col-xs-6">
						<div id="myCarousel" class="carousel slide col-xs-12 p0 mTop20">
								    <!-- 轮播（Carousel）项目 -->
								    <div class="carousel-inner col-xs-12 p0">
								    <?php 
										$pagecount=count($sysLabel['CarrierTemplateHighcopyType'])%9==0?(count($sysLabel['CarrierTemplateHighcopyType'])/9):(intval(count($sysLabel['CarrierTemplateHighcopyType'])/9+1)); 
										$tmp=0;
										for($page=0;$page<$pagecount;$page++){
											?>
											<div class="item <?php echo $tmp===0?'active':''; ?>">
												<?php 
													foreach ($sysLabel['CarrierTemplateHighcopyType'] as $key=>$sysLabelone){
														?>
														<div class="col-xs-4 mBottom10 ">
															<div class="col-xs-12 miandanImgAclick" type="<?php echo $type; ?>" style="height:100px;width:121px;">
																<img src="<?php echo empty($sysLabelone['template_img'])?'':$sysLabelone['template_img']; ?>" data="<?php echo empty($sysLabelone['id'])?'':$sysLabelone['id']; ?>" class="imgCss">
																<input type='hidden' value='<?php echo $sysLabelone['additional_print_options'];?>' id='addshow'>
															</div>
															<div class="col-xs-12 text-center p0 mTop5" style="height:40px;">
																<span><?php echo empty($sysLabelone['template_name'])?'':$sysLabelone['template_name']; ?></span>
															</div>
														</div>
														<?php 
														$tmp++;
														unset($sysLabel['CarrierTemplateHighcopyType'][$key]);
														if($tmp%9==0)
															break;
													}
												?>
											</div>
											<?php 
										}
									?>	
									</div>
								    <!-- 轮播（Carousel）导航 -->
								    <a class="carousel-control left" href="#myCarousel" 
								        data-slide="prev">									<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
									<span class="sr-only">Previous</span>
								    </a>
								    <a class="carousel-control right" href="#myCarousel" 
								        data-slide="next">									<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
									<span class="sr-only">Next</span>
								    </a>
						
						
						
								
						

						</div>
			</div>
</div>
<div class="modal-footer col-xs-12">
					<button type="button" class="btn btn-primary">确定</button>
					<button type="button" class="iv-btn btn-default btn-sm modal-close" data-dismiss="modal" >取消</button>
</div>


<script>
$("#myCarousel").carousel('pause');

$('.miandanImgAclick').click(function(){
	var img=$(this).children('img').attr('src');
	var span=$(this).next().children().html();
	var id=$(this).children('img').attr('data');
	var addshow=$(this).children('input').attr('value');
	$('#BigImg').children('img').attr('src',img);
	$('#BigImg').children('img').attr('data',id);
	$('#BigImg').children('input').attr('value',addshow);
	$('#BigImgSpan').children().html(span);
	$('.miandanImgAclick').each(function(i){
		$(this).attr('class','col-xs-12 miandanImgAclick');
	});
	$(this).attr('class','col-xs-12 miandanImgAclick active-mdImg');
});

$('.miandanImgAclick').mouseover(function(){
	$(this).attr('style','border: 1px solid #73B8EE;-webkit-box-shadow: 0 0 8px #73B8EE;box-shadow: 0 0 8px #73B8EE;');
}).mouseout(function(){
	$(this).attr('style','');
});

</script>