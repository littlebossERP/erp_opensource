<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\models\EbayCategory;
?>
<!--------------------------
--平台与细节 (1级行 第2行)--
---------------------------->
	<!-- BEGIN subbox-title -->
	<div class="subbox-title row">
		<div class="caption col-lg-8">
			<span class="caption-subject">平台与细节</span>
		</div>
		<div class="action">
			<span></span>
		</div>
	</div><!-- end  subbox-title-->


	<div class="subbox-body form">
		<!-- BEGIN FORM -->
		<div class="form-horizontal">
			<h4 class="class-title">分类</h4>
			<!-- START-->
			<div>
				<!-- 2.1.1行  eBay站点-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="siteid">eBay站点<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?=Html::dropDownList('siteid',@$data['siteid'],$sitearr,['id'=>'siteid','class'=>'form-control'])?>
					</div>
				</div>

				<!-- 2.1.2行  刊登类型-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="listingtype">刊登类型<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?=Html::dropDownList('listingtype',@$data['listingtype'],$listingtypearr,['id'=>'listingtype','class'=>'form-control'])?>
					</div>
				</div>
				<!-- 2.1.3行  ProductID-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="product_id_tbl">ProductID<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<div class="whole-onebox">
						<?php if (isset($product['upcenabled']) && $product['upcenabled'] == 'Required'){
								$viewone=' ';$viewtwo='display:none';
								$viewthree='display:none';$viewfour='display:none';

							}elseif (isset($product['isbnenabled']) && $product['isbnenabled'] == 'Required'){
								$viewone='display:none';$viewtwo=' ';
								$viewthree='display:none';$viewfour='display:none';
							}elseif (isset($product['eanenabled']) && $product['eanenabled'] == 'Required'){
								$viewone='display:none';$viewtwo='display:none';
								$viewthree=' ';$viewfour='display:none';
							}else{
								$viewone='display:none';$viewtwo='display:none';
								$viewthree='display:none';$viewfour=' ';
							}
						?>

						<ul id="product_id_tbl">
							<li class="row" style="<?=$viewone?>">
								<label class="control-label col-lg-2">UPC</label>
								<input class="iv-input col-lg-5" name="upc" value="<?php echo $data['upc']?>"></input>
							</li>
							<li class="row" style="<?=$viewtwo?>">
								<label class="control-label col-lg-2">ISBN</label>
								<input class="iv-input col-lg-5" name="isbn" value="<?php echo $data['isbn']?>"></input>
							</li>
							<li class="row" style="<?=$viewthree?>">
								<label class="control-label col-lg-2">EAN</label>
								<input class="iv-input col-lg-5" name="ean" value="<?php echo $data['ean']?>"></input>
							</li>
							<li class="row" style="<?=$viewfour?>">
								<label class="control-label col-lg-2">EPID</label>
								<input class="iv-input col-lg-5" name="epid" value="<?php echo $data['epid']?>"></input>
							</li>
							<li class="row" style="<?=$viewfour?>">
								<label class="control-label col-lg-2">ISBN</label>
								<input class="iv-input col-lg-5" name="isbn" value="<?php echo $data['isbn']?>"></input>
							</li>
							<li class="row" style="<?=$viewfour?>">
								<label class="control-label col-lg-2">UPC</label>
								<input class="iv-input col-lg-5" name="upc" value="<?php echo $data['upc']?>"></input>
							</li>
							<li class="row" style="<?=$viewfour?>">
								<label class="control-label col-lg-2">EAN</label>
								<input class="iv-input col-lg-5" name="ean" value="<?php echo $data['ean']?>"></input>
							</li>

						</ul>
						</div>
					</div>
				</div>

				<!-- 2.1.4行  刊登分类一-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="primarycategory">刊登分类一<span class="requirefix">*</span></label>
					<div class="col-lg-8">
						<input class="category iv-input main-input3" id="primarycategory" name="primarycategory" size="25" value="<?=$data['primarycategory']?>">

						<button type="button" class="iv-btn" onclick="window.open('<?=Url::to(['/listing/ebaymuban/selectebaycategory','siteid'=>$data['siteid'],'elementid'=>'primarycategory'])?>')">选择分类</button>

						<?=Html::button('搜索',['onclick'=>'searchcategory("primary")','class'=>'iv-btn btn-search'])?>
					</div>

				</div>
					<div class="form-group">
						<!-- <label class="forcategory"> -->
							<div class="col-lg-8 col-lg-offset-3">
							<?php if(strlen($data['primarycategory'])){
								$ec=EbayCategory::findBySql('select * from ebay_category where siteid='.$data['siteid'].' AND categoryid='.$data['primarycategory'].' and leaf=1')->one();
								if (empty($ec)){
									echo "<span style='color:red;font-size:10px;'>无法查找该类目,请重新选择</font>";
									$hasPricategory=false;
								}else{
									echo EbayCategory::getPath($ec,$ec->name,$data['siteid']);
									$hasPricategory=true;
								}
							}
							?>
							</div>
						<!-- </label> -->
					</div>
				<!-- 2.1.5行  刊登分类二-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="secondarycategory" >刊登分类二<span class="requirefix">*</span></label>
					<div class="col-lg-8">
						<input class="category iv-input main-input3" id="secondarycategory" name="secondarycategory" size="25" value="<?php echo $data['secondarycategory']?>">

						<input type="button" class="iv-btn" value="选择分类" onclick="window.open('<?=Url::to(['/listing/ebaymuban/selectebaycategory','siteid'=>$data['siteid'],'elementid'=>'secondarycategory'])?>')">

						<?=Html::button('搜索',['onclick'=>'searchcategory("second")','class'=>'iv-btn btn-search'])?><br/>
					</div>

				</div>
					<div class="form-group">
						<!-- <label class="forcategory"> -->
						<div class="col-lg-8 col-lg-offset-3">
							<?php if(strlen($data['secondarycategory'])){
								$ec=EbayCategory::findBySql('select * from ebay_category where siteid='.$data['siteid'].' AND categoryid='.$data['secondarycategory'].' and leaf=1')->one();
								if (empty($ec)){
									echo "<span style='color:red;font-size:10px;'>无法查找该类目,请重新选择</font>";
								}else{
									echo EbayCategory::getPath($ec,$ec->name,$data['siteid']);
								}
							}
							?>
							</div>
						<!-- </label> -->
					</div>
				<!-- 2.1.6行  物品状况-->
				<div class="form-group">
					<?php echo $this->render('_condition',array('condition'=>$condition,'val'=>$data))?>
				</div>

				<!-- 2.1.7行  物品细节-->
				 <?php if (count($specifics)):?>
				<div class="form-group">
					<?php echo $this->render('_specific',array('specifics'=>$specifics,'val'=>$data['specific']))?>
				</div>
				<?php endif;?>
				<!-- 2.1.8行  多属性-->
				<?php if (isset($hasPricategory) && $hasPricategory===true):?>
					<div class="form-group">
						<?php echo $this->render('_variation',array('data'=>$data))?>
					</div>
				<?php endif;?>



			</div><!-- END  -->
		</div><!-- END FORM -->
	</div><!-- END SUBBOX-BODY -->




