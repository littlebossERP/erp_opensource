/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: ouss <songshun.ou@witsion.com>
+----------------------------------------------------------------------
| Create Date: 2014-05-05
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 报表管理模块js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		report
 * @subpackage  Exception
 * @author		ouss <songshun.ou@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if(typeof report === 'undefined')
	var report = new Object();

report = {
	currentDateString : '2014-03-06',
	startDateString : '2014-03-01',
	endDateString : '2014-03-31',
	currentYear : null, 
	currentMonth : null, 
	currentDay : null, 
	currentDate : null, 
	currentWeek : null,
	period : 'day',
	minDate : new Date(2009, 9 - 1, 9),
	maxDate : null,
	callbackFun : null,
	/**
	 +----------------------------------------------------------
	 * 初始化页面各组件
	 +----------------------------------------------------------
	 * @access	public
	 +----------------------------------------------------------
	 * @return 
	 +----------------------------------------------------------
	 * log		name	date					note
	 * @author	ouss	2014/02/27 13:33:00		初始化
	 +----------------------------------------------------------
	**/
	init : function(fun, setCurrDate, setMaxDate){
		if (fun) {
			report.callbackFun = fun;
		}
		if (setCurrDate) {
			report.currentDateString = setCurrDate;
		} else {
			var tmpDate = new Date();
			report.currentDateString = tmpDate.getFullYear() + '-' + ( tmpDate.getMonth() + 1) + '-' + (tmpDate.getDate());
		}
		if (!setMaxDate) {
			setMaxDate = false;
		}
		report.reloadData(setMaxDate);
		report.updateDate(report.currentDateString);
		
	},
	reloadData : function (setMaxDate) {
		if (!report.currentDateString) {
	        return;
	    }
	    report.setCurrentDate(report.currentDateString);
	    todayDate = new Date;
	    todayMonth = todayDate.getMonth() + 1;
	    todayYear = todayDate.getFullYear();
	    todayDay = todayDate.getDate();
	    if (setMaxDate) {
	    	report.maxDate = setMaxDate;
	    } else {
	    	report.maxDate = new Date(todayYear, todayMonth -1, todayDay);
	    }
	    selectedPeriod = report.period;
	    
	        datepickerElem = $('#datepicker').datepicker(report.getDatePickerOptions()), 
	        periodLabels = $('#periodString').find('.period-type label'), 
	        periodTooltip = $('#periodString').find('.period-click-tooltip').html();

	        report.unhighlightAllDates();
	        datepickerElem.on('mouseenter', 'tbody td', function() {
	            if ($(this).hasClass('ui-state-hover')) {
	                return;
	            }
	            if ($(this).hasClass('ui-state-disabled') && selectedPeriod != 'year') {
	            	report.unhighlightAllDates();
	                if (selectedPeriod == 'week') {
	                	report.highlightCurrentPeriod.call(this);
	                }
	            } 
	            else {
	            	report.highlightCurrentPeriod.call(this);
	            }
	        });
	        datepickerElem.on('mouseleave', 'tbody td', function() {
	            $('a', this).addClass('ui-state-hover');
	        });
	        datepickerElem.on('mouseleave', 'table', report.unhighlightAllDates).on('mouseenter', 'thead', report.unhighlightAllDates);
	        datepickerElem.on('click', 'tbody td.ui-datepicker-other-month', function() {
	            if ($(this).hasClass('ui-state-hover')) {
	                var row = $(this).parent(), tbody = row.parent();
	                if (row.is(':first-child')) {
	                    $('a', tbody).first().click();
	                } 
	                else {
	                    $('a', tbody).last().click();
	                }
	            }
	        });
	        var reloading = false;
	        var changePeriodOnClick = function(periodInput) {
	            if (reloading) 
	            {
	                return;
	            }
	            var url = periodInput.val(), period = report.getValueFromUrl('period', url);
	            if (selectedPeriod == period && selectedPeriod != 'range') {
	                if (period != selectedPeriod && !reloading) {
	                    reloading = true;
	                    selectedPeriod = period;
	                    report.updateDate(report.currentDateString);
	                }
	                return true;
	            }
	            return false;
	        };
	        $("#otherPeriods").find("label").on('click', function(e) {
	            var id = $(e.target).attr('for');
	            changePeriodOnClick($('#' + id));
	        });
	        $("#otherPeriods").find("input").on('click', function(e) {
	            var request_URL = $(e.target).val(), period = report.getValueFromUrl('period', request_URL), lastPeriod = selectedPeriod;
	            if (changePeriodOnClick($(e.target))) {
	                return true;
	            }
	            selectedPeriod = period;
	            periodLabels.each(function() {
	                $(this).attr('title', '').removeClass('selected-period-label');
	            });
	            if (period == 'range') {
	                return true;
	            }
	            if (period != report.period) 
	            {
	                $(this).parent().find('label[for=period_id_' + period + ']').attr('title', periodTooltip).addClass('selected-period-label');
	            }
	            report.togglePeriodPickers(true);
	            if (selectedPeriod == 'year' || lastPeriod == 'year') {
	                var currentMonth = $('.ui-datepicker-month', datepickerElem).val(), currentYear = $('.ui-datepicker-year', datepickerElem).val();
	                datepickerElem.datepicker('option', 'stepMonths', selectedPeriod == 'year' ? 12 : 1).datepicker('setDate', new Date(currentYear, currentMonth));
	            }
	            datepickerElem.datepicker('refresh');
	            report.unhighlightAllDates();
	            report.toggleMonthDropdown();
	            return true;
	        });
	        $(datepickerElem).on('click', '.ui-datepicker-next,.ui-datepicker-prev', function() {
	        	report.unhighlightAllDates();
	        	report.toggleMonthDropdown(selectedPeriod == 'year');
	        });
	        $("#periodString").on('click', "#date,.calendar-icon", function() {
	            var periodMore = $("#periodMore").toggle();
	            if (periodMore.is(":visible")) {
	                periodMore.find(".ui-state-highlight").removeClass('ui-state-highlight');
	            }
	        });
	        $('body').on('click', function(e) {
	            var target = $(e.target);
	            if (target.closest('html').length && !target.closest('#periodString').length && !target.is('option') && $("#periodMore").is(":visible")) {
	                $("#periodMore").hide();
	            }
	        });
	        function onDateRangeSelect(dateText, inst) {
	            var toOrFrom = inst.id == 'calendarFrom' ? 'From' : 'To';
	            $('#inputCalendar' + toOrFrom).val(dateText);
	        }
	        $("#period_id_range").on('click', function(e) {
	        	report.togglePeriodPickers(false);
	            var options = report.getDatePickerOptions();
	            options.onSelect = onDateRangeSelect;
	            options.beforeShowDay = '';
	            options.defaultDate = report.startDateString;
	            $('#calendarFrom').datepicker(options).datepicker("setDate", $.datepicker.parseDate('yy-mm-dd', report.startDateString));
	            onDateRangeSelect(report.startDateString, {"id": "calendarFrom"});
	            options.defaultDate = report.endDateString;
	            $('#calendarTo').datepicker(options).datepicker("setDate", $.datepicker.parseDate('yy-mm-dd', report.endDateString));
	            onDateRangeSelect(report.endDateString, {"id": "calendarTo"});
	            $('.ui-state-hover').removeClass('ui-state-hover');
	            $('#calendarRangeApply').on('click', function() {
	                
	                var dateFrom = $('#inputCalendarFrom').val(), dateTo = $('#inputCalendarTo').val(), oDateFrom = $.datepicker.parseDate('yy-mm-dd', dateFrom), oDateTo = $.datepicker.parseDate('yy-mm-dd', dateTo);
	                if (!report.isValidDate(oDateFrom) || !report.isValidDate(oDateTo) || oDateFrom > oDateTo) {
	                    alert("时间段不正确，请重试");
	                    return false;
	                }
	                report.startDateString  = dateFrom;
	            	report.endDateString =  dateTo;
	            	report.period = 'range';
	            	$('#date > strong').html ('从 '+ report.startDateString +' 至 ' + report.endDateString);
	            	if (report.callbackFun) {
	                	report.callbackFun(report.getParams());
	                }
	                $("#periodMore").hide();
	                
	                
	                // 重新选择日期后调用执行该调用方法的方法   修改时间：2015-04-29
	                DateSelectWidgetObj.selectAftClose();
	                
	            }).show();
	            $('#inputCalendarFrom, #inputCalendarTo').keyup(function(e) {
	                var fromOrTo = this.id == 'inputCalendarFrom' ? 'From' : 'To';
	                var dateInput = $(this).val();
	                try {
	                    var newDate = $.datepicker.parseDate('yy-mm-dd', dateInput);
	                } catch (e) {
	                    return;
	                }
	                $("#calendar" + fromOrTo).datepicker("setDate", newDate);
	                if (e.keyCode == 13) {
	                    $('#calendarRangeApply').click();
	                }
	            });
	            return true;
	        });
	},
    isDateInCurrentPeriod : function(date) {
        if (selectedPeriod != report.period) {
            return [true, ''];
        }
        var valid = false;
        var dateMonth = date.getMonth();
        var dateYear = date.getFullYear();
        var dateDay = date.getDate();
        if (dateMonth == todayMonth && dateYear == todayYear && dateDay > todayDay) {
            return [true, ''];
        }
        if (dateYear < 2009 || (dateYear == 2009 && ((dateMonth == 9 - 1 && dateDay < 9) || (dateMonth < 9 - 1)))) {
            return [true, ''];
        }
        if (report.period == "month" && dateMonth == report.currentMonth && dateYear == report.currentYear) {
            valid = true;
        } 
        else if (report.period == "year" && dateYear == report.currentYear) {
            valid = true;
        } 
        else if (report.period == "week" && report.getWeek(date) == report.currentWeek && dateYear == report.currentYear) {
            valid = true;
        } 
        else if (report.period == "day" && dateDay == report.currentDay && dateMonth == report.currentMonth && dateYear == report.currentYear) {
            valid = true;
        }
        if (valid) {
            return [true, 'ui-datepicker-current-period'];
        }
        return [true, ''];
    },
    getDatePickerOptions : function() {
        var result = report.getBaseDatePickerOptions(report.currentDate);
        result.beforeShowDay = report.isDateInCurrentPeriod;
        result.stepMonths = selectedPeriod == 'year' ? 12 : 1;
        result.onSelect = function() {
        	report.updateDate.apply(this, arguments);
        };
        return result;
    },
    isValidDate : function(d) {
        if (Object.prototype.toString.call(d) !== "[object Date]")
            return false;
        return !isNaN(d.getTime());
    },
    getBaseDatePickerOptions : function(defaultDate) {
        return {showOtherMonths: false,dateFormat: 'yy-mm-dd',firstDay: 1,minDate: report.minDate,maxDate: report.maxDate,prevText: "",nextText: "",currentText: "",defaultDate: defaultDate,changeMonth: true,changeYear: true,stepMonths: 1,dayNamesMin: ["日", "一", "二", "三", "四", "五", "六"],dayNamesShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六"],dayNames: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"],monthNamesShort: ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"],monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"]};
    },
    toggleWhitespaceHighlighting : function(klass, toggleTop, toggleBottom) {
        var viewedYear = $('.ui-datepicker-year', datepickerElem).val(), viewedMonth = +$('.ui-datepicker-month', datepickerElem).val(), firstOfViewedMonth = new Date(viewedYear, viewedMonth, 1), lastOfViewedMonth = new Date(viewedYear, viewedMonth + 1, 0);
        if (firstOfViewedMonth >= report.minDate) {
            $('tbody>tr:first-child td.ui-datepicker-other-month', datepickerElem).toggleClass(klass, toggleTop);
        }
        if (lastOfViewedMonth < report.maxDate) {
            $('tbody>tr:last-child td.ui-datepicker-other-month', datepickerElem).toggleClass(klass, toggleBottom);
        }
    },
    highlightCurrentPeriod : function() {
        switch (selectedPeriod) {
            case 'day':
                $('a', $(this)).addClass('ui-state-hover');
                break;
            case 'week':
                var row = $(this).parent();
                $('a', row).addClass('ui-state-hover');
                var toggleTop = row.is(':first-child'), toggleBottom = row.is(':last-child');
                report.toggleWhitespaceHighlighting('ui-state-hover', toggleTop, toggleBottom);
                break;
            case 'month':
                $('a', $(this).parent().parent()).addClass('ui-state-hover');
                break;
            case 'year':
                $('a', $(this).parent().parent()).addClass('ui-state-hover');
                report.toggleWhitespaceHighlighting('ui-state-hover', true, true);
                break;
        }
    },
    unhighlightAllDates : function() {
        $('.ui-state-active,.ui-state-hover', datepickerElem).removeClass('ui-state-active ui-state-hover');
        if (report.period == 'year') {
            var viewedYear = $('.ui-datepicker-year', datepickerElem).val(), toggle = selectedPeriod == 'year' && report.currentYear == viewedYear;
            report.toggleWhitespaceHighlighting('ui-datepicker-current-period', toggle, toggle);
        } 
        else if (report.period == 'week') {
            var toggleTop = $('tr:first-child a', datepickerElem).parent().hasClass('ui-datepicker-current-period'), toggleBottom = $('tr:last-child a', datepickerElem).parent().hasClass('ui-datepicker-current-period');
            report.toggleWhitespaceHighlighting('ui-datepicker-current-period', toggleTop, toggleBottom);
        }
    },
	setCurrentDate : function(dateStr) {
        var splitDate = dateStr.split("-");
        report.currentYear = splitDate[0];
        report.currentMonth = splitDate[1];
        report.currentDay = splitDate[2];
        report.currentDate = new Date(report.currentYear, report.currentMonth - 1, report.currentDay);
        report.currentWeek = report.getWeek(report.currentDate);
    },
    updateDate : function(dateText) {
        //piwikHelper.showAjaxLoading('ajaxLoadingCalendar');
    	
        report.setCurrentDate(dateText);
        report.period = selectedPeriod;
        setTimeout(report.unhighlightAllDates, 1);
        datepickerElem.datepicker('refresh');
        report.currentDateString = dateText;
        if (selectedPeriod == 'week') {
        	var tmpDay = report.currentDate.getDay();
        	if (tmpDay == 0) {
        		tmpDay = 7;
        	}
        	var tmpStartDate = report.currentDay - tmpDay + 1;
        	var tmpStartMonth = report.currentMonth;
        	var tmpStartYear = report.currentYear;
        	if (tmpStartDate < 1) {
        		tmpStartMonth = tmpStartMonth - 1;
        		if (tmpStartMonth < 1) {
        			tmpStartMonth = 12;
        			tmpStartYear = tmpStartYear - 1;
        		}
        		var tmpLastDay = report.getLastDay(tmpStartYear, tmpStartMonth);
        		tmpStartDate = tmpLastDay + tmpStartDate;
        	}
        	var tmpEndDate = report.currentDay - tmpDay + 7;
        	var tmpEndMonth = report.currentMonth;
        	var tmpEndYear = report.currentYear;
        	if (tmpEndDate > report.getLastDay(report.currentYear, report.currentMonth)) {
        		tmpEndMonth = parseInt(tmpEndMonth) + 1;
        		if (tmpEndMonth > 12) {
        			tmpEndMonth = 1;
        			tmpEndYear = parseInt(tmpEndYear);
        		}
        		tmpEndDate = tmpEndDate - report.getLastDay(report.currentYear, report.currentMonth);
        	}
        	report.startDateString  = tmpStartYear + '-' + tmpStartMonth + '-' + tmpStartDate;
        	report.endDateString =  tmpEndYear + '-' + tmpEndMonth + '-' + tmpEndDate;
        	$('#date > strong').html ('从 '+ report.startDateString +' 至 ' + report.endDateString);
        } else if (selectedPeriod == 'month') {
        	var tmpLastDay = report.getLastDay(report.currentYear, report.currentMonth);
        	report.startDateString  = report.currentYear + '-' + report.currentMonth + '-01';
        	report.endDateString =  report.currentYear + '-' + report.currentMonth + '-' + tmpLastDay;
        	
        	$('#date > strong').html (report.currentYear + ',' + report.getBaseDatePickerOptions().monthNames[(report.currentMonth - 1)]);
        } else if (selectedPeriod == 'year'){
        	report.startDateString  = report.currentYear + '-01-01';
        	report.endDateString =  report.currentYear + '-12-31';
        	$('#date > strong').html (report.currentYear);
        } else if (selectedPeriod == 'day') {
        	$('#date > strong').html (report.currentDateString);
        }
        if (report.callbackFun) {
        	report.callbackFun(report.getParams());
        }
        $("#periodMore").hide();
        //broadcast.propagateNewPage('date=' + dateText + '&period=' + selectedPeriod);
        //report.reloadData();
        
        
        // 重新选择日期后调用执行该调用方法的方法   修改时间：2015-04-29
        DateSelectWidgetObj.selectAftClose();

    },
    toggleMonthDropdown : function(disable) {
        if (typeof disable === 'undefined') {
            disable = selectedPeriod == 'year';
        }
        $('.ui-datepicker-month', datepickerElem).attr('disabled', disable);
    },
    togglePeriodPickers : function(showSingle) {
        $('#periodString').find('.period-date').toggle(showSingle);
        $('#periodString').find('.period-range').toggle(!showSingle);
        $('#calendarRangeApply').toggle(!showSingle);
    },
    getValueFromUrl :function (key, url) {
    	var searchString = '';
        if (url) {
            var urlParts = url.split('#');
            searchString = urlParts[0];
        } else {
            searchString = location.search;
        }
        url = searchString;
        var lookFor = key + '=';
        var startStr = url.indexOf(lookFor);
        if (startStr >= 0) {
            var endStr = url.indexOf("&", startStr);
            if (endStr == -1) {
                endStr = url.length;
            }
            var value = url.substring(startStr + key.length + 1, endStr);
            if (key != 'segment') {
                value = value.replace(/[^_%~\*\+\-\<\>!@\$\.()=,;0-9a-zA-Z]/gi, '');
            }
            return value;
        } else {
            return '';
        }
    },
    getWeek : function(date) {
        var onejan = new Date(date.getFullYear(), 0, 1), 
        	onejan_utc = Date.UTC(date.getFullYear(), 0, 1), 
        	this_utc = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()), 
        	daysSinceYearStart = (this_utc - onejan_utc) / 86400000;
        return Math.ceil((daysSinceYearStart + onejan.getDay()) / 7);
    },
    getLastDay : function (year,month)        {        
    	var new_year = year;
    	var new_month = month++;
    	if(month>12) {        
    		new_month -=12;
    		new_year++;
    	}        
    	var new_date = new Date(new_year,new_month,1);                //取当年当月中的第一天        
    	return (new Date(new_date.getTime()-1000*60*60*24)).getDate();//获取当月最后一天日期        
    },
    /**
	 +----------------------------------------------------------
	 * 目录数组转换为适合easyUI的tree数据格式
	 +----------------------------------------------------------
	 * @access	public
	 * @param 	rows		目录数据数组
	 +----------------------------------------------------------
	 * @return 
	 +----------------------------------------------------------
	 * log		name	date					note
	 * @author	ouss	2014/02/27 13:33:00		初始化
	 +----------------------------------------------------------
	**/
	convertTree : function (rows){  
	    function exists(rows, parentId){  
	        for(var i=0; i<rows.length; i++){  
	            if (rows[i].category_id == parentId) return true;  
	        }  
	        return false;  
	    }  
	      
	    var nodes = [];  
	    // 得到顶层节点
	    for(var i=0; i<rows.length; i++){  
	        var row = rows[i];  
	        if (!exists(rows, row.parent_id)){  
	            nodes.push({  
	                id:row.category_id,  
	                text:row.name
	            });  
	        }  
	    }  
	      
	    var toDo = [];  
	    for(var i=0; i<nodes.length; i++){  
	        toDo.push(nodes[i]);  
	    }  
	    while(toDo.length){  
	        var node = toDo.shift();    // 父节点 
	        // 得到子节点 
	        for(var i=0; i<rows.length; i++){  
	            var row = rows[i];  
	            if (row.parent_id == node.id){  
	                var child = {id:row.category_id,text:row.name};  
	                if (node.children){  
	                    node.children.push(child);  
	                } else {  
	                    node.children = [child];  
	                }  
	                toDo.push(child);  
	            }  
	        }  
	    }  
	    return nodes;  
	},
	/**
	 +----------------------------------------------------------
	 * 弹出信息窗口
	 +----------------------------------------------------------
	 * @access	public
	 * @param 	msgTitle		信息标题
	 * @param	msgContent		信息内容
	 +----------------------------------------------------------
	 * @return 
	 +----------------------------------------------------------
	 * log		name	date					note
	 * @author	ouss	2014/02/27 13:33:00		初始化
	 +----------------------------------------------------------
	**/
	showMessage : function (msgTitle, msgContent) {
		$.messager.show({
			 title : msgTitle,
			 msg : msgContent,
			 showType :'slide',
			 style : {
	   			 right : '',
	   			 top : document.body.scrollTop + document.documentElement.scrollTop,
	   			 bottom : ''
			 }
		});
	},
	reset : function () {
//		$('#index-centerayout-maintab').width(application.index.getCenterLayoutWidth()).height(application.index.getCenterLayoutHeight());
		if ($('#append-remove-tag').length == 0) {
			$('body').append('<div id="append-remove-tag"></div>');
		} else {
			$('#append-remove-tag').nextAll().remove();
		}
	},
	getParams : function () {
		var params = {};
		if (report.period == 'day') {
			params['start'] = report.currentDateString + ' 00:00:00';
			params['end'] = report.currentDateString + ' 23:59:59';
		}
		else {
			params['start'] = report.startDateString + ' 00:00:00';
			params['end'] = report.endDateString + ' 23:59:59';
		}
	       
		return params;
	}
};
report.reset();