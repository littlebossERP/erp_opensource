$(function() {
	//左侧组件清单自适应高度
	function autoTypeHeight() {
		var toolsHeight = parseInt($(".label-type").height()) - 5,
			headingHeight = $("#type-group").find(".panel:visible").length * 40,
			panelBodyHeight = toolsHeight - headingHeight;
		$("#type-group .panel-body").height(panelBodyHeight);
	};
	autoTypeHeight();
	$(window).resize(function() {
		autoTypeHeight();
	});

	var headHeight = 0,
		footHeight = 0,
		loopHeight = 31,
		maxNum = 1, //第一页可显示商品行数
		moreNum = 1; //延伸页可显示商品行数
	$(function() {

		//=====================全局控制====================
		$(".label-group").draggable(); //标签在画布内改变位置
		$(".custom-drop .dropitem").each(function(index, element) { //初始化标签内容
			dropCreate($(this));
		});

		//显示区域居中
		var tempItem,
			labelWidth = $(".label-content").width(),
			labelHeight = $(".label-content").height()
		labelLeft = ($(document).width() / 2) - (labelWidth / 2),
			labelTop = ($(document).height() / 2) - (labelHeight / 2);
		$(".label-group").css({
			"left": labelLeft,
			"top": labelTop,
		});
		//离开页面提示
		$(window).bind('beforeunload', function() {
			return '离开页面前，请确保您编辑的内容已经保存完毕';
		});

		//计算商品清单第二页可显示行数
		function countNum() {
			var looplist = $(".custom-drop .skulist-table");
			headHeight = looplist.find("thead tr").height();
			footHeight = looplist.find("tfoot tr").height();
			loopHeight = looplist.find("tbody tr").height();
			maxNum = parseInt(looplist.find("tbody").height() / loopHeight);
			if (looplist.hasClass("declare")) moreNum = parseInt((labelHeight - 10) / loopHeight);
			else moreNum = parseInt((labelHeight - headHeight - footHeight - 10) / loopHeight);
			var loopTr = $(".custom-drop .skulist-table tbody tr:first").html(),
				loopTbody = "";
			for (var i = 1; i <= moreNum; i++) {
				loopTbody += '<tr id="ITEM_LIST_NO_DETAIL">' + loopTr + '</tr>';
			};
			$(".copy-drop .skulist-table tbody").empty().append(loopTbody).find("tr:not(:first) td span").empty().removeAttr("title");
			return loopHeight, moreNum, maxNum, headHeight, footHeight;
		};

		//选择模板
		$("#template-name").unbind().on("change", function() {
			$(this).val() == 0 ? $(".loadtemplate").addClass("disabled") : $(".loadtemplate").removeClass("disabled");
		});
		//清除模板
		$(".btn-reset").unbind().on("click", function() {
			$(".custom-drop").empty();
			$(".label-type .complete").removeClass("complete");
			$(".label-content.overflow").remove();
			closeLabelSet();
			maxNum = moreNum = 0;
		});

		//关闭属性面板
		function closeLabelSet() {
			$(".label-set").removeClass("opened");
			$(".custom-label").removeClass("haslabelset");
			$(".custom-drop").find(".active").removeClass("active");
		};
		$(document).bind("click", function(e) {
			var target = $(e.target);
			if (target.closest(".label-set").length == 0 && target.closest(".dropitem").length == 0) {
				closeLabelSet();
				$(".hotkey-panel").css("opacity", "0");
			}
		});

		//===========================预览模式==========================
		$(".btn-view").unbind().on("click", function() {
			$("body").addClass("viewtype");
			$(".view-mask, .view-panel").show();
			//$(".label-group").css({"left":"50%","top":"50%"});
		});
		$(".btn-close-view").unbind().on("click", function() {
			$("body").removeClass("viewtype");
			$(".view-mask, .view-panel").hide();
			$(".label-group").css({
				"left": labelLeft,
				"top": labelTop
			}).removeClass("scale150 scale200 scale300");
			$(".viewpct .text").text("100%");
		});
		//调整显示比例
		$(".viewpct .dropdown-menu a").unbind().on("click", function() {
			var newClass = $(this).attr("rel");
			switch (newClass) {
				case "default":
					$(".label-group").removeClass("scale150 scale200 scale300");
					break;
				case "scale150":
					$(".label-group").removeClass("scale200 scale300").addClass("scale150");
					break;
				case "scale200":
					$(".label-group").removeClass("scale150 scale300").addClass("scale200");
					break;
				case "scale300":
					$(".label-group").removeClass("scale200 scale150").addClass("scale300");
					break;
			}
		});

		//===========================快捷键功能==========================
		jwerty.key("right", function() {
			var itemLeft = parseInt($(".dropitem.active").css("left")),
				itemWidth = parseInt($(".dropitem.active").outerWidth()),
				parentWidth = parseInt($(".custom-drop").width());
			if (itemLeft < (parentWidth - itemWidth)) $(".dropitem.active").css("left", (parseInt($(".dropitem.active").css("left")) + 1) + "px");
		});
		jwerty.key("left", function() {
			var itemLeft = parseInt($(".dropitem.active").css("left"));
			if (itemLeft > 0) $(".dropitem.active").css("left", (parseInt($(".dropitem.active").css("left")) - 1) + "px");
		});
		jwerty.key("up", function() {
			var itemTop = parseInt($(".dropitem.active").css("top"));
			if (itemTop > 0) $(".dropitem.active").css("top", (parseInt($(".dropitem.active").css("top")) - 1) + "px");
		});
		jwerty.key("down", function() {
			var itemTop = parseInt($(".dropitem.active").css("top")),
				itemHeight = parseInt($(".dropitem.active").outerHeight()),
				parentHeight = parseInt($(".custom-drop").height());
			if (itemTop < (parentHeight - itemHeight)) $(".dropitem.active").css("top", (parseInt($(".dropitem.active").css("top")) + 1) + "px");
		});
		jwerty.key("delete", function() {
			$(".btn-clear-item").click()
		});

		//=====================拖拽动作====================
		//元素移动位置及拉伸
		function dropCreate(obj) {
			obj.draggable({
				containment: "parent",
				opacity: 0.7,
				revert: "invalid",
				start: function(event, ui) {
					if (!$(this).hasClass("active")) {
						getTempSet($(this));
						tempItem = $(this);
						return tempItem;
					}
				},
				stop: function(event, ui) {
					$(".label-set").addClass("opened");
				}
			});
			//拖拽改变尺寸
			if (obj.hasClass("character")) {
				obj.resizable({
					containment: ".custom-drop"
				});
			};
			if (obj.hasClass("imageitem")) {
				obj.resizable({
					containment: ".custom-drop",
					aspectRatio: true,
					stop: function(event, ui) {
						$(this).css("lineHeight", $(this).css("height"));
					}
				});
			};
			if (obj.hasClass("barcode")) {
				obj.resizable({
					handles: "e",
					minWidth: obj.attr("data-minwidth")
				});
			};

			if (obj.hasClass("skulist")) {
				var heightOld;
				obj.resizable({
					//handles: "s",
					minHeight: loopHeight + headHeight + footHeight,
					grid: loopHeight,
					containment: ".custom-drop",
					start: function(event, ui) {
						heightOld = obj.height();
					},
					stop: function(event, ui) {
						var colspanNum = obj.find(".skulist-table th:not('.dis-none')").length,
							heightNew = obj.height() - heightOld;
						var changeNum = parseInt(heightNew / loopHeight);
						var loopTr = obj.find(".skulist-table tbody tr").html(),
							loopTbody = "";
						if (heightNew > 0) {
							for (var i = 1; i <= changeNum; i++) {
								loopTbody += '<tr id="ITEM_LIST_NO_DETAIL">' + loopTr + '</tr>';
							};
							obj.find(".skulist-table tbody").append(loopTbody).find("tr:not(:first) td span").empty().removeAttr("title");
							maxNum = maxNum + changeNum;
						} else if (heightNew < 0) {
							for (var i = 1; i <= Math.abs(changeNum); i++) {
								obj.find(".skulist-table tbody tr:last").remove();
							};
							maxNum = maxNum - Math.abs(changeNum);
						};
						return maxNum;
					}
				});
			};
			if (obj.hasClass("declarelist")) {
				var heightOld;
				obj.resizable({
					minHeight: loopHeight + headHeight + footHeight,
					grid: loopHeight,
					containment: ".custom-drop",
					start: function(event, ui) {
						heightOld = obj.height();
					},
					stop: function(event, ui) {
						var colspanNum = obj.find(".skulist-table th:not('.dis-none')").length,
							heightNew = obj.height() - heightOld;
						var changeNum = parseInt(heightNew / loopHeight);
						var loopTr = obj.find(".skulist-table tbody tr").html(),
							loopTbody = "";
						if (heightNew > 0) {
							for (var i = 1; i <= changeNum; i++) {
								loopTbody += '<tr id="ITEM_LIST_NO_DETAIL">' + loopTr + '</tr>';
							};
							obj.find(".skulist-table tbody").append(loopTbody).find("tr:not(:first) td span").empty().removeAttr("title");
							maxNum = maxNum + changeNum;
						} else if (heightNew < 0) {
							for (var i = 1; i <= Math.abs(changeNum); i++) {
								obj.find(".skulist-table tbody tr:last").remove();
							};
							maxNum = maxNum - Math.abs(changeNum);
						};
						return maxNum;
					}
				});
			};

		};

		//临时接收drop
		$(".custom-area").droppable({
			accept: '.dragitem:not(".complete")',
			drop: function(event, ui) {
				var positionLeft, positionTop, tempstyle;
				positionLeft = Math.round(ui.offset.left - $(".custom-area").offset().left),
					positionTop = Math.round(ui.offset.top - $(".custom-area").offset().top),
					labeltype = ui.draggable.attr("data-type"),
					defaultWidth = ui.draggable.attr("data-default-width"),
					defaultHeight = ui.draggable.attr("data-default-height");
				switch (labeltype) {
					//创建字段属性
					case "character":
						$(".custom-drop").append('<div class="dropitem character" data-type="' + labeltype + '" data-title="' + ui.draggable.find(".title").text() + '" style="width:' + defaultWidth + 'px;left:' + positionLeft + 'px;top:' + positionTop + 'px"><strong class="title">' + ui.draggable.find(".title").text() + '</strong><span class="detail">' + ui.draggable.find(".detail").html() + '</span></div>');
						break;
						//创建地址组合字段
					case "address":
						$(".custom-drop").append('<div class="dropitem character address" data-type="' + labeltype + '" data-title="' + ui.draggable.find(".title").text() + '" style="width:' + defaultWidth + 'px;left:' + positionLeft + 'px;top:' + positionTop + 'px"><span class="detail block">' + ui.draggable.find(".detail").html() + '</span></div>');
						break;
						//创建地址2组合字段
					case "address_mode2":
						$(".custom-drop").append('<div class="dropitem character address_mode2" data-type="' + labeltype + '" data-title="' + ui.draggable.find(".title").text() + '" style="width:' + defaultWidth + 'px;left:' + positionLeft + 'px;top:' + positionTop + 'px"><span class="detail block">' + ui.draggable.find(".detail").html() + '</span></div>');
						break;
						//创建条码
					case "barcode":
						$(".custom-drop").append('<div class="dropitem barcode" data-type="' + labeltype + '" data-title="' + ui.draggable.find(".title").text() + '" data-minwidth="' + defaultWidth + '" style="width:' + defaultWidth + 'px;left:' + positionLeft + 'px;top:' + positionTop + 'px">' + ui.draggable.find(".detail").html() + '</div>');
						break;
						//创建商品清单
					case "skulist":
						$(".custom-drop").append('<div class="dropitem skulist" data-type="' + labeltype + '" data-title="' + ui.draggable.find(".title").text() + '" style="width:100%;height:' + defaultHeight + 'px;left:0px;top:' + positionTop + 'px">' + ui.draggable.find(".detail").html() + '</div>');
						ui.draggable.addClass("complete");
//						$(".label-group").append('<div id="PRODUCT_LIST_OVERFLOW" class="label-content overflow" style="width:' + labelWidth + 'px;height:' + labelHeight + 'px;"><div class="copy-drop">' + ui.draggable.find(".detail").html().replace('FULL_ITEMS_DETAIL_TABLE', 'FULL_ITEMS_DETAIL_TABLE_COPY') + '</div></div>');
						countNum();
						break;
						//创建报关单
					case "declarelist":
						$(".custom-drop").append('<div class="dropitem declarelist" data-type="' + labeltype + '" data-title="' + ui.draggable.find(".title").text() + '" style="width:100%;height:' + defaultHeight + 'px;left:0px;top:' + positionTop + 'px">' + ui.draggable.find(".detail").html() + '</div>');
						ui.draggable.addClass("complete");
						$(".label-group").append('<div id="PRODUCT_LIST_OVERFLOW" class="label-content overflow dis-none" style="width:' + labelWidth + 'px;height:' + labelHeight + 'px;"><div class="copy-drop">' + ui.draggable.find(".detail").html().replace('FULL_ITEMS_DETAIL_TABLE', 'FULL_ITEMS_DETAIL_TABLE_COPY') + '</div></div>');
						$(".copy-drop .skulist-table").find("thead, tfoot").remove();
						countNum();
						break;
						//创建自订文本
					case "customtext":
						$(".custom-drop").append('<div class="dropitem character" data-type="' + labeltype + '" data-title="' + ui.draggable.find(".title").text() + '" style="width:' + defaultWidth + 'px;left:' + positionLeft + 'px;top:' + positionTop + 'px"><i class="ico-checkbox-unchecked2"></i><span class="detail">自定义文本内容</span></div>');
						break;
						//创建水平线
					case "line-x":
						$(".custom-drop").append('<div class="dropitem linetype" data-type="' + labeltype + '" data-title="' + ui.draggable.find(".title").text() + '" style="width:100%;border-top-color:#000;height:2px;border-top-style:solid;border-top-width:2px;top:' + (positionTop + 30) + 'px"><div class="line-handle"></div></div>');
						break;
						//创建垂直线
					case "line-y":
						$(".custom-drop").append('<div class="dropitem linetype" data-type="' + labeltype + '" data-title="' + ui.draggable.find(".title").text() + '" style="width:2px;border-left-color:#000;height:100%;border-left-style:solid;border-left-width:2px;top:0px;left:' + (positionLeft + 30) + 'px;"><div class="line-handle"></div></div>');
						break;
						//创建圆形文本框
					case "circletext":
						$(".custom-drop").append('<div class="dropitem imageitem circletext" data-type="' + labeltype + '" data-title="' + ui.draggable.find(".title").text() + '" style="width:40px;height:40px;line-height:40px;border-width:2px;left:' + positionLeft + 'px;top:' + positionTop + 'px"><span class="detail" style="font-size:24px;">A</span></div>');
						break;
						//创建在线图片
					case "onlineimage":
						$(".custom-drop").append('<div class="dropitem character onlineimage" data-type="' + labeltype + '" data-title="' + ui.draggable.find(".title").text() + '" style="width:50px;height:50px;left:' + positionLeft + 'px;top:' + positionTop + 'px"><span></span><img src="http://www.littleboss.com/images/project/application/index/index/logo.jpg"></div>');
						break;
						//创建预设图片
					case "image":
						$(".custom-drop").append('<div class="dropitem imageitem" data-type="' + labeltype + '" data-title="图片" style="width:' + defaultWidth + 'px;height:' + defaultHeight + 'px;left:' + positionLeft + 'px;top:' + positionTop + 'px"><img src="' + ui.draggable.find("img").attr("src") + '"></div>');
						break;
				};
				dropCreate($(".custom-drop .dropitem").last());
			}
		});
		//调整位置
		$(".custom-drop").droppable({
			activeClass: "active",
			accept: '.dropitem'
		});

		//=====================点击属性标签返回设定值====================
		//拖拽属性名称标签
		$(".label-type .dragitem").draggable({
			helper: "clone",
			appendTo: "body",
			opacity: 0.7,
			revert: "invalid",
			start: function(event, ui) {
				closeLabelSet();
				$(".custom-drop").addClass("active");
			},
			stop: function(event, ui) {
				$(".custom-drop").removeClass("active");
			}
		});

		function getTempSet(obj) {
			obj.addClass("active").siblings().removeClass("active");
			//面板初始化
			$(".label-set .panel-title").text(obj.attr("data-title"));
			$(".label-set .nav-tabs li").css("display", "").removeClass("active");
			$(".label-set .tab-pane").removeClass("active");
			//不同类别区分处理
			switch (obj.attr("data-type")) {
				//固定属性
				case "character":
					$(".label-set").find(".panel-toolbar-wrapper").show();
					$(".label-set .edit-btn .btn-copy").removeClass("disabled");
					//获取目标css
					var itemTitle = obj.find(".title"),
						itemDetail = obj.find(".detail");
					//返回标题设定
					$(".label-set .nav-tabs").find("li.title").css("display", "table-cell").addClass("active");
					$("#titelset").addClass("active");
					if (itemTitle.css("display") == "none") {
						$("#viewTitle").attr("checked", false);
						$("#titelset .title-set").find("input,select").attr("disabled", true);
					} else {
						$("#viewTitle").attr("checked", true);
						$("#titelset .title-set").find("input,select").attr("disabled", false);
					}
					itemTitle.css("display") == "block" ? $("#titleNowrap").attr("checked", true) : $("#titleNowrap").attr("checked", false);
					$("#titleName").val(itemTitle.text());
					$("#titleAlign").val(itemTitle.css("textAlign"));
					$("#titleFontFamily").val(itemTitle.css("font-family"));
					$("#titleFontSize").val(itemTitle.css("font-size"));
					$("#titleLineHeight").val(Math.round(parseInt(itemTitle.css("lineHeight")) / parseInt(itemTitle.css("font-size")) * 10) / 10);
					itemTitle.css("fontWeight") >= "700" ? $("#titleFontWeight").attr("checked", true) : $("#titleFontWeight").attr("checked", false);
					//返回内容设定
					$(".label-set .nav-tabs").find("li.detail").css("display", "table-cell");
					$("#detailAlign").val(itemDetail.css("textAlign"));
					$("#detailFontFamily").val(itemDetail.css("font-family"));
					$("#detailFontSize").val(itemDetail.css("font-size"));
					$("#detailLineHeight").val(Math.round(parseInt(itemDetail.css("lineHeight")) / parseInt(itemDetail.css("font-size")) * 10) / 10);
					itemDetail.css("fontWeight") >= "700" ? $("#detailFontWeight").attr("checked", true) : $("#detailFontWeight").attr("checked", false);
					//返回边框设定
					setBorder(obj);
					break;
					//地址字段组合
				case "address":
					$(".label-set").find(".panel-toolbar-wrapper").show();
					$(".label-set .edit-btn .btn-copy").removeClass("disabled");
					//获取目标css
					var itemDetail = obj.find(".detail");
					//返回内容设定
					$(".label-set .nav-tabs").find("li.detail").css("display", "table-cell").addClass("active");
					$("#detailset").addClass("active");
					$("#detailAlign").val(itemDetail.css("textAlign"));
					$("#detailFontFamily").val(itemDetail.css("font-family"));
					$("#detailFontSize").val(itemDetail.css("font-size"));
					$("#detailLineHeight").val(Math.round(parseInt(itemDetail.css("lineHeight")) / parseInt(itemDetail.css("font-size")) * 10) / 10);
					itemDetail.css("fontWeight") >= "700" ? $("#detailFontWeight").attr("checked", true) : $("#detailFontWeight").attr("checked", false);
					//返回字段设定
					$(".label-set .nav-tabs").find("li.field-address").css("display", "table-cell");
					itemDetail.find("span").each(function() {
						var className = $(this).attr("class"),
							itemForm = $("#fieldset-address .form-group.multiple");
						if ($(this).css("display") == "none") {
							itemForm.find("input[type='checkbox'][value='" + className + "']").attr("checked", false);
						} else {
							itemForm.find("input[type='checkbox'][value='" + className + "']").attr("checked", true);
						}
					});
					if (itemDetail.find(".country").css("display") == "none") {
						$("#fieldset-address .multiple input[name='viewField'][value='country_cn']").attr("disabled", true);
					} else {
						$("#fieldset-address .multiple input[name='viewField'][value='country_cn']").attr("disabled", false);
					};
					itemDetail.hasClass("block") ? $("#newline").attr("checked", true) : $("#newline").attr("checked", false);
					//返回边框设定
					setBorder(obj);
					break;
					//地址字段组合2
				case "address_mode2":
					$(".label-set").find(".panel-toolbar-wrapper").show();
					$(".label-set .edit-btn .btn-copy").removeClass("disabled");
					//获取目标css
					var itemDetail = obj.find(".detail");
					//返回内容设定
					$(".label-set .nav-tabs").find("li.detail").css("display", "table-cell").addClass("active");
					$("#detailset").addClass("active");
					$("#detailAlign").val(itemDetail.css("textAlign"));
					$("#detailFontFamily").val(itemDetail.css("font-family"));
					$("#detailFontSize").val(itemDetail.css("font-size"));
					$("#detailLineHeight").val(Math.round(parseInt(itemDetail.css("lineHeight")) / parseInt(itemDetail.css("font-size")) * 10) / 10);
					itemDetail.css("fontWeight") >= "700" ? $("#detailFontWeight").attr("checked", true) : $("#detailFontWeight").attr("checked", false);
					//返回字段设定
					$(".label-set .nav-tabs").find("li.field-address-mode2").css("display", "table-cell");
					itemDetail.find("span").each(function() {
						var className = $(this).attr("class"),
							itemForm = $("#fieldset-address-mode2 .form-group.multiple");
						if ($(this).css("display") == "none") {
							itemForm.find("input[type='checkbox'][value='" + className + "']").attr("checked", false);
						} else {
							itemForm.find("input[type='checkbox'][value='" + className + "']").attr("checked", true);
						}
					});
					itemDetail.hasClass("block") ? $("#newline_mode2").attr("checked", true) : $("#newline_mode2").attr("checked", false);
					//返回边框设定
					setBorder(obj);
					break;
					//条码
				case "barcode":
					$(".label-set").find(".panel-toolbar-wrapper").hide();
					$(".label-set .edit-btn .btn-copy").addClass("disabled");
					//返回条码设置
					var codeNum = obj.find(".codemunber");
					$("#barcode").addClass("active");
					$("#codeType").val(obj.attr("data-code-type"));
					if (codeNum.css("display") == "none") {
						$("#viewCodeNum").attr("checked", false);
						$("#barcode .codenum-set").find("input,select").attr("disabled", true);
					} else {
						$("#viewCodeNum").attr("checked", true);
						$("#barcode .codenum-set").find("input,select").attr("disabled", false);
					};
					$("#codeNumAlign").val(codeNum.css("textAlign"));
					$("#codeNumFontSize").val(codeNum.css("font-size"));
					codeNum.css("fontWeight") >= "700" ? $("#codeNumFontWeight").attr("checked", true) : $("#codeNumFontWeight").attr("checked", false);
					$("#codePrefix").val(codeNum.find(".prefix").text());
					break;
					//商品清单
				case "skulist":
					$(".label-set").find(".panel-toolbar-wrapper").show();
					$(".label-set .edit-btn .btn-copy").addClass("disabled");
					//获取目标css
					var table = obj.find(".skulist-table"),
						thead = table.find("thead"),
						tbody = table.find("tbody"),
						tfoot = table.find("tfoot");
					//返回表格设定
					$(".label-set .nav-tabs").find("li.table").css("display", "table-cell").addClass("active");
					$("#tableset").addClass("active");
					table.hasClass("no-tdborder") ? $("#viewTdBorder").attr("checked", false) : $("#viewTdBorder").attr("checked", true);
					thead.is(":hidden") ? $("#viewThead").attr("checked", false).parent(".form-group").next(".moreinfo").find("input,select").attr("disabled", true) : $("#viewThead").attr("checked", true).parent(".form-group").next(".moreinfo").find("input,select").attr("disabled", false);
					$("#theadFontFamily").val(thead.css("font-family"));
					$("#theadFontSize").val(thead.css("font-size"));
					$("#tbodyFontFamily").val(tbody.css("font-family"));
					$("#tbodyFontSize").val(tbody.css("font-size"));
					tfoot.is(":hidden") ? $("#viewTfoot").attr("checked", false).parent(".form-group").next(".moreinfo").find("input,select").attr("disabled", true) : $("#viewTfoot").attr("checked", true).parent(".form-group").next(".moreinfo").find("input,select").attr("disabled", false);
					$("#tfootFontFamily").val(tfoot.css("font-family"));
					$("#tfootFontSize").val(tfoot.css("font-size"));
					//$("#tfootAlign").val(tfoot.css("textAlign"));

					//返回字段设定
					$(".label-set .nav-tabs").find("li.field-sku").css("display", "table-cell");
					thead.find("th").each(function() {
						var index = $(this).index(),
							itemForm = $("#fieldset-sku .form-group").eq(index);
						if ($(this).hasClass("dis-none")) {
							itemForm.find("input[type='checkbox']").attr("checked", false);
							itemForm.find("input.form-control").attr("disabled", true).val($(this).find("span").text());
						} else {
							itemForm.find("input[type='checkbox']").attr("checked", true);
							itemForm.find("input.form-control").attr("disabled", false).val($(this).find("span").text());
						}
					});
					break;
					//报关物品
				case "declarelist":
					$(".label-set").find(".panel-toolbar-wrapper").show();
					$(".label-set .edit-btn .btn-copy").addClass("disabled");
					//获取目标css
					var table = obj.find(".skulist-table.declare"),
						thead = table.find("thead"),
						tbody = table.find("tbody"),
						tfoot = table.find("tfoot");
					//返回表格设定
					$(".label-set .nav-tabs").find("li.table").css("display", "table-cell").addClass("active");
					$("#tableset").addClass("active");
					table.hasClass("no-tdborder") ? $("#viewTdBorder").attr("checked", false) : $("#viewTdBorder").attr("checked", true);
					thead.is(":hidden") ? $("#viewThead").attr("checked", false).parent(".form-group").next(".moreinfo").find("input,select").attr("disabled", true) : $("#viewThead").attr("checked", true).parent(".form-group").next(".moreinfo").find("input,select").attr("disabled", false);
					$("#theadFontFamily").val(thead.css("font-family"));
					$("#theadFontSize").val(thead.css("font-size"));
					$("#tbodyFontFamily").val(tbody.css("font-family"));
					$("#tbodyFontSize").val(tbody.css("font-size"));
					tfoot.is(":hidden") ? $("#viewTfoot").attr("checked", false).parent(".form-group").next(".moreinfo").find("input,select").attr("disabled", true) : $("#viewTfoot").attr("checked", true).parent(".form-group").next(".moreinfo").find("input,select").attr("disabled", false);
					$("#tfootFontFamily").val(tfoot.css("font-family"));
					$("#tfootFontSize").val(tfoot.css("font-size"));
					//$("#tfootAlign").val(tfoot.css("textAlign"));

					//返回字段设定
					$(".label-set .nav-tabs").find("li.field-declare").css("display", "table-cell");
					$("#declareNameTitle").val(thead.find(".name_declare span").html().replace(/<br>/g, "\n"));
					$("#declareWeightTitle").val(thead.find(".weight_declare span").html().replace(/<br>/g, "\n"));
					$("#declarePriceTitle").val(thead.find(".price_declare span").html().replace(/<br>/g, "\n"));
					$("#declareOriginTitle").val(tfoot.find("th.origin_declare span").html().replace(/<br>/g, "\n"));
					$("#declareTotalWeightTitle").val(tfoot.find("th.weight_declare span").html().replace(/<br>/g, "\n"));
					$("#declareTotalPriceTitle").val(tfoot.find("th.price_declare span").html().replace(/<br>/g, "\n"));

					$("#declareNameCustom").val(tbody.find(".custom span").html().replace(/<br>/g, "\n"));
					var nameClass = tbody.find(".name_declare:not('.dis-none')").attr("class"),
						nameNum = nameClass.length,
						typeName = nameClass.indexOf(" ") + 1;
					$("#declareType").val(nameClass.substr(typeName, nameNum));
					$("#declareName").find("." + nameClass.substr(typeName, nameNum)).show().siblings(".multiple").hide();
					break;
					//自定义文字
				case "customtext":
					$(".label-set").find(".panel-toolbar-wrapper").show();
					$(".label-set .edit-btn .btn-copy").removeClass("disabled");
					//获取目标css
					var textDetail = obj.find(".detail"),
						textData = textDetail.html().replace(/<br>/g, "\n"),
						checkBox = obj.find("i"),
						textLineHeight = parseInt(obj.css("lineHeight")) / parseInt(obj.css("font-size"));
					//返回文字设定
					$(".label-set .nav-tabs").find("li.text").css("display", "table-cell").addClass("active");
					$("#textset").addClass("active");
					$("#textDetail").val(textData);
					$("#textAlign").val(obj.css("textAlign"));
					$("#textFontFamily").val(textDetail.css("font-family"));
					$("#textFontSize").val(obj.css("font-size"));
					$("#textLineHeight").val(Math.round(parseInt(obj.css("lineHeight")) / parseInt(obj.css("font-size")) * 10) / 10);
					textDetail.css("fontWeight") >= "700" ? $("#textFontWeight").attr("checked", true) : $("#textFontWeight").attr("checked", false);
					if (checkBox.css("display") == "none") {
						$("#textCheckBox").attr("checked", false).parents(".form-group").next(".form-group").hide();
					} else {
						$("#textCheckBox").attr("checked", true).parents(".form-group").next(".form-group").show();
						$("#checkBoxType").val(checkBox.attr("class"));
					}
					//返回边框设定
					setBorder(obj);
					break;
					//水平线条
				case "line-x":
					$(".label-set").find(".panel-toolbar-wrapper").hide();
					$(".label-set .edit-btn .btn-copy").removeClass("disabled");
					//返回水平线设置
					$("#line-x").addClass("active");
					$("#xLineStyle").val(obj.css("border-top-style"));
					$("#xLineWeight").val(obj.css("border-top-width"));
					$("#xLineWidth").val(parseInt(obj.css("width")));
					break;
					//垂直线条
				case "line-y":
					$(".label-set").find(".panel-toolbar-wrapper").hide();
					$(".label-set .edit-btn .btn-copy").removeClass("disabled");
					//返回垂直线设置
					$("#line-y").addClass("active");
					$("#yLineStyle").val(obj.css("border-left-style"));
					$("#yLineWeight").val(obj.css("border-left-width"));
					$("#yLineWidth").val(parseInt(obj.css("height")));
					break;
					//圆框文字
				case "circletext":
					$(".label-set").find(".panel-toolbar-wrapper").hide();
					$(".label-set .edit-btn .btn-copy").removeClass("disabled");
					var textDetail = obj.find(".detail");
					$("#circletext").addClass("active");
					$("#circleBorderWidth").val(obj.css("border-top-width"));
					$("#circleText").text(textDetail.text());
					$("#circleFontFamily").val(textDetail.css("font-family"));
					$("#circleFontSize").val(textDetail.css("font-size"));
					textDetail.css("fontWeight") >= "700" ? $("#circleFontWeight").attr("checked", true) : $("#circleFontWeight").attr("checked", false);
					break;
					//在线图片
				case "onlineimage":
					$(".label-set").find(".panel-toolbar-wrapper").show();
					$(".label-set .edit-btn .btn-copy").removeClass("disabled");
					//获取目标css
					var image = obj.find("img");
					//返回图片路径设定
					$(".label-set .nav-tabs").find("li.imgurl").css("display", "table-cell").addClass("active");
					$("#imageurl").addClass("active");

					image.attr("src") == "/images/customprint/photo_default.jpg" ? $("#imageUrl").val("") : $("#imageUrl").val(image.attr("src"));
					//返回边框设定
//					setBorder(obj);
					break;
					//图片
				case "image":
					$(".label-set").find(".panel-toolbar-wrapper").hide();
					$(".label-set .edit-btn .btn-copy").removeClass("disabled");
					//返回边框设定
//					$("#borderset").addClass("active");
//					setBorder(obj);
					break;
			}
		};
		//返回边框设定
		function setBorder(obj) {
			$(".label-set .nav-tabs").find("li.border").css("display", "table-cell");
			if (parseInt(obj.css("border-top-width")) == 0) {
				$("#borderTop").attr("checked", false).parents(".form-group").find("select.form-control").attr("disabled", true);
			} else {
				$("#borderTop").attr("checked", true).parents(".form-group").find("select.form-control").attr("disabled", false);
			};
			$("#borderTopWidth").val(obj.css("border-top-width"));
			$("#paddingTop").val(obj.css("padding-top"));
			if (parseInt(obj.css("border-bottom-width")) == 0) {
				$("#borderBottom").attr("checked", false).parents(".form-group").find("select.form-control").attr("disabled", true);
			} else {
				$("#borderBottom").attr("checked", true).parents(".form-group").find("select.form-control").attr("disabled", false);
			};
			$("#borderBottomWidth").val(obj.css("border-bottom-width"));
			$("#paddingBottom").val(obj.css("padding-bottom"));
			if (parseInt(obj.css("border-left-width")) == 0) {
				$("#borderLeft").attr("checked", false).parents(".form-group").find("select.form-control").attr("disabled", true);
			} else {
				$("#borderLeft").attr("checked", true).parents(".form-group").find("select.form-control").attr("disabled", false);
			};
			$("#borderLeftWidth").val(obj.css("border-left-width"));
			$("#paddingLeft").val(obj.css("padding-left"));
			if (parseInt(obj.css("border-right-width")) == 0) {
				$("#borderRight").attr("checked", false).parents(".form-group").find("select.form-control").attr("disabled", true);
			} else {
				$("#borderRight").attr("checked", true).parents(".form-group").find("select.form-control").attr("disabled", false);
			};
			$("#borderRightWidth").val(obj.css("border-right-width"));
			$("#paddingRight").val(obj.css("padding-right"));
		}
		//标签点击
		$(".custom-content").unbind().on("click", ".dropitem", function() {
			tempItem = $(this);
			$(".label-set").addClass("opened");
			$(".custom-label").addClass("haslabelset");
			$(".hotkey-panel").css({
				"opacity": "1",
				"z-index": "3"
			});
			dropCreate($(this));
			getTempSet($(this));
			return tempItem;
		});

		//=====================属性设置参数=====================

		//========标题修改=========
		//是否显示标题
		$("#viewTitle").unbind().on("click", function() {
			if ($(this).attr("checked")) {
				tempItem.find(".title").css("display", "");
				$("#titelset .title-set").find("input,select").attr("disabled", false);
			} else {
				tempItem.find(".title").css("display", "none");
				$("#titelset .title-set").find("input,select").attr("disabled", true);
			}
		});
		//标题整行显示
		$("#titleNowrap").unbind().on("click", function() {
			if ($(this).attr("checked")) {
				$(this).parents(".form-group").next(".moreinfo").show();
				tempItem.find(".title").css("display", "block")
			} else {
				$(this).parents(".form-group").next(".moreinfo").hide();
				tempItem.find(".title").css("display", "");
			};
		});
		//修改标题对齐方式
		$("#titleAlign").unbind().on("change", function() {
			tempItem.find(".title").css("textAlign", $(this).val());
		});

		//修改标题与内容间距
		$("#titlePaddingBottom").unbind().on("change", function() {
			tempItem.find(".title").css("paddingBottom", $(this).val() + "px");
		});

		//修改标题文本
		$("#titleName").unbind().on("keyup", function() {
			tempItem.find(".title").text($(this).val());
		});
		//修改标题文字字体
		$("#titleFontFamily").unbind().on("change", function() {
			tempItem.find(".title").css("font-family", $(this).val());
		});
		//修改标题文字尺寸
		$("#titleFontSize").unbind().on("change", function() {
			if (parseInt($(this).val()) <= "11" || parseInt($("#detailFontSize").val()) <= "11") $(".label-set .group-warning").show();
			else $(".label-set .group-warning").hide();
			tempItem.find(".title").css("font-size", $(this).val());
			tempItem.find(".title").css("lineHeight", parseInt($(this).val()) * $("#titleLineHeight").val() + "px");
		});
		//修改标题文字行距
		$("#titleLineHeight").unbind().on("change", function() {
			tempItem.find(".title").css("lineHeight", $(this).val() * parseInt($("#titleFontSize").val()) + "px");
		});
		//标题文字是否加粗
		$("#titleFontWeight").unbind().on("click", function() {
			$(this).attr("checked") ? tempItem.find(".title").css("fontWeight", "700") : tempItem.find(".title").css("fontWeight", "400");
		});

		//========内容修改=========
		//修改内容对齐方式
		$("#detailAlign").unbind().on("change", function() {
			tempItem.css("textAlign", $(this).val());
		});
		//修改内容文字字体
		$("#detailFontFamily").unbind().on("change", function() {
			tempItem.find(".detail").css("font-family", $(this).val());
		});
		//修改内容文字尺寸
		$("#detaillFontSize").unbind().on("change", function() {
			if (parseInt($(this).val()) <= "11" || parseInt($("#titleFontSize").val()) <= "11") $(".label-set .group-warning").show();
			else $(".label-set .group-warning").hide();
			tempItem.find(".detail").css("font-size", $(this).val());
			tempItem.find(".detail").css("lineHeight", parseInt($(this).val()) * $("#detailLineHeight").val() + "px");
		});
		//修改内容文字行距
		$("#detailLineHeight").unbind().on("change", function() {
			tempItem.find(".detail").css("lineHeight", $(this).val() * parseInt($("#detaillFontSize").val()) + "px");
		});
		//内容文字是否加粗
		$("#detailFontWeight").unbind().on("click", function() {
			$(this).attr("checked") ? tempItem.find(".detail").css("fontWeight", "700") : tempItem.find(".detail").css("fontWeight", "400");
		});

		//========组合地址字段修改=======
		//修改显示字段格式
		$("#fieldset-address .multiple input[type='checkbox']").unbind().on("click", function() {
			var cName = $(this).val(),
				viewField = $(this).parents(".multiple").find("input[type='checkbox']:checked").length;
			if ($(this).attr("checked")) {
				tempItem.find(".detail").find("." + cName).css("display", "");
			} else {
				if (viewField == 0) {
					$.gritter.add({
						class_name: 'gritter-error',
						title: "操作错误",
						text: "至少需勾选一项",
						time: 2000,
						sticky: false
					});
					return false;
				} else {
					tempItem.find(".detail").find("." + cName).css("display", "none");
				}
			}
		});
		//修改显示字段格式2
		$("#fieldset-address-mode2 .multiple input[type='checkbox']").unbind().on("click", function() {
			var cName = $(this).val(),
				viewField = $(this).parents(".multiple").find("input[type='checkbox']:checked").length;
			if ($(this).attr("checked")) {
				tempItem.find(".detail").find("." + cName).css("display", "");
			} else {
				if (viewField == 0) {
					$.gritter.add({
						class_name: 'gritter-error',
						title: "操作错误",
						text: "至少需勾选一项",
						time: 2000,
						sticky: false
					});
					return false;
				} else {
					tempItem.find(".detail").find("." + cName).css("display", "none");
				}
			}
		});
		//是否显示中文国家名
		$("#fieldset-address .multiple input[name='viewField'][value='country']").on("click", function() {
			$(this).attr("checked") ? $("#fieldset-address .multiple input[name='viewField'][value='country_cn']").attr("disabled", false) : $("#fieldset-address .multiple input[name='viewField'][value='country_cn']").attr("disabled", true);
		});
		//单个字段是否整行显示
		$("#newline").unbind().on("click", function() {
			$(this).attr("checked") ? tempItem.find(".detail").addClass("block") : tempItem.find(".detail").removeClass("block");
		});
		
		//单个字段是否整行显示2
		$("#newline_mode2").unbind().on("click", function() {
			$(this).attr("checked") ? tempItem.find(".detail").addClass("block") : tempItem.find(".detail").removeClass("block");
		});

		//========自定义文字修改========
		//修改自定义文本
		$("#textDetail").unbind().on("keyup", function() {
			var newText = $(this).val().replace(/\n/gi, "<br>");
			tempItem.find(".detail").html(newText);
		});
		$("#textDetail").on("blur", function() {
			if ($(this).val().length <= 0) {
				$(this).val("自定义文本内容");
				tempItem.find(".detail").html("自定义文本内容");
			};
		});
		//文字对齐方式
		$("#textAlign").unbind().on("change", function() {
			tempItem.css("textAlign", $(this).val());
		});
		//修改自定义文本字体
		$("#textFontFamily").unbind().on("change", function() {
			tempItem.find(".detail").css("font-family", $(this).val());
		});
		//修改自定义文本文字尺寸
		$("#textFontSize").unbind().on("change", function() {
			parseInt($(this).val()) <= "11" ? $(".label-set .group-warning").show() : $(".label-set .group-warning").hide();
			tempItem.css("font-size", $(this).val());
			tempItem.css("lineHeight", parseInt($(this).val()) * $("#textLineHeight").val() + "px");
		});
		//修改自定义文本行距
		$("#textLineHeight").unbind().on("change", function() {
			tempItem.css("lineHeight", $(this).val() * parseInt($("#textFontSize").val()) + "px");
		});
		//自定义文本是否加粗
		$("#textFontWeight").unbind().on("click", function() {
			$(this).attr("checked") ? tempItem.find(".detail").css("fontWeight", "700") : tempItem.find(".detail").css("fontWeight", "400");
		});
		//否是显示复选框
		$("#textCheckBox").unbind().on("click", function() {
			if ($(this).attr("checked")) {
				tempItem.find("i").show();
				$(this).parents(".form-group").next(".form-group").show();
			} else {
				tempItem.find("i").hide();
				$(this).parents(".form-group").next(".form-group").hide();
			}
		});
		//修改复选框勾选状态
		$("#checkBoxType").unbind().on("change", function() {
			tempItem.find("i").attr("class", $(this).val());
		});

		//========边框修改=========
		//显示上边框
		$("#borderTop").unbind().on("click", function() {
			if ($(this).attr("checked")) {
				$(this).parents(".form-group").find("select.form-control").attr("disabled", false);
				tempItem.css("border-top-width", $("#paddingTop").val());
				if (parseInt($("#borderTopWidth").val()) >= 1) {
					tempItem.css("border-top-width", $("#borderTopWidth").val());
				} else {
					tempItem.css("border-top-width", "1px");
					$("#borderTopWidth").val("1px");
				}
			} else {
				$(this).parents(".form-group").find("select.form-control").attr("disabled", true);
				tempItem.css({
					"border-top-width": "0px",
					"padding-top": "0px"
				});
				$("#borderTopWidth, #paddingTop").val("0px");
			};
		});
		//上边框厚度
		$("#borderTopWidth").unbind().on("change", function() {
			tempItem.css("border-top-width", $(this).val());
		});
		//上边距
		$("#paddingTop").unbind().on("change", function() {
			tempItem.css("padding-top", $(this).val());
		});
		//显示下边框
		$("#borderBottom").unbind().on("click", function() {
			if ($(this).attr("checked")) {
				$(this).parents(".form-group").find("select.form-control").attr("disabled", false);
				tempItem.css("border-bottom-width", $("#paddingBottom").val());
				if (parseInt($("#borderBottomWidth").val()) >= 1) {
					tempItem.css("border-bottom-width", $("#borderBottomWidth").val());
				} else {
					tempItem.css("border-bottom-width", "1px");
					$("#borderBottomWidth").val("1px");
				}
			} else {
				$(this).parents(".form-group").find("select.form-control").attr("disabled", true);
				tempItem.css({
					"border-bottom-width": "0px",
					"padding-bottom": "0px"
				});
				$("#borderBottomWidth, #paddingBottom").val("0px");
			};
		});
		//下边框厚度
		$("#borderBottomWidth").unbind().on("change", function() {
			tempItem.css("border-bottom-width", $(this).val());
		});
		//下边距
		$("#paddingBottom").unbind().on("change", function() {
			tempItem.css("padding-bottom", $(this).val());
		});
		//显示左边框
		$("#borderLeft").unbind().on("click", function() {
			if ($(this).attr("checked")) {
				$(this).parents(".form-group").find("select.form-control").attr("disabled", false);
				tempItem.css("border-left-width", $("#paddingLeft").val());
				if (parseInt($("#borderLeftWidth").val()) >= 1) {
					tempItem.css("border-left-width", $("#borderLeftWidth").val());
				} else {
					tempItem.css("border-left-width", "1px");
					$("#borderLeftWidth").val("1px");
				}
			} else {
				$(this).parents(".form-group").find("select.form-control").attr("disabled", true);
				tempItem.css({
					"border-left-width": "0px",
					"padding-left": "0px"
				});
				$("#borderLeftWidth, #paddingLeft").val("0px");
			};
		});
		//左边框厚度
		$("#borderLeftWidth").unbind().on("change", function() {
			tempItem.css("border-left-width", $(this).val());
		});
		//左边距
		$("#paddingLeft").unbind().on("change", function() {
			tempItem.css("padding-left", $(this).val());
		});
		//显示右边框
		$("#borderRight").unbind().on("click", function() {
			if ($(this).attr("checked")) {
				$(this).parents(".form-group").find("select.form-control").attr("disabled", false);
				tempItem.css("border-right-width", $("#paddingRight").val());
				if (parseInt($("#borderRightWidth").val()) >= 1) {
					tempItem.css("border-right-width", $("#borderRightWidth").val());
				} else {
					tempItem.css("border-right-width", "1px");
					$("#borderRightWidth").val("1px");
				}
			} else {
				$(this).parents(".form-group").find("select.form-control").attr("disabled", true);
				tempItem.css({
					"border-right-width": "0px",
					"padding-right": "0px"
				});
				$("#borderRightWidth, #paddingRight").val("0px");
			};
		});
		//右边框厚度
		$("#borderRightWidth").unbind().on("change", function() {
			tempItem.css("border-right-width", $(this).val());
		});
		//右边距
		$("#paddingRight").unbind().on("change", function() {
			tempItem.css("padding-right", $(this).val());
		});

		//========水平线修改========
		//修改水平线宽度
		$("#xLineWidth").unbind().on("keyup", function() {
			tempItem.css("width", $(this).val() + "px");
		});

		$("#xLineWidth").on("blur", function() {
			var value = $(this).val(),
				leftWidth = parseInt(tempItem.css("left")),
				maxWidth = parseInt(tempItem.parent(".custom-drop").width()) - leftWidth;
			if (value <= 15) {
				tempItem.css("width", "15px");
				$(this).val("15");
			} else if (value >= maxWidth) {
				tempItem.css("width", maxWidth + "px");
				$(this).val(maxWidth);
			}
		});
		//设为100%宽度
		$("#setMaxWidth").unbind().on("click", function() {
			var maxWidth = tempItem.parent(".custom-drop").width();
			tempItem.css({
				"width": maxWidth,
				"left": "0px"
			});
			$("#xLineWidth").val(parseInt(maxWidth));
		});

		//水平线数字输入框组件
		$("#line-x .customnum .add").unbind().on("click", function() {
			var input = $("#xLineWidth"),
				value = parseInt(input.val()),
				newValue = value * 1 + 1,
				leftWidth = parseInt(tempItem.css("left")),
				maxWidth = parseInt(tempItem.parent(".custom-drop").width()) - leftWidth;
			if (value < maxWidth) {
				input.val(newValue);
				tempItem.css("width", newValue + "px");
			};
		});
		$("#line-x .customnum .subtract").unbind().on("click", function() {
			var input = $("#xLineWidth"),
				value = parseInt(input.val()),
				newValue = value * 1 - 1;
			if (value > 15) {
				input.val(newValue);
				tempItem.css("width", newValue + "px");
			}
		});
		//修改水平线样式
		$("#xLineStyle").unbind().on("change", function() {
			tempItem.css("border-top-style", $(this).val());
		});
		//修改水平线厚度
		$("#xLineWeight").unbind().on("change", function() {
			tempItem.css({
				"border-top-width": $(this).val(),
				"height": $(this).val()
			});
		});

		//========垂直线修改========
		//修改垂直线高度
		$("#yLineHeight").unbind().on("keyup", function() {
			tempItem.css("height", $(this).val() + "px");
		});
		$("#yLineHeight").on("blur", function() {
			var value = $(this).val(),
				topHeight = parseInt(tempItem.css("top")),
				maxHeigh = parseInt(tempItem.parent(".custom-drop").height()) - topHeight;
			if (value <= 15) {
				tempItem.css("height", "15px");
				$(this).val("15");
			} else if (value >= maxHeigh) {
				tempItem.css("height", maxHeigh + "px");
				$(this).val(maxHeigh);
			}
		});
		//设为100%宽度
		$("#setMaxHeight").unbind().on("click", function() {
			var maxHeight = tempItem.parent(".custom-drop").height();
			tempItem.css({
				"height": maxHeight,
				"top": "0px"
			});
			$("#yLineHeight").val(parseInt(maxHeight));
		});
		//垂直线数字输入框组件
		$("#line-y .customnum .add").unbind().on("click", function() {
			var input = $("#yLineHeight"),
				value = parseInt(input.val()),
				newValue = value * 1 + 1,
				topHeight = parseInt(tempItem.css("top")),
				maxHeigh = parseInt(tempItem.parent(".custom-drop").height()) - topHeight;
			if (value < maxHeigh) {
				input.val(newValue);
				tempItem.css("height", newValue + "px");
			};
		});
		$("#line-y .customnum .subtract").unbind().on("click", function() {
			var input = $("#yLineHeight"),
				value = parseInt(input.val()),
				newValue = value * 1 - 1;
			if (value > 15) {
				input.val(newValue);
				tempItem.css("height", newValue + "px");
			}
		});
		//修改水平线样式
		$("#yLineStyle").unbind().on("change", function() {
			tempItem.css("border-left-style", $(this).val());
		});
		//修改水平线厚度
		$("#yLineWeight").unbind().on("change", function() {
			tempItem.css({
				"border-left-width": $(this).val(),
				"width": $(this).val()
			});
		});

		//========圆边文字框修改========
		//修改圆边厚度
		$("#circleBorderWidth").unbind().on("change", function() {
			tempItem.css("border-width", $(this).val());
		});
		//修改圆框文本
		$("#circleText").unbind().on("keyup", function() {
			var newText = $(this).val();
			tempItem.find(".detail").text(newText);
		});
		$("#circleText").on("blur", function() {
			if ($(this).val().length <= 0) {
				$(this).val("A");
				tempItem.find(".detail").text("A");
			};
		});
		//修改圆框文本字体
		$("#circleFontFamily").unbind().on("change", function() {
			tempItem.find(".detail").css("font-family", $(this).val());
		});
		//修改圆框文本文字尺寸
		$("#circleFontSize").unbind().on("change", function() {
			parseInt($(this).val()) <= "11" ? $(".label-set .group-warning").show() : $(".label-set .group-warning").hide();
			tempItem.find(".detail").css("font-size", $(this).val());
		});
		//圆框文本是否加粗
		$("#circleFontWeight").unbind().on("click", function() {
			$(this).attr("checked") ? tempItem.find(".detail").css("fontWeight", "700") : tempItem.find(".detail").css("fontWeight", "400");
		});
		//条码前缀
		$("#codePrefix").unbind().on("keyup", function() {
			var newPrefix = $(this).val();
			tempItem.find(".prefix").text(newPrefix);
		});

		//========条码修改========
		//修改条码类别
		$("#codeType").unbind().on("change", function() {
			tempItem.attr("data-code-type", $(this).val());
		});
		//是否显示条码编码文字
		$("#viewCodeNum").unbind().on("click", function() {
			if ($(this).attr("checked")) {
				tempItem.find(".codemunber").show();
				$("#barcode .codenum-set").find("input,select").attr("disabled", false);
			} else {
				tempItem.find(".codemunber").hide();
				$("#barcode .codenum-set").find("input,select").attr("disabled", true);
			}
		});
		//修改条码编码文字对齐方式
		$("#codeNumAlign").unbind().on("change", function() {
			tempItem.find(".codemunber").css("textAlign", $(this).val());
		});
		//修改条码编码文字尺寸
		$("#codeNumFontSize").unbind().on("change", function() {
			parseInt($(this).val()) <= "11" ? $(".label-set .group-warning").show() : $(".label-set .group-warning").hide();
			tempItem.find(".codemunber").css("font-size", $(this).val());
		});
		//圆框文本是否加粗
		$("#codeNumFontWeight").unbind().on("click", function() {
			$(this).attr("checked") ? tempItem.find(".codemunber").css("fontWeight", "700") : tempItem.find(".codemunber").css("fontWeight", "400");
		});

		//========商品清单表格修改========
		//表格编号是否显示
		$("#viewTdBorder").unbind().on("click", function() {
			$(this).attr("checked") ? $(".label-content .skulist-table").removeClass("no-tdborder") : $(".label-content .skulist-table").addClass("no-tdborder");
		});
		$("#viewThead").unbind().on("click", function() {
			if ($(this).attr("checked")) {
				tempItem.find(".skulist-table thead").show();
				$(".copy-drop").find(".skulist-table thead").show();
				headHeight = tempItem.find(".skulist-table thead").height();
				tempItem.height(tempItem.height() + headHeight);
				$(this).parents(".form-group").next(".moreinfo").find("input,select").attr("disabled", false);
			} else {
				tempItem.find(".skulist-table thead").hide();
				$(".copy-drop").find(".skulist-table thead").hide();
				tempItem.height(tempItem.height() - headHeight);
				$(this).parents(".form-group").next(".moreinfo").find("input,select").attr("disabled", true);
				headHeight = 0;
			}
			countNum();
			return headHeight;
		});
		$("#viewTfoot").unbind().on("click", function() {
			if ($(this).attr("checked")) {
				tempItem.find(".skulist-table tfoot").show();
				$(".copy-drop").find(".skulist-table tfoot").show();
				footHeight = tempItem.find(".skulist-table tfoot").height();
				tempItem.height(tempItem.height() + footHeight);
				$(this).parents(".form-group").next(".moreinfo").find("input,select").attr("disabled", false);
			} else {
				tempItem.find(".skulist-table tfoot").hide();
				$(".copy-drop").find(".skulist-table tfoot").hide();
				footHeight = tempItem.find(".skulist-table tfoot").height();
				tempItem.height(tempItem.height() - footHeight);
				$(this).parents(".form-group").next(".moreinfo").find("input,select").attr("disabled", true);
				footHeight = 0;
			}
			countNum();
			return footHeight;
		});
		//修改表头字体
		$("#theadFontFamily").unbind().on("change", function() {
			$(".label-content .skulist-table thead").css("font-family", $(this).val());
		});
		//修改表头文字尺寸
		$("#theadFontSize").unbind().on("change", function() {
			if (parseInt($("#theadFontSize").val()) <= "11" || parseInt($("#tbodyFontSize").val()) <= "11" || parseInt($("#tfootFontSize").val()) <= "11") $(".label-set .group-warning").show();
			else $(".label-set .group-warning").hide();
			$(".label-content .skulist-table thead").css("font-size", $(this).val());
		});
		//修改内容字体
		$("#tbodyFontFamily").unbind().on("change", function() {
			$(".label-content .skulist-table tbody").css("font-family", $(this).val());
		});
		//修改内容文字尺寸
		$("#tbodyFontSize").unbind().on("change", function() {
			if (parseInt($("#theadFontSize").val()) <= "11" || parseInt($("#tbodyFontSize").val()) <= "11" || parseInt($("#tfootFontSize").val()) <= "11") $(".label-set .group-warning").show();
			else $(".label-set .group-warning").hide();
			$(".label-content .skulist-table tbody").css("font-size", $(this).val());
		});
		//修改脚注字体
		$("#tfootFontFamily").unbind().on("change", function() {
			$(".label-content .skulist-table tfoot").css("font-family", $(this).val());
		});
		//修改脚注文字尺寸
		$("#tfootFontSize").unbind().on("change", function() {
			if (parseInt($("#theadFontSize").val()) <= "11" || parseInt($("#tbodyFontSize").val()) <= "11" || parseInt($("#tfootFontSize").val()) <= "11") $(".label-set .group-warning").show();
			else $(".label-set .group-warning").hide();
			$(".label-content .skulist-table tfoot").css("font-size", $(this).val());
		});
		//修改脚注文字对齐方式
		$("#tfootAlign").unbind().on("change", function() {
			$(".label-content .skulist-table tfoot").css("textAlign", $(this).val());
		});

		//========商品清单字段修改=======
		//勾选显示字段可编辑显示文
		$("#fieldset-sku .checkbox-inline input[type='checkbox']").unbind().on("click", function() {
			var cName = $(this).val(),
				viewField = $("#fieldset-sku").find("input[type='checkbox']:checked").length;
			if ($(this).attr("checked")) {
				$(this).parents(".form-group").find("input.form-control").attr("disabled", false);
				$(".label-content .skulist-table").find("." + cName).removeClass("dis-none");
				$(".label-content .skulist-table tfoot td").attr("colspan", viewField);
			} else {
				if (viewField == 0) {
					$.gritter.add({
						class_name: 'gritter-error',
						title: "操作错误",
						text: "至少需勾选一项",
						time: 2000,
						sticky: false
					});
					return false;
				} else {
					$(this).parents(".form-group").find("input.form-control").attr("disabled", true);
					$(".label-content .skulist-table").find("." + cName).addClass("dis-none");
					$(".label-content .skulist-table tfoot td").attr("colspan", viewField);
				}
			}
		});

		//修改商品缩略图文本
		$("#fieldTextPhoto").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .photo span").text(newText);
		});
		//修改商品编号文本
		$("#fieldTextSku").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .sku span").text(newText);
		});
		//修改原厂编号文本
		$("#fieldTextOriginal").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .sku_original span").text(newText);
		});
		//修改itemID文本
		$("#fieldTextItemid").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .itemid span").text(newText);
		});
		//修改中文名称文本
		$("#fieldTextName").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .name span").text(newText);
		});
		//修改中文名称文本
		$("#fieldTextNameEn").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .name_en span").text(newText);
		});
		//修改标题名称文本
		$("#fieldTextProducttitle").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .product_title span").text(newText);
		});
		//修改申报品名文本
		$("#fieldTextNameDeclare").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .name_declare span").text(newText);
		});
		//修改仓库文本
		$("#fieldTextWarehouse").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .warehouse span").text(newText);
		});
		//修改仓位文本
		$("#fieldTextPosition").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .position span").text(newText);
		});
		//修改数量文本
		$("#fieldTextNumber").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .number span").text(newText);
		});
		//修改重量文本
		$("#fieldTextWeight").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .weight span").text(newText);
		});
		//修改多属性文本
		$("#fieldTextMultiproperty").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .multi-property span").text(newText);
		});
		//修改单价文本
		$("#fieldTextPrice").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .price span").text(newText);
		});
		//修改小计文本
		$("#fieldTextTotal").unbind().on("keyup", function() {
			var newText = $(this).val();
			$(".label-content .skulist-table thead .total span").text(newText);
		});

		//========报关物品字段修改=======
		//修改报关品名表头文字
		$("#declareNameTitle").unbind().on("keyup", function() {
			var newText = $(this).val().replace(/\n/gi, "<br>");
			tempItem.find(".skulist-table thead .name_declare span").html(newText);
		});
		//修改报关品名显示格式
		$("#declareType").unbind().on("change", function() {
			var type = $(this).val();
			$("#declareName").find("." + type).show().siblings(".multiple").hide();
			$(".label-content .skulist-table tbody").find("." + type).removeClass("dis-none").siblings(".name_declare").addClass("dis-none");
			type == "sort" ? $(".label-content.overflow").addClass("dis-none") : $(".label-content.overflow").removeClass("dis-none");
		});
		//修改商品目录内容格式
		$("#declareName .multiple input[type='checkbox']").unbind().on("click", function() {
			var cName = $(this).val(),
				viewField = $(this).parents(".multiple").find("input[type='checkbox']:checked").length;
			if ($(this).attr("checked")) {
				$(".label-content .skulist-table tbody .name_declare").find("." + cName).removeClass("dis-none");
			} else {
				if (viewField == 0) {
					$.gritter.add({
						class_name: 'gritter-error',
						title: "操作错误",
						text: "至少需勾选一项",
						time: 2000,
						sticky: false
					});
					return false;
				} else {
					$(".label-content .skulist-table tbody .name_declare").find("." + cName).addClass("dis-none");
				}
			}
		});
		//修改自定义文字内容
		$("#declareNameCustom").unbind().on("keyup", function() {
			var newText = $(this).val().replace(/\n/gi, "<br>");
			tempItem.find(".skulist-table tbody tr:first .name_declare.custom span").html(newText);
			$(".copy-drop .skulist-table tbody tr:first .name_declare.custom span").html(newText);
		});
		//修改报关重量表头文字
		$("#declareWeightTitle").unbind().on("keyup", function() {
			var newText = $(this).val().replace(/\n/gi, "<br>");
			tempItem.find(".skulist-table thead .weight_declare span").html(newText);
		});
		//修改报关价值表头文字
		$("#declarePriceTitle").unbind().on("keyup", function() {
			var newText = $(this).val().replace(/\n/gi, "<br>");
			tempItem.find(".skulist-table thead .price_declare span").html(newText);
		});
		//修改原产国表头文字
		$("#declareOriginTitle").unbind().on("keyup", function() {
			var newText = $(this).val().replace(/\n/gi, "<br>");
			tempItem.find(".skulist-table tfoot th.origin_declare span").html(newText);
		});
		//修改总重量表头文字
		$("#declareTotalWeightTitle").unbind().on("keyup", function() {
			var newText = $(this).val().replace(/\n/gi, "<br>");
			tempItem.find(".skulist-table tfoot th.weight_declare span").html(newText);
		});
		//修改总价值表头文字
		$("#declareTotalPriceTitle").unbind().on("keyup", function() {
			var newText = $(this).val().replace(/\n/gi, "<br>");
			tempItem.find(".skulist-table tfoot th.price_declare span").html(newText);
		});

		//========在线图片修改========
		//修改在线图片路径
		$("#loadImgUrl").unbind().on("click", function() {
			var ImgObj = new Image(); //判断图片是否存在  
			ImgObj.src = $("#imageUrl").val(); //没有图片，则返回-1
			tempItem.find("img").attr("src", $("#imageUrl").val());
		});

		//========编辑控制=======
		//复制该项属性
		$(".btn-copy:not('.disabled')").unbind().on("click", function() {
			var itemLeft = parseInt(tempItem.css("left")),
				itemTop = parseInt(tempItem.css("top")),
				newLeft = itemLeft + 20,
				newTop = itemTop + 20;
			tempItem.clone().css({
				"left": newLeft,
				"top": newTop
			}).appendTo(tempItem.parent(".custom-drop")).removeClass("active").find(".ui-resizable-handle").remove();
			dropCreate($(".custom-drop .dropitem:last"));
		});
		//删除该项属性
		$(".btn-clear-item").unbind().on("click", function() {
			var itemSort = tempItem.attr("data-title"),
				repeatSort = $(".label-type .dragitem.complete strong:contains('" + itemSort + "')").map(function() {
					if ($(this).text() == itemSort) {
						return this;
					}
				});
			repeatSort.parent(".dragitem").removeClass("complete");
			if (tempItem.hasClass("skulist") || tempItem.hasClass("declarelist")) {
				$(".label-content.overflow").remove();
				maxNum = 1;
			}
			tempItem.remove();
			closeLabelSet();
			$(".hotkey-panel").css({
				"opacity": "0",
				"z-index": "0"
			});
			return maxNum;
		});
	});

	function doSavePrintTemplate(ctrl, mapString) {
		var $form = $("form[name=frmPrintTemplate]");
		var $this = $(ctrl);
		$this.prop("disabled", true);
		//$('#divPrintTemplateHtml').find(".dropitem").removeClass("ui-draggable ui-resizable").find(".ui-resizable-handle").remove();
//		var labelHtml = $(".label-group").clone().find(".dropitem").removeClass("ui-draggable ui-resizable").end().find(".ui-resizable-handle").remove().end().html();
		
		var labelHtml = $(".label-group").clone().find(".dropitem").removeClass("ui-draggable ui-resizable").end().find(".ui-resizable-handle, .refer-tools, .refer-content").remove().end().html();
		
		$('#html').val(Base64.encode(labelHtml));
		$('#template_content_json').val(mapString);
		$.ajax({
			type: "POST",
			url: "/carrier/carriercustomtemplate/savecustomprint",
			data: $form.serialize(),
			dataType: 'json',
			success: function(r) {
				$('.loading_large').fadeOut();
				$this.prop("disabled", false);
				if (r.error) {
					$.gritter.add({
						class_name: 'gritter-error',
						title: "操作失败",
						text: r.message,
						time: 2000,
						sticky: false
					})
				} else {
					$("#id").val(r.data.template_id);
					$.gritter.add({
						class_name: 'gritter-success',
						title: "操作成功",
						text: '保存成功',
						time: 2000,
						sticky: false
					});
					//window.opener.location.reload(true);
//					window.opener.location.href = '/configuration/carrierconfig/carrier-custom-label-list/?tab_active=self';
				}
			}
		});
	}

	// 保存模版
	$("#save-data").unbind().on('click',function() {
		mapString = getHtmlToJson();

		doSavePrintTemplate(this, mapString);
	});
	
	//将Hmtl转为Json
	function getHtmlToJson(){
		var showArray = new Array();    //数组
		var int1 = 0;

		$('.custom-drop.ui-droppable').children().each(function(){
			//定义结构
			var myMap = {};
			
			//获取对应的类型
			myMap['data_type'] = $(this).attr('data-type');
			
			//获取对应的style属性值
			myMap['coordinate'] = $(this).attr('style');
			
			if(myMap['data_type'] == 'barcode'){
				myMap['barcode_height'] = $(this).height();
			}else if(myMap['data_type'] == 'skulist'){
				if($(this).find('#FULL_ITEMS_DETAIL_TABLE').hasClass('no-tdborder') == true)
					myMap['no_tdborder'] = 1;
				else
					myMap['no_tdborder'] = 0;
			}
			
			$(this).children().each(function(){
				var display = $(this).css('display');
				if(display == 'none') return ;
				if($(this).hasClass('ui-resizable-handle')) return ;
		
				if(myMap['data_type'] == 'skulist'){
					var thead = new Array();
					var tbody = new Array();
					var tfoot = new Array();
					
					var thead_style = '';
					var tbody_style = '';
					var tfoot_style = '';
					
					//获取thead数据
					$(this).find('thead').find('tr').each(function(){
						//如果隐藏了表头则直接跳过
						if($(this).parent().css("display") == "none"){
							return ;
						}
						
						if(thead_style == '')
							thead_style = $(this).parent().attr('style');
								
						var tmp_int = 0;
						$(this).find('th').each(function(){
							if($(this).hasClass('dis-none')){
								return ;
							}
							thead[tmp_int] = new Array($(this).width(), $(this).height(), $.trim($(this).text()));
							tmp_int++;
						});
					});
					
					var item_list_no_detail_int = 0;
					//获取tbody数据
					$(this).find('tbody').find('tr').each(function(){
						if(tbody_style == '')
							tbody_style = $(this).parent().attr('style');
						
						if($(this).attr('id') == 'ITEM_LIST_DETAIL'){
							var tmp_int = 0;
							$(this).find('td').each(function(){
								if($(this).hasClass('dis-none')){
									return ;
								}
								var tmp_id = new Array();
								var tmp_td_int = 0;
								$(this).find('littleboss').each(function(){
									if($(this).attr('id') == 'CUSTOMTEXT_ID'){
										tmp_id[tmp_td_int] = 'CUSTOMTEXT_ID:'+$(this).text();
									}else{
										tmp_id[tmp_td_int] = $(this).attr('id');
									}
									
									tmp_td_int++;
								});
								
								tbody[tmp_int] = new Array($(this).width(), $(this).height(), tmp_id);
								tmp_int++;
							});
						}else if($(this).attr('id') == 'ITEM_LIST_NO_DETAIL'){
							item_list_no_detail_int++;
						}
					});
					myMap['tfoot_no_detail'] = item_list_no_detail_int;
					
					//获取tfoot数据
					$(this).find('tfoot').find('tr').each(function(){
						if($(this).parent().css("display") == "none"){
							return ;
						}
						
						if(tfoot_style == '')
							tfoot_style = $(this).parent().attr('style');
						
						$(this).find('td').each(function(){
							var tmp_int = 0;
							
							myMap['tfoot_width'] = $(this).width();
							myMap['tfoot_height'] = $(this).height();
							
							$(this).find('span').each(function(){
								if($(this).hasClass('dis-none')){
									return ;
								}
								
								var span_left = $(this).offset().left;
	
								$(this).find('littleboss').each(function(){
									tfoot[tmp_int] = new Array($(this).attr('id'), $.trim($(this).parent().prev().text()), $.trim($(this).parent().next().text()), span_left);
									tmp_int++;
								});
							});
						});
					});
					
					myMap['thead'] = thead;
					myMap['tbody'] = tbody;
					myMap['tfoot'] = tfoot;
					
					myMap['thead_style'] = thead_style;
					myMap['tbody_style'] = tbody_style;
					myMap['tfoot_style'] = tfoot_style;
				}else if(myMap['data_type'] == 'barcode'){
					if($(this).prop("tagName") == 'LITTLEBOSS'){
						myMap['barcode_id'] = $(this).attr('id');
					}
					
					if($(this).attr('class') == 'codemunber'){
						myMap['codemunber'] = new Array('Y');
					}
				}else if((myMap['data_type'] == 'onlineimage') || (myMap['data_type'] == 'image')){
					myMap['img_url'] = $(this).attr('src');
				}else if(myMap['data_type'] == 'customtext'){
//					myMap['text'] = $(this).text();
					myMap['text'] = $(this).html();
					
					if($(this).attr('style') != undefined){
						myMap['style'] = $(this).attr('style');
					}
				}else{
					if($(this).hasClass('title')){
						myMap['text'] = $(this).text();
					};
					
					if($(this).hasClass('detail')){
						if($(this).attr('style') != undefined){
							myMap['style'] = $(this).attr('style');
						}
						
						var idArr = new Array();
						var int2 = 0;
						
						$(this).find('littleboss').each(function(){
							idArr[int2] = $(this).attr('id');
							
							int2++;
						});
						
						myMap['ids'] = idArr;
					};
				}
			});
			
			showArray[int1] = myMap;
			int1++;
		});

		var mapString=JSON.stringify(showArray);
//		console.log(mapString);
		return mapString;
	}
	
	// 打印预览
	$("#printPreview").on('click',function(){
		mapString = getHtmlToJson();
		
//		console.log(mapString);
		
		var $form = $('#toPreview'),
			$pform = $('form[name=frmPrintTemplate]');
//			labelHtml = $(".label-group").clone().find(".dropitem").removeClass("ui-draggable ui-resizable").end().find(".ui-resizable-handle").remove().end().html();
		$form.find('input[name=template_content]').val(mapString),
		$form.find('input[name=width]').val($pform.find('input[name=width]').val()),
		$form.find('input[name=height]').val($pform.find('input[name=height]').val());
		$form.submit();
	});

	// 根据单据类型显示不同字段
//	$.get('get-custom-menu',{
//		template_type:$("[name=template_type]").val()
//	},function(menu){
//		$("#type-group").find(".panel-default").hide();
//		$.each(menu,function(k,v){
//			var $group = $("#"+k).closest(".panel-default");
//			$group.show().find('[data-type]').hide();
//			$.each(v,function(k2,v2){
//				$group.find('[data-type='+k2+']').show();
//			});
//		});
//	});
	
	$("#save-sys-data").unbind().on('click',function(){
		doSysSavePrintTemplate(this);
	});
	
	function doSysSavePrintTemplate(ctrl) {
		var $form = $("form[name=frmPrintTemplate]");
		var $this = $(ctrl);
		$this.prop("disabled", true);
		var labelHtml = $(".label-group").clone().find(".dropitem").removeClass("ui-draggable ui-resizable").end().find(".ui-resizable-handle").remove().end().html();
		$('#html').val(Base64.encode(labelHtml));
		
		$.ajax({
			type: "POST",
			url: "/carrier/carriercustomtemplate/save-sys-template",
			data: $form.serialize(),
			dataType: 'json',
			success: function(r) {
				$('.loading_large').fadeOut();
				$this.prop("disabled", false);
				if (r.error) {
					$.gritter.add({
						class_name: 'gritter-error',
						title: "操作失败",
						text: r.message,
						time: 2000,
						sticky: false
					})
				} else {
//					alert("操作成功");
					$("#id").val(r.data.template_id);
					$.gritter.add({
						class_name: 'gritter-success',
						title: "操作成功",
						text: '保存成功',
						time: 2000,
						sticky: false
					});
//					window.opener.location.href = '/carrier/carriercustomtemplate/?tab_active=self';
				}
			}
		});
	}
	
	$("#lable_type").unbind().on("change", function() {
//		findShippingMethodLable();
	});
	
	$("#shipping_method_id").unbind().on("change", function() {
//		findShippingMethodLable();
		
		shipping_methodid = $("#shipping_method_id").val();
		
		if(shipping_methodid == ''){
			return false;
		}
		
		var tmp_carrier_code_sys = $("#shipping_method_id").find("option:selected").text();
		tmp_carrier_code_sys_arr = tmp_carrier_code_sys.split(":");
		$('input[name*=carrier_code_sys]').val(tmp_carrier_code_sys_arr[1]);
	});
	
	function findShippingMethodLable() {
		shipping_methodid = $("#shipping_method_id").val();
		lable_type = $("#lable_type").val();
		
		if((shipping_methodid == '') || (lable_type == '')){
			return false;
		}
		
		$('input[name*=name]').val($("#shipping_method_id").find("option:selected").text());
		
		$.ajax({
			type: "POST",
			url: "/carrier/carriercustomtemplate/get-carrier-template",
			data: {type : 'system',shipping_methodid : shipping_methodid, lable_type : lable_type},
			dataType: 'json',
			success: function(r) {
				if(r.error){
					//该判断主要是加载空模板
					$("#divPrintTemplateHtml").html(Base64.decode('PGRpdiBjbGFzcz0ibGFiZWwtY29udGVudCIgc3R5bGU9IndpZHRoOjk4bW07IGhlaWdodDo5OG1tOyI+DQo8ZGl2IGNsYXNzPSJ2aWV3LW1hc2siPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tYXJlYSB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tZHJvcCB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8L2Rpdj4='));
					$("#html").html('PGRpdiBjbGFzcz0ibGFiZWwtY29udGVudCIgc3R5bGU9IndpZHRoOjk4bW07IGhlaWdodDo5OG1tOyI+DQo8ZGl2IGNsYXNzPSJ2aWV3LW1hc2siPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tYXJlYSB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tZHJvcCB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8L2Rpdj4=');
				}else{
					$("#divPrintTemplateHtml").html(Base64.decode(r.template));
					$("#html").html(r.template);
					$('input[name*=country_sys]').val(r.country_codes)
				}
				$('script[src*=run]').remove();
				$("<script>").attr({ src: "/js/project/carrier/customprint/run.js"}).appendTo("head");
				
//				$("<link>").attr({ rel: "stylesheet",type: "text/css",href: "/css/carrier/bootstrap.min.css"}).appendTo("head");
//				$("<script>").attr({ src: "/js/project/carrier/customprint/jquery.min.js"}).appendTo("head");
			}
		});
	}
	
	$("#user_cr_template_id").unbind().on("change", function() {
		findUserCrTemplate();
	});
	
	function findUserCrTemplate() {
		user_cr_template_id = $("#user_cr_template_id").val();
		
		if(user_cr_template_id == ''){
			return false;
		}
		
		$.ajax({
			type: "POST",
			url: "/carrier/carriercustomtemplate/get-carrier-template",
			data: {type : 'user',user_cr_template_id : user_cr_template_id},
			dataType: 'json',
			success: function(r) {
				if(r.error){
					//该判断主要是加载空模板
					$("#divPrintTemplateHtml").html(Base64.decode('PGRpdiBjbGFzcz0ibGFiZWwtY29udGVudCIgc3R5bGU9IndpZHRoOjk4bW07IGhlaWdodDo5OG1tOyI+DQo8ZGl2IGNsYXNzPSJ2aWV3LW1hc2siPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tYXJlYSB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tZHJvcCB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8L2Rpdj4='));
					$("#html").html('PGRpdiBjbGFzcz0ibGFiZWwtY29udGVudCIgc3R5bGU9IndpZHRoOjk4bW07IGhlaWdodDo5OG1tOyI+DQo8ZGl2IGNsYXNzPSJ2aWV3LW1hc2siPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tYXJlYSB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tZHJvcCB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8L2Rpdj4=');
				}else{
					$("#divPrintTemplateHtml").html(Base64.decode(r.template));
					$("#html").html(r.template);
				}
				$('script[src*=run]').remove();
				$("<script>").attr({ src: "/js/project/carrier/customprint/run.js"}).appendTo("head");
				
			}
		});
		
		$("#user_cr_template_id").val('');
	}
	
	$("#sys_cr_template_id").unbind().on("change", function() {
		findSysCrTemplate();
	});
	
	function findSysCrTemplate() {
		template_id = $("#sys_cr_template_id").val();
		
		if(template_id == ''){
			return false;
		}
//		
////		$('input[name*=name]').val($("#shipping_method_id").find("option:selected").text());
//		
		$.ajax({
			type: "POST",
			url: "/carrier/carriercustomtemplate/get-carrier-template",
			data: {type : 'system2',template_id : template_id},
			dataType: 'json',
			success: function(r) {
				if(r.error){
					//该判断主要是加载空模板
					$("#divPrintTemplateHtml").html(Base64.decode('PGRpdiBjbGFzcz0ibGFiZWwtY29udGVudCIgc3R5bGU9IndpZHRoOjk4bW07IGhlaWdodDo5OG1tOyI+DQo8ZGl2IGNsYXNzPSJ2aWV3LW1hc2siPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tYXJlYSB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tZHJvcCB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8L2Rpdj4='));
					$("#html").html('PGRpdiBjbGFzcz0ibGFiZWwtY29udGVudCIgc3R5bGU9IndpZHRoOjk4bW07IGhlaWdodDo5OG1tOyI+DQo8ZGl2IGNsYXNzPSJ2aWV3LW1hc2siPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tYXJlYSB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8ZGl2IGNsYXNzPSJjdXN0b20tZHJvcCB1aS1kcm9wcGFibGUiPg0KPC9kaXY+DQo8L2Rpdj4=');
				}else{
					$("#divPrintTemplateHtml").html(Base64.decode(r.template));
					$("#html").html(r.template);
					$('input[name*=country_sys]').val(r.country_codes);
					$('input[name*=name]').val(r.template_name);
					$('input[name*=carrier_code_sys]').val(r.carrier_code);
					$("#sys_cr_template_open_close").val(r.is_use);
				}
				$('script[src*=run]').remove();
				$("<script>").attr({ src: "/js/project/carrier/customprint/run.js"}).appendTo("head");
			}
		});
	}
	
	//================参照图片===============
	//上传参考图
	$(".refer-upload i").unbind().on("click",function(){$(this).next().click()});
	$(".refer-upload input[type='file']").unbind().on("change",function(){
		if (window.File && window.FileList) {
			var $this = $(this);
			var	file=this.files[0];
			//文件尺寸过滤
			if (file.size >= 2048000) {
				$.gritter.add({
					class_name:'gritter-error',
					text: "图片容量过大，应小于2MB",
					time: 1500,
					sticky: false
				});
				return false;
        	};
        	//文件格式过滤
        	if(!/image\/\w+/.test(file.type)){
        		$.gritter.add({
					class_name:'gritter-error',
					text: "请上传图片格式文件",
					time: 1500,
					sticky: false
				});
				return false;
        	};
        	//创建参考图容器
			var reader = new FileReader(), htmlImage;
			reader.onload = function(e) {
                var newImage='<div class="refer-content"><span></span><img src="'+ e.target.result +'" /></div>';
                $(".label-content").first().append(newImage);
                $(".refer-upload").hide().siblings("a").show();
        	};
            reader.readAsDataURL(file);
			
		}else {
			$.gritter.add({
				class_name:'gritter-error',
				text: "抱歉，你的浏览器不支持FileAPI，请升级浏览器！",
				time: 2000,
				sticky: false
			});
			return false;
		}
	});
	
	//移除参考图
	$(".refer-remove").unbind().on("click",function(){
		bootbox.dialog({
			//backdrop: "static",
			message: "确定删除参考图？",
			buttons: {
				success: {
					label: "确定",
					className: "btn-primary",
					callback: function () {
						$(".refer-content").remove();
						$(".refer-upload").show().children("input[type='file']").val("").end().siblings("a").hide();
					}
				},
				cancel: {
					label: "取消",
					className: "btn-default",
					callback: function () {
						return null;
					}
				}
				
			}
		});
	});
	
	//切换显示位置
	$(".refer-switch").unbind().on("click",function(){
		$(".refer-content").toggleClass("multiply");
	});
	
	//切换是否显示
	$(".refer-view").unbind().on("click",function(){
		if($(".refer-content").hasClass("dis-none")){
			$(".refer-content").removeClass("dis-none");
			$(this).children("i").attr("class","ico-eye-close text-muted");
		}else{
			$(".refer-content").addClass("dis-none");
			$(this).children("i").attr("class","ico-eye-open");
		};
	});
	
});
