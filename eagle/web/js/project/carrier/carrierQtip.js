/**
 +------------------------------------------------------------------------------
 *用于传递直接将数组转化为qtip
 *请传入Json格式数组，键值对为 qtip_id=>qtip_val
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		order
 * @subpackage  Exception
 * @author		hqw
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof carrierQtip === 'undefined')  carrierQtip = new Object();
	carrierQtip = {
		initCarrierQtip:function(carrierQtipList){
			var carrierQtipList=$.parseJSON(carrierQtipList);
			
			iconSrc = global.baseUrl + 'images/questionMark.png';
			var addHtml = "<img style='cursor: pointer;' width=16 src='" + iconSrc + "'  />";
			
			$.each(carrierQtipList,function(index,values){
				var maxWidth = 440; // MyTootipClass set max-width: 440px;
				// 获取窗口宽度
				if (window.innerWidth)
					winWidth = window.innerWidth;
				else if ((document.body) && (document.body.clientWidth))
					winWidth = document.body.clientWidth;
				// 获取窗口高度
				if (window.innerHeight)
					winHeight = window.innerHeight;
				else if ((document.body) && (document.body.clientHeight))
					winHeight = document.body.clientHeight;

				position_at = 'top right';
				position_my = 'bottom left';
				// tooltip初始化时，根据触发位置与浏览器窗口对比,设置tooltip postion at,my 位置
				var initTipItem = "."+values.qtip_id;
				
				$(initTipItem).append(addHtml);
				
				$(initTipItem).qtip({
					content: {
						text: values.qtip_val
					},
					position: {
						at: position_at,
						my: position_my,
						viewport: $(window),
						adjust: {
							method: 'shift flip'
						},
					},
					show: {
						delay: 500,
						effect: function() {
							$(this).fadeIn(500, function() {
								// show tooltip 时,根据tooltip 宽高与浏览器窗口对比,调整postion at ,my位置  
								var tooltip = this;
								if ($(tooltip).data('get_content')) {
									$(tooltip).css('width', 'auto');
									$(tooltip).qtip('api').reposition();
								}
							});
						},
					},
					hide: {
						effect: function() {
							$(this).fadeOut(500);
						}
					},
					style: {
						classes: 'qtip-dark qtip-rounded qtip-shadow MyTootipClass qtip-bootstrap',
					},
				});
			});
		},
	};