$(function() {
	'use strict';

	// 确认
	$('[click-confirm]')._ready('click', function(e) {
		var $this = $(this),
			dfd = $.Deferred();
		bootbox.confirm($this.attr('click-confirm') + "<br /><br />确定要继续吗？", function(e) {
			if (e === true) {
				dfd.resolve();
			}
		})
		return dfd.promise();
	});

	$('[ajax-request]')._on('click', function() {
		var $this = $(this);
		$.ajax({
			url: $this.attr('ajax-request'),
			method: $this.attr('ajax-method') || 'get',
			data: $this.attr('ajax-data') || ''
		}).success(function(res) {
			//console.log(res)
			$this.trigger('ajax-request', res);
		});
	});

	$('[ajax-request]').on('ajax-request', function(e,rs) {
		//console.log(arguments)
		if(!rs.error) {
			bootbox.alert('操作成功');
			$(this).closest('tr').remove();
		}else{
			bootbox.alert('操作失败：'+rs.message);
		}
	});

	$("[data-modal]")._on('click', function() {
		var $this = $(this),
			href = $this.attr('data-modal'),
			param = $this.data('params');
		try {
			param = $.parseJSON(param);
			console.log(param)
		} catch (e) {};
		$.showLoading();
		$.get(href, param).success(function(html) {
			$.hideLoading();
			bootbox.dialog({
				className: $this.attr('className'),
				title: $this.attr('title'),
				message: html
			});
		});
	});
	//显示查看留言
	$("[show-model]").on('click',function(){
		var $this = $(this),
		href = $this.attr('show-model'),
		m_id = $this.data('showtpl');
		try{
			m_id = $.parseJSON(m_id);
			console.log(m_id)
		}catch(e){};
		$.showLoading();
		$.post(href,m_id).success(function(html){
			$.hideLoading();
			bootbox.dialog({
				className: $this.attr('className'),
				title: $this.attr('title'),
				message: html
			});
		});
	});





	$("body")
		.on('change', '#addTags', function() {
			var text = $(this).val();
			if (text) {
				$("textarea[name=message_content]").insertToText(text);
			}
		})
		.on('change',"[data-check-all]", function() {
			var status = $(this).is(':checked'),
				target = $(this).data('check-all');
			if(target == 'check_All'){
				$('.lb-countries :checkbox').prop('checked',status);
				return false;
			}
			$("[data-check=" + target + "]").prop('checked', status).trigger('change');
		})
		.on('click','.glyphicon-checkbox', function() {
			var $this = $(this),
				_open = $this.data('open') || false;
			if (!_open) {
				$this.text('-').addClass('glyphicon-checkbox-close').removeClass('glyphicon-checkbox-open');
			} else {
				$this.text('+').addClass('glyphicon-checkbox-open').removeClass('glyphicon-checkbox-close');
			}
			$this.data('open', !_open);
		})
		.on('change', '#tplModel', function() {
			var $form = $(this).closest('form'),
				lang = 'content_en',
				id = $form.find('[name=tplModel]:checked').val();
			if (!lang || !id) return;
			return $.get('translate', {
				language: lang,
				id: id
			}, function(result) {
				$form.find('[name=message_content]').val(result.content);
			});
		})
		// 选择催款模板语言
		.on('change', '#language,[name=dptpl]', function() {
			var $form = $(this).closest('form'),
				lang = $form.find('#language').val(),
				id = $form.find('[name=dptpl]:checked').val();
			if (!lang || !id) return;
			return $.get('translate', {
				language: lang,
				id: id
			}, function(result) {
				$form.find('[name=message_content]').val(result.content);
			});
		})
		.on('change', '#country', function() {
			var key = $(this).val(),
				val = $(this).find('option:selected').text();
			$(".tags-view").eq(0).pushTag(key, val)
		})
		.on('change', '#region', function() {
			var $this = $(this),
				_html;
			if (!$this.data('options')) {
				$this.data('options', $("#country").html());
			}
			if ($this.val()) {
				_html = "<option value=''>请选择国家</option>" + $("<select>" + $this.data('options') + "</select>").find("[label='" + $this.val() + "']").html();
			} else {
				_html = $this.data('options');
			}
			$("#country").html(_html);
		});


	$(".iosSwitch").on('iosSwitch', function(e, val) {
		var $this = $(this);
		$this.closest('td').prev().text($this.val() == 2 ? '启用' : '停用');
	});



	// var myChart = echarts.init(document.getElementById('charts'));
	// 为echarts对象加载数据 
	// myChart.setOption(option);

	/*****  催款2期 ***/
	$(document).on('change','[data-disabled]',function(){
		var $this = $(this),$target = $($this.data('disabled'));
		if($this.is(":checked")){
			$target.removeAttr("disabled");
		}else{
			$target.prop('checked',false).trigger('change').attr("disabled",true);
		}
	})
		.on('change',"[lb-show]",function(){
			var $target = $($(this).attr("lb-show"));
			if( $(this).is(":checked")){
				$target.show();
			}else{
				$target.hide();

			}
		})
		.on('change',"[tp-show]",function(){
			var $target = $($(this).attr("tp-show"));
			$target.hide();
		})
		.on('click','#showTpl',function(){
			var val = $(this).closest('td').prev().find('option:selected').index();
			var num = val - 1;
			if(num>=0){
				$(this).closest('table').find("tr:eq(3) p").eq(num).toggle();
			}
		})
		.on('change',"[time-style]",function(){
			var $target = $($(this).attr("time-style"));
			if( $(this).is(":checked")){
				if($target.attr('id') == 'oneTime'){
					$target.attr('min',30);
				}else{
					$target.attr('min',1);
				}
			}else{
				$target.attr('min',0);
			}
		})

});


$.fn.extend({
	_ready: function(event, promise) {
		return $(this).each(function() {
			var $this = $(this);
			$this.data('_ready', $this.data('_ready') || {});
			if (!(event in $this.data('_ready'))) {
				$this.data('_ready')[event] = [];
			}
			$this.data('_ready')[event].push(promise);
		});
	},
	_on: function(event, fn) {
		return $(this).each(function() {
			var $this = $(this);
			$this.data('events', $this.data('events') || {});
			if (!(event in $this.data('events'))) {
				$this.data('events')[event] = [];
			}
			$this.data('events')[event].push(fn);
			$this.off(event).on(event, function() {
				var e = arguments,
					queue = [];
				if ($this.data('_ready')) {
					queue = $this.data('_ready')[event].map(function(fn) {
						return fn.apply($this[0], e);
					});
				}
				$.when.apply($, queue).done(function() {
					$.each($this.data('events')[event], function(key, fn) {
						fn.apply($this[0], e);
					});
				});
			});
		});
	}
});