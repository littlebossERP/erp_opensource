/**
 * 设置模块js公用文件
 */

/**
 * 配置设置公共方法
 * million 2016-05-10
 */
function setconfig(obj){
		$.maskLayer(true);
		var form = $(obj).parents('.config-form');
		$.ajax({
			url: global.baseUrl + "configuration/default/setconfig",
			data: $(form).serialize(),
			type: 'post',
			success: function(response) {
				$.maskLayer(false);
				var result = JSON.parse(response);
				alert(result.message);
			},
			error: function(XMLHttpRequest, textStatus) {
				$.maskLayer(false);
				alert('网络不稳定.请求失败,请重试');
			}
		});
};