<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\util\helpers\TranslateHelper;
?>

<table  class="table  table-hover content_right">
	<tr>
		<th qtipkey="tracker_order_id" class="th_orderid">订单号</th>
		<th qtipkey="tracker_tracking_no">物流单号</th>
		<th>发件国家</th>
		<th>目的国家</th>
		<th>最近事件</th>
		<th>包裹状态</th>
		<th>操作</th>
	</tr>
<?php 
try{
$tracking_no_list = [];
$div_event_html = "";
foreach($trackingList as $row):
$tracking_no_list[] = $row['track_no'];
$all_events_str = ""; 
$all_events_rt = TrackingHelper::generateTrackingEventHTML([$row['track_no']],[],true);
$all_events_str = $all_events_rt[$row['track_no']];

$div_event_html .= "<div id='div_more_info_".$row['id']."' class='div_more_tracking_info div_space_toggle'>";
$div_event_html .=  $all_events_str;
$div_event_html .= "</div>";

$tr_class = Tracking::getTrClassByState($row['state']);
//生成 TR 的HTML 代码
$Tr_result =  TrackingHelper::generateTrackingInfoHTML([$row['track_no']]);
if (! empty($Tr_result[$row['track_no']])){
	$TrHtmlStr = $Tr_result[$row['track_no']];
}else{
	$TrHtmlStr = "";
}
echo "<tr id=\"tr_info_".$row['track_no']."\"  ".((in_array($row['status'], ['received']))?"":"track_no=\"".$row['track_no']."\"")." data-track-id='".$row['id']."' >".$TrHtmlStr."</tr>";

$remarkHtml = TrackingHelper::generateRemarkHTML($row['remark']);

endforeach;
}catch (Exception $e){
	echo $e->getMessage();
}
	

?>
</table>

<input type="hidden" id="query_track_no_list"  value="<?= htmlspecialchars(json_encode($tracking_no_list))?>"/>
<input type="hidden" id="query_track_no_list_count" value="<?= count($tracking_no_list)?>"/>

<?= $div_event_html;?>