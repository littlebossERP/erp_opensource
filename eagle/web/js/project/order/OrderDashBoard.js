/**
 +------------------------------------------------------------------------------
 * OMS dash-board 专用js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		order
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com> 2015-04-20 eagle 2.0
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof OmsDashBoard === 'undefined')  OmsDashBoard = new Object();
OmsDashBoard = {
	orderCountChartData:'',
	ProfitChartData:'',

	initOrderCountChart:function(obj){
		if(obj!==false)
		$(".nav a").removeClass('active');
		$(obj).addClass('active');
		//$.domReady(function() {
			$.pluginReady('Highcharts', function(Highcharts) {
				countData = OmsDashBoard.orderCountChartData;
				
				if(typeof(countData.xAxis)=='undefined')
					return false;
				else chartXAxis = countData.xAxis;
				
				if(typeof(countData.yAxis)=='undefined')
					return false;
				else chartYAxis = countData.yAxis;
				
				if(typeof(countData.series)=='undefined')
					return false;
				else chartSeries = countData.series;
				
				if(typeof(countData.type)=='undefined')
					chartType = 'column';
				else chartType = countData.type;
					
				if(typeof(countData.title)=='undefined')
					chartTitle = '最近订单获取情况';
				else chartTitle = countData.title;
				if(typeof(countData.subtitle)=='undefined')
					chartSubTitle = '最近订单获取情况';
				else chartSubTitle = countData.subtitle;
				
				$("#chart").highcharts({
					chart: {
						type: chartType,
					},
					title: {
						text: false,//样式问题不显示title
					},
					subtitle: {
						text: chartTitle+'--'+chartSubTitle,
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
				
				var chart = $('#chart').highcharts();
				if(chart.series.length>4){
					for(var i=0;i<chart.series.length;i++){
						var series = chart.series[i];
						if(i>0)
							series.hide();
					}
				}
			});
		//});
	},
	
	initOrderProfitChart:function(obj){
		$(".nav a").removeClass('active');
		$(obj).addClass('active');
		$.pluginReady('Highcharts', function(Highcharts) {
			ChartData = OmsDashBoard.ProfitChartData;
			
			// if(typeof(countData.xAxis)=='undefined')
				// return false;
			// else chartXAxis = countData.xAxis;
			
			// if(typeof(countData.yAxis)=='undefined')
				// return false;
			// else chartYAxis = countData.yAxis;
			
			// if(typeof(countData.series)=='undefined')
				// return false;
			// else chartSeries = countData.series;
				
			if(typeof(ChartData.title)=='undefined')
				chartTitle = '最近已统计的订单利润情况';
			else chartTitle = ChartData.title;
			if(typeof(ChartData.subtitle)=='undefined')
				chartSubTitle = '';
			else chartSubTitle = '--'+ChartData.subtitle;
			if(typeof(ChartData.xAxis)=='undefined')
				return false;
			else chartXAxis = ChartData.xAxis;
			if(typeof(ChartData.series)=='undefined')
				return false;
			else chartSeries = ChartData.series;
			
			$("#chart").highcharts({
				chart: {
					zoomType: 'xy',
					spacingBottom: 50
				},
				title: {
					text: false,//样式问题不显示title
				},
				subtitle: {
					text: chartTitle+chartSubTitle,
				},
				xAxis: {
					categories: chartXAxis,
				},
				yAxis: [{ // Primary yAxis
					labels: {
						format: '{value}RMB',
						style: {
							color: '#89A54E'
						}
					},
					title: {
						text: '每单平均利润',
						style: {
							fontSize: '14px',
							fontWeight: '600',
							color: '#89A54E'
						}
					}
				}, { // Secondary yAxis
					title: {
						text: '总利润',
						style: {
							fontSize: '14px',
							fontWeight: '600',
							color: '#4572A7'
						}
					},
					labels: {
						format: '{value}RMB',
						style: {
							color: '#4572A7'
						}
					},
					opposite: true
				}],
				tooltip: {
					shared: true
				},
				
				legend: {
					layout: 'horizontal',
					align: 'center',
					x: 0,
					verticalAlign: 'bottom',
					y: 60,
					floating: true,
					backgroundColor: '#FFFFFF',
				},
				series: chartSeries,
				credits: {
					enabled: false
				},
			});
			
			var chart = $('#chart').highcharts();
			if(chart.series.length>4){
				for(var i=0;i<chart.series.length;i++){
					var series = chart.series[i];
					if(i>0 && i!==chart.series.length/2)
						series.hide();
				}
			}
		});
	},
}