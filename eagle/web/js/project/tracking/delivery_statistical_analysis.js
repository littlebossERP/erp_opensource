/**
 +------------------------------------------------------------------------------
 *TrackingµÄ½çÃæjs
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		tracker
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
 
 if (typeof delivery_statistical_analysis === 'undefined')  delivery_statistical_analysis = new Object();
delivery_statistical_analysis={
	'init': function() {
		$( "#startdate" ).datepicker({dateFormat:"yy-mm-dd" , minDate: -90});
		$( "#enddate" ).datepicker({dateFormat:"yy-mm-dd", minDate: -90});
		
		if ($('#tips_message').val() != '' ){
			bootbox.alert($('#tips_message').val());
		}
		
		$('select[name=to_nations]').change(function(){
			$("form:first").submit();
		});
	} , 
	
	'export_detail_excel':function(obj){
		var a_obj = $(obj);
		var ship_by = a_obj.parents('tr').children('td[data-ship-by]').text();
		url = '/tracking/tracking/export_delivery_statistical_analysis_detail';
		url += '?ship_by='+ship_by+'&'+$("form:first").serialize();
		window.open(url);
	},
	
}

$(function(){
	delivery_statistical_analysis.init();
})