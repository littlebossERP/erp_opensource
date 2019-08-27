<?php
use yii\helpers\Html;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderFrontHelper; 

?>
<style>
.modal-body{
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
.label {
    font-size: 12px;
    line-height: 1.15;
    height: 20px;
	border-radius: 0;
    text-shadow: none;
    font-weight: 400;
    display: inline-block;
    background-color: #abbac3!important;
}
</style>
<div class="alert alert-warning">会同时取消以下订单，如果不是已付款状态将不可取消。</div>

<?php foreach ($orderList as $keys=>$orderListone){ ?>
<div style="overflow: auto;" class="splitbody">
<div class="col-xs-6 origin splitparcel">
            <div class="panel panel-default">
                <div class="panel-heading ng-binding">
                	<span class="label ng-binding orderstatus" ><?php echo $orderListone['order_status'];?></span>
                    <?php echo $keys;?>
                </div>
                <div class="panel-body">
                    <ul class="list-unstyled">
                    <?php foreach ($orderListone['items'] as $orderListoneone){
                    ?>
                        <li class="prd ng-scope">
                            <div class="mui-media">
                                <img class="mui-media-object" src="<?php echo $orderListoneone['photo_primary'];?>">
                                <div class="mui-media-body">
                                    <div class="ng-binding"><?php echo $orderListoneone['sku'];?></div>
                                    <h4 class="ng-binding" ><?php echo $orderListoneone['quantity'];?></h4>
                                </div>
                            </div>
                         </li>
                    <?php } ?>
                    </ul>
                </div>
            </div>
</div>
<?php } ?>

<div class="col-xs-6 splitparcel splitparceldel">
	<?php foreach ($orderChildrenList as $keys=>$orderChildrenListone){ ?>
	<div class="panel panel-default ng-scope">
               <div class="panel-heading ng-binding">
               		<span class="label ng-binding orderstatus" ><?php echo $orderChildrenListone['order_status'];?></span>
                    <?php echo $keys;?>
                </div>
                <div class="panel-body">
                    <ul class="list-unstyled">
                    <?php 
                    if(!empty($orderChildrenListone['items'])){
                    foreach ($orderChildrenListone['items'] as $orderChildrenListoneone){
                    ?>
                        <li class="prd ng-scope">
                            <div class="mui-media">
                                <img class="mui-media-object" src="<?php echo $orderChildrenListoneone['photo_primary'];?>">
                                <div class="mui-media-body">
                                    <div class="ng-binding"><?php echo $orderChildrenListoneone['sku'];?></div>
                                    <h4 class="ng-binding" ><?php echo $orderChildrenListoneone['quantity'];?></h4>
                                </div>
                            </div>
                         </li>
                    <?php } }?>
                    </ul>
                </div>
    </div>
    <?php } ?>
</div>

</div>

<div class="modal-footer col-xs-12 w1009">
	<button type="button" class="btn btn-primary queding">取消拆分</button>
	<button class="btn-default btn modal-close">取消</button>
</div>