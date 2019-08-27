<?php
use yii\helpers\Html;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderFrontHelper; 

?>
<style>
.modal-body{
	padding-left: 0px!important;
	width:700px;
}
.col-xs-6 {
    width: 50%;
}
.splitparcel .panel:last-child {
    margin-bottom: 0;
}
.panel-default {
    border-color: #ddd;
}
.panel {
    margin-bottom: 20px;
    background-color: #fff;
/*     border: 1px solid transparent; */
    border-radius: 4px;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.05);
    box-shadow: 0 1px 1px rgba(0,0,0,.05);
}
.panel-body {
    padding: 9px;
}
.splitparcel .panel-heading {
    padding: 10px;
}
.panel-default>.panel-heading {
    color: #333;
    background-color: #f5f5f5;
    border-color: #ddd;
}
.pull-right {
	margin-top: -7px;
    float: right!important;
}
.splitparcel .origin .pull-right {
    margin-top: -5px;
}
.list-inline, .list-unstyled {
    margin-left: 0;
}
.list-unstyled {
    padding-left: 0;
    list-style: none;
}
ol, ul {
    margin: 0 0 10px 25px;
    padding: 0;
}
.splitparcel .prd {
    position: relative;
    min-height: 50px;
    margin-bottom: 10px;
    padding: 5px;
    border: 1px solid #ddd;
    background: #f5f5f5;
    border-radius: 3px;
}
.splitparcel .mui-media {
    padding-left: 60px;
    overflow: hidden;
}
.splitparcel .mui-media-object {
    float: left;
    max-width: 50px;
    max-height: 50px;
    margin-left: -60px;
}
.splitparcel .btn-group {
    position: absolute;
    top: 17px;
    right: 10px;
}
.splitparcel .btn-group .fa {
    font-size: 18px;
    cursor: pointer;
}
.fa-fw {
    width: 1.28571429em;
    text-align: center;
}
.fa {
    display: inline-block;
    font: normal normal normal 14px/1 FontAwesome;
    font-size: inherit;
    text-rendering: auto;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
.mui-media-body h4 {
    margin: 2px 0 5px;
    font-size: 14px;
}
h4 {
    font-size: 18px;
    font-weight: 400;
    font-family: "Open Sans","Helvetica Neue",Helvetica,Arial,sans-serif;
}
.glyphicon{
	cursor: pointer;
}
.mar-top{
	margin-top:15px;
}
.display{
	display:none;
}
.splitparcel .del {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    width: 24px;
    padding: 4px 4px 0;
    color: #fff;
    background: rgba(0,0,0, 0.6);
    text-align: center;
    line-height: 16px;
}
.red{
	color: #dd5a43;
}
.dropdown-menu{
	display: block;
}
</style>

<?php foreach ($orderid as $keys=> $orderidone){?> 
<div style="overflow: auto;" class="splitbody <?php echo empty($keys)?'':'mar-top'; ?>">
<div class="col-xs-6 origin splitparcel css<?php echo preg_replace('/^0+/','',$orderidone);?>">
            <div class="panel panel-default">
                <div class="panel-heading ng-binding">
                    <?php echo preg_replace('/^0+/','',$orderidone);?>
                    <div class="pull-right"><button type="button" id="btnsplitPackage" class="iv-btn btn-important" onclick="OrderCommon.splitPackage('<?php echo preg_replace('/^0+/','',$orderidone); ?>')">拆分订单</button></div>
                </div>
                <div class="panel-body">
                    <ul class="list-unstyled">
                    <?php foreach ($order_item_list as $order_item){
                    	if(preg_replace('/^0+/','',$order_item['order_id'])==preg_replace('/^0+/','',$orderidone)){
                    ?>
                        <li class="prd ng-scope css<?php echo $order_item['order_item_id'];?>">
                            <div class="mui-media">
                                <img class="mui-media-object" src="<?php echo $order_item['photo_primary'];?>">
                                <div class="mui-media-body">
                                    <div class="ng-binding"><?php echo $order_item['sku'];?></div>
                                    <h4 class="ng-binding" id="qty<?php echo $order_item['order_item_id'];?>"><?php echo $order_item['quantity'];?></h4>
                                </div>
                            </div>
               				<div class="btn-group ng-scope">
                                <i class="glyphicon glyphicon-chevron-right" onclick="OrderCommon.splitOrderChildren(this,'<?php echo preg_replace('/^0+/','',$orderidone);?>','<?php echo $order_item['order_item_id'];?>',0,-1)"></i>
                            </div>
                         </li>
                    <?php }} ?>
                    </ul>
                </div>
            </div>
</div>


<div class="col-xs-6 splitparcel splitparceldel delcss<?php echo preg_replace('/^0+/','',$orderidone);?>">
	<div class="panel panel-default ng-scope" id="pk<?php echo preg_replace('/^0+/','',$orderidone).'-0';?>" data-number="<?php echo preg_replace('/^0+/','',$orderidone);?>">
                <div class="panel-heading ng-binding">
                    <?php echo preg_replace('/^0+/','',$orderidone);?>-1
                    <div class="pull-right"><button type="button" class="btn btn-xs btn-danger" onclick="OrderCommon.splitPackageDel(this,'<?php echo preg_replace('/^0+/','',$orderidone);?>')">删除</button></div>
                </div>
                <div class="panel-body">
                </div>
    </div>
</div>

</div>
<?php } ?>

<input type="hidden" id="deldata" value="<?php echo $deldata;?> ">
<input type="hidden" id="splitqty" value="<?php echo $splitqty;?> ">

<div id="choiceOrderChildren" class="display" style="position: absolute;"></div>

<div class="modal-footer col-xs-12 w1009">
	<button type="button" class="btn btn-primary queding">确定</button>
	<button class="btn-default btn modal-close">取消</button>
</div>
<script>
$(document).on('mouseover','.pre',function(){
	$(this).find('.del').removeClass('display');
});   
$(document).on('mouseout','.pre',function(){
	$(this).find('.del').addClass('display');
}); 
$(document).on('click','.del',function(){
	$orderid=$(this).attr('data-order');
	$orderitemid=$(this).attr('data-item');
	$signt=$(this).attr('data-index');
	OrderCommon.splitOrderChildren(this,$orderid,$orderitemid,2,$signt);
}); 
$(document).click(function(){
    $("#choiceOrderChildren").addClass('display');
});
$(document).on('click','.choice',function(){
	$orderid=$(this).attr('data-order');
	$orderitemid=$(this).attr('data-item');
	$signt=$(this).attr('data-index');
	OrderCommon.splitOrderChildren(this,$orderid,$orderitemid,0,$signt);
}); 
</script>