/**
 +------------------------------------------------------------------------------
 * dash-board 专用js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		dashboard
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com> 2016-08 eagle 2.0
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof DashBoard === 'undefined')  DashBoard = new Object();
DashBoard = {
	orderCountChartData:'',
	ProfitChartData:'',
	maxSeriesShow:5,

	initClickTip : function(){
		$('.click-to-tip').each(function(){
			DashBoard.initMemoQtipEntryBtn(this);
		});
		
		DashBoard.rightAdv();
	},
	
	initMemoQtipEntryBtn:function(obj){
		var btnObj = $(obj);
		var tipkey = $(obj).data('qtipkey');
		btnObj.qtip({
			show: {
				event: 'click',
				solo: true,
			},
			hide: {
				event: 'click',	
			},
			content: {
			    button:false,
				text: $('#'+tipkey).html(),
		    },
			position:{
				at:'bottomMiddle',
				my:'topMiddle',
				viewport:$(window)
			},
			style: {
				classes:'pending_list',
				// def:false,
				// width:200,
			}
		});
		btnObj.prop('title','点击查看相关说明');
	},
	
	initOrderCountChart:function(obj){
		if(obj!=='false'){
			$(".nav a").removeClass('active');
			$(obj).addClass('active');
		}
		//$.domReady(function() {
			$.pluginReady('Highcharts', function(Highcharts) {
				countData = DashBoard.orderCountChartData;
				
				if(typeof(countData.xAxis)=='undefined')
					return false;
				else chartXAxis = countData.xAxis;
				
				// if(typeof(countData.yAxis)=='undefined')
					// return false;
				// else chartYAxis = countData.yAxis;
				
				if(typeof(countData.series)=='undefined')
					return false;
				else chartSeries = countData.series;
				
				if(chartSeries.length>DashBoard.maxSeriesShow)
					legend_enabled = false;
				else
					legend_enabled = true;
				
				if(typeof(countData.type)=='undefined')
					chartType = 'column';
				else chartType = countData.type;
					
				if(typeof(countData.title)=='undefined')
					chartTitle = '最近订单获取情况';
				else chartTitle = countData.title;
				if(typeof(countData.currency)!=='undefined')
					currency = countData.currency;
				else currency = 'USD';
				
				$("#chart").empty();
				$("#chart").highcharts({
					chart: {
						type: chartType,
					},
					title: {
						text: false,	//chartTitle,//样式问题不显示title
					},
					subtitle: {
						text: false,	//chartTitle+'--'+chartSubTitle,
					},
					xAxis: {
						categories: chartXAxis,
					},
					yAxis: [
						{ // Primary yAxis
							labels: {
								format: '{value}单',
								style: {
									color: '#4572A7'
								}
							},
							title: {
								text: '订单数',
								style: {
									fontSize: '14px',
									fontWeight: '600',
									color: '#4572A7'
								}
							}
						},
						{ // Secondary yAxis
							title: {
								text: '销售额',
								style: {
									fontSize: '14px',
									fontWeight: '600',
									color: '#89A54E'
								}
							},
							labels: {
								format: '{value} '+currency,
								style: {
									color: '#89A54E'
								}
							},
							opposite: true
						},
						{ // Tertiary yAxis
							title: {
								text: '利润(RMB)',
								style: {
									fontSize: '14px',
									fontWeight: '600',
									color: '#666'
								}
							},
							labels: {
								format: '{value} RMB',
								style: {
									color: '#666'
								}
							},
							opposite: true
						}
					],
					 tooltip: {
						headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
						pointFormat: '<tr><td style="width:80%;font-size:10px;color:{series.color};padding:0">{series.name}: </td>' +
							'<td style="width:20%;font-size:10px;padding:0"><b>{point.y}</b></td></tr>',
						footerFormat: '</table>',
						shared: true,
						useHTML: true,
					},
					series: chartSeries,
					credits: {
						enabled: false
					},
					legend: {
						//enabled:legend_enabled,
					}
				});

				SelectLegend(true);
				ShowProfit();
			});
			
		//});
	},
	
	refreshPendingOrderNum : function(){
		$.showLoading();
		$.ajax({
			type: "GET",
			url:'/dash_board/dash-board/refresh-pending-order-num',
			dataType:'json',
			success: function (result) {
				$.hideLoading();
				if(result.success===true){
					bootbox.alert({
			            message: '操作成功',  
			            callback: function() {  
			            	window.location.reload();
			            }
					});
				}else{
					bootbox.alert(result.message);
					return false;
				}
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('操作失败,后台返回异常'));
				return false;
			}
		});
	},
	
	rightAdv : function(){
        createRightUCT = nativejStorage.get('createRightUCT')(),
        createRightDCT = nativejStorage.get('createRightDCT')();
		
		//右侧广告
//		var width = document.body.clientWidth;
//		if(width < 1400){
//			$('div[cid="indexContentRight"]').css('max-width','950px');
//		}
		var nowDate = (Date.parse(new Date())) / 1000;
		
        var chaA = 1,chaB = 1;
		if(createRightUCT){
			chaA = nativejStorage.timestampDiff(createRightUCT,nowDate, 21600);
		}
		if(createRightDCT){
        	chaB = nativejStorage.timestampDiff(createRightDCT,nowDate, 21600);
		}
		
		if(chaA > 0 && ''!=''){
		    //图片大小120*280 jetstile	yangrenwu
            var positioncss='ra-ad',cids='advertising',dataname='createRightUCT',code='jetstile',url = '',imgUrl = '';
            createAdv(code,positioncss,cids,dataname,url,imgUrl,130,280);
	    }
        if(chaB > 0 && ''!=''){
            //图片大小120*165
            var positioncss='rz-ad',cids='advertising2',dataname='createRightDCT',code='',url = '',imgUrl = '';
            createAdv(code,positioncss,cids,dataname,url,imgUrl,130,165);
        }
        
        $(document).on('click','a[data-names="close"]',function(){
            var obj = $(this).closest('.createOut');
            
            var closeTimestamp = (Date.parse(new Date())) / 1000,
                name = obj.data('name');
            nativejStorage.set(name, closeTimestamp)();
            obj.hide();
        });
		
	},
	
	refreshSalesCount : function(){
		$.showLoading();
		$.ajax({
			type: "GET",
			url:'/dash_board/dash-board/refresh-sales-count',
			dataType:'json',
			success: function (result) {
				$.hideLoading();
				if(result.success===true){
					bootbox.alert({
			            message: '操作成功',  
			            callback: function() {  
			            	window.location.reload();
			            }
					});
				}else{
					bootbox.alert(result.message);
					return false;
				}
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('操作失败,后台返回异常'));
				return false;
			}
		});
	},
}

function SelectLegend(init) {
	var chart = $('#chart').highcharts();
	var series_count = chart.series.length;
	if(series_count>DashBoard.maxSeriesShow)
		var total_show_all = false;
	else
		var total_show_all = true;
	
    // 绑定事件
    $('.legend').delegate('select', 'change', function(){
        var self = $(this);
		var index = self.find('option:checked').data('index');
		
		var profit_checked = $("input#show_profit").prop('checked');
		if(typeof(profit_checked)=='undefined')
			profit_checked=false;
		for(var i=0; i<series_count;i++){
			var legend_item_show = false;
			if(index==0){
				if(i==index*3 || i==(index*3+1) || i==(index*3+2)){
					if(i==(index*3+2) && profit_checked==false){
						chart.series[i].hide();
					}else{
						chart.series[i].show();
						legend_item_show = true;
					}
				}
				else{
					if(total_show_all===true){
						chart.series[i].show();
						legend_item_show = true;
					}else
						chart.series[i].hide();
				}
			}else{
				if(i==index*3 || i==(index*3+1) || i==(index*3+2)){
					if(i==(index*3+2) && profit_checked==false){
						chart.series[i].hide();
					}else{
						chart.series[i].show();
						legend_item_show = true;
					}
				}else
					chart.series[i].hide();
			}
			
			if(legend_item_show==true){
				$("g.highcharts-legend .highcharts-legend-item").eq(i).show();
			}else
				$("g.highcharts-legend .highcharts-legend-item").eq(i).hide();
		}
    });
	
	if(init==true){
		for(var i=0; i<series_count;i++){
			var legend_item_show = false;
			if(i==0 || i==1 ){
				chart.series[i].show();
				legend_item_show = true;
			}else{
				if(total_show_all===true){
					chart.series[i].show();
					legend_item_show = true;
				}else
					chart.series[i].hide();
			}
			if(legend_item_show==true){
				$("g.highcharts-legend .highcharts-legend-item").eq(i).show();
			}else
				$("g.highcharts-legend .highcharts-legend-item").eq(i).hide();
		}
	}
			
}

function ShowProfit(){
	$("input#show_profit").change(function(){
		var checked = $("input#show_profit").prop('checked');
		
		var chart = $('#chart').highcharts();
		if(typeof(chart)=='undefined'){
			return;
		}
		var profit_series = chart.yAxis[2].series;
		if(typeof(profit_series)=='undefined'){
			return;
		}
		
		var checked_legend_index = $('.legend select').find('option:checked').data('index');
		if(typeof(checked_legend_index)=='undefined') checked_legend_index=0;
		for(i in profit_series){
			if(typeof( profit_series[i])=='object'){
				var column_index = profit_series[i].index;
				if(checked){
					if(i==checked_legend_index){
						chart.series[column_index].show();
					}
				}else{
					chart.series[column_index].hide();
				}
			}
			
		}
	});
}

function createAdv(code,positioncss,cids,dataname,url,imgUrl,width,height){
    var createObj = $('<div class="createOut '+positioncss+'" cid="'+cids+'" data-name="'+dataname+'"><div class="closeAdver"><span class="pull-left" style="color:#f63;margin-left:5px;">广告</span><a href="javascript:;" data-names="close" class="glyphicon glyphicon-remove-circle"></a></div><a href="/adv/adv-count?code='+code+'&url='+url+'" target="_blank"><img src="'+imgUrl+'"  width="'+width+'px" height="'+height+'px"/></a></div>');
//	var createObj = $('<div class="createOut '+positioncss+'" cid="'+cids+'" data-name="'+dataname+'"><div class="closeAdver"><span class="pull-left" style="color:#f63;margin-left:5px;"></span><a href="javascript:;" data-names="close" class="glyphicon glyphicon-remove-circle"></a></div><a href="/adv/adv-count?code='+code+'&url='+url+'" target="_blank"><img src="'+imgUrl+'"  width="'+width+'px" height="'+height+'px"/></a></div>');
    $('body').append(createObj);
}

/*
 * 本地存储公共方法	native_storage
* 设(string)     nativejStorage.set(key,string)()
* 设(obj)        nativejStorage.objSet(key,obj)()
* 取(string)     nativejStorage.get(key)()
* 取(obj)        nativejStorage.objGet(key)()
* 查             nativejStorage.storageObj()  返回整个storageObj对象
* 删             nativejStorage.remove(key)()  销毁storageObj中的key
* 查Key          nativejStorage.getKeyName()()  返回arr storageObj中的key
* 时间比较       nativejStorage.dateDiff(DateOne,DateTwo)   日期格式 YYYY/MM/dd 返回Number
* 时间戳比较	 nativejStorage.timestampDiff()
*/
var nativejStorage = {
    storageObj : function(){
        return window.localStorage;
    },
    get : function(key){
        return function(){
            return this.nativejStorage.storageObj().getItem(key);//this (window)
        };
    },
	objGet : function(key){
		return function(){
			return JSON.parse(this.nativejStorage.storageObj().getItem(key));//this (window)
		};
	},
    set : function(key,value){
		return function(){
			this.nativejStorage.storageObj().setItem(key,value);
		}
	},
	objSet : function(key,value){
		return function(){
			this.nativejStorage.storageObj().setItem(key,JSON.stringify(value));
		}
	},
    remove : function(key){
        return function(){
            this.nativejStorage.storageObj().removeItem(key);
        }
    },
    dateDiff : function(DateOne,DateTwo){
		var DateOne = DateOne.replace(/-|\//g,'');
		var DateTwo = DateTwo.replace(/-|\//g,'');
		var cha = 1;
		if(DateOne == DateTwo){
			cha = 0;
		}
        //return Math.abs(cha);
        return cha;
    },
    getKeyName : function(){
        return function(){
            var arr = [];
            $.each(this.nativejStorage.storageObj(),function(keyName,value){
                arr.push(keyName);
            });
            return arr;
        }
    },
    timestampDiff : function(timeOne, timeTwo, interval){
    	var cha = 1;
		if(parseInt(timeOne) + parseInt(interval) > parseInt(timeTwo)){
			cha = 0;
		}
        return cha;
    },

}