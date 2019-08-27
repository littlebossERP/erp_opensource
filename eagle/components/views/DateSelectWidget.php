<style type="text/css">
#periodString {
    background-color: #F7F7F7;
    border: 1px solid #E4E5E4;
    border-radius: 4px;
    color: #444444;
    display: block;
    float: left;
    font-size: 14px;
    margin-right: 10px;
    padding: 5px 30px 6px 10px;
    position: absolute;
    z-index: 122;
}
#periodString #date {
    cursor: pointer;
    margin: -5px -10px -6px;
    padding: 5px 10px 6px;
}
#periodString #date {
    background-clip: padding-box;
    background-color: #FFFFFF;
    border-radius: 0;
    color: #4D4D4D;
    font-family: Verdana,sans-serif;
    font-size: 10px;
    line-height: 12px;
    padding: 8px 10px;
    text-transform: uppercase;
}
#periodString .calendar-icon {
    background: url("/images/datebox_arrow.png") no-repeat scroll 0 0 rgba(0, 0, 0, 0);
    cursor: pointer;
    display: inline-block;
    height: 15px;
    position: absolute;
    right: 9px;
    top: 7px;
    width: 13px;
}
#periodString .calendar-icon {
    height: 17px;
    width: 17px;
}
#periodMore {
    display: none;
    overflow: hidden;
    padding: 6px 0 0;
}
#periodString .period-date, #periodString .period-range {
    float: left;
    padding: 0 16px 0 0;
}
#periodString h6 {
    font-size: 14px;
    padding: 0 0 4px;
	margin: 0;
}
#periodString h6 {
    color: #0D0D0D;
    font-family: Verdana,sans-serif;
    font-size: 13px;
    font-weight: normal;
    line-height: 16px;
}
#periodString .period-date, #periodString .period-range {
    float: left;
    padding: 0 16px 0 0;
}
#calendarRangeFrom {
    float: left;
}
#calendarRangeTo {
    float: left;
    margin-left: 20px;
}
#periodString .period-type {
    float: left;
    padding: 0 20px 0 0;
}
.top_controls {
	height: 30px;
	display: inline-block;
}
</style>
<div class="top_controls">
    <div id="periodString" class="piwikTopControl periodSelector">
    <div id="date">时间范围: <strong>2014, 三月</strong></div>
    <div class="calendar-icon"></div>
    <div id="periodMore">
        <div class="period-date">
            <h6>日期</h6>

            <div id="datepicker"></div>
        </div>
        <div class="period-range" style="display:none;">
            <div id="calendarRangeFrom">
                <h6>从<input tabindex="1" type="text" id="inputCalendarFrom" name="inputCalendarFrom"/></h6>

                <div id="calendarFrom"></div>
            </div>
            <div id="calendarRangeTo">
                <h6>至<input tabindex="2" type="text" id="inputCalendarTo" name="inputCalendarTo"/></h6>

                <div id="calendarTo"></div>
            </div>
        </div>
        <div class="period-type">
            <h6>统计时间</h6>
			<span id="otherPeriods">
                <input type="radio" name="period" id="period_id_day" value="?period=day&date=2014-03-06"  checked="checked" />
                <label for="period_id_day">按日</label>
                <br/>
                <input type="radio" name="period" id="period_id_week" value="?period=week&date=2014-03-06" />
                <label for="period_id_week">按周</label>
                <br/>
                <input type="radio" name="period" id="period_id_month" value="?period=month&date=2014-03-06"/>
                <label for="period_id_month">按月</label>
                <br/>
                <input type="radio" name="period" id="period_id_year" value="?period=year&date=2014-03-06" />
                <label for="period_id_year">按年</label>
                <br/>
                <input type="radio" name="period" id="period_id_range" value="?period=range&date=2014-03-06" />
                <label for="period_id_range">时间段</label>
                <br/>
            </span>
            <input tabindex="3" type="submit" value="应用时间段" id="calendarRangeApply" style="display: none;"/>
		</div>
</div>
</div>
</div>