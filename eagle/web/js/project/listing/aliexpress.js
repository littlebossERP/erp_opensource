(function($){
	'use strict';

	var sizeList = [];
	var colorList = [];
	var attrList = [];
	var attrData = []; 
	var infoModule = [];
	var temporaryProductListData = [];
	$(function(){
		var $a = $.aliexpress;
		$('#new_product').size() && $(window).scroll(function(event){
	        var winPos = $(window).scrollTop();
	        var $new_product = $('#new_product').offset().top;            
	        var $product_attributes = $('#product_attributes').offset().top-3;
	        var $product_info = $('#product_info').offset().top-3;
	        var $variance_info = $('#variance_info').offset().top-3;
	        var $product_describe = $('#product_describe').offset().top-3;
	        var $rest_info = $('#rest_info').offset().top-3;
	        if(isClick == false){
	            if(winPos < $product_attributes){
	                    showscrollcss('new_product');
	            }else if(winPos >= $product_attributes && winPos < $product_info){
	                    showscrollcss('product_attributes');
	            }else if(winPos >= $product_info && winPos < $variance_info){
	                    showscrollcss('product_info');
	            }else if(winPos >= $variance_info && winPos < $product_describe){
	                    showscrollcss('variance_info');
	            }else if(winPos >= $product_describe && winPos < $rest_info){
	                    showscrollcss('product_describe');
	            }else if(winPos >= $rest_info){
	                    showscrollcss('rest_info');
	            }
	        }
	    });
		$('[target="#category-modal"]').on('click',function(){
			$a.getCategory(0).done(function(data){
				var level = 0;
				$a.ajaxAppend(data,level);
			});
			$('.cate_list').hide();
			$('.category_content').html('').removeAttr('data-name').removeAttr('data-cateid');
		});

		$('.add_attr').on('click',function(){
			var obj = $(this);
			$a.addUserDefinedAttr(obj);
		});

		$('.not').on('click',function(e){
			var e = e || window.event;
        	e.preventDefault();
		});

		$('.check_len').on('input blur',function(){
			var self = $(this);
			$a.CheckInputContentLength(self);
		});

		$('select[name="selleruserid"]').on('change',function(){
			var selleruserid = $(this).val();
			console.log(selleruserid);
			$a.GetProductGroupList(selleruserid).done(function(data){
				var groups = $a.AppendProductGroupList(data);
				$('#select-product-group .group-list').html('').append(groups);
			});
			$a.getInfoModule(selleruserid).done(function(data){
				$('#infoModule').find('tbody').html($a.AppendInfoModule(data));
			});
			($('.right_content .content').data('type') == 'add') && $a.getFreightTemplate(selleruserid).done(function(data){
				if(data != ''){
					var freight_templateid = $('select[name="freight_templateid"]').data('id');
					var str = '<option value="">---请选择产品运费模板---</option>';
					for(var i = 0 ;i<data.length;i++){
						// var selected = (freight_templateid == data[i]['templateid']) ? 'checked' : '';
						str += '<option value="'+ data[i]['templateid']+'">'+ data[i]['template_name'] +'</option>';
					}
				}
				$('select[name="freight_templateid"]').html('').append(str);
			});
			
			($('.right_content .content').data('type') == 'add') && $a.getPromiseTemplate(selleruserid).done(function(data){
				if(data != ''){
					var promise_templateid = $('select[name="promise_templateid"]').data('id');
					var str = '<option value="">---请选择产品运费模板---</option>';
					for(var i = 0 ;i<data.length;i++){
						// var selected = (promise_templateid == data[i]['templateid']) ? 'checked' : '';
						str += '<option value="'+ data[i]['templateid']+'">'+ data[i]['name'] +'</option>';
					}
					$('select[name="promise_templateid"]').html('').append(str); 
				}	
			});
		});

		$('.selProductGroup').on('click',function(){
			var $this = $(this);
			var selleruserid = $('select[name="selleruserid"]').val();	
			if(selleruserid == ''){
				$.tips({type:'error',msg:'请先选择速卖通店铺',exitTime:3000});
				return false;
			}
			var html = $('#select-product-group').html();
			var title = '选择产品分组';
			$.showModal(html,title,undefined,'',{},{}).then(function($modal){
				// $this.trigger('modal.ready',[$modal,title]);
				// $modal.on('modal.then',function(e,data){
				// 	$this.trigger('modal.submit',[data,$modal]);
				// });
				$modal.on('click','input[name="product_groups[]"]',function(){
					var len = $modal.find('input[name="product_groups[]"]:checked').length;
					if(len >= 3){
						$modal.find('input[name="product_groups[]"]').attr('disabled',true);
						$modal.find('input[name="product_groups[]"]:checked').removeAttr('disabled');
					}else{
						$modal.find('input[name="product_groups[]"]').removeAttr('disabled');
					}
				});
				$modal.find('a.sync_group').on('click',function(){
					$a.GetProductGroupList(selleruserid).done(function(data){
						var groups = $a.AppendProductGroupList(data);
						$modal.find('.group-list').html('').append(groups);
					});	
				});
				$modal.find('a.select_group').on('click',function(){
					var $data = $modal.serializeObject();
					if($data.product_groups.length){
						var name = [];
						var id = [];
						for(var i=0 ; i < $data.product_groups.length; i++){
							console.log($data.product_groups[i]);
							var data = $data.product_groups[i].split('=');
							id.push(data[0]);
							console.log(data[1]);
							name.push(data[1].replace(/\++/g,' '));
							console.log(name);
						}
						$('.product_group_list_box span').html(name.join('、')).attr('data-id',id.join(','));
					}else{
						$('.product_group_list_box span').html('未选择分组').removeAttr('data-id');
					}
					$modal.close();
				});
				$modal.find('.show-list ul[target]').hide();
			})

		});
		$('.ali-editor').on('aliEditor',function(){
			var $this  = $(this);
			$a.loadJsFile('/js/lib/kindeditor/kindeditor.js');
			KindEditor.plugin('imgbank',function(K){
				var editor = this, name="imgbank";
				editor.clickToolbar(name,function(){
					var target = '/util/image/select-image-lib';
					var title = '图片库';
					var options = {
						resolve : true,
						reject: true
					};
					$.openModal(target,{},title,'get','',{},options).done(function($modal){
						$modal.on('modal.then',function(e,data){
							$(editor).trigger('modal.submit',[data,$modal]);
						});
					});
					$(editor).on('modal.submit',function(e,data){
						if(data.img.length){
							var str = '';
							for(var i =0; i<data.img.length;i++){
								str += '<img src="'+ data.img[i] +'">';
							}
							editor.insertHtml(str);
						}
					})
				});
				editor.lang({
					'imgbank': '图片库'
				});	
			});
			KindEditor.plugin('infoModule',function(K){
				var editor = this, name = "infoModule";	
				var showInfoModule = "<kse:widget data-widget-type=\"relatedProduct\" id=\"17997158\" title=\"Repair kit C99 002 and C01 001\" type=\"relation\"><\/kse:widget>";
				editor.clickToolbar(name,function(){
					var selleruserid = $('select[name="selleruserid"]').val();
					if(selleruserid == ""){
						$.tips({type:'error',msg:'请先选择速卖通店铺',exitTime:3000});
						return false;
					}
					var target = '#infoModule';
					var title = '选择产品信息模块';
					var typeList = {'relation':'关联模板'}
					$.showModal($(target).html(),title,undefined,'',{},{}).then(function($modal){
						$modal.find('.select_infoModule').on('click',function(){
							var infoModuleList = $modal.serializeObject();
							var showInfoModule = '';
							for(var i=0;i<infoModuleList.Ids.length;i++){
								// showInfoModule += '<kse:widget data-widget-type="relatedProduct" id="'+ infoModuleList.Ids[i] +'" title="'+ infoModule[infoModuleList.Ids[i]] +'" type="relation"></kse:widget>';
								showInfoModule += '<img data-kse-id="'+ infoModuleList.Ids[i] +'" src="/images/aliexpress/kse-default.png" title="'+ infoModule[infoModuleList.Ids[i]] +'" />';
							}
							editor.insertHtml(showInfoModule);
							$modal.close();
						});	
						$modal.find('.sync_infoModule').on('click',function(){
							var selleruserid = $('select[name="selleruserid"]').val();
							$a.getInfoModule(selleruserid,'').done(function(data){
								$modal.find('tbody').html($a.AppendInfoModule(data));
								$.tips({
									type:'success',
									msg: '同步产品信息模块成功',
									exitTime: 3000
								});
							}).fail(function(){
								$tips({
									type:'error',
									msg:'同步产品信息模块失败',
									exitTime: 3000
								});
							});	
						});

						$modal.find('input[name="searchByName"]').on('keyup',function(e){
							e = e || window.event;
							var $this = $(this);
							var selleruserid = $('select[name="selleruserid"]').val();
							var name = $this.val();
							(e.which == '13') && $modal.find('.searchNameBtn').trigger('click');	
						});

						$modal.find('.searchNameBtn').on('click',function(){
							var selleruserid = $('select[name="selleruserid"]').val();
							var name = $modal.find('input[name="searchByName"]').val();
							console.log(name);
							$a.getInfoModule(selleruserid,name).done(function(data){
								$modal.find('tbody').html($a.AppendInfoModule(data));
							});
						});
					});
				})
				editor.lang({
					'infoModule':'插入产品信息模块'
				});
			});

			KindEditor.ready(function(K){
				var editor = $(this);
				K.create('.ali-editor',{
					afterBlur: function(){
						$this.val(editor.html());
						this.sync();
					},
					filterMode: false,
					items: [
						'bold','underline','underline','strikethrough','|','forecolor','hilitecolor','|',
						'justifyleft','justifycenter','justifyright','justifyfull','|',
						'insertorderedlist','insertunorderedlist','|','outdent','indent','|',
						'superscript','subscript','|','selectall','removeformat','"',
						'undo','redo','/','fontname','fontsize','formatblock','|','cut','copy','paste','plainpaste',
						'wordpaste','table','|','link','unlink','|','image','imgbank','infoModule','|','fullscreen','source'
					],
					uploadJson: '/util/image/upload',
					filePostName: 'product_photo_file',
					minWidth:800,
					minHeight:400
				});
			});

		});
		$('.ali-editor').trigger('aliEditor');

		$('.variance_info_box').on('mouseover','a[set-value]',function(){
			$(this).qtip({
				content:{
					text: function(){
						var type = $(this).attr('set-value');

						var str = '<label>'+$(this).data('name')+'</label>';
						if(type == 'product_code'){
							str += '<input class="input-spacing left-spacing-m" name="fill_value" type="text" data-fill="'+ type +'" focus placeholder="请填写编码前缀">';
						}else{
							str += '<input class="input-spacing left-spacing-m" name="fill_value" type="text" data-fill="'+ type +'" focus>';
						}
						str += '<a class="iv-btn btn-primary left-spacing-m" fill>确定</a>';
						return str;
					}
				},
				show:'click',
				hide:{
					fixed:true,
					delay:3000,
					leave:false,
					distance:false
				},
				position:{
					at:'bottomMiddle',
					my: 'topMiddle'
				},
				style:'qtip-light'
			});	
		});
	
		$('.sync_freight_template').on('click',function(){
			var self = $(this);
			var sellerloginid = $('select[name="selleruserid"]').val();
			if(sellerloginid == 0){
				$.tips({type:'error',msg:'请先选择速卖通店铺',exitTime:3000});
				return false;
			}
			$a.getFreightTemplate(sellerloginid).done(function(data){
				if(data != ''){
					var str = '<option value="">---请选择产品运费模板---</option>';
					for(var i = 0 ;i<data.length;i++){
						// if(data[i]['default'] == 'true'){
							// str += '<option value="'+ data[i]['templateid']+'" selected >'+ data[i]['template_name'] +'</option>';
						// }else{
							str += '<option value="'+ data[i]['templateid']+'">'+ data[i]['template_name'] +'</option>';
						// }
					}
					$('select[name="freight_templateid"]').html('').append(str);
				}
				// $.tips({type:'success',msg:'同步运费模板成功',exitTime:3000});
				self.parent().find('.freight_tip').html('同步成功');
			});
		});
		$('.sync_promise_template').on('click',function(){
			var self = $(this);
			var sellerloginid = $('select[name="selleruserid"]').val();	
			if(sellerloginid == 0){
				$.tips({type:'error',msg:'请先选择速卖通店铺',exitTime:3000});
			}
			$a.getPromiseTemplate(sellerloginid).done(function(data){
				if(data != ''){
					var str = '<option value="">---请选择产品运费模板---</option>';
					for(var i = 0 ;i<data.length;i++){
						str += '<option value="'+ data[i]['templateid']+'">'+ data[i]['name'] +'</option>';
					}
					$('select[name="promise_templateid"]').html('').append(str); 
				}	
				// $.tips({type:'success',msg:'同步服务'});
				self.parent().find('.promise_tip').html('同步成功');
			});
		});

		$('body').on('click','[fill]',function(){
			var $this = $(this).prev();
			$a.fillProductAttrData($this);	
		});

		$('body').on('click','[show]',function(){
			console.log('show');
			$('[show-content="'+ $(this).attr('show') +'"]').show();
		})

		$('body').on('click','[hide]',function(){
			console.log('hide');
			$('[show-content="'+ $(this).attr('hide') +'"]').hide();
		})

		$('body').on('click','[show-target]',function(){
			var t = $(this);
			var target = t.attr('show-target');	
			var box = $('[target="'+ target +'"]');
			if(box.is(':visible')){
				t.removeClass('icon-jianhao').addClass('icon-jiahao');
				box.hide();
			}else{
				t.removeClass('icon-jiahao').addClass('icon-jianhao');
				box.show();				
			}

		});

		$('body').on('keyup input','[check-valid]',function(){
			var self = $(this);
			switch(self.attr('check-valid')){
				case 'percent':
					self.val(self.val().replace(/[^\d\.-]/g,''));
					break;
				case 'number':
				case 'size':
				case 'day':
				case 'inventory':
					self.val(self.val().replace(/[^\d]/g,''));
					break;
				case 'discount':
				case 'price':
				case 'weight':
					self.val(self.val().replace(/[^\d.]/g,''));
					break;
			}
			// if(self.attr('max') != '' || self.attr('max') != undefined){
			// 	if(self.val() > self.attr('max')){
			// 		self.val(self.attr('max'));
			// 	}
			// }
			// if(self.attr('min') != '' || self.attr('min') != undefined){
			// 	if(self.val() < self.attr('min')){
			// 		self.val(self.attr('min'));
			// 	}
			// }

		});


		$('body').on('keyup','input[name="fill_value"]',function(e){
			e = e || window.event;
			var $this = $(this);
			(e.which == '13') && $a.fillProductAttrData($this);
		});

		$('select[name="product_unit"]').on('change',function(){
			var self = $(this).find(':selected');
			var name_zh = self.data('name_zh');
			var name_en = self.data('name_en');
			var unit = name_zh.split('/')[0];
			var id = self.val();
			$('.package_unit').html(name_zh+'('+name_en+')出售');
			$('.unit').html(unit);		
			$('.product_unit').html(name_zh);
		});

		$('[for]').on('click',function(){
			var box = $('[show-sign="'+ $(this).attr('for')+'"]');
			console.log($(this).attr('for'));
			if(box.is(':visible')){
				box.hide();
			}else{
				box.show();
			}
		});

		$('.aliexpress_save').on('click',function(){
			var $id = $(this).data('id');
			$id= $id == undefined ? '' : $id;
			$a.save($id).then(function(data){
				if(data['return'] == true){
					$.hideLoading();
					$.tips({
						type:'success',
						msg : '商品保存成功',
						exitTime: 3000
					});
				}
				setTimeout(function(){
					$.closeCurrentPage();
				},2000);	
			},function(data){
				$.tips(data);
			});
		});

		$('.aliexpress_push').on('click',function(){
			var $id = $(this).data('id');
			var sellerloginid = $('select[name=selleruserid]').val();
			$id = $id == undefined ? '' : $id;
			$a.save($id).then(function(data){
				if(data['return'] == true){
					if($id == '')  $id = data['msg']['listing_id'];
					$a.editPost($id,sellerloginid).done(function(data){
						$.hideLoading();
						if(data['return'] == true){
							$.tips({
								type:'success',
								msg: '商品发布成功',
								exitTime:3000
							});
							setTimeout(function(){
								$.closeCurrentPage();
							},2000);
						}else{
							$.tips({
								type:'error',
								msg: '商品发布失败',
								exitTime: 3000
							});
						}
					});
				}
			},function(data){
				$.hideLoading();
				$.tips(data);
			});
		
		});

		$('.aliexpress_OnlineSave').on('click',function(){
			var $productid = $(this).data('productid');	
			$a.onlineSave($productid).then(function(data){
				$.hideLoading();
				if(data['return'] == true){
					$.tips({
						type:'success',
						msg: '商品保存成功',
						exitTime:3000
					});
					setTimeout(function(){
						$.closeCurrentPage();
					},2000);
				}else{
					$.tips({
						type:'error',
						msg: '商品保存失败',
						exitTime: 3000
					});
				}	
			},function(data){
				$.hideLoading();
				$.tips(data);
			});
		});
	});
	var data = [];
	$.aliexpress = {
		categoryAttr: '',
		skuAttr: [],
		productPropertys: [],
		getCategory: function(pid){
			return $.post('/listing/aliexpress/get-category',{pid:pid},'json');
		},
		ajaxAppend: function(data,level){
			var str = '';
			var $this = this;
			if(data.length){
				data.forEach(function(e){
					e.isleaf == 'true' ?
						str += '<li role="presentation" title="'+ e.name_zh +'" data-level="'+ e.level +'" data-leaf="0" data-cateid="'+ e.cateid +'"><a href="#">'+ $this.substrCategoryName(e.name_zh) +'</a></li>'
					:
						str += '<li role="presentation" title="'+ e.name_zh +'" data-level="'+ e.level +'" data-leaf="1" data-cateid="'+ e.cateid +'"><a href="#">'+ $this.substrCategoryName(e.name_zh) +'<span class="glyphicon glyphicon-chevron-right pull-right"></span></a></li>';
				});
			}else{
				str = '<li style="text-align: center;line-height: 280px;">暂无类目<li>';
			}
			$('ul[data-level="'+ level +'"]').html('').append(str).parent().show();	
			this.hideCategoryBox(level+1);
		},
		substrCategoryName: function(name){
			return name = (name.length > 10) ? name.substring(0,10) + '...' : name;
		},
		hideCategoryBox: function(level){
			$('ul[data-level="'+ level +'"]').html('').parent().hide();
			(level < 3) && this.hideCategoryBox(level+1);
		},
		showCategoryName: function(obj){
			var showName = obj.find('a').text();
			var cateid = obj.data('cateid');
			var level = obj.data('level');
			var name = obj.attr('title');
			(level == '0') || (showName = ' > ' + showName);
			$('span[data-level="'+ level +'"]').html(showName).attr({'data-cateid':cateid,'data-name':name});
			this.hideChildCategoryName(level+1);
		},
		hideChildCategoryName: function(level){
			$('span[data-level="'+ level +'"]').html('').removeAttr('data-cateid').removeAttr('data-name');
			(level < 3) && this.hideChildCategoryName(level + 1);
		},
		showCategoryList: function(data){
			var str = '';
			if(data.length){
				for(var i in data){
					if(data.hasOwnProperty(i)){
						str += '<li>';	
						str += '<a href="javascript:;" data-cateids="'+data[i]['cateIds']+'" data-pids="'+ data[i]['pids'] +'" data-nameZh="'+ data[i]['nameZh']+'" data-nameEn="'+ data[i]['nameEn'] +'" title="'+ data[i]['nameZh'] +'"><span>'+ data[i]['nameZh'] +'</span></a>';
						str += '</li>';
					}
				}
			}else{
				str ='<p style="text-align:center;">无相关类目</p>';
			}
			$('.cate_search_count').html(data.length);
			$('.cate_search_ul').html(str);
			$('.cate_list').show();
			$('.category-ensure').attr('disabled',true).addClass('not');
		},
		getCategoryAttr: function(cateid){
			return $.post('/listing/aliexpress/get-category-attr',{cateid:cateid},'json');
		},
		showCategoryAttr: function(data){
			var attr = '';
			var table = '';
			attrList = [];
			for(var key in data)
			{
				if(data.hasOwnProperty(key)){
					if(data[key].sku){
						attrList.push({
							customizedname: data[key].customizedName,
							customizedpic: data[key].customizedPic,
							keyAttribute: data[key].keyAttribute,
							name_en: data[key].name_en,
							name_zh: data[key].name_zh,
							sku: data[key].sku,
							spec: data[key].spec,
							id: data[key].id,
							required: data[key].required,
							values: data[key].values,
							listData: []
						});
					}else{
						attr += this.appendCategoryAttr(data[key]);
					}
				}
			}
			this.showSpecialAttr();
			$('.product_attributes_box').append(attr);
			$('.attr-value select').on('change',function(){
				var self = $(this);
				var input = self.parent().find('input[type="checkbox"]');
				(self.val() != '') && input.prop('checked',false);
				(self.val() == input.val()) && input.prop('checked',true);
				
			});
			$('.attr-value input[type="checkbox"]').on('click',function(){
				var self = $(this);
				var id = self.val();
				if(self.is(':checked')){
					self.closest('.attr-value').find('select').val(id);
				}
			});
		},
		showSpecialAttr: function(){
			var $this = this;
			$('.variance_info_box .multiProperty').remove();
			if(attrList.length){
				var str = '';
				var CountriesLang = {CN:'中国',UK:'英国',DE:'德国',ES:'西班牙',AU:'澳大利亚',RU:'俄罗斯',ID:'印尼',FR:'法国',IT:'意大利',US:'美国'};
				for(var j in attrList){
					if(attrList.hasOwnProperty(j)){
						str += "<div class='rows input-spacing multiProperty' data-pid='"+ attrList[j].id +"'>";
						str += "<span class='text-left' style='display:inline-block' customizedname='"+ attrList[j].customizedname +"' customizedpic ='"+ attrList[j].customizedpic +"'>"+ attrList[j].name_zh +"</span>";
						(attrList[j].name_en == 'Ships From') && (str += '<span class="remove-btn left-spacing-m" for="'+ attrList[j].name_en +'">展开 </span><span class="sign">非海外仓用户请勿勾选</span>');
						str += "<div class='attr-box input-spacing' data-name='"+ attrList[j].name_en.toLowerCase() +"'>";
						(attrList[j].name_en == 'Ships From') ? str += '<ul style="margin-top:10px;display:none;" show-sign="'+ attrList[j].name_en +'">' : str += "<ul>";
						for(var i in attrList[j].values){
							if(attrList[j].values.hasOwnProperty(i)){
								switch(attrList[j].name_en){
									case 'Ships From':
										str += '<li><input type="checkbox" class="ships_from no-spacing" name="ships_from" value="'+ attrList[j]['values'][i].id +'" data-name="'+ attrList[j].name_en.toLowerCase() +'" data-name_zh= "'+ CountriesLang[attrList[j]['values'][i].name_zh] +'" data-name_en="'+ attrList[j]['values'][i].name_zh +'" data-cid="'+ attrList[j]['values'][i].id +'" data-spec="'+ attrList[j].spec +'" data-pid="'+ attrList[j].id +'"> '+ CountriesLang[attrList[j]['values'][i].name_zh] +'</li>';
										break;
									case 'Color':
										str += '<li><input type="checkbox" name="'+ attrList[j].name_en.toLowerCase().replace(/\s*/g,'') +'" id="p'+attrList[j].id +'-'+ attrList[j]['values'][i].id +'" value="'+ attrList[j]['values'][i].id+'" data-name="'+ attrList[j].name_en.toLowerCase() +'" data-name_zh="'+ attrList[j]['values'][i].name_zh +'" data-name_en="'+ attrList[j]['values'][i].name_en +'"  data-cid="'+ attrList[j]['values'][i].id +'" data-spec="'+ attrList[j].spec +'" data-pid="'+ attrList[j].id +'">';
										if(attrList[j].id == 14){
											str += '<label class="color color-'+ attrList[j]['values'][i].id +'" for="p-'+ attrList[j].id +'-'+ attrList[j]['values'][i].id +'" title="'+ attrList[j]['values'][i].name_zh+'" style="background-color:'+ attrList[j]['values'][i].name_en.toUpperCase().replace(/\s*/g,'') +'"></label>';
										}else{
											str += '<label  title="'+ attrList[j]['values'][i].name_zh +'">'+ attrList[j]['values'][i].name_zh +'</label>';
										}
										str += '</li>';
										break;
									case 'Size':
										str += '<li><input type="checkbox" class="size no-spacing" name="size" value="'+ attrList[j]['values'][i].id +'" data-name="'+ attrList[j].name_en.toLowerCase() +'" data-name_zh="'+ attrList[j]['values'][i].name_zh +'" data-name_en="'+ attrList[j]['values'][i].name_en +'"  data-cid="'+ attrList[j]['values'][i].id +'" data-spec="'+ attrList[j].spec +'" data-pid="'+ attrList[j].id +'">'+ attrList[j]['values'][i].name_zh +'</li>';	
										break;
									default:
										str += '<li><input type="checkbox" class="'+ attrList[j].name_en.toLowerCase() +' no-spacing" name="'+ attrList[j].name_en.toLowerCase().replace(/\s*/g,'') +'" value="'+ attrList[j]['values'][i].id +'" data-name="'+ attrList[j].name_en.toLowerCase() +'" data-name_zh="'+ attrList[j]['values'][i].name_zh +'" data-name_en="'+ attrList[j]['values'][i].name_en +'"  data-cid="'+ attrList[j]['values'][i].id +'" data-spec="'+ attrList[j].spec +'" data-pid="'+ attrList[j].id +'">'+ attrList[j]['values'][i].name_zh +'</li>';
								} 
								
							}
						}
						str += "</ul></div>";
						(attrList[j].customizedname == 1 || attrList[j].customizedpic == 1) && (str += this.appendCustomizedTable(attrList[j].name_zh,attrList[j].id,attrList[j].name_en,attrList[j].customizedpic,attrList[j].customizedname));
						str +="</div>";
					}
				}
				str += "<div class='rows product_list input-spacing product-box multiProperty' style='display:none;'><table class='table table-bordered'></table></div>";
				// str += '<div class="rows input-spacing single_product"><label class="label-left">零售价<i class="sign">*</i></label><input name="sale_price" class="iv-input" style="margin-left:25px;">&nbsp;USD/<span class="unit">件</span></div>';
				// str += '<div class="rows input-spacing single_product"><label class="label-left">库存<i class="sign">*</i></label><input name="inventory" class="iv-input" style="margin-left:25px;">&nbsp;/<span class="unit">件</span></div>';
				// str += '<div class="rows input-spacing single_product"><label class="label-left">商品编码</label><input class="iv-input" name="product_code" class="product_code" style="margin-left:25px;"></div>';
				// str += '<div class="rows input-spacing"><label class="label-left">批发价</label><label><input class="iv-radio left-spacing-l" type="radio" name="is_bulk" value="0" hide="bulk_detail" checked>不支持</label>';
				// str += '<label><input class="iv-radio left-spacing-l" type="radio" name="is_bulk" value="1" show="bulk_detail">支持</label><p class="" show-content="bulk_detail" style="margin-left:100px;display:none">购买数量 <input class="iv-input short-input" type="text" name="bulk_order"> 件及以上时，每件价格在零售价的基础上减免 <input class="iv-input short-input" type="text" name="bulk_discount"> %,即<span data-name="bulk_discount">--</span>折</p></div>';
				$('.variance_info_box').prepend(str);
				$('.single_product').show();
				$('.variance_info_box :checkbox').on('click',function(){
					var self = $(this);
					var id = self.data('cid');
					var pid = self.data('pid');
					var spec = self.data('spec');
					var name_zh = self.data('name_zh');
					var name_en = self.data('name_en');
					var customizedPic = self.closest('div.rows').find('span.text-left').attr('customizedpic');
					var customizedName = self.closest('div.rows').find('span.text-left').attr('customizedname');
					var thead = '<thead>';
					for(var i in attrList){
						if(attrList.hasOwnProperty(i)){
							thead += '<th data-id="'+ attrList[i].id +'" data-name_en ="'+ attrList[i].name_en +'" data-spec="'+ attrList[i].spec +'">'+ attrList[i].name_zh +'</th>'; 
						}
					}
					thead += '<th><i class="sign">*</i> 零售价 <a class="click-btn" set-value="sale_price" data-name="零售价">设置</a></th><th><i class="sign">*</i> 库存 <a class="click-btn" set-value="inventory" data-name="库存">设置</a></th><th>商品编码 <a class="click-btn" set-value="product_code" data-name="商品编码">设置</a></th><tr></thead>';
					var tbody = '<tbody id="goods_list"></tbody>';
					$('.single_product').hide();
					$this.storeProductListData();
					$('.product_list > table').html('').append(thead+tbody);
					for(var i=0;i < attrList.length;i++){
						if(attrList[i].id == pid){
							if(this.checked){
								if(customizedPic == 1 || customizedName == 1){
									//自定义名称
									$this.showCustomizeData(id,name_zh,pid,name_en,customizedPic,customizedName);
								}
								attrList[i].listData.push({
									id: id,
									name_zh: name_zh,
									name_en: name_en,
									pid: pid,
									customizedPic: customizedPic,
									customizedName:customizedName
								});
								$this.appendProductListData();
							}else{
								if(customizedPic == 1 || customizedName == 1){
									$this.hideCustomizeData(id,name_zh,pid,name_en,customizedPic,customizedName);
								}
								for(var j=0;j<attrList[i].listData.length;j++){
									if(attrList[i].listData[j].id == id){
										attrList[i].listData.splice(j,1);
									}
								}	
								$this.appendProductListData();
							}
						} 
					}
				});
			}
			// else{
				// var str = "<div class='rows product_list input-spacing product-box' style='display:none;'><table class='table table-bordered'></table></div>";
				// str += '<div class="rows input-spacing single_product"><label class="label-left">零售价<i class="sign">*</i></label><input name="sale_price" class="iv-input" style="margin-left:25px;">&nbsp;USD/<span class="unit">件</span></div>';
				// str += '<div class="rows input-spacing single_product"><label class="label-left">库存<i class="sign">*</i></label><input name="inventory" class="iv-input" style="margin-left:25px;">&nbsp;/<span class="unit">件</span></div>';
				// str += '<div class="rows input-spacing single_product"><label class="label-left">商品编码</label><input class="iv-input" name="product_code" class="product_code" style="margin-left:25px;"></div>';
				// str += '<div class="rows input-spacing"><label class="label-left">批发价</label><label><input class="iv-radio left-spacing-l" type="radio" name="is_bulk" value="0" hide="bulk_detail" checked>不支持</label><label><input class="iv-radio left-spacing-l" type="radio" name="is_bulk" value="1" show="bulk_detail">支持</label><p class="" show-content="bulk_detail" style="margin-left:100px;display:none">购买数量 <input class="iv-input short-input" type="text" name="bulk_order"> 件及以上时，每件价格在零售价的基础上减免 <input class="iv-input short-input" type="text" name="bulk_discount"> %,即<span data-name="bulk_discount">--</span>折</p></div>';
				// $('.variance_info_box > .form-group').append(str);
			// }

			$('p[show-content] input[name="bulk_discount"]').on('input change',function(){
				var self = $(this);			
				var name = $(this).attr('name');
				var value = $(this).val();	
				var discount = '--';
				if(value != ''){
					if(value % 10 == 0){
						discount = ((1-value/100)*10).toFixed(0);
					}else{
						discount = ((1-value/100)*10).toFixed(1);
					}
				}
				$('p[show-content] span[data-name="'+ name +'"]').html(discount);
			});
		},
		storeProductListData: function(){
			temporaryProductListData = [];
			$('#goods_list tr[data-name="skuProperty"]').each(function(){
				var self = $(this);
				var obj = {},id = self.attr('trId');
				obj['id'] = id;
				obj['sale_price'] = self.find('td[data-name="sale_price"] input[name="sale_price"]').val();
				obj['inventory'] = self.find('td[data-name="inventory"] input[name="inventory"]').val();
				obj['product_code'] = self.find('td[data-name="product_code"] input[name="product_code"]').val();
				temporaryProductListData.push(obj);
			});
		},
		appendCustomizedTable: function(name_zh,pid,name_en,customizedPic,customizedName){
			var str = '<div class="table-box"><table data-pid="'+ pid +'" style="display:none;">';
			if(customizedPic == 1 && customizedName == 1){
				str += '<thead><tr><th style="min-width:53px" data-pid="'+pid+'">'+ name_zh +'</th><th style="min-width: 110px;">自定义名称</th><th style="width:220px">图片(无图片可以不填)</th><th style="width:75px;"></th></tr></thead>';
			}
			if(customizedPic == 1 && customizedName == 0){
				str += '<thead><tr><th style="min-width:53px" data-pid="'+pid+'">'+ name_zh +'</th><th style="width:220px">图片(无图片可以不填)</th><th style="width:75px;"></th></tr></thead>';
			}
			if(customizedPic == 0 && customizedName == 1){
				str += '<thead><tr><th style="min-width:53px" data-pid="'+ pid +'">'+ name_zh +'</th><th style="min-width: 110px;">自定义名称</th></tr></thead>';
			}			
			str += '<tbody>';
			str += '</tbody>';
			str += '</table></div>';
			return str;
		},
		showCustomizeData: function(id,name_zh,pid,name_en,customizedPic,customizedName){
			var color = 'color-'+ id;
			var target= '#select-url-img';
			var str = '';
			if(customizedPic == 1 && customizedName == 1){
				str += '<tr data-cid="'+ id +'" data-pid ="'+ pid +'">';
				if(pid == 14){
					str += '<td data-cid="'+ id +'"><label class=" color '+ color +'" title="'+ name_zh +'"></label></td>';
				}else{
					str += '<td data-cid="'+ id +'"><label>'+ name_zh +'</label></td>';
				}
				str += '<td><input type="text" name="custom_name" class="iv-input" value="'+ name_en.replace(/\s*/g,'') +'"></td>';
				str += '<td><a class="iv-btn btn-success btn-hidden">本地上传<input class="iv-btn btn-success" type="file" multiple="multipe" value="本地上传" data-upload="color" style="width:100%;"></a>';
				str += '<a class="iv-btn btn-success left-spacing-s select_url" btn-resolve target="'+ target +'" title="URL上传">URL上传</a>';
				str += '<a class="iv-btn btn-success left-spacing-s select-lib" btn-resolve btn-reject target="_modal" href="/util/image/select-image-lib">图片库上传</a></td>';
				str += '<td class="img_show"></td>';
				str += '</tr>';
			}

			if(customizedPic == 1 && customizedName == 0){
				str += '<tr data-cid="'+ id +'" data-pid ="'+ pid +'">';
				if(pid == 14){
					str += '<td data-cid="'+ id +'"><label class=" color '+ color +'" title="'+ name_zh +'"></label></td>';
				}else{
					str += '<td data-cid="'+ id +'"><label>'+ name_zh +'</label></td>';
				}
				str += '<td><a class="iv-btn btn-success btn-hidden">本地上传<input class="iv-btn btn-success" type="file" multiple="multipe" value="本地上传" data-upload="color" style="width:100%;"></a>';
				str += '<a class="iv-btn btn-success left-spacing-s select_url" btn-resolve target="'+ target +'" title="URL上传">URL上传</a>';
				str += '<a class="iv-btn btn-success left-spacing-s select-lib" btn-resolve btn-reject target="_modal" href="/util/image/select-image-lib">图片库上传</a></td>';
				str += '<td class="img_show"></td>';
				str += '</tr>';
			}
			if(customizedPic == 0 && customizedName == 1){
				str += '<tr data-cid="'+ id +'" data-pid ="'+ pid +'">';
				if(pid == 14){
					str += '<td data-cid="'+ id +'"><label class=" color '+ color +'" title="'+ name_zh +'"></label></td>';
				}else{
					str += '<td data-cid="'+ id +'"><label>'+ name_zh +'</label></td>';
				}
				str += '<td><input type="text" name="custom_name" class="iv-input" value="'+ name_en.replace(/\s*/g,'') +'" data-id="'+ id +'" ></td>';
				str += '</tr>';
			}
			$('.table-box table[data-pid="'+ pid +'"]').find('tbody').$append(str);
			$('.table-box table[data-pid="'+ pid +'"]').show();
		},
		hideCustomizeData: function(id,name_zh,pid,name_en,customizedPic,customizedName){
			$('.table-box table[data-pid="'+ pid +'"]').find('td[data-cid="'+ id +'"]').closest('tr').remove();
			var len = $('.table-box table[data-pid="'+ pid +'"] tbody').find('tr').length;
			if(len == 0) $('.table-box table[data-pid="'+ pid +'"]').hide();
		},
		appendProductListData: function(){
			attrData = [];
			for(var i in attrList){
				if(attrList.hasOwnProperty(i)){
					var arr = attrList[i].listData;
					if(arr.length != 0){
						attrData.push(arr);
					}
				}
			}
			var len = attrData.length;
			this.appendAllData(attrData,0,'',len);
			this.createTrId();
			this.fillProductListData();
			if($('#goods_list tr[data-name="skuProperty"]').length == 0){
				$('.product_list').hide() 
				$('.single_product').show();
			}
		},
		fillProductListData: function(){
			$(temporaryProductListData).each(function(i){
				$('#goods_list').find('tr[trId="'+ temporaryProductListData[i].id +'"]').find('td[data-name="sale_price"] input[name="sale_price"]').val(temporaryProductListData[i].sale_price);
				$('#goods_list').find('tr[trId="'+ temporaryProductListData[i].id +'"]').find('td[data-name="inventory"] input[name="inventory"]').val(temporaryProductListData[i].inventory);
				$('#goods_list').find('tr[trId="'+ temporaryProductListData[i].id +'"]').find('td[data-name="product_code"] input[name="product_code"]').val(temporaryProductListData[i].product_code);
			});
		},
		createTrId: function(){
			$('#goods_list tr[data-name="skuProperty"]').each(function(){
				var id = '',pushId ='trID';
				$(this).find('td[data-names="property"]').each(function(){
					id += $(this).data('value');
					pushId  += $(this).data('cid');
				});
				$(this).attr('trId',id).attr('pushId',pushId);
			});
		},
		appendAllData: function(arr,idx,node,len){
			var str = '';
			if(idx + 1 == len){
				for(var i in arr[idx]){
					if(arr[idx].hasOwnProperty(i)){
						var obj = arr[idx][i];
						if(node == ''){
							str = this.createProductListHtml(obj.id+'||'+obj.name_zh+'||'+obj.pid+'||'+obj.name_en+'||'+obj.customizedName+'||'+obj.customizedPic);
						}else{
							obj = node+','+obj.id+'||'+obj.name_zh+'||'+obj.pid+'||'+obj.name_en+'||'+obj.customizedName+'||'+obj.customizedPic;
							str = this.createProductListHtml(obj);
						}
					}
				}
			}else{
				for(var i in arr[idx]){
					if(arr[idx].hasOwnProperty(i)){
						var obj = arr[idx][i];
						if(node == ''){
							this.appendAllData(arr,idx+1,(obj.id+'||'+obj.name_zh+'||'+obj.pid+'||'+obj.name_en+'||'+obj.customizedName+'||'+obj.customizedPic),len);
						}else{
							this.appendAllData(arr,idx+1,(node+','+obj.id+'||'+obj.name_zh+'||'+obj.pid+'||'+obj.name_en+'||'+obj.customizedName+'||'+obj.customizedPic),len);
						}
					}
				}
			}
		},
		createProductListHtml: function(str){
		var newArr = [];
		newArr = str.split(',');
		var unit = $('select[name="product_unit"] :selected').data('name_zh').split('/')[0];
		var str = '<tr data-name="skuProperty">';
		for(var i=0;i<attrList.length;i++){
			var id = attrList[i].id;
			$('.product_list table').find('th[data-id="'+ id +'"]').hide();
		}
		for(var i= 0; i<newArr.length;i++){
			var arr = newArr[i].split('||');
			$('.product_list table').find('th[data-id="'+arr[2]+'"]').show();
			if(arr[2] == 14){
				str += '<td data-cid="'+ arr[0] +'" data-pid="'+ arr[2]+'" data-value="'+ arr[3]+'" data-names="property" customizedName="'+ arr[4] +'" customizedPic="'+ arr[5] +'"><label class="color color-'+ arr[0] +'" title="'+ arr[1] +'"></label></td>';
			}else{
				str += '<td data-cid="'+ arr[0] +'" data-pid="'+ arr[2]+'" data-value="'+ arr[3]+'" data-names="property" customizedName="'+ arr[4] +'" customizedPic="'+ arr[5] +'">'+ arr[1] +'</td>';
			}
		}
		str +='<td data-name="sale_price"><input class="iv-input" type="text" check-valid="price" name="sale_price"/>&nbsp;USD/<span class="unit">'+ unit +'</span></td><td data-name="inventory"><input class="iv-input" type="text" name="inventory" check-valid="inventory"></td><td data-name="product_code"><input class="iv-input" type="text" name="product_code" maxlength="20"/></td></tr>';
		$('.product_list table').append(str);
		$('.product_list').show();
		},
		appendCategoryAttr: function(obj){
			$('.product_attributes_box').html('');
			var type = obj.attributeShowTypeValue == 'list_box' ? 'select' : (obj.attributeShowTypeValue == 'check_box' ? 'checkbox' : 'input'); 
			var str  = "<div class='form-group'>";
			if(obj.required){
				str += "<label class='attr-name label-inline'>"+ obj.name_zh +" <span class='sign'>*</span></label>";
			}else{
				str += "<label class='attr-name label-inline'>"+ obj.name_zh +"</label>";
			}
			str += "<div class='attr-value label-spacing' data-id='"+ obj.id +"' data-type='"+ type +"' data-required='"+ obj.required +"' data-name_zh='"+ obj.name_zh +"'>";
			switch(obj.attributeShowTypeValue){
				case 'list_box':
					var hasNone = false;
					str += "<select class='iv-input center-width' placeholder='---请选择---'>";
					str += "<option value=''>---请选择---</option>";
					if(obj.values != undefined){
						for(var k in obj.values){
							if(obj.values.hasOwnProperty(k)){
								(obj.values[k].id == '201512802') && (hasNone = true);
								str += "<option value='"+ obj.values[k].id +"'>"+ obj.values[k].name_zh +"</option>";
							}
						}
					}
					str += '</select>';
					(obj.id == 2 && hasNone) && (str += '<label class="left-spacing-l"><input type="checkbox" value="201512802">无品牌</label>');
					break;
				case 'check_box':
					str += "<ul>";
						for(var k in obj.values){
							if(obj.values.hasOwnProperty(k)){
								str += "<li><input type='checkbox' class='no-spacing' data-id='"+ obj.values[k].id +"'>"+ obj.values[k].name_zh +"</li>";
							}
						}
					str += "</ul>";
					break;
				case 'input':
					str += "<input type='text' class='iv-input'>";
			}
			(obj.required) && (str += '<p class="error-tips input-spacing" style="width:330px;display:none;"><span class="iconfont icon-cuowu sign right-spacing-m"></span></p>');
			str += "</div></div>";
			return str;
		},
		addUserDefinedAttr: function(obj){
			var box = obj.parent();
			var str = this.addUserDefinedAttrView();	
			box.prepend(str);	
			(box.find('.form-group').length >= 10) ? obj.addClass('not'): obj.removeClass('not');
		},
		addUserDefinedAttrView: function(){
		var str = '<div class="form-group  custom-attr input-spacing left-spacing-l">';
		str += '<input class="iv-input custom-name" type="text" placeholder="属性名 - 例如 Color"> ：';
		str += ' <input class="iv-input custom-value" type="text" placeholder="属性值 - 例如 Red">';
		str += '<span class="left-spacing-m remove-btn" onclick="$.aliexpress.removeThisAttr(this)">删除</span>';
		str += '</div>';	
		return str;
		},
		removeThisAttr: function(obj){
			$(obj).parent().remove();
			this.ajustUserDefinedAttrAreaHeight();
		},
		ajustUserDefinedAttrAreaHeight: function(){
			var box = $('.user-defined-box');
			var obj = box.find('.add_attr');
			var total = box.find('.form-group').length;
			(box.find('.form-group').length >= 10) ? obj.addClass('not'): obj.removeClass('not');
			// box.height(total*36 + 70);
		},
		CheckInputContentLength: function(t){
			var len = t.val().length;
			(len >128) && (t.val(t.val().substr(0,128))) && (len = 128);
			$('.product_title_length').html(len);
		},
		GetProductGroupList: function(sellerloginid){
			return $.post('/listing/aliexpress/get-product-group-list',{sellerloginid:sellerloginid},'json');
		},
		AppendProductGroupList: function(t){
			var str = '<ul class="show-list">';
			for(var i in t){
				if(t.hasOwnProperty(i)){
					if(t[i].childGroup != undefined){
						str += '<li><label><span class="iconfont icon-jiahao" show-target="'+ t[i].groupId +'"></span>'+ t[i].groupName+'</label><ul target="'+ t[i].groupId +'" style="display:none;">';
						for(var j in t[i].childGroup){
							if(t[i].childGroup.hasOwnProperty(j)){
								str+= '<li style="margin-left:10px;"><label><input type="checkbox" name="product_groups[]" value="'+ t[i].childGroup[j].groupId +'='+ t[i].childGroup[j].groupName +'">'+ t[i].childGroup[j].groupName +'</label></li>';
							}
						}
						str += '</ul></li>';
					}else{
						str += '<li><label><input type="checkbox" name="product_groups[]" value="'+ t[i].groupId +'='+ t[i].groupName +'">'+ t[i].groupName +'</label></li>';
					}
				}
			}
			str += '</ul>';
			return str;
		},
		AppendInfoModule: function(data){
			var str = '';
			var moduleList = {'relation':'关联模板',}
			infoModule = [];
			for(var i = 0; i < data.length; i++){
				infoModule[data[i].id] = data[i].name;
				str += '<tr>';
				str += '<td>'+ data[i].name +'</td>';
				str += '<td>'+ moduleList[data[i].type] + '</td>';
				str += '<td><input type="checkbox" name="Ids[]" value="'+ data[i].id +'"></td>';
				str += '</tr>';
			}
			return str;
		},
		getInfoModule: function(sellerloginid,name){
			return $.ajax({
				type:'post',
				dataType:'json',
				data:{
					'sellerloginid':sellerloginid,
					'name': name
				},
				url:'/listing/aliexpress/get-info-module'
			});
		},	
		getFreightTemplate: function(sellerloginid){
			return $.ajax({
				type:'post',
				dataType:'json',	
				data:'sellerloginid='+sellerloginid,
				url: '/listing/aliexpress/get-freight-template'
			});
		},
		getPromiseTemplate: function(sellerloginid){
			return $.ajax({
				type:'post',
				dataType:'json',
				data:'sellerloginid='+sellerloginid,
				url: '/listing/aliexpress/get-promise-template'
			});
		},
		fillProductAttrData: function(t){
			if(t.data('fill') == 'product_code'){
				$('#goods_list tr[data-name="skuProperty"]').each(function(){
					var $this  = $(this);
					var tdName = '';
					$this.find('td[data-names="property"]').each(function(){
						tdName += $(this).data('value') + '-';
					});
					var product_code = (t.val().toString() + '-' + tdName).replace(/\s*/g,'').replace(/-$/g,'');
					$this.find('input[name="'+ t.data('fill') +'"]').val(product_code);
				});

			}else{
				$('#goods_list input[name="'+ t.data('fill') +'"]').val(t.val());
			}
			$('.qtip').hide();

		},
		fillColorImg: function(t,res,sellerloginid){
			var $this = this;
			$.get('/listing/aliexpress/upload-img-to-aliexpress-bank?img='+res.data.original+'&sellerloginid='+sellerloginid).then(function(data){
				$this.appendColorImg(t,data['photobankUrl']);
			});

		},
		// uploadToAliexpressBank: function(imgurl){
		// 	return $.promise(function(reslove,reject){

		// 	});
		// },
		appendColorImg: function(e,url){
			var img = document.createElement('img');
			img.src = url;
			img.width= 30;
			img.height = 30;
			var div = document.createElement('div');
			div.appendChild(img);
			var span = document.createElement('span');
			span.setAttribute('onclick','$.aliexpress.delThisAttr(this)');
			span.innerHTML ='删除';
			span.setAttribute('class','remove-btn');
			e.closest('tr').find('.img_show').html('').append(div).append(span);
		},
		delThisAttr: function(obj){
			$(obj).parent().html('');
		},
		initAliexpressList:function(){
			$('.delOne').on('click',function(){
				if(confirm('您确定要删除吗？')){
					var id = $(this).data('id');
					$.get('/listing/aliexpress/del-product?id='+ id).done(function(data){
						if(data == 1){
							$('input[data-id="'+ id +'"]').closest('tr').remove();
						}
					});
				}
			});
			$('.batch_del').on('click',function(){
				if(!$('input[name="check_one"]:checked').length){
					$.tips({type:'error',msg:'请至少选择一件商品',exitTime:3000});
					return false;
				}
				if(confirm('您确定要删除吗？')){
					var ajaxFn = [];
					$('input[name="check_one"]:checked').each(function(){
						var id = $(this).data('id');
						console.log(id);
						ajaxFn.push(function(){
							return $.get('/listing/aliexpress/del-product?id='+ id);	
						});
					});
					var is_all_success = true;
					$.showLoading();
					$.asyncQueue(ajaxFn,function(idx,data){
						if(data != 1){
							is_all_success = false;
						}
						if(idx == (ajaxFn.length - 1)){
							$.hideLoading();
							if(is_all_success){
								$.tips({
									type:'success',
									msg : '删除成功',
									exitTime: 3000
								});
								setTimeout(function(){
									window.location.reload();
								},2000);
							}else{
								$.tips({
									type:'error',
									msg: '网络延时，请稍后再试',
									exitTime: 3000
								});
							}
						}
					});
				}
			})
			$('input[name="check_all"]').on('click',function(){
				var self = $(this);	
				self.is(':checked')? $('input[name="check_one"]').prop('checked',true) : $('input[name="check_one"]').prop('checked',false);
			});
			$('.pushProduct').on('click',function(){
				var id = $(this).closest('tr').find('input[name="check_one"]').data('id');
				console.log(id);
				$.aliexpress.post(id).done(function(data){
					if(data['return'] == true){
						$.tips({type:'success',msg:'发布成功',exitTime:3000});
						setTimeout(function(){
							window.loaction.reload();
						},2000);
					}else{
						// $.tips({type:'error',msg:'错误信息:'+data['msg'].error_message,existTime:3000});
					}
				});
			});
			var  QueueInfo = [];
			$('.batch_post').on('click',function(){
				var $this = $(this);
				if(!$('input[name="check_one"]:checked').length){
					$.tips({type:'error',msg:'请至少选择一件商品',exitTime:3000});
					return false;
				}
				var ids = [];
				$('input[name="check_one"]:checked').each(function(){
					ids.push($(this).data('id'));
				});
				$.aliexpress.pushProduct(ids).done(function(data){
					QueueInfo = data;
					console.log(data);
					$.tips({type:"success",msg:'产品发布成功,请在发布中查看您的发布的商品',exitTime:3000});
					setTimeout(function(){
						window.location.reload();
					},2000);
				});	
			});
			$('img[qtip]').qtip({
				content:{
					text:function(event,api){
						console.log(api.elements.target.context.currentSrc);
						var str  = '<img src="'+ api.elements.target.context.currentSrc +'" width="200">';
						return str;
					}
				},
				show: 'mouseover',
				hide: 'mouseout',
				position:{
					at: 'rightMiddle',
					my: 'leftMiddle',
					viewport:$(window)
				},
				style: 'qtip-light'
			});
			$('a.edit_tips').qtip({
				content:{
					text: $(this).attr('title')
				},
				position:{
					at:'rightMiddle',
					my:'leftMiddle',
					viewport:$(window)
				}
			});
			$('.filter').on('change',function(){
				$(this).closest('form').submit();
			});
		},
		initAliexpressEdit:function(){
			var selleruserid = $('select[name="selleruserid"]').val();
			var cateid = $('select[name="categoryid"]').val();
			var $this = this;
			$('select[name="selleruserid"]').trigger('change');
			$('select[name="product_unit"]').trigger('change');
			$this.getCategoryAttr(cateid).done(function(data){
				$this.showCategoryAttr(data);
				$this.fillCategoryAttr();
				$this.fillSpecialAttr();

			});
			$this.getFreightTemplate(selleruserid).done(function(data){
				if(data != ''){
					var freight_templateid = $('select[name="freight_templateid"]').data('id');
					var str = '<option value="">---请选择产品运费模板---</option>';
					for(var i = 0 ;i<data.length;i++){
						// var selected = (freight_templateid == data[i]['templateid']) ? 'checked' : '';
						str += '<option value="'+ data[i]['templateid']+'">'+ data[i]['template_name'] +'</option>';
					}
				}
				$('select[name="freight_templateid"]').html('').append(str).val(freight_templateid);
			});
			$this.getPromiseTemplate(selleruserid).done(function(data){
				if(data != ''){
					var promise_templateid = $('select[name="promise_templateid"]').data('id');
					var str = '<option value="">---请选择产品运费模板---</option>';
					for(var i = 0 ;i<data.length;i++){
						// var selected = (promise_templateid == data[i]['templateid']) ? 'checked' : '';
						str += '<option value="'+ data[i]['templateid']+'">'+ data[i]['name'] +'</option>';
					}
					$('select[name="promise_templateid"]').html('').append(str).val(promise_templateid); 
				}	
			});
		},
		initAliexpressOnlineEdit:function(){
			var selleruserid = $('select[name="selleruserid"]').val();
			var cateid = $('select[name="categoryid"]').val();
			var $this = this;
			$.promise(function(resolve,reject){
				$this.GetProductGroupList(selleruserid).done(function(data){
				var groups = $this.AppendProductGroupList(data);
				$('#select-product-group .group-list').html('').append(groups);
				});
				$this.getInfoModule(selleruserid).done(function(data){
					$('#infoModule').find('tbody').html($this.AppendInfoModule(data));
				});	
				resolve();
			}).then(function(){
				$('select[name="selleruserid"]').attr('disabled',true);
			});	
			
			$('select[name="product_unit"]').trigger('change');
			$this.getCategoryAttr(cateid).done(function(data){
				$this.showCategoryAttr(data);
				$this.fillCategoryAttr();
				$this.fillSpecialAttr();

			});
			$this.getFreightTemplate(selleruserid).done(function(data){
				if(data != ''){
					var freight_templateid = $('select[name="freight_templateid"]').data('id');
					var str = '<option value="">---请选择产品运费模板---</option>';
					for(var i = 0 ;i<data.length;i++){
						// var selected = (freight_templateid == data[i]['templateid']) ? 'checked' : '';
						str += '<option value="'+ data[i]['templateid']+'">'+ data[i]['template_name'] +'</option>';
					}
				}
				$('select[name="freight_templateid"]').html('').append(str).val(freight_templateid);
			});
			$this.getPromiseTemplate(selleruserid).done(function(data){
				if(data != ''){
					var promise_templateid = $('select[name="promise_templateid"]').data('id');
					var str = '<option value="">---请选择产品运费模板---</option>';
					for(var i = 0 ;i<data.length;i++){
						// var selected = (promise_templateid == data[i]['templateid']) ? 'checked' : '';
						str += '<option value="'+ data[i]['templateid']+'">'+ data[i]['name'] +'</option>';
					}
					$('select[name="promise_templateid"]').html('').append(str).val(promise_templateid); 
				}	
			});
		},
		fillCategoryAttr: function(){
			var cateAttr = this.productPropertys;
			for(var i in cateAttr){
				if(cateAttr.hasOwnProperty(i)){
					if(cateAttr[i].attrNameId != undefined){
						var attrBox = $('.product_attributes_box .attr-value[data-id="'+ cateAttr[i].attrNameId +'"]');
						var type = attrBox.data('type');
						switch(type){
							case 'select':
								attrBox.find('select').val(cateAttr[i]["attrValueId"]).trigger('change');
								attrBox.find('input[value="'+cateAttr[i]["attrValueId"]+'"]').prop('checked',true);
								break;
							case 'checkbox':
								attrBox.find('input[type="checkbox"][data-id="'+ cateAttr[i]["attrValueId"] +'"]').prop('checked',true);
								break;
							case 'input':
								attrBox.find('input[type="text"]').val(cateAttr[i]['attrValue']);
						}
					}else{
						var str = '<div class="form-group custom-attr input-spacing left-spacing-l">';
						str += '<input class="iv-input custom-name" type="text" placeholder="属性名 - 例如 Color" value="'+ cateAttr[i].attrName +'"> : ';
						str += '<input class="iv-input custom-value" type="text" placeholder="属性值 - 例如 Red" value="'+ cateAttr[i].attrValue +'">';
						str += '<span class="left-spacing-m remove-btn" onclick="$.aliexpress.removeThisAttr(this)">删除</span>';
						str += '</div>';
						$('.user-defined-box').prepend(str);
					}
				}
			}
		},
		fillSpecialAttr:function(){
			var skuAttr = this.skuAttr;
			for(var i in skuAttr){
				if(skuAttr.hasOwnProperty(i)){
					if(skuAttr[i].aeopSKUProperty && skuAttr[i].aeopSKUProperty.length != 0){
						var trId= '';
						for(var j in skuAttr[i].aeopSKUProperty){
							if(skuAttr[i].aeopSKUProperty && skuAttr[i].aeopSKUProperty.hasOwnProperty(j)){
								var property = skuAttr[i].aeopSKUProperty[j];
								var pid = property.skuPropertyId;
								var id = property.propertyValueId;
								var custom_name = property.propertyValueDefinitionName;
								var img_url = property.skuImage;
								var input = $('.variance_info_box').find('input[data-pid="'+ pid +'"][data-cid="'+ id +'"]');
								input.is(':checked') || input.trigger('click');
								var customTr = $('.variance_info_box').find('tr[data-pid="'+ pid +'"][data-cid="'+ id +'"]');	

								(custom_name != undefined) && (customTr.find('input[name="custom_name"]').val(custom_name));
								var str = '<div><img src="'+ img_url +'" width="30" height="30"></div><span onclick="$.aliexpress.delThisAttr(this)" class="remove-btn">删除</span>';
								(img_url !=undefined) && (customTr.find('.img_show').html(str));
								trId += input.data('name_en');
							}
						}
						var Tr = $('#goods_list tr[trid="'+trId+'"]');
						Tr.find('input[name="sale_price"]').val(skuAttr[i].skuPrice);
						Tr.find('input[name="inventory"]').val(skuAttr[i].ipmSkuStock);
						Tr.find('input[name="product_code"]').val(skuAttr[i].skuCode);
					}else{
						$('.single_product').find('input[name="sale_price"]').val(skuAttr[i].skuPrice);
						$('.single_product').find('input[name="inventory"]').val(skuAttr[i].ipmSkuStock);
						$('.single_product').find('input[name="product_code"]').val(skuAttr[i].skuCode);
					}
				}
			}
		},
		save: function($id){
			var $this = this;
			return $.promise(function(resolve,reject){
				data.product = [];
				var selleruserid = $('select[name="selleruserid"]').val();//速卖通店铺ID *
				var categoryid = $('select[name="categoryid"]').val().toString(); //产品类目ID *
				var aeopAeProductPropertys = $this.getProductProperty();// 产品类目属性aeopAeProductPropertys ,json格式 *
				var subject = $('.product_title').find('input[name="subject"]').val(); // 产品标题 *
				var product_groups = $('.product_group_list_box span').data('id');//产品分组 
				var img_url = '';
				var product_unit = $('select[name="product_unit"]').val(); // 最小计量单位 *
				var package_type = $('.sale_ways input[name="package_type"]:checked').val();// 销售方式*
				var lot_num = $('.sale_ways input[name="lot_num"]').val();
				lot_num = (package_type == 0) ? 1 : (lot_num == '') ? 1 : lot_num;
				var reduce_strategy = $('input[name="reduce_strategy"]:checked').val(); //库存扣减方式 1-下单减库存 2-支付减库存
				var delivery_time = $('input[name="delivery_time"]').val();
				delivery_time = (delivery_time== '' ) ? 0 : delivery_time; // 发货期*
				var is_bulk = $('input[name="is_bulk"]:checked').val();//是否支持批发价
				var detail = $this.getProductDetail();
				var product_gross_weight = $('input[name="product_gross_weight"]').val(); //产品包装后的重量,毛重 *
				var isPackSell = $('input[name="isPackSell"]').is(':checked') ? 1:0;
				var baseUnit = '';
				var addUnit = '';
				var addWeight = '';
				if(isPackSell == 1){
					baseUnit = $('input[name="baseUnit"]').val();  //isPackSell为true时,此项必填。购买几件以内不增加运费。
					addUnit = $('input[name="addUnit"]').val();//isPackSell为true时,此项必填。 每增加件数.
					addWeight = $('input[name="addWeight"]').val(); //isPackSell为true时,此项必填。 对应增加的重量
				}
				var product_length = $('input[name="product_length"]').val();
				var product_width = $('input[name="product_width"]').val();
				var product_height = $('input[name="product_height"]').val();
				var freight_templateid = $('select[name="freight_templateid"]').val(); //产品运费模板ID *
				var promise_templateid = $('select[name="promise_templateid"]').val();	 //服务模板ID *
				var wsValidNum = $('input[name="wsValidNum"]:checked').val(); //产品有效期 *
				var img_url = $this.getProductPic();// data.product.img_url 产品图片*
				data.product = {
					'selleruserid': selleruserid,
					'categoryid': categoryid,
					'aeopAeProductPropertys': aeopAeProductPropertys,
					'subject': subject,
					'groups':product_groups,
					'img_url': img_url,
					'product_unit':product_unit,
					'package_type':package_type,
					'lot_num':lot_num,
					'reduce_strategy':reduce_strategy,
					'delivery_time':delivery_time,
					'detail':detail,
					'product_gross_weight':product_gross_weight,
					'isPackSell':isPackSell,
					'baseUnit':baseUnit,
					'addUnit':addUnit,
					'addWeight':addWeight,
					'product_length':product_length,
					'product_width': product_width,
					'product_height': product_height,
					'freight_templateid':freight_templateid,
					'promise_templateid':promise_templateid,
					'wsValidNum':wsValidNum
				}
				if(is_bulk == 1){
					var bulk_order = $('input[name="bulk_order"]').val();  //批发最小数量 
					var bulk_discount = $('input[name="bulk_discount"]').val();  //批发折扣。扩大100倍
					data.product['bulk_order'] = bulk_order;
					data.product['bulk_discount'] = bulk_discount;

				}
				var aeopAeProductSKUs = $this.getProductSKUs();  //data.product.aeopAeProductSKUs 产品编码等sku属性,json格式 *
				if(aeopAeProductSKUs.length == 1){
					if(aeopAeProductSKUs[0].aeopSKUProperty.length == 0){
						data.product['product_price'] = aeopAeProductSKUs[0].skuPrice;
					}

				}
				data.product['aeopAeProductSKUs'] = aeopAeProductSKUs;
				console.log(data);
				$this.check();
				if(!data.check){
				var _rtn = {type:'error',msg:data.msg,exitTime:3000};
					reject(_rtn);
				}else{
					$.showLoading();
					$.ajax({
						type:'post',
						dataType: 'json',
						url : '/listing/aliexpress/save?id='+$id,
						data: {
								'product':data.product
						}
					}).success(resolve)
					.fail(function(){
						reject({type:'error',msg:'保存失败'});
					});
				}
			
			});
		},
		onlineSave: function($productid){
			var $this = this;
			return $.promise(function(resolve,reject){
				data.product = [];
				var selleruserid = $('select[name="selleruserid"]').val();//速卖通店铺ID *
				var categoryid = $('select[name="categoryid"]').val().toString(); //产品类目ID *
				var aeopAeProductPropertys = $this.getProductProperty();// 产品类目属性aeopAeProductPropertys ,json格式 *
				var subject = $('.product_title').find('input[name="subject"]').val(); // 产品标题 *
				var product_groups = $('.product_group_list_box span').data('id');//产品分组 
				var img_url = '';
				var product_unit = $('select[name="product_unit"]').val(); // 最小计量单位 *
				var package_type = $('.sale_ways input[name="package_type"]:checked').val();// 销售方式*
				var lot_num = $('.sale_ways input[name="lot_num"]').val();
				lot_num = (package_type == 0) ? 1 : (lot_num == '') ? 1 : lot_num;
				var reduce_strategy = $('input[name="reduce_strategy"]:checked').val(); //库存扣减方式 1-下单减库存 2-支付减库存
				var delivery_time = $('input[name="delivery_time"]').val();
				delivery_time = (delivery_time== '' ) ? 0 : delivery_time; // 发货期*
				var is_bulk = $('input[name="is_bulk"]:checked').val();//是否支持批发价
				var detail = $this.getProductDetail();
				var product_gross_weight = $('input[name="product_gross_weight"]').val(); //产品包装后的重量,毛重 *
				var isPackSell = $('input[name="isPackSell"]').is(':checked') ? 1:0;
				var baseUnit = '';
				var addUnit = '';
				var addWeight = '';
				if(isPackSell == 1){
					baseUnit = $('input[name="baseUnit"]').val();  //isPackSell为true时,此项必填。购买几件以内不增加运费。
					addUnit = $('input[name="addUnit"]').val();//isPackSell为true时,此项必填。 每增加件数.
					addWeight = $('input[name="addWeight"]').val(); //isPackSell为true时,此项必填。 对应增加的重量
				}
				var product_length = $('input[name="product_length"]').val();
				var product_width = $('input[name="product_width"]').val();
				var product_height = $('input[name="product_height"]').val();
				var freight_templateid = $('select[name="freight_templateid"]').val(); //产品运费模板ID *
				var promise_templateid = $('select[name="promise_templateid"]').val();	 //服务模板ID *
				var wsValidNum = $('input[name="wsValidNum"]:checked').val(); //产品有效期 *
				var img_url = $this.getProductPic();// data.product.img_url 产品图片*
				data.product = {
					'selleruserid': selleruserid,
					'categoryid': categoryid,
					'aeopAeProductPropertys': aeopAeProductPropertys,
					'subject': subject,
					'groups':product_groups,
					'img_url': img_url,
					'product_unit':product_unit,
					'package_type':package_type,
					'lot_num':lot_num,
					'reduce_strategy':reduce_strategy,
					'delivery_time':delivery_time,
					'detail':detail,
					'product_gross_weight':product_gross_weight,
					'isPackSell':isPackSell,
					'baseUnit':baseUnit,
					'addUnit':addUnit,
					'addWeight':addWeight,
					'product_length':product_length,
					'product_width': product_width,
					'product_height': product_height,
					'freight_templateid':freight_templateid,
					'promise_templateid':promise_templateid,
					'wsValidNum':wsValidNum
				}
				if(is_bulk == 1){
					var bulk_order = $('input[name="bulk_order"]').val();  //批发最小数量 
					var bulk_discount = $('input[name="bulk_discount"]').val();  //批发折扣。扩大100倍
					data.product['bulk_order'] = bulk_order;
					data.product['bulk_discount'] = bulk_discount;

				}
				var aeopAeProductSKUs = $this.getProductSKUs();  //data.product.aeopAeProductSKUs 产品编码等sku属性,json格式 *
				if(aeopAeProductSKUs.length == 1){
					if(aeopAeProductSKUs[0].aeopSKUProperty.length == 0){
						data.product['product_price'] = aeopAeProductSKUs[0].ipmSkuStock;
					}

				}
				data.product['aeopAeProductSKUs'] = aeopAeProductSKUs;
				console.log(data);
				$this.check();
				if(!data.check){
				var _rtn = {type:'error',msg:data.msg,exitTime:3000};
					reject(_rtn);
				}else{
					$.showLoading();
					$.ajax({
						type:'post',
						dataType: 'json',
						url : '/listing/aliexpress/online-save?productid='+$productid,
						data: {
								'product':data.product
						}
					}).success(resolve)
					.fail(function(){
						reject({type:'error',msg:'保存失败'});
					});
				}
			
			});
		},
		pushProduct:function($ids){
			return $.post('/listing/aliexpress/push-product',{'ids':$ids},'json');
		},
		post: function($id,sellerloginid){
			var data = {};
			data['id'] = $id;
			data['sellerloginid'] = sellerloginid;
			return $.$ajax({
				url:'/listing/aliexpress/push',
				type:'get',
				dataType:'json',
				data: data
				});
		},
		editPost: function($id,sellerloginid){
			var data = {};
			data['id'] = $id;
			data['sellerloginid'] = sellerloginid;
			return $.ajax({
				url:'/listing/aliexpress/edit-push',
				type:'get',
				dataType:'json',
				data: data
				});
		},
		getProductDetail: function(){
			var detail = $('textarea.ali-editor').val();
			// console.log(detail.replace(/<img[/s]+[data\-kse\-id].*?>/gi,''));
			// return detail.replace(/<img[\s]+data-kse-id.*?\/>/g,'');
			return detail;
		},
		getProductSKUs: function(){
			var Property = [];
			if($('#goods_list tr[data-name="skuProperty"]').length == 0){
				var product_price = $('.single_product input[name="sale_price"]').val();
				var ipmSkuStock = $('.single_product input[name="inventory"]').val();
				var skuStock = true;
				var skuCode = $('.single_product input[name="product_code"]').val();
				Property.push({
					'aeopSKUProperty':[], 
					'skuPrice': product_price,
					'skuStock': skuStock,
					'skuCode': skuCode,
					'ipmSkuStock': ipmSkuStock,
					'currencyCode':'USD'
				});
			}else{
				$('#goods_list tr[data-name="skuProperty"]').each(function(){
					var skuProperty = [];
					var t = $(this);		
					var skuPrice = t.find('input[name="sale_price"]').val();	
					var skuStock = true;
					var ipmSkuStock = t.find('input[name="inventory"]').val();
					var skuCode = t.find('input[name="product_code"]').val();
					t.find('td[data-names="property"]').each(function(){
						var $this = $(this);
						var customizedname = $this.attr('customizedname');
						var customizedpic = $this.attr('customizedpic');
						var pid = $this.data('pid');
						var id = $this.data('cid');
						if((customizedname == 1) || (customizedpic == 1)){
							var customTr = $('table[data-pid="'+ pid +'"] tr[data-cid="'+ id +'"]');
							var propertyValueDefinitionName = (customizedname == 1) ? customTr.find('input[name="custom_name"]').val() : '';
							var skuImage = (customizedpic == 1) ? customTr.find('.img_show img').attr('src') : '';	
							skuProperty.push({
								'skuPropertyId': pid,
								'propertyValueId': id,
								'propertyValueDefinitionName': propertyValueDefinitionName,
								'skuImage': skuImage
							});
						}else{
							skuProperty.push({
								'skuPropertyId': pid,
								'propertyValueId': id
							});
						}
					});
					Property.push({
						'aeopSKUProperty': skuProperty,	
						'skuPrice':skuPrice,
						'skuStock': skuStock,
						'skuCode':skuCode, 	
						'ipmSkuStock':ipmSkuStock,
						'currencyCode': 'USD'
					});
				});
			}
			return Property;
		},
		getProductProperty: function(){
			var property = [];
			$('.product_attributes_box').find('.attr-value').each(function(){
				var self = $(this);
				var type = self.data('type');
				var pid = self.data('id');
				switch(type){
					case 'select':
							if(self.find('select :selected').val() != ''){
								property.push({
									'attrNameId':self.data('id'),
									'attrValueId': self.find('select :selected').val()
								});
							}
						break;
					case 'checkbox':
						self.find(':checked').each(function(){
							property.push({
								'attrNameId': self.data('id'),
								'attrValueId': $(this).data('id')
							});
						});
						break;
					case 'input':
						if(self.find('input').val() != ''){
							property.push({
								'attrNameId': self.data('id'),	
								'attrValue': self.find('input').val()
							});
						}
				}
			});
			var customAttrLen = $('.user-defined-box').find('.custom-attr').length;	
			if(customAttrLen > 0){
				$('.user-defined-box').find('.custom-attr').each(function(){
					var t = $(this);
					var custom_name = t.find('.custom-name').val();
					var custom_value = t.find('.custom-value').val();
					if((custom_name != '') && (custom_value != '')){
						property.push({
							'attrName': custom_name,
							'attrValue': custom_value
						});	
					}
				});
			}
			return property;
		},
		getProductPic: function(){
			var t = $('.product_info_box').find('input[name="extra_images[]"]:checked').length;
			var extra_images  = [];
			var main_image =  '';
			var img_url = '';
			var $this = this;
			var sellerloginid = $('select[name="selleruserid"]').val();
			if(t){
				$('.iv-image-box').each(function(){
					var $this = $(this);
					var extra_image = $this.find('input[name="extra_images[]"]:checked').val();
					if(extra_image != undefined || extra_image != 'on') extra_images.push(extra_image);
					if($(this).find('input[name="main_image"]:checked').val() != undefined)
					main_image = $(this).find('input[name="main_image"]:checked').val();
				});
				var index = $.inArray(main_image,extra_images);
				(index >= 0) && extra_images.splice(index,1);
				img_url = (main_image + ';' + extra_images.join(';')).replace(/^;+|;+$/g,'');
			}
			return img_url;
		},
		check: function(){
			$('.error-tips').hide();
			$('.error_reminded').removeClass('error_reminded');
			data.check = true;
			data.msg = '';
			if(data.product.selleruserid == ''){
				data.check = false;	
				data.msg = '请先选择速卖通店铺';
				$('.selleruser_box .error-tips').show();
				return false;
			}
			if(data.product.categoryid == ''){
				data.check = false;
				data.msg = '请选择产品类目';
				$('.category_box .error-tips').show();
				return false;
			}

			var isPropertyEmpty = false;
			$('.attr-value[data-required="1"]').each(function(){
				var self = $(this);
				var type = self.data('type');
				var name = self.data('name_zh');
				if(type == 'checkbox'){
					if(self.find('input[type="checkbox"]:checked').length == 0){
						self.find('p.error-tips').append(name+'为必填项').show();
						isPropertyEmpty = true;
					}
				}else{
					if(!self.find(type).val()){
						self.find('p.error-tips').append(name+'为必填项').show();
						isPropertyEmpty = true;
					}
				}
				if(isPropertyEmpty){
					data.check = false;
					data.msg = '请填写产品必填属性';
					return false;
				}

			});

			if(data.product.aeopAeProductPropertys == ''){
				data.check = false;
				data.msg = '请填写产品属性';
				return false;	
			}

			if(data.product.subject == ''){
				data.check = false;
				data.msg = '请填写产品标题';
				$('.product_title .error-tips').show();
				return false;
			}
			if(data.product.img_url == ''){
				data.check = false;
				data.msg = '请选择产品图片';
				return false;
			}else{
				var len = data.product.img_url.split(';').length;
				if(len > 6){
					data.check = false;
					data.msg = '产品图片数量不能超过6张';
				}
			}


			// if(data.product.product_unit == ''){
			// 	data.check = false;
			// 	data.msg = '请选择产品计量单位';
			// 	return false;
			// }

			// if(data.product.package_type == ''){
			// 	data.check = false;
			// 	data.msg = '请选择产品销售方式';
			// 	return false;
			// }
			if(data.product.package_type == true){
				if(data.product.lot_num <= 1){
					data.check = false;
					data.msg = '请填写打包销售每包件数';
					$('input[name="lot_num"]').addClass('error_reminded');
					return false;
				}
			}
			// if(data.product.reduce_strategy == ''){
			// 	data.check = false;
			// 	data.msg = '请选择产品库存扣减方式';
			// 	return false;
			// }
			if(data.product.delivery_time == ''){
				data.check = false;
				data.msg = '请填写发货期';
				$('input[name="delivery_time"]').addClass('error_reminded');
				$('.delivery_time_box .error_tips').html('<span class="iconfont icon-cuowu sign right-spacing-m"></span> 发货期为必填项').show();
				return false;
			}else{
				if(data.product.delivery_time < 1 || data.product.delivery_time > 7){
					data.check = false;
					data.msg = '请填写有效的发货期';
					$('input[name="delivery_time"]').addClass('error_reminded');
					$('.delivery_time_box .error-tips').html('<span class="iconfont icon-cuowu sign right-spacing-m"></span> 发货期的有效范围:1-7天').show();
					return false;	
				}
			}
			if(data.product.aeopAeProductSKUs == undefined){
				data.check = false;
				data.msg = '请填写变体信息';
				return false;
			}
			for(var i =0; i<data.product.aeopAeProductSKUs.length; i++){
				if(data.product.aeopAeProductSKUs[i].skuPrice == ''){
					data.check = false;
					data.msg = '请填写产品零售价';
					return false;
				}
				if(data.product.aeopAeProductSKUs[i].ipmSkuStock && data.product.aeopAeProductSKUs[i].ipmSkuStock <= 0 && data.product.aeopAeProductSKUs[i].ipmSkuStock > 999999){
					data.check = false;
					data.msg = '请填写合理的产品库存,取值范围:1~999999';
					return false;
				}
				if(data.product.skuCode == ''){
					data.check = false;
					data.msg = '请填写产品的商品编码';
					return false;
				}
			}
			var $find = /(<img[\s]+data-kse-id.*?\/>)/g;
			var $match = data.product.detail.match($find);
			if($match!= undefined && $match.length > 2){
				data.check = false;
				data.msg = '产品信息模块不能超过2个';
			}
			if(data.product.detail.replace($find,'') == ''){
				data.check = false;
				data.msg = '产品的产品描述不能为空';
				return false;
			}

			if(data.product.product_gross_weight == ''){
				data.check = false;
				data.msg = '请填写产品包装后的重量';
				$('.product_gross_weight_tips').html('<span class="iconfont icon-cuowu sign right-spacing-m"></span>产品包装后的重量为必填项').show();
				return false;
			}else if(data.product.product_gross_weight < 0.001 || data.product.product_gross_weight > 500){
				data.check = false;
				data.msg = '请填写合理的产品包装后的重量';
				$('.product_gross_weight_tips').html('<span class="iconfont icon-cuowu sign right-spacing-m"></span>产品包装后的重量的取值范围为:0.001-500').show();
				return false;
			}
			if(data.product.isPackSell == 1){
				if(data.product.baseUnit <1 && data.product.baseUnit > 1000){
					data.check = false;
					data.msg = '自定义称重时请填写合理的按单件产品计算运费的商品购买数量,取值范围:1~1000';
					return false;
				}
				if(data.product.addUnit <1 && data.product.addUnit > 1000){
					data.check = false;
					data.msg = '自定义称重时请填写合理的增加运费的商品购买数量,取值范围:1~1000';
					return false;
				}
				if(data.product.addWeight < 0.001 && data.product.addWeight > 500.00){
					data.check = false;
					data.msg = '自定义称重时请填写合理的对应增加的重量,取值范围:0.001~500.000';
					return false;
				}
			}
			if(data.product.product_length == '' || data.product.product_width == '' || data.product.product_height == ''){
				data.check = false;
				data.msg = '请填写产品包装后的尺寸';
				return false;
			}
			if(data.product.freight_templateid == ''){
				data.check = false;
				data.msg = '请选择产品运费模板';
				return false;
			}
			if(data.product.promise_templateid == '' ){
				data.check = false;
				data.msg = '请选择产品服务模板';
				return false;
			}
		},
		loadJsFile: function(src,key,pos){
			if(pos == undefined)   pos = 'before';
			var scripts = document.getElementsByTagName('SCRIPT');
			var script = $('<script type="text/javascript" src="'+ src +'"></script>');
			// console.log(thescript.length);
			if(key == '' || key == undefined){

				var thescript = $(scripts[scripts.length-1]);
				thescript.after(script);
			}else{
				var thescript = $('script[src="'+ key +'"]');
				if(!thescript.length){
					$(scripts).each(function(){
						var self = $(this);
						if(self.attr('src') != undefined){
							// console.log(self.attr('src'));
							var src = self.attr('src');
							console.log(src.indexOf(key));
							if(src.indexOf(key) != -1){
								thescript = self;
							}
						}
					});
				}
			(pos == 'before') ? thescript.before(script) : thescript.after(script);
			}
		}
	}

	$.domReady(function($el){
		var $a = $.aliexpress;
		var $document = this;
		$el('.nav').on('click','li[data-leaf="1"]',function(){
			var self = $(this);
			var pid = self.data('cateid');
			var level = self.data('level');
			var isEnd = false;
			setTimeout(function(){
				!isEnd && $.showLoading();
			},1000);
			$a.getCategory(pid).done(function(data){
				isEnd = true;
				$a.ajaxAppend(data,++level);
				$.hideLoading();
			});
		});

		$el('a[target=#category-modal]').on('modal.ready',function(e,$modal){
			$modal.on('click','.search_btn',function(){
				var cateName = $modal.find('.search_input').val();	
				$.get('/listing/aliexpress/get-category-name',{cateName:cateName},'json').done(function(data){
						$a.showCategoryList(data);
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
				var pids = self.find('a').data('pids').split(',');
				var name = self.find('a').data('namezh').replace(/\s*/g,'').split('>');
				for(var i=0;i<cateids.length;i++){
					(i== 0)||(name[i] = ' > ' + name[i]);
					$('span.category_content[data-level]').html('').removeAttr('data-name').removeAttr('data-cateid');
					$('span.category_content[data-level="'+ i +'"]').html(name[i]).attr('data-name',name[i].replace(/[\s\>\s]+/g,'')).attr('data-cateid',cateids[i]);
				}
				$modal.find('.category-ensure').removeAttr('disabled').removeClass('not');
			});

			$modal.on('click','.cancel_cate_search',function(){
				$modal.find('.cate_list').hide();
				$('.category-ensure').attr('disabled','true').addClass('not');
			});
		});


		$el('.nav').on('click','li[data-leaf="0"]',function(){
			var self = $(this);
			var level = self.data('level');
			$a.hideCategoryBox(level+1);
		});

		$el('.nav').on('click','li[role="presentation"]',function(){
			var self = $(this);		
			var level = self.data('level');
			var name = self.find('a').text();
			var cateid = self.data('cateid');
			self.parent().find('li[role=presentation]').removeClass('active');
			self.addClass('active');
			(self.data('leaf') == '1') ? $('.category-ensure').attr('disabled','true').addClass('not'): $('.category-ensure').removeAttr('disabled').removeClass('not');
			$a.showCategoryName(self);
		});

		$el('.category-ensure').on('click',function(){
			var name = '';
			var cateid = 0;
			var category_name = '';
			$el('span.category_content').each(function(){
				var self = $(this);
				(self.data('name') != undefined) && (name = $.trim(self.data('name'))) && (category_name += $.trim(self.data('name') + ' > '));
				(self.data('cateid') != undefined) && (cateid = self.data('cateid'));
			});
			$a.hideChildCategoryName(0);
			$a.hideCategoryBox(1);
			if($('select[name="categoryid"] option[value="'+ cateid +'"]').length == 0)
				$('select[name="categoryid"]').append('<option value="'+ cateid +'">'+ name +'</option>').val(cateid).trigger('change');	
			else
				$('select[name="categoryid"]').val(cateid).trigger('change');
			// $a.getCategoryAttr(cateid).done(function(data){
			// 	$a.showCategoryAttr(data);
			// });
			$('a[target="#category-modal"]+p').html(category_name.slice(0,-2));
		});

		$el('.search_category').on('click',function(){
			console.log($(this).html());
		})

		$el('select[name="categoryid"]').on('change',function(){
			var cateid = $(this).val();
			console.log(cateid);
			if(cateid == ''){
				return false;
			}
			$a.getCategoryAttr(cateid).done(function(data){
				$a.showCategoryAttr(data);
			});
		});

		$el('body').on('input change','input[data-upload="color"]',function(e){
			var self = $(this);
			var uploadQueue = [];
			var files = this.files
			var selleruserid = $('select[name="selleruserid"]').val();
			Array.prototype.forEach.call(files,function(file){
				uploadQueue.push(function(){
					return $.upload(file);
				});
			});
			$.asyncQueue(uploadQueue,function(idx,res){
				$a.fillColorImg(self,res,selleruserid);
			});
		});

		$el('.variance_info_box').on('click','[for]',function(){
			var box = $('[show-sign="'+ $(this).attr('for') +'"]');
			console.log(box.is(':visible'));
			if(box.is(':visible')){
				box.hide();
				$(this).html('展开 ');
			}else{
				box.show();
				$(this).html('收起 ');
			}
		});
		
		$el('.select_url').on('modal.submit',function(e,data){
			var self = $(this);
			(data['img_url'] == '') || $a.appendColorImg(self,data['img_url']);
		});

		$el('.select-lib').on('modal.submit',function(e,data){
			var self = $(this);
			(data['img'][0] == '') || $a.appendColorImg(self,data['img'][0]);
		});
	});

	$.arrDel = function(arr,index){
		var newArr = []
		for(var i in arr){
			if(arr.hasOwnProperty(i) && (i != index)){
				newArr[i] = arr[i];
			}
		}
		return newArr;
	}

	$.tips = function(args){
		var $content = '<div class="alert" role="alert" style="z-index:99999999;width:500px;height:230px;left:30%;right:30%;margin: auto;top:20%;position: fixed;background-color:#F9F9F9;"><button type="button" class="close" data-dismiss="alert">×</button>';
		var $tip = '<div style="text-align:center;font:bold 20px/50px Microsoft Yahei;color:#717171;display: block;position: absolute;top: 0;left: 0;right: 0;bottom: 0;margin: auto;width: 87%;height: 20%;">';
		if(args['type'] != 'success'){
			$tip += '<span class="iconfont icon-cuowu" style="font-size:35px;color:red;margin-right:20px;vertical-align:middle"></span>'+ args['msg'] +'</div>';
		}else{
			$tip +='<span class="iconfont icon-zhengque" style="font-size:50px;color:green;margin-right:20px;vertical-align:middle;"></span>'+ args['msg'] +'</div>';

		}
		$content += $tip;
		$content += '</div>';
		$('.alert').remove();
		$('.right_content').append($content);
		if(args['exitTime'] != undefined){
			setTimeout(function(){
				$('.alert').remove();
			},args['exitTime']);
		}
	}

	$.confirm = function(msg){
		var $content = '<div class="alert" role="alert" style="z-index:99999999;width:500px;height:230px;left:30%;right:30%;margin: auto;top:20%;position: fixed;background-color:#F9F9F9;"><button type="button" class="close" data-dismiss="alert">×</button>';
		var $tip = '<div style="text-align:center;font:bold 20px/50px Microsoft Yahei;color:#717171;display: block;position: absolute;top: 0;left: 0;right: 0;bottom: 0;margin: auto;width: 87%;height: 20%;">';
		$tip += msg +'</div>';
		var $footer = '<div style="text-align:center;word-spacing:10px;position:absolute;bottom:10px;left:20%;right:20%;"><button type="button" class="btn btn-primary" style="letter-spacing: 10px;padding: 5px 15px 5px 25px;margin-right:20px;font: bold 14px/20px Microsoft Yahei;" data-dismiss="alert" onclick="">确定</botton><button type="button" style="letter-spacing: 10px;padding: 5px 15px 5px 25px;color:#616161;background-color:#EFEFEF;font: bold 14px/20px Microsoft Yahei;" class="btn" data-dismiss="alert" onclick="$.overLay(0)">取消</botton></div>';
		$content += $tip + $footer;
		$content += '</div>';
		$('.alert').remove();
		$('.right_content').append($content);
	}

	$.closeCurrentPage = function(){
		window.opener = null;
		window.open('','_self');
		window.close();
	}

})(jQuery);


// online_list
$.domReady(function($el){

	$el("#batchEditBrand").on('click',function(e){
		e.preventDefault();
		// 获取选中的productid
		var $this = $(this),
			data = $("#onlineList").serializeObject(),
			handle;
		if('productid' in data){
			handle = $.openModal($this.attr('href'),data,'批量修改品牌','post',false,{}, {
				resolve:true,
				reject:true
			});
		}else{
			$.alertBox('请先选择商品');
		}
		// handle.done(function($modal){

		// });


	});

	$el('.productEnable').on('click',function(){
		var self = $(this);
		var productid = [];
		productid.push(self.data('productid'));
		var on = self.data('on');
		$.post('/listing/aliexpress/batch-enable?on='+on,{'productid':productid},'json').done(function(data){
			if(data['response']['success']){
				$.message(data['response']['message'],"success");
				self.closest('tr').remove();
			}
		});
	});


});


var  isClick = false ;	
//页面滚动到指定位置
	function goto(str){
		var winPos = $(window).scrollTop();
	    var $new_product = $('#new_product').offset().top;            
	    var $product_attributes = $('#product_attributes').offset().top;
	    var $product_info = $('#product_info').offset().top;
	    var $variance_info = $('#variance_info').offset().top;
	    var $product_describe = $('#product_describe').offset().top;
	    var $rest_info = $('#rest_info').offset().top;
	    isClick = true;
	    $('html,body').animate({scrollTop:$('#'+str).offset().top},300,function(){
	        isClick =false;
	    });
	     gotowhere = str;
	    showscrollcss(str);
	}

	function showscrollcss(str){
       var eqtmp = new Array;
	    eqtmp['new_product'] =  0;
	    eqtmp['product_attributes'] = 1;
	    eqtmp['product_info'] = 2;
	    eqtmp['variance_info'] = 3;
	    eqtmp['product_describe'] = 4;
	    eqtmp['rest_info'] = 5;  
	    $('.left_pannel p a').removeClass('active');
	    $('.left_pannel p a').eq(eqtmp[str]).addClass('active');
	}

