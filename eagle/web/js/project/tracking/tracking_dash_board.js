/**
 +------------------------------------------------------------------------------
 * tracking dash-board 专用js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		tracking
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com> 2016-01-27 eagle 2.0
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof TrackingDashBoard === 'undefined')  TrackingDashBoard = new Object();
TrackingDashBoard = {
	chartData:'',

	initChart:function(){
		//$.domReady(function() {
			$.pluginReady('Highcharts', function(Highcharts) {
				data = TrackingDashBoard.chartData;
				
				if(typeof(data.xAxis)=='undefined')
					return false;
				else chartXAxis = data.xAxis;
				
				if(typeof(data.yAxis)=='undefined')
					return false;
				else chartYAxis = data.yAxis;
				
				if(typeof(data.series)=='undefined')
					return false;
				else chartSeries = data.series;
				
				if(typeof(data.type)=='undefined')
					chartType = 'column';
				else chartType = data.type;
					
				if(typeof(data.title)=='undefined')
					chartTitle = 'Tracker物流跟踪';
				else chartTitle = data.title;
				if(typeof(data.subtitle)=='undefined')
					chartSubTitle = '';
				else chartSubTitle = data.subtitle;
				
				$("#chart").highcharts({
					chart: {
						type: chartType,
					},
					title: {
						text: false,//样式问题不显示title
					},
					subtitle: {
						text: chartTitle+(chartSubTitle=='')?'':chartSubTitle,
					},
					xAxis: {
						categories: chartXAxis,
					},
					yAxis: {
						title: {
							text: chartYAxis,
						}
					},
					 tooltip: {
						headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
						pointFormat: '<tr><td style="width:80%;font-size:10px;color:{series.color};padding:0">{series.name}: </td>' +
							'<td style="width:20%;font-size:10px;padding:0"><b>{point.y} 单</b></td></tr>',
						footerFormat: '</table>',
						shared: true,
						useHTML: true,
					},
					series: chartSeries,
					credits: {
						enabled: false
					},
				});
			});
		//});
	},
}