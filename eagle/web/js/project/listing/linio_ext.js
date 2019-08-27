function Linio_ext(args){
	var $this = this;
	this.uid = args.uid == undefined ? '' : args.uid;
	this.currentCateId = 0;
	this.catePid = 0;
	this.childCateListUrl = args.cateTreeUrl; 
	this.level = 1;
	this.maxLevel = args.maxLevel;
	this.childCateList = [];
	this.searchCateList = [];
	this.searchCateListUrl = args.searchUrl;
	this.cateAttrUrl = args.cateAttrUrl;
	this.sizeList = ['none'];
	this.colorList = ['none'];
	this.editProductData = {};
	this.varianceData = [];
	this.cateAttrGroup = {
		commonCategoryAttr:["ConditionType","Name","Brand","Model","TaxClass","ColorFamily","ProductionCountry",],
		hideCategoryAttr: ["Gender","Note","ProductIdOld","ShipmentType","BrowseNodes","PublishedDate","MaterialFamily","MaxDeliveryTime","MinDeliveryTime"],
		varianceAttr: ["Variation","Color","SellerSku","ProductGroup","Quantity","ProductId","Price","SalePrice","SaleStartDate","SaleEndDate"],
		describAttr: ["Description","ShortDescription","PackageContent"],
		shipingAttr: ["ProductMeasures","ProductWeight","PackageLength","PackageWidth","PackageHeight","PackageWeight","DeliveryTimeSupplier","EligibleFreeShipping"],
		warrantyAttr: ["ProductWarranty","SupplierWarrantyMonths","ResponsibleForWarranty",'Certifications'],
		showCategoryAttr: ["Name","Brand","Model","Taxes","Variation","Color","SellerSku","ProductGroup","Quantity","ProductId","Price","SalePrice","SaleStartDate","SaleEndDate","Description","ShortDescription","PackageContent","ProductMeasures","ProductWeight","PackageLength","PackageWidth","PackageHeight","PackageWeight","Categories","PrimaryCategory","ParentSku","NameMs","DescriptionMs","ProductWarranty"]
	};
	this.varianceTr = [
		'<tr>',
		'<td style="width:90px;" data-id="sku" data-name="variation" data-type="input"><input type="text" class="iv-input" placeholder="..." style="width:120px;"></td>',
		'<td data-name="sellerSku"><input type="text" class="iv-input"></td>',
		'<td data-name="productGroup" name="EAN"><input type="text" class="iv-input"></td>',
		'<td data-name="quantity" style="width:50px"><input type="text" class="iv-input" style="width:80px;" onkeyup="value=value.replace(/[^0-9]/g,\'\')"></td>',
		'<td data-name="price" style="width:50px;"><input type="text" class="iv-input" style="width:80px;" onkeyup="value=value.replace(/[^0-9.]/g,\'\')"></td>',
		'<td data-name="salePrice" style="width:90px;"><input type="text" class="iv-input" style="width:80px;" onkeyup="value=value.replace(/[^0-9.]/g,\'\')"></td>',
		'<td data-name="saleDate" class="text-center"><div class="date-box"><input type="text" class="iv-input Wdate" date="start" data-name="saleStartDate" style="padding-left:30px;width:120px;"><span class="iconfont icon-riqikaishi"></span></div><p>至</p><div class="date-box"><input type="text" class="iv-input Wdate" date="end" data-name="saleEndDate" style="padding-left:30px;width:120px;"><span class="iconfont icon-riqijieshu"></span></div></td>',
		'<td class="borderNo" data-name="no"><a cid="remove"><span class="iconfont icon-guanbi"></span></a></td>',
		'</tr>'
	];
	this.varianceTrTemplate = [
		'<tr trId="{trId}">',
		'<td data-name="color" data-val="{colorId}">{color}</td>',
		'<td data-name="variation" data-val="{sizeId}">{size}</td>',
		'<td data-name="sellerSku"><input type="text" class="iv-input"></td>',
		'<td data-name="productGroup" name="EAN"><input type="text" class="iv-input"></td>',
		'<td data-name="quantity" style="width:50px"><input type="text" class="iv-input" style="width:80px;" onkeyup="value=value.replace(/[^0-9]/g,\'\')"></td>',
		'<td data-name="price" style="width:50px;"><input type="text" class="iv-input" style="width:80px;" onkeyup="value=value.replace(/[^0-9.]/g,\'\')"></td>',
		'<td data-name="salePrice" style="width:90px;"><input type="text" class="iv-input" style="width:80px;" onkeyup="value=value.replace(/[^0-9.]/g,\'\')"></td>',
		'<td data-name="saleDate" class="text-center"><div class="date-box"><input type="text" class="iv-input Wdate" date="start" data-name="saleStartDate" style="padding-left:30px;width:120px;"><span class="iconfont icon-riqikaishi"></span></div><p>至</p><div class="date-box"><input type="text" class="iv-input Wdate" date="end" data-name="saleEndDate" style="padding-left:30px;width:120px;"><span class="iconfont icon-riqijieshu"></span></div></td>',
		'<td class="borderNo" data-name="no"><a cid="remove"><span class="iconfont icon-guanbi"></span></a></td>',
		'</tr>'
	];
	this.categoryAttrsTemplate = {
		dropdown:['<div data-name="Property" cid="{name}" name="{name}" class="mTop10" isMust="{Mandatory}" attrtype="select">',
			'<label>{title}{sign}</label>','<div class="input-spacing">','<select class="iv-input input-width" cid="{name}">{options}</select>','</div>','</div>'],
		textfield:['<div data-name="Property" cid="{name}" name="{name}" class="mTop10" isMust="{Mandatory}" attrtype="input">','<label>{title}{sign}</label>','<div class="input-spacing">','<input type="text" class="iv-input input-width">','</div>','</div>'],
		numberfield:['<div data-name="Property" cid="{name}" name="{name}" class="mTop10" isMust="{Mandatory}" attrtype="input">','<label>{title}{sign}</label>','<div class="input-spacing">','<input type="text" class="iv-input input-width" onkeyup="value=value.replace(/[^0-9]/g,\'\')">','</div>','</div>'],
		textarea: ['<div data-name="Property" cid="{name}" name="{name}" class="mTop10" attrtype="kindeditor" isMust="{Mandatory}">','<label>{title}</label>','<div class="input-spacing">','<textarea id="{name}" name="content" class="iv-editor" style="width:850px;height:360px;"></textarea>','</div>','</div>']
	};
	this.productImageLibTemplate = [
		'<div class="productImageLib" data-name="{colorId}">',
		'<label>{colorName}图片信息</label>',
		'<div class="image-box">',
		'<div class="productImageBox">',
		'<div class="productImage"></div>',
		'<label style="color:#333333;">小提示:</label>',
		'<font style="display: inline-block;margin-left:10px;vertical-align: top;line-height: 15px;color:#949494;">产品图片要求<br>(1)图片图片纯白色背景，不能有倒影 、不能有文字 、不能有水印 (2)产品要占图片<i class="fRed">80%</i>的部分 (3)图片像素最小<i class="fRed">500x500</i>，最大<i class="fRed">2000x2000</i><br>(4)不可以在同一张图有几个产品,不可以多个角度合成的拼图</font>',
		'</div>',
		'<div>',
		'<div class="productDescribeImageBox">',
		'<div class="productDescribeImage"></div>',
		'<label style="color:#333333;">小提示:</label>',
		'<font style="display: inline-block;margin-left:10px;vertical-align: top;line-height: 15px;color:#949494;">此处可以上传的产品描述图片。此处的图片将被插入到Product Description的最后，没有图片则不会插入。</font>',
		'</div>',
		'</div>',
		'</div>'
		];
	this.PushProductDataProperties = ["Brand","Description","Name","Price","PrimaryCategory","SellerSku","TaxClass","Categories","Condition","CountryCity","ParentSku","ProductGroup","ProductId","Quantity","SaleEndDate","SalePrice","SaleStarDate","ShipmentType","Status","Variation","BrowseNodes"];
	this.ErrorTipTemplate = ['<p class="error-tips input-width mBottom10" data-type="tip">','<span class="fRed iconfont icon-cuowu">','</span>',' {message}','</p>'];

	this.init = function(){
		$('.categoryModalShow').on('click',function(){
			if($this.uid == ''){
				$('#select_shop_info').html('<span class="iconfont icon-cuowu fRed pRight15"></span>请选择Linio店铺!').show();
			}else{
				var html = $('#category-modal').html();
				var title ='选择产品目录';
				$.showModal(html,title,undefined,'',{},{}).then(function($modal){
					$modal.on('click','li[role="presentation"]',function(){
						var self = $(this);		
						var leaf = self.data('leaf');
						if(leaf){
							var isEnd = false;
							setTimeout(function(){
								!isEnd && $.showLoading();
							},1000);
							$this.catePid = self.data('cateid');
							$this.level = parseInt(self.data('level')) + 1;
							$this.getChildCategoryList().done(function(data){
								isEnd = true;
								$this.childCateList = data.message;
								$this.ajaxAppend();
								$.hideLoading();
							});	
						}else{
							$this.hideCategoryBox($this.level+1);
						}
						self.parent().find('li[role=presentation]').removeClass('active');
						self.addClass('active');
						(leaf == '1') ? $('.category-ensure').attr('disabled','true').addClass('not'): $('.category-ensure').removeAttr('disabled').removeClass('not');
						$('.nav[data-level="'+ $this.level +'"]').show();
						$this.showCategoryName(self);
					});

					$modal.on('click','.search_btn',function(){
						var cateName = $modal.find('.search_input').val();	
						$this.getSearchCategoryList(cateName).done(function(data){
							$this.searchCateList = data.message;
							$this.showCategoryList();
						});
					});
					$modal.on('keyup','.search_input',function(e){
						e = e || window.event;
						var $this = $(this);
						(e.which == '13') && $('.search_btn').trigger('click');
					});

					$modal.on('click','.cate_search_ul li',function(){
						var self = $(this);
						var cateids = self.find('a').data('cateids').split(',');
						var name = self.find('a').data('names').split(',');
						console.log(cateids,name);
						$('span.category_content[data-level]').html('').removeAttr('data-name').removeAttr('data-cateid');
						for(var i=0;i< cateids.length;i++){
							(i== 0)? name[i] : name[i] = ' > ' + name[i];
							$('span.category_content[data-level="'+ (i+1) +'"]').html(name[i]).attr('data-name',name[i].replace(/[\s\>\s]+/g,'')).attr('data-cateid',cateids[i]);
						}
						$modal.find('.category-ensure').removeAttr('disabled').removeClass('not');
					});
					$modal.on('click','.cancel_cate_search',function(){
						$(this).closest('.cate_list').hide();
					});
					$modal.find('a.category-ensure').on('click',function(){
						var name = '';
						var cateid = 0;
						var category_name = '';
						var cateids = '';
						$modal.find('span.category_content').each(function(){
							var self = $(this);
							(self.data('name') != undefined) && (name = $.trim(self.data('name'))) && (category_name += self.data('name') + ' > ');
							(self.data('cateid') != undefined) && (cateid = self.data('cateid')) && (cateids += self.data('cateid') + ',');
						});
						$this.hideChildCategoryName(1);
						$this.hideCategoryBox(2);
						$this.level = 1;
						$this.catePid = 0;
						$('.cate_list').hide();
						$('.category').html(category_name.slice(0,-3)).attr('data-ids',cateids.slice(0,-1));
						$('<option value="'+ cateid +'" selected>'+ name +'</option>').appendTo($('#categoryHistoryId'));
						$('#categoryHistoryId').trigger('change');
						$('#variance_tab').show();
					});
					$modal.find('a.modal-close').on('click',function(){
						$this.hideChildCategoryName(1);
						$this.hideCategoryBox(2);
						$this.level = 1;
						$this.catePid = 0;
					});

				});	
			}
		});
		$('select[name="lazadaUid"]').on('change',function(){
			var uid = $(this).val();
			$this.uid = uid;
			$('#base-info .lzdAttrShow').html('');
			$('#warranty-info .lzdWarrantyInfo')
			$('#shipping-info .lzdShipingAttr').html('');
			if(uid != ''){
				$('#select_shop_info').html('').hide();
				$this.getChildCategoryList().done(function(data){
					if(data.code == 200){
						$this.childCateList = data.message;
						$this.ajaxAppend();
					}else{
						$.message('获取目录失败','warn');
					}
				});
			}

			
		});
		$('select[name="categoryHistoryId"]').on('change',function(){
			var cateid = $(this).val();
			if(cateid != ''){
				$('#select_info').html('').hide();
				$this.currentCateId = cateid;
				$this.getCategoryAttrs().done(function(data){
					if(data.code == 200){
						$this.showCategoryAttrs(data.data);
					}else{
						$.message('获取产品类目属性失败','warn');
					}
				});
				// $('#variance_tab').show();
			}

		});
		// $('a[cid="addOneSku"]').on('click',function(){
		// 	$this.addOneVariance();
		// });

		//标题首大写转换
		$('.productTitTextSize').click(function(){
			var str = $(this).closest('div').find('input[type="text"]').val();
			var newStr = str.replace(/\s[a-z]/g,function($1){return $1.toLocaleUpperCase()}).replace(/^[a-z]/,function($1){return $1.toLocaleUpperCase()}).replace(/\sOr[^a-zA-Z]|\sAnd[^a-zA-Z]|\sOf[^a-zA-Z]|\sAbout[^a-zA-Z]|\sFor[^a-zA-Z]|\sWith[^a-zA-Z]|\sOn[^a-zA-Z]/g,function($1){return $1.toLowerCase()});
			$(this).closest('div').find('input[type="text"]').val(newStr);
		});

		$('div[cid="Brand"] input').autocomplete({
					source: function(request,response){
						$.ajax({
						   type: "post",
						   url: "/listing/linio-listing/get-brands",
						   data: { lazada_uid : $("#lazadaUid").val(),name : request.term},
						   dataType:'json',
						}).done(function(data){
							function cmp(a, b) {
							    if (a > b) { return 1; }
							    if (a < b) { return -1; }
							    return 0;
									}
							function by(keyword) {
								keyword = keyword.toLowerCase();
							    return function(a, b) {
							    	a = a.toLowerCase();
							    	b = b.toLowerCase();
							        var i = a.indexOf(keyword);
							        var j = b.indexOf(keyword);
							        if (i === 0) {
							            if (j === 0) {
							                return cmp(a, b);
							            }
							            else {
							                return -1;
							            }
							        }
							        else {
							            if (j === 0) {
							                return 1;
							            }
							            return cmp(a, b);
							        }
							    };
							};
							
							if(data.code == 200){
								data.data.sort(by(request.term));
								response(data.data);
							}else{
								console.log(data.message);
							}
						});
					}

				});

		$('#variance_tab').on('click','a[cid="remove"]',function(){
			$(this).closest('tr').remove();
		});

		$(document).on('mouseenter', ".Wdate", function() {
			$(this).datepicker({
				dateFormat: 'yy-mm-dd'// 2015-11-11
			});
		});


		$('a.click-btn').on('modal.ready',function(e,$modal){
			$modal.find('a[data-name="sku"]').on('click',function(){
				var start = $modal.find('input[data-names="start"]').val();
				var end = $modal.find('input[data-names="end"]').val();
				start = (start != '') ? start : '';
				end = (end != '') ? end : '';
				var ExistVariation = [];
				var checkIsAllRight = true;
				$('#variance_tab tbody').find('tr').each(function(){
					var color= $(this).find('td[data-name="color"]').data('val').toLowerCase().replace(/\s/g,'_');
					var size = $(this).find('td[data-name="variation"]').data('val').toLowerCase().replace(/\s/g,'_');
					var sku = '';
					color != 'none'? (size != 'none' ? (sku = start + '-' + color + '-' + size + '-' + end) : (sku = start + '-' + color + '-' + end)) : (size != 'none' ? (sku = start + '-' + size + '-' + end) : (sku = start + '-' + end));
					$(this).find('td[data-name="sellerSku"] input').val(sku);
				});
				$modal.close();
			});
			$modal.find('a[data-name="quantity"]').on('click',function(){
				var type = $modal.find('input[data-type]:checked').data('type');
				var mod_val = $.trim($modal.find('input[data-show="'+ type +'"]').val());
				if(type == 'mod'){
					if(mod_val != '' && parseInt(mod_val) > 0){
						$('#variance_tab tr').find('td[data-name="quantity"] input').each(function(){
							$(this).val(mod_val);
						});
					}else{
						$.message('修改库存不能小于0','warn');
					}
				}else{
					if(mod_val != ''){
						$('#variance_tab tr').find('td[data-name="quantity"] input').each(function(){
							var current_val = $(this).val();
							if(current_val == ''){
								$(this).val(mod_val);
							}else{
								current_val = parseInt(mod_val) + parseInt(current_val);
								(current_val > 0) ? $(this).val(current_val) : $(this).val(1);
							}
						});
					}
				}
				$modal.close();
			});
			$modal.find('a[data-name="price"]').on('click',function(){
				var type = $modal.find('input[data-type]:checked').data('type');
				var mod_val = parseFloat($modal.find('input[data-show="'+ type +'"]').val()).toFixed(2);
				if(type == "mod"){
					if(mod_val != '' && mod_val > 0){
						$('#variance_tab tr').find('td[data-name="price"] input').each(function(){
							$(this).val(mod_val);
						});
					}else{
						$.message('修改价格必须大于0','warn');
					}
				}else{
					var mod_type = $modal.find('select[data-show="'+ type +'"]').val();
					if(mod_val != ''){
						$('#variance_tab tr').find('td[data-name="price"] input').each(function(){
							var current_val = $(this).val();
							if(current_val != ''){
								if(mod_type == 'mon'){
									current_val = (parseFloat(mod_val) + parseFloat(current_val)).toFixed(2);
									// console.log(mod_val);
									// console.log(current_val);
									// console.log(mod_val + current_val);
									$(this).val(current_val);
								}else{
									current_val = ((1 + (mod_val/100)) * parseFloat(current_val)).toFixed(2);
									(current_val > 0) && $(this).val(current_val);
								}	
							}
						});
					}
				}
				$modal.close();
			});
			$modal.find('a[data-name="sale_price"]').on('click',function(){
				var type = $modal.find('input[data-type]:checked').data('type');
				var mod_val = parseFloat($modal.find('input[data-show="'+ type +'"]').val()).toFixed(2);
				if(type == "mod"){
					if(mod_val != '' && mod_val > 0){
						$('#variance_tab tr').find('td[data-name="salePrice"] input').each(function(){
							$(this).val(mod_val);
						});
					}else{
						$.message('修改价格必须大于0','warn');
					}
				}else{
					var mod_type = $modal.find('select[data-show="'+ type +'"]').val();
					if(mod_val != ''){
						$('#variance_tab tr').find('td[data-name="salePrice"] input').each(function(){
							var current_val = $(this).val();
							if(current_val != ''){
								if(mod_type == 'mon'){
									current_val = (parseFloat(mod_val) + parseFloat(current_val)).toFixed(2);
									// console.log(mod_val);
									// console.log(current_val);
									// console.log(mod_val + current_val);
									$(this).val(current_val);
								}else{
									current_val = ((1 + (mod_val/100)) * parseFloat(current_val)).toFixed(2);
									(current_val > 0) && $(this).val(current_val);
								}	
							}

						});
					}
				}
				$modal.close();
			});
			$modal.find('a[data-name="date"]').on('click',function(){
				var startDate = $modal.find('input[data-name="saleStartDate"]').val();
				var endDate = $modal.find('input[data-name="saleEndDate"]').val();
				var startDateObj = new Date(startDate);
				var endDateObj = new Date(endDate);
				var NowObj = new Date();
				if((startDate == '') || (endDate == '')){
					$modal.find('#batchDateTips').html('<span class="iconfont icon-cuowu fRed pRight10"></span>请填写完整的促销时间').show();
					return;
				}
				if(startDateObj.getTime() > endDateObj.getTime()){
					$modal.find('#batchDateTips').html('<span class="iconfont icon-cuowu fRed pRight10"></span>结束时间不能小于开始时间').show();
					return;
				}
				if((NowObj.getTime() - startDateObj.getTime()) >= 86400){
					startDate = NowObj.getFullYear();
					var month = parseInt(NowObj.getMonth())+1;
					( month >= 10) ? (startDate += '-' + month) : (startDate += '-0' + month);
					startDate += '-' + NowObj.getDate();
				}
				$('#variance_tab tr').find('td[data-name="saleDate"]').each(function(){
					var self = $(this);
					self.find('input[data-name="saleStartDate"]').val(startDate);
					self.find('input[data-name="saleEndDate"]').val(endDate);
				});
				$modal.close();
			});
			$modal.find('input[data-type]').on('click',function(){
				$modal.find('[data-show]').attr({'disabled':'true'});
				$modal.find('[data-show="'+ $(this).attr('data-type') +'"]').removeAttr('disabled');
			});
		});
	
		$(document).find('.new-radio').each(function(){
			$(this).wrap('<div class="new-radio"></div>').after('<label></label>');
		});
		$(document).find('.Wdate').each(function(){
			$(this).wrap('<div class="date-box"></div>')
			$(this).attr('date')== 'start' ? $(this).after('<span class="iconfont icon-riqikaishi"></span>')  : $(this).attr('date') == 'end' ? $(this).after('<span class="iconfont icon-riqijieshu"></span>') : '';	
		});

		$('#good_colors').on('click','input[type="checkbox"][name="color"]',function(event){
			var  target = event.target || event.srcElement,
			colorId = $(target).val();
			if(!!target.checked){
				if(colorId.toLowerCase() == 'none'){
					$('#good_colors input[type="checkbox"]').each(function(){
						$(this).prop('checked',false);
					});
					$this.colorList.length = 0;
					$this.colorList.push('none');
					$('#good_colors input[value="none"]').prop('checked',true);
				}else{
					($this.colorList.length == 1 && $this.colorList[0] == 'none') && $('#good_colors input[value="none"]').click();
					$this.colorList.push(colorId);
				}
			}else{
				for(var i=0; i< $this.colorList.length; i++){
					if(colorId.toLowerCase() == $this.colorList[i].toLowerCase()){
						$this.colorList.splice(i,1);
					}
				}
				($this.colorList.length == 0) && $('#good_colors input[value="none"]').click();
			}
			var data = {
				color:colorId,
				product_imgs:[],
				product_describ_imgs:[]
			}
			$this.ImageAppearByColor();
			$this.addOneVariation();
		});

		$('.colorAdd').on('click',function(){
			var otherColor = $('#otherColor').val().replace(/(\w)/,function(v){return v.toUpperCase()});
			var index = -1;
			var colorList = []; //页面颜色列表
			$('#good_colors input[type="checkbox"]').each(function(){
				colorList.push($(this).val());	
			}); 

			for(var i=0; i < colorList.length; i++){
				if(colorList[i].toLowerCase() == otherColor.toLowerCase()){
					($this.colorList.length == 1 && $this.colorList[0] == 'none') && $('#good_colors input[value="none"]').click();
					$this.colorList.push(otherColor);	
					$('#good_colors input[type="checkbox"][value="'+ colorList[i] +'"]').prop('checked',true);
					$('#otherColor').val('');
				}
			}
			for(var i= 0;  i< $this.colorList.length; i++){
				if($this.colorList[i].toLowerCase() == otherColor.toLowerCase()){
					index = i;
				}
			}
			if(index == -1){
				($this.colorList.length == 1 && $this.colorList[0] == 'none') &&  $('#good_colors input[value="none"]').click();
				$this.colorList.push(otherColor);
				$('#good_colors ul').append('<li><label title="'+ otherColor +'"><input type="checkbox" name="color" value="'+ otherColor +'" checked>'+ otherColor +'</label></li>');
				$('#otherColor').val('');
			}
			$this.LoadImgTmpl();
			$this.addOneVariation();
		});

		$('#good_sizes').on('click','input[type="checkbox"][name="size"]',function(event){
			var  target = event.target || event.srcElement,
			sizeId = $(target).val();
			if(!!target.checked){
				if(sizeId == 'none'){
					$('#good_sizes input[type="checkbox"]').each(function(){
						$(this).prop('checked',false);
					});
					$this.sizeList.length = 0;
					$this.sizeList.push('none');
					$('#good_sizes input[value="none"]').prop('checked',true);
				}else{
					($this.sizeList.length == 1 && $this.sizeList[0] == 'none') && $('#good_sizes input[value="none"]').click();
					$this.sizeList.push(sizeId);
				}
			}else{
				for(var i=0,len=$this.sizeList.length;i< len;i++){
					if(sizeId.toLowerCase() == $this.sizeList[i].toLowerCase()){
						$this.sizeList.splice(i,1);
					}
				}
				($this.sizeList.length == 0) && $('#good_sizes input[value="none"]').click();
			}
			$this.addOneVariation();
		});

		$('.sizeAdd').on('click',function(){
			var otherSize = $('#otherSize').val().replace(/(\w)/,function(v){return v.toUpperCase()});
			var index = -1;
			var sizeList = [];
			$('#good_sizes input[type="checkbox"]').each(function(){
				sizeList.push($(this).val());	
			});

			for(var i=0; i < sizeList.length; i++){
				if(sizeList[i].toLowerCase() == otherSize.toLowerCase()){
					($this.sizeList.length == 1 && $this.sizeList[0] == 'none') && $('#good_sizes input[value="none"]').click();
					$this.sizeList.push(otherSize);	
					$('#good_sizes input[type="checkbox"][value="'+ sizeList[i] +'"]').prop('checked',true);
					$('#otherSize').val('');
				}
			}
			for(var i= 0;  i< $this.sizeList.length; i++){
				if($this.sizeList[i].toLowerCase() == otherSize.toLowerCase()){
					index = i;
				}
			}
			if(index == -1){
				($this.sizeList.length == 1 && $this.sizeList[0] == 'none') && $('#good_sizes input[value="none"]').click();
				$this.sizeList.push(otherSize);	
				$('#good_sizes ul').append('<li><label title="'+ otherSize +'"><input type="checkbox" name="size" value="'+ otherSize +'" checked>'+ otherSize +'</label></li>');
				$('#otherColor').val('');
			}
			$this.addOneVariation();
		});

		//图片插件加载
		$this.LoadImgTmpl('none',{productImage:[],productDescribeImage:[]});
		

		$('.save').on('click',function(){
			$this.saveProduct('save');
		});

		$('.push').on('click',function(){
			$this.saveProduct('push');
		});
	},
	this.addOneVariation = function(){
		$this.storeVarianceData();	
		$('#variance_tab tbody').html('');
		if($this.colorList.length){
			for(var i=0;i<$this.colorList.length;i++){
				if($this.sizeList.length){
					for(var j=0;j<$this.sizeList.length;j++){
						var $Tr = $this.varianceTrTemplate.join('').format({
							trId: 'tr-'+$this.colorList[i] + '-' + $this.sizeList[j],
							color: $this.colorList[i] == 'none' ? '无颜色': $this.colorList[i],
							size: $this.sizeList[j] == 'none' ? '无尺寸' : $this.sizeList[j],
							colorId: $this.colorList[i],
							sizeId: $this.sizeList[j]
						});

						$('#variance_tab tbody').append($Tr);
					}
				}else{
					var $Tr = $this.varianceTrTemplate.join('').format({
						trId: 'tr-'+$this.colorList[i],
						color: $this.colorList[i] == 'none' ? '无颜色' : $this.colorList[i],
						size: '',
						colorId: $this.colorList[i],
						sizeId: 'none'
					});	
					$('#variance_tab tbody').append($Tr);
				}
			}
		}else{
			if($this.sizeList.length){
				for(var j=0;j<$this.sizeList.length;j++){
					var $Tr = $this.varianceTrTemplate.join('').format({
						trId: 'tr-'+$this.sizeList[j],
						color: '',
						size: $this.sizeList[j] == 'none' ? '无尺寸' : $this.sizeList[j],
						colorId: 'none',
						sizeId: $this.sizeList[j]
					});
					$('#variance_tab tbody').append($Tr);
				}
			}
		}
		$this.fillVarianceData();
	}
	this.ImageAppearByColor = function(){
		$('#image-info').html('');
		for(var i=0,len=$this.colorList.length;i<len;i++){
			$this.LoadImgTmpl($this.colorList[i],{
				productImage: [],
				productDescribeImage: []
			});
		}	
	}
	this.storeVarianceData = function(){
		this.varianceData = [];
		if($('#variance_tab tbody tr').length){
			$('#variance_tab tbody tr').each(function(){
				var self = $(this);
				var Color = self.find('td[data-name="color"]').data('val') == 'none' ? '' : self.find('td[data-name="color"]').data('val');
				var Variation = self.find('td[data-name="variation"]').data('val') == 'none' ? '' : self.find('td[data-name="variation"]').data('val');
				var trId = '';
				Color != ''	? (Variation != '' ? (trId = 'tr-' + Color + '-' + Variation) : trId = 'tr-' + Color) : (Variation !='' ? (trId= 'tr-' + Variation) : trId = '');
				var SellerSku = self.find('td[data-name="sellerSku"] input').val();
				var ProductId = self.find('td[data-name="productGroup"] input').val();
				var Quantity = self.find('td[data-name="quantity"] input').val();
				var Price = self.find('td[data-name="price"] input').val();
				var SalePrice = self.find('td[data-name="salePrice"] input').val();
				var SaleStartDate = self.find('td[data-name="saleDate"] input[data-name="saleStartDate"]').val();
				var SaleEndDate = self.find('td[data-name="saleDate"] input[data-name="saleEndDate"]').val();
				$this.varianceData.push({
					trId : trId,
					Color: Color,
					Variation: Variation,
					SellerSku: SellerSku,
					ProductId: ProductId,
					Quantity: Quantity,
					Price: Price,
					SalePrice: SalePrice,
					SaleStartDate: SaleStartDate,
					SaleEndDate: SaleEndDate
				});
			});
		}
	}
	this.fillVarianceData = function(){
		if($this.varianceData.length){
			for(var i  in $this.varianceData){
				if($this.varianceData.hasOwnProperty(i)){
					var Color = $this.varianceData[i].Color,
					Variation = $this.varianceData[i].Variation,
					trId = '';
					Color != '' ? (Variation != '' ? trId= 'tr-'+ Color +'-'+ Variation : trId='tr-'+ Color) : (Variation != '' ? trId='tr-'+ Variation : trId='')
					var tr = $('#variance_tab tbody').find('tr[trId="'+ trId +'"]');
					tr.find('td[data-name="sellerSku"] input').val($this.varianceData[i].SellerSku);
					tr.find('td[data-name="productGroup"] input').val($this.varianceData[i].ProductId);
					tr.find('td[data-name="quantity"] input').val($this.varianceData[i].Quantity);
					tr.find('td[data-name="price"] input').val($this.varianceData[i].Price);
					tr.find('td[data-name="salePrice"] input').val($this.varianceData[i].SalePrice);
					tr.find('td[data-name="saleDate"] input[data-name="saleStartDate"]').val($this.varianceData[i].SaleStartDate);
					tr.find('td[data-name="saleDate"] input[data-name="saleEndDate"]').val($this.varianceData[i].SaleEndDate);
				}
			}
		}
	}

	this.LoadImgTmpl = function(colorId,options){
		colorName = colorId == 'none' ? '' : colorId;
		$($this.productImageLibTemplate.join('').format({colorId: colorId,colorName:colorName})).appendTo($('#image-info'));
		$('.productImageLib[data-name="'+colorId+'"] .productImage').registerPlugin("SelectImageLib",function(ImageLib){
			var $this = $(this),data = [];
			new ImageLib(this,options.productImage,{
				modules:[
					'iv-local-upload',
					'iv-online-url',
					'iv-lb-lib',
					'meitu',
					'copy-url',
					'remove',
				]
			});
		});	
		$('.productImageLib[data-name="'+colorId+'"] .productDescribeImage').registerPlugin("SelectImageLib",function(ImageLib){
			var $this = $(this),data=[];
			new ImageLib(this,options.productDescribeImage,{
				modules:[
					'copy-lib',
					'iv-local-upload',
					'iv-online-url',
					'iv-lb-lib',
					'meitu',
					'copy-url',
					'remove',
				]
			})
		});
	}


	this.getCategoryAttrs = function(){
		return $.ajax({
			type:'post',
			dataType: 'json',
			data: {
				lazada_uid: $this.uid,
				primaryCategory: $this.currentCateId
			},
			url: $this.cateAttrUrl
		});	
	}

	this.getChildCategoryList = function(){
		return $.ajax({
			type:'get',
			dataType:'json',
			data: {
				parentCategoryId: $this.catePid,
				lazada_uid: $this.uid
			},
			url: $this.childCateListUrl
		});
	}

	this.getSearchCategoryList = function(name){
		return $.ajax({
			type:'get',
			dataType:'json',
			data: {
				search_value: name,
				lazada_uid: $this.uid
			},
			url: $this.searchCateListUrl
		});
	}

	this.showCategoryAttrs = function(data){
		var  productInfo = [];
		var  productAttr = [];
		var  shipingAttr = [];
		var  warrantyAttr = [];
		data.attributes.forEach(function(e){
			if($.inArray(e.FeedName,$this.cateAttrGroup.showCategoryAttr) == -1 && $.inArray(e.FeedName,$this.cateAttrGroup.hideCategoryAttr) == -1){
				if($.inArray(e.FeedName,$this.cateAttrGroup.commonCategoryAttr) != -1){
					if(e.InputType  == 'dropdown' &&  e.Options != ''){
						productInfo.push(e);
	
					}
				}else if($.inArray(e.FeedName,$this.cateAttrGroup.warrantyAttr) != -1){
					warrantyAttr.push(e);
				}else if($.inArray(e.FeedName,$this.cateAttrGroup.shipingAttr) != -1){
					shipingAttr.push(e);
				}else{
					productAttr.push(e);
				}
			}	
		});
		$('#categoryHistoryId').attr('data-site',data.site);
		$this.appendProductInfo(productInfo);
		$this.appendWarrantyAttr(warrantyAttr);
		$this.appendShipingAttr(shipingAttr);
		$this.appendProductAttr(productAttr);
		$this.fillCategoryData();	
	}
	this.appendProductInfo = function(data){
		$('#base-info .lzdAttrShow').html('');
		for(var i in data){
			if(data.hasOwnProperty(i)){
				$('#base-info .lzdAttrShow').append($this.createCategoryAttrHtml(data[i]));
			}
		}
	}
	this.appendWarrantyAttr = function(data){
		$('.lzdWarrantyInfo div[data-name="Property"]').remove();
		for(var i in data){
			if(data.hasOwnProperty(i)){
				$('.lzdWarrantyInfo').prepend($this.createCategoryAttrHtml(data[i]));
			}
		}
	}

	this.appendShipingAttr = function(data){
		$('.lzdShipingAttr div[data-name="Property"]').remove();
		$('#shipping-info .lzdShipingAttr').html('');
		for(var i in data){
			if(data.hasOwnProperty(i)){
				$('#shipping-info .lzdShipingAttr').append($this.createCategoryAttrHtml(data[i]));
			}
		}
	}
	this.appendProductAttr = function(data){
		$('#product-info .lzdProductAttr').html('');
		$('#product-info').show();
		for(var i in data){
			if(data.hasOwnProperty(i)){
				$('#product-info .lzdProductAttr').append($this.createCategoryAttrHtml(data[i]));
			}
		}
	}
	this.createCategoryAttrHtml = function(data){
		var str = '';
		switch(data.InputType){
			case 'dropdown':
				str = $this.categoryAttrsTemplate.dropdown.join('').format(
					{
						name: data.FeedName,
						title: data.Name.transferToTitle(),
						sign: data.isMandatory == 1 ? '<span class="fRed">*</span>': '',
						Mandatory: data.isMandatory,
						options: function(data){
							var str = [];
							str.push('<option value="">---请选择---</option>');
							data.forEach(function(option){
								str.push('<option value="'+ option.GlobalIdentifier +'">'+ option.Name +'</option>');
							});
							return str.join('');
						}(data.Options.Option)
					}) ;
				break;
			case 'numberfield':
				str = $this.categoryAttrsTemplate.numberfield.join('').format({
					name: data.FeedName,
					title: data.Name.transferToTitle(),
					sign: data.isMandatory == 1 ? '<span class="fRed">*</span>':'',
					Mandatory: data.isMandatory
				});
				break;
			case 'textfield':
				str = $this.categoryAttrsTemplate.textfield.join('').format(
					{
						name: data.FeedName,
						title: data.Name.transferToTitle(),
						sign: data.isMandatory == 1 ? '<span class="fRed">*</span>': '',
						Mandatory: data.isMandatory
					});
				break;
			case 'multiselect':
				if(typeof data.Options == 'object'){
					str = $this.categoryAttrsTemplate.dropdown.join('').format({
						name: data.FeedName,
						title: data.Name.transferToTitle(),
						sign: data.isMandatory == 1 ? '<span class="fRed">*</span>' : '',
						Mandatory: data.isMandatory,
						options: function(data){
							var str = [];
							str.push('<option value="">---请选择---</option>');
							for(var i in data){
								str.push('<option value="'+ data[i].GlobalIdentifier +'">'+ data[i].Name +'</option>');
							}
							return str.join('');
						}(data.Options)
					});
				}else{
					str = $this.categoryAttrsTemplate.textfield.join('').format({
						name: data.FeedName,
						title: data.Name.transferToTitle(),
						sign: data.isMandatory ? '<span class="fRed">*</span>': '',
						Mandatory: data.isMandatory
					});
				}
				break;
			case 'textarea': 
				str = $this.categoryAttrsTemplate.textarea.join('').format({
					name: data.FeedName,
					title: data.Name.transferToTitle(),
					sign: data.isMandatory == 1 ? '<span class="fRed">*</span>' : '',
					Mandatory: data.isMandatory
				});
				break;
		}
		return str;
	}
	this.fillCategoryData = function(){
		if(!$.isEmptyObject($this.editProductData)){
			for(var i in $this.editProductData){
				if($this.editProductData.hasOwnProperty(i)){
					var type = $('div[cid="'+ i +'"]').attr('attrtype');
					if(type != undefined){
						if(i == 'ProductMeasures'){
							var value = $this.editProductData[i].split(' x ');
							for(var j=0,len=value.length;j<len;j++){
								$('div[cid="'+ i +'"]').find(type).eq(j).val(value[j]);
							}
						}else if($.inArray(i,['Description','ShortDescription','PackageContent','ProductWarranty']) != -1){
							KindEditor.html('#'+i,$this.editProductData[i]);
						}else{
							$('div[cid="'+ i +'"]').find(type).val($this.editProductData[i]);
						}
					}else if($.inArray(i,['PackageWidth','PackageHeight','PackageLength']) != -1){
							$('input[cid="'+i+'"]').val($this.editProductData[i]);
					}
				}
			}
		}
	}
	this.ajaxAppend  = function(){
		var str = '';
		if($this.childCateList){
			var cateList = $this.childCateList;
			for(var i in cateList){
				if(cateList.hasOwnProperty(i)){
					cateList[i].isLeaf ?
					str += '<li role="presentation" title="'+ cateList[i].name +'" data-level="'+ cateList[i].level +'" data-leaf="0" data-cateid="'+ cateList[i].categoryId +'"><a href="#">'+ $this.substrCategoryName(cateList[i].name) +'</a></li>'
				:
					str += '<li role="presentation" title="'+ cateList[i].name +'" data-level="'+ cateList[i].level +'" data-leaf="1" data-cateid="'+ cateList[i].categoryId +'"><a href="#">'+ $this.substrCategoryName(cateList[i].name) +'<span class="glyphicon glyphicon-chevron-right pull-right"></span></a></li>';
				}
			}
		}else{
			str = '<li style="text-align: center;line-height: 280px;">暂无类目<li>';
		}
		$('ul[data-level="'+ $this.level +'"]').html('').append(str).parent().show();	
		this.hideCategoryBox($this.level+1);
	}

	this.showCategoryList = function(){
		var str = '';
		var num = 0;
		if($this.searchCateList){
			var cateList = $this.searchCateList;
			for(var i in cateList){
				if(cateList.hasOwnProperty(i)){
					num++;
					var cateIds = '';
					var showName = '';
					var cateId = '';
					var cateNames = '';
					for(var j in cateList[i]){
						if(cateList[i].hasOwnProperty(j)){
							(j == 1) ? cateIds += cateList[i][j].categoryId : cateIds += ',' + cateList[i][j].categoryId;
							(j == 1) ? showName += cateList[i][j].name : (showName += ' > ' + cateList[i][j].name );
							cateId = cateList[i][j].categoryId;
							(j == 1) ? cateNames += cateList[i][j].name : cateNames += ',' + cateList[i][j].name ;
						}
					}
					str += '<li>';	
					str += '<a href="javascript:;" data-cateids="'+ cateIds +'" data-cateid="'+ cateId +'" data-names="'+ cateNames +'" title="'+ showName +'"><span>'+ showName +'</span></a>';
					str += '</li>';
				}
			}
		}else{
			str ='<p style="text-align:center;">无相关类目</p>';
			num = 0;	
		}
		$('.cate_search_count').html(num);
		$('.cate_search_ul').html(str);
		$('.cate_list').show();
		$('.category-ensure').attr('disabled',true).addClass('not');
	}

	this.showCategoryName = function(obj){
		var cateid = obj.data('cateid');
		var level = obj.data('level');
		var name = obj.attr('title');
		var showName = name;
		(level == '1') || (showName = ' > ' + name);
		$('span[data-level="'+ level +'"]').html(showName).attr({'data-cateid':cateid,'data-name':name});
		$this.hideChildCategoryName(level+1);
	}

	// this.addOneVariance = function(){
	// 	if($this.uid == ''){
	// 		$('#select_shop_info').html('<span class="iconfont icon-cuowu fRed pRight15"></span>请选择Linio店铺!').show();
	// 		// $.message('请选择Linio店铺','warn');
	// 		$('html,body').animate({scrollTop:$('#store-info').offset().top},800);
	// 		return;
	// 	}
	// 	if($this.currentCateId == ''){
	// 		$('#select_info').html('<span class="iconfont icon-cuowu fRed pRight15"></span>请选择产品目录!').show();
	// 		$('html,body').animate({scrollTop:$('#store-info').offset().top},800);
	// 		return;
	// 	}
	// 	$('#variance_tab tbody').append($this.varianceTr.join(''));
	// }

	this.hideChildCategoryName = function(level){
		$('span[data-level="'+ level +'"]').html('').removeAttr('data-cateid').removeAttr('data-name');
		(level <= $this.maxLevel) && $this.hideChildCategoryName(level + 1);
	}

	this.hideCategoryBox = function(level){
		$('ul[data-level="'+ level +'"]').html('').parent().hide();
		(level <= $this.maxLevel) && $this.hideCategoryBox(level+1);
	}	

	this.substrCategoryName = function(name){
		return name = (name.length > 20) ? name.substring(0,20) + '...' : name;
	}
	this.saveProduct = function(type){
		var multiSites = [];
		// multiSites['products'] = $this.dealProductData();
		// multiSites['lazadaUid']  = $('#lazadaUid').val();
		var lazadaUid = $('#lazadaUid').val();
		var Products = $this.getProductData();
		console.log(Products);
		if(!$this.checked(lazadaUid)){
			return false;
		}
		multiSites.push({
			products: $this.dealProductData(Products),
			lazadaUid: lazadaUid,

		});
		$.post('/listing/linio-listing-v2/save-product',{
			'multiSites': multiSites
			// 'op': type =='push'? '2' : '1'
		});
	}
	this.getProductData = function(){
		var site = $('#categoryHistoryId').data('site');
		var productProperty = function(){
			var productProperty = [];
			$('.search-info div[cid]').each(function(){
					var type = $(this).attr('attrtype');
					var Name = $(this).attr('cid');
					switch(type){
						case 'input':
							if(Name == 'ProductMeasures'){
								var ProductMeasures = [];
								$(this).find('input').each(function(){
									ProductMeasures.push($(this).val());
								});
								productProperty.push({ 
									name:'ProductMeasures',
									value: ProductMeasures.join(' x ').replace(/\sx\s$/,'')
								});
							}else if(Name == 'packingSize'){
								$(this).find('input').each(function(){
									productProperty.push({
										name: $(this).attr('cid'),
										value: $(this).val(),
									});
								});
							}else{
								productProperty.push({
									name: Name,
									value: $(this).find(type).val()
								});
							}
							break;
						case 'select':
							productProperty.push({
								name:Name,
								value: $(this).find(type).val()
							});
							break;
						case 'kindeditor':
							productProperty.push({
								name:Name,
								value: $(this).find('textarea[name="content"]').val()
							})
							break;
					}
			});
			$('.description-info div[cid]').each(function(){
				var name = $(this).attr('cid');
				productProperty.push({
					name: name,
					value: $(this).find('textarea[name=["content"]').val()
				});
			});
			productProperty.push({
				name: 'PrimaryCategory',
				value: $('#categoryHistoryId').val()
			});
			productProperty.push({
				name: 'Categories',
				value: $('.category').data('ids')
			});
			return productProperty;
		}();
		var variance = function(){
			var variance = [];
			$('#variance_tab tbody tr').each(function(){
				variance.push({
					Color:  $(this).find('td[data-name="color"]').data('val') == 'none' ? '' : $(this).find('td[data-name="color"]').data('val'),
					Variation: $(this).find('td[data-name="variation"]').data('val') == 'none' ? '' : $(this).find('td[data-name="variation"]').data('val'),
					Price: $(this).find('td[data-name="price"] input').val(),
					SalePrice : $(this).find('td[data-name="salePrice"] input').val(),
					SaleStartDate: $(this).find('td[data-name="saleDate"] input[data-name="saleStartDate"]').val(),
					SaleEndDate: $(this).find('td[data-name="saleDate"] input[data-name="saleEndDate"]').val(),
					Sku: $(this).find('td[data-name="sellerSku"] input').val(),
					ProductId: $(this).find('td[data-name="productGroup"] input').val(),
					Quantity: $(this).find('td[data-name="quantity"] input').val()
				});
			});
			return variance ;
		}();
		var Image = function(){
			var Images = [];
			$this.colorList.forEach(function(color){
				var images = [];
				var imagesThumbnail = [];
				$('#image-info  .productImageLib[data-name="'+ color +'"] .productImage').find('input[name="extra_images[]"]:checked').each(function(){
					images.push($(this).val());
					imagesThumbnail.push($(this).val() + '?imageView2/1/w/210/h/210');
				});
				var otherImage = [];
				$('#image-info .productImageLib[data-name="'+ color +'"] .productDescribeImage').find('input[name="extra_images[]"]:checked').each(function(){
					otherImage.push($(this).val());
				});
				var mainImage = images.shift();
				var mainImageThumnail = imagesThumbnail.shift();
				Images.push({
					color: color,
					images: images,
					imagesThumbnail: imagesThumbnail,
					mainImage: mainImage,
					mainImageThumnail: mainImageThumnail,
					productDetailImage: otherImage	
				})
			});
			return Images;
		}();
		return {
			site : site,
			productProperty: productProperty,
			variance: variance,
			image : Image
		}
	}

	this.dealProductData = function(obj){
		var IsVariationDivide = $('input[name="IsVariationDivide"]:checked').val();
		var variance = obj.variance;
		var Image = obj.image;
		var productProperty = obj.productProperty;
		var site = obj.site;
		var products = [];
		for(var i=0,len=$this.colorList.length;i<len;i++){
			var product = {};
			product['ProductData'] = {};
			productProperty.forEach(function(data){
				if($.inArray(data.name,$this.PushProductDataProperties) == -1){
					product['ProductData'][data.name] = data.value;		
				}else{
					if(data.name == 'Description' && site == 'co'){
						product['ProductData']['DescriptionMs'] = data.value;
					}else if(data.name== 'Name' && site == 'co'){
						product['ProductData']['NameMs'] = data.value;
						product[data.name] = data.value + '(' + variance[i].Color + ')';	
					}
					product[data.name] = data.value;	
				}
			});
			var mainImage = '';
			var images = [];
			var mainImageThumbnail = '';
			var imagesThumbnail = [];
			for(var k=0;k<Image.length;k++){
				if(Image[k].color == $this.colorList[i]){
					mainImage = Image[k].mainImage;
					images = Image[k].images;
					mainImageThumnail = Image[k].mainImageThumnail;
					imagesThumbnail = Image[k].imagesThumbnail;
				}
			}			
			
			if(IsVariationDivide == 'yes'){

				for(var j=0;j<$this.sizeList.length;j++){
					var variation =  [];	
					variance.forEach(function(data){
						if(data.Color == $this.colorList[i]  && data.Variation == $this.sizeList[j]){
							product['SellerSku'] = data.Sku;
							product['SalePrice'] = data.SalePrice;
							product['Price'] = data.Price;
							product['SaleStartDate'] = data.SaleStartDate;
							product['SaleEndDate'] = data.SaleEndDate;
							product['ProductId'] = data.ProductId;
							product['Quantity'] = data.Quantity;
							product['Color'] = data.Color;
						}
					});
					if($this.sizeList.length == 1 && $this.sizeList[0] == ''){
						variation = [];
					}else{
						variation[0] = $this.sizeList[0];
					}
					products.push({
						product: product,
						Variation: variation,
						mainImage:mainImage,
						images : images,
						mainImageThumbnail:mainImageThumbnail,
						imagesThumbnail: imagesThumbnail,
						id: '',
						objectId: ''
					});
				}
			}else{
				var variation = [];
				if($this.sizeList.length == 1 && $this.sizeList[0] == ''){
					variation = [];
				}else{
					variance.forEach(function(data){
						if(data.Color == $this.colorList[i]){
							variation.push({
								'Variation': data.Variation,	
								'SellerSku' : data.Sku,
								'SalePrice' : data.SalePrice,
								'Price' : data.Price,
								'SaleStartDate': data.SaleStartDate,
								'SaleEndDate' : data.SaleEndDate,
								'ProductId': data.Productid,
								'Quantity': data.Quantity,
								'Color': data.Color
							});		
						}
					});
					
				}
				products.push({
					product: product,
					Variations: variation,
					mainImage: mainImage,
					images : images,
					mainImageThumbnail:mainImageThumbnail,
					imagesThumbnail: imagesThumbnail,
					id: '',
					objectId: ''
				});
			}
		}
		return products;
	}

	this.checked = function(site){	
		$('.error-tips[data-type="tip"]').remove();
		$('.error-tips').html('').hide();	
		$('#shipping-info input').removeClass('error_tips');
		$('#variance_tab input').attr('placeholder','').removeClass('error_tips');
		$('div.iv-tips').remove();
		var isChecked = true;
		if(site == ''){
			$this.MindTips('请选择Linio店铺',$('#store-info'),'error');	
			$('#select_shop_info').html('<span class="iconfont icon-cuowu fRed"></span> 请选择Linio店铺').show();
			isChecked = false;
			return  isChecked;
		}

		var primaryCategory = $('#categoryHistoryId').val(); 
		if(primaryCategory == undefined || primaryCategory == ''){
			$this.MindTips('请选择产品目录',$('#store-info'),'error');
			$('#select_info').html('<span class="iconfont icon-cuowu fRed"></span> 请选择产品目录').show();
			isChecked = false;
			return false;
		}

		$('div[cid]').each(function(){
			var isMust = $(this).attr('ismust');		
			var  type = $(this).attr('attrtype');
			var Name = $(this).attr('cid').transferToTitle();

			if(isMust == '1'){
				switch(type){
					case 'checkbox':
						if($(this).find('checkbox:checked').length == 0){
							// $.message(Name+'是必选项','warn');
							$this.MindTips(Name+'是必填字段，请填写',$(this).closest('.search-info'),'error');
							$(this).append($this.ErrorTipTemplate.join('').format({'message':Name+'是必选项'}));	
							isChecked = false;
							return false;
						}
						break;
					case 'input':
						if($(this).find(type).val() == ''){
							// $.message(Name+'是必填项','warn');
							$this.MindTips(Name+'是必填字段，请填写',$(this).closest('.search-info'),'error');
							$(this).append($this.ErrorTipTemplate.join('').format({'message':Name+'是必填项'}));	
							isChecked = false;
							return false;
						}
						break;
					case 'select':
						if($(this).find(type).val() == '' || $(this).find(type).val() == '---请选择---'){
							// $.message(Name+'是必选项','warn');
							$this.MindTips(Name+'是必填字段，请填写',$(this).closest('.search-info'),'error');
							$(this).append($this.ErrorTipTemplate.join('').format({'message':Name+'是必选项'}));	
							isChecked = false;
							return false;
						}
						break;
					case 'kindeditor':
						if($(this).find('textarea#'+Name).val() == ''){
							$this.MindTips(Name+'是必填字段，请填写',$('#description-info'),'error');
							isChecked = false;
							return false;
						}
					default: 
						$(this).find('input[ismust="1"]').each(function(){
							if($(this).val() == ''){
								$this.MindTips(Name+'是必填字段，请填写',$('#shipping-info'),'error');
								$(this).addClass('error_tips');
								isChecked = false;
								return false;
							}
						});
						if(!isChecked){
							return false;
						}

				}
			}
		});
		if(!isChecked){
			return false;
		}
		if(!$('#variance_tab tbody').find('tr').length){
			$this.MindTips('请填写变体信息',$('#variant-info'),'error');
			isChecked = false;
			return false;
		}

		$('#variance_tab tbody').find('tr').each(function(){
			var self = $(this);
			var product_group = self.find('td[data-name="productGroup"] input').val();
			var quantity = self.find('td[data-name="quantity"] input').val();
			var price = self.find('td[data-name="price"] input').val();
			var sale_price = self.find('td[data-name="salePrice"] input').val();
			var saleStartDate = self.find('td[data-name="saleDate"] input[data-name="saleStartDate"]').val();
			var saleEndDate = self.find('td[data-name="saleDate"] input[data-name="saleEndDate"]').val();
			if(product_group == ''){
				$this.MindTips('产品EAN/UPC/ISBN为必填字段，请填写',$('#variant-info'),'error');
				self.find('td[data-name="productGroup"] input').addClass('error_tips');
				isChecked = false;
				return isChecked;
			}
			if(quantity == ''){
				$this.MindTips('产品库存为必填字段，请填写',$('#variant-info'),'error');
				self.find('td[data-name="quantity"] input').addClass('error_tips');
				isChecked =false;
				return isChecked;
			}
			if(price == ''){
				$this.MindTips('产品价格为必填字段，请填写',$('#variant-info'),'error');
				self.find('td[data-name="price"] input').addClass('error_tips');
				isChecked = false;
				return isChecked;
			}
			if(parseFloat(price) <= parseFloat(sale_price)){
				$this.MindTips('商品的促销价必须小于售价，请修改',$('#variant-info'),'error');
				self.find('td[data-name="salePrice"] input').addClass('error_tips');
				isChecked = false;
				return isChecked;
			}
			if((saleStartDate !='' && saleEndDate == '') || (saleStartDate == '' && saleEndDate != '')){
				$this.MindTips('商品的促销时间填写不完整，请填写',$('#variant-info'),'error');
				self.find('td[data-name="saleDate"] input').addClass('error_tips');
				isChecked =false;
				return isChecked;
			}else if( saleStartDate != '' && saleEndDate != ''){
				var startDate = new Date(saleStartDate).getTime();
				var endDate = new Date(saleEndDate).getTime();
				if(startDate > endDate){
					$this.MindTips('商品的开始促销时间不能大于结束促销时间,请修改',$('#variant-info'),'error');
					self.find('td[data-name="saleDate"] input').addClass('error_tips');
					isChecked = false;
					return isChecked;
				}
			}
		});
		return isChecked;
	}

	this.fillEditData = function(){
		var productData = $('input[name="productData"]').val();
		if(productData != ''){
			productData =JSON.parse(productData);
			$('#lazadaUid').val(productData.lazadaUid).trigger('change');
			for(var i in productData.variations){
				if(productData.variations.hasOwnProperty(i)){
					var Color  = productData.variations[i].Color,
					Variation = productData.variations[i].Variation;
					Color != '' ? (Variation != '' ? trId= 'tr-'+ Color +'-'+ Variation : trId='tr-'+ Color) : (Variation != '' ? trId='tr-'+ Variation : trId='');
					var colorList = []; //页面颜色列表
					$('#good_colors input[type="checkbox"]').each(function(){
						colorList.push($(this).val());	
					}); 
					var sizeList = [];
					$('#good_sizes input[type="checkbox"]').each(function(){
						sizeList.push($(this).val());	
					});
					if($.inArray(Color,colorList) != -1){
						(!$('#good_colors input[value="'+Color+'"]').is(':checked')) && $('#good_colors input[value="'+ Color +'"]').click();
					}else{
						$('#otherColor').val(Color);
						$('.colorAdd').trigger('click');
					}
					if($.inArray(Variation,sizeList) != -1){
						(!$('#good_sizes input[value="'+Variation+'"]').is(':checked')) && $('#good_sizes input[value="'+ Variation +'"]').click();
					}else{
						$('#otherSize').val(Variation);
						$('.sizeAdd').trigger('click');
					}
				}
			}
			$this.getOneCompleteCategory(productData.lazadaUid,productData.PrimaryCategory).done(function(data){
				$this.editProductData = productData;
				$('#categoryHistoryId').append('<option value="'+ data.categoryId +'">'+ data.name +'</option>').val(data.categoryId).trigger('change');
				$('.cateogory').attr('data-ids',data.categoryIds).html(data.names);
			});

		}
	}
	this.getOneCompleteCategory = function(lazadaUid,categoryid){
		return $.post('/listing/linio-listing-v2/get-complete-category-name',{'lazada_uid':lazadaUid,'categoryid':categoryid},'json');
	}
	this.MindTips = function(message,obj,type){
		switch(type){
			case 'error':
				$.message(message,'warn');
				$('html,body').animate({scrollTop:obj.offset().top},800);
				break;
		}
	}
}

window.onload = function(){
	var LinioExt = new Linio_ext({
		uid: $('select[name="lazadaUid"]').val(),
		catePid : 0,
		cateTreeUrl: '/listing/linio-listing-v2/get-category-tree',
		searchUrl: '/listing/linio-listing-v2/search-categories',
		cateAttrUrl: '/listing/linio-listing-v2/get-category-attrs',
		maxLevel: 6
	});
	LinioExt.init();
	LinioExt.fillEditData();
	$(window).scroll(function(event){
        var winPos = $(window).scrollTop();
        var $store_info = $('#store-info').offset().top;            
        var $base_info = $('#base-info').offset().top-3;
        var $variant_info = $('#variant-info').offset().top-3;
        var $description_info = $('#description-info').offset().top-3;
        var $shipping_info = $('#shipping-info').offset().top-3;
        var $warranty_info = $('#warranty-info').offset().top-3;
        if(isClick == false){
            if(winPos < $base_info){
                    showscrollcss('store-info');
            }else if(winPos >= $base_info && winPos < $variant_info){
                    showscrollcss('base-info');
            }else if(winPos >= $variant_info && winPos < $description_info){
                    showscrollcss('variant-info');
            }else if(winPos >= $description_info && winPos < $shipping_info){
                    showscrollcss('description-info');
            }else if(winPos >= $shipping_info && winPos < $warranty_info){
                    showscrollcss('shipping-info');
            }else if(winPos >= $warranty_info){
                    showscrollcss('warranty-info');
            }
        }
    });
}

String.prototype.format = function(args) {
    var result = this;
    if (arguments.length > 0) {    
        if (arguments.length == 1 && typeof (args) == "object") {
            for (var key in args) {
                if(args[key]!=undefined){
                    var reg = new RegExp("({" + key + "})", "g");
                    result = result.replace(reg, args[key]);
                }
            }
        }
        else {
            for (var i = 0; i < arguments.length; i++) {
                if (arguments[i] != undefined) {
                    var reg = new RegExp("({[" + i + "]})", "g");
                    result = result.replace(reg, arguments[i]);
                }
            }
        }
    }
    return result;
}

String.prototype.transferToTitle = function(){
	var self = this;	
	var result = '';
	self = self.split('_');

	for(var i=0,len=self.length; i<len; i++){
		result += self[i].replace(/\w/,function(v){ return v.toUpperCase();}) + ' ';
	}
	return $.trim(result);
}

var  isClick = false ;	
//页面滚动到指定位置
function goto(str){
	var winPos = $(window).scrollTop();
    var $store_info = $('#store-info').offset().top;            
    var $base_info= $('#base-info').offset().top;
    var $variant_info = $('#variant-info').offset().top;
    var $description_info = $('#description-info').offset().top;
    var $shipping_info = $('#shipping-info').offset().top;
    var $warranty_info = $('#warranty-info').offset().top;
    isClick = true;
    $('html,body').animate({scrollTop:$('#'+str).offset().top},300,function(){
        isClick =false;
    });
     gotowhere = str;
    showscrollcss(str);
}

function showscrollcss(str){
   var eqtmp = new Array;
    eqtmp['store-info'] =  0;
    eqtmp['base-info'] = 1;
    eqtmp['variant-info'] = 2;
    eqtmp['description-info'] = 3;
    eqtmp['shipping-info'] = 4;
    eqtmp['warranty-info'] = 5;  
    $('.left_pannel p a').removeClass('active');
    $('.left_pannel p a').eq(eqtmp[str]).addClass('active');
}
