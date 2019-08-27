/**
 *
 */
String.prototype.formatOther = function (args) {
    if (arguments.length > 0) {
        var result = this;
        if (arguments.length == 1 && typeof (args) == "object") {
            for (var key in args) {
                var reg = new RegExp("(#{" + key + "})", "g");
                result = result.replace(reg, args[key]);
            }
        } else {
            for (var i = 0; i < arguments.length; i++) {
                if (arguments[i] == undefined) {
                    return "";
                }
                else {
                    var reg = new RegExp("(#{[" + i + "]})", "g");
                    result = result.replace(reg, arguments[i]);
                }
            }
        }
        return result;
    } else {
        return this;
    }
};
Date.prototype.format = function (fmt) { //author: meizz 
    var o = {
        "M+": this.getMonth() + 1, //月份 
        "d+": this.getDate(), //日 
        "h+": this.getHours(), //小时 
        "m+": this.getMinutes(), //分 
        "s+": this.getSeconds(), //秒 
        "q+": Math.floor((this.getMonth() + 3) / 3), //季度 
        "S": this.getMilliseconds() //毫秒 
    };
    if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
        if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
    return fmt;
}

//页面滚动到指定位置
function goto(str) {
    lazadaListing.str = '';
    lazadaListing.str = str;
    $('html,body').animate({scrollTop: $('#' + str).offset().top}, 800);
}

//处理页面右侧快捷栏的css显示
function showscrollcss(str) {
    var _eqtmp = new Array;
    _eqtmp['store-info'] = 0;
    _eqtmp['base-info'] = 1;
    _eqtmp['description-info'] = 2;
    _eqtmp['variant-info'] = 3;
    
    //全部为默认黑
    $('.left_pannel p a').css('color', '#333');
    $('.left_pannel p a').eq(_eqtmp[str]).css('color', 'rgb(255,153,0)');

    return false;
}

if (typeof lazadaListing === 'undefined')  lazadaListing = new Object();

lazadaListing = {
    initReference: false,
    str: '',
    info_id: ['store-info', 'base-info', 'variant-info', 'image-info', 'description-info', 'shipping-info', 'warranty-info'],
    init: function () {
        var attrStr = $("#productDataStr").val();
        var skuArr = $("#skus").val();
        
        //一级目录下子项点击事件
        $(document).on('click', '.categoryDiv', function () {
            lazadaListing.categoryClick(this);
        });

        //隐藏相关信息
        $(document).on('click', '.glyphicon-chevron-up', function () {
            lazadaListing.hide(this);
        });
        //显示相关信息
        $(document).on('click', '.glyphicon-chevron-down', function () {
            lazadaListing.show(this);
        });
        //skuRemove
        $(document).on('click', 'a[cid="remove"]', function () {
            $(this).closest('tr').remove();
        });

        
        // 点击变参属性，触发生成sku html 
        $(document).on('click', '#variant-info table[cid="lzdVariantAttr"] input:checkbox', function () {
        	var _skuSelObj = this;
        	if ($(_skuSelObj).prop('checked')) {
        		lazadaListing.addOneSkuAttr($(_skuSelObj).closest('tr').attr('cid'), $(_skuSelObj).val());
    		} else {
    			lazadaListing.removeOneSkuAttr($(_skuSelObj).closest('tr').attr('cid'), $(_skuSelObj).val());
    		}
        });
        
        //sku批量修改
        //批量弹层html生成
        $(document).on('click', 'a.lzdSkuBatchEdit', function () {
            lazadaListing.skuBatchEdit(this);
        });
        //弹层radio切换
        $(document).on('click', 'input[name="numRadio"]', function () {
            var val = $(this).attr('data-val');
            if (val == 1) {
                $("#num2").attr('disabled', true);
                $("#num1").attr('disabled', false);
            } else if (val == 2) {
                $("#num2").attr('disabled', false);
                $("#num1").attr('disabled', true);
            }

        });
        $(document).on('click', 'input[name="priceEditType"]', function () {
            $(this).closest('div.modal-body').find('input[type="text"],select').attr('disabled', true);
            if (this.checked) {
                $(this).closest('div').find('input[type="text"],select').attr('disabled', false);
            }
            ;
        });

        //时间插件
        $(document).on('mouseenter', ".form-control.Wdate", function () {
            $(this).datepicker({
                dateFormat: 'yy-mm-dd'// 2015-11-11
            });
        })

        $(document).on('change', 'select[data-names="retailPrice"]', function () {
            var num = $(this).val();
            if (num == 1) {
                $('span.danwei').html('');
            }
            ;
            if (num == 2) {
                $('span.danwei').html('%');
            }
        });
        //产品标题计数
        $(document).on('keyup', 'div.lzdProductTitle input', function () {
            var num = $(this).val().length;
            $(this).closest('div.lzdProductTitle').find('span.unm').html(num);
        });

        //标题首大写转换
//			$('[data-toggle="tooltip"]').tooltip();
        $('.productTitTextSize').click(function () {
            var str = $(this).closest('div').find('input[type="text"]').val();
            var newStr = str.replace(/\s[a-z]/g, function ($1) {
                return $1.toLocaleUpperCase()
            }).replace(/^[a-z]/, function ($1) {
                return $1.toLocaleUpperCase()
            }).replace(/\sOr[^a-zA-Z]|\sAnd[^a-zA-Z]|\sOf[^a-zA-Z]|\sAbout[^a-zA-Z]|\sFor[^a-zA-Z]|\sWith[^a-zA-Z]|\sOn[^a-zA-Z]/g, function ($1) {
                return $1.toLowerCase()
            });
            $(this).closest('div').find('input[type="text"]').val(newStr);
        });

        //批量弹层确定按钮
        $(document).on('click', 'button.lzdSkuBatchEdit', function () {
            lazadaListing.skuBatchEditConfirm(this);
        });

        // 初始化 品牌autocomplete
        $('[cid="brand"]>.secondTd>input').autocomplete({
            source: function (request, response) {
                $.ajax({
                    type: "post",
                    url: "/listing/lazada-listing/get-brands",
                    data: {lazada_uid: $("#lazadaUid").val(), name: request.term},
                    dataType: 'json',
                }).done(function (data) {
                    function cmp(a, b) {
                        if (a > b) {
                            return 1;
                        }
                        if (a < b) {
                            return -1;
                        }
                        return 0;
                    }

                    function by(keyword) {
                        keyword = keyword.toLowerCase();
                        return function (a, b) {
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

                    if (data.code == 200) {
                        data.data.sort(by(request.term));
                        response(data.data);
                    } else {
                        console.log(data.message);
                    }
                });
            }

        });

        //加载页面编辑器
        KindEditor.ready(function (K) {
            window.editor = K.create('', lazadaListing.dataSet.kdeOption);
        });

        lazadaListing.pageBeginning();
        lazadaListing.dataSet.temporaryData.submitData = JSON.parse(attrStr || '{}');
        lazadaListing.dataSet.temporaryData.skuData = JSON.parse(skuArr || '[]');
        if (attrStr == '') {
            lazadaListing.dataSet.temporaryData.submitData['store-info'] = JSON.parse('{}');
            lazadaListing.dataSet.temporaryData.submitData['base-info'] = JSON.parse('{}');
            lazadaListing.dataSet.temporaryData.submitData['variant-info'] = JSON.parse('{}');
            lazadaListing.dataSet.temporaryData.submitData['image-info'] = JSON.parse('{}');
            lazadaListing.dataSet.temporaryData.submitData['description-info'] = JSON.parse('{}');
        } else {
            lazadaListing.selectShop();//初始化商铺 
//				lazadaListing.pageBeginning();
            if (lazadaListing.initReference == true) {// 引用商品数据初始化 与编辑/复制页面不同
                lazadaListing.lzdSkuBorn();
                lazadaListing.pushlazadaData(lazadaListing.dataSet.temporaryData.submitData, lazadaListing.dataSet.temporaryData.skuData);
            } else {
                var categoryIdList = JSON.parse($('#productCategoryIds').val() || '[]');
                if (categoryIdList.length > 0) {
                    var categoryId = categoryIdList[0];
                    lazadaListing.initCategory(categoryIdList); //回填目录
                    lazadaListing.initEditProduct(categoryId);  // 回填信息
                } else {
                    bootbox.alert("选择目录初始化失败！");
                    return;
                }
            }
        }

        //滚动监听快捷
        $(window).scroll(function (event) {
            //获取每个监听节点的高度
            var winPos = $(window).scrollTop();
            var storeInfo = $('#store-info').offset().top - 20;
            var baseInfo = $('#base-info').offset().top - 20;
            var variantInfo = $('#variant-info').offset().top - 20;
            var descriptionInfo = $('#description-info').offset().top - 20;

            if (winPos > storeInfo && winPos < baseInfo) {
                showscrollcss('store-info');
            } else if (winPos > baseInfo && winPos < descriptionInfo) {
                showscrollcss('base-info');
            } else if (winPos > descriptionInfo && winPos < variantInfo) {
                showscrollcss('description-info');
            } else if (winPos > variantInfo) {
            	showscrollcss('variant-info');
            }
        });

    },
    initV2: function () {
        var attrStr = $("#productDataStr").val();
        var skuArr = $("#skus").val();
        //一级目录下子项点击事件
        $(document).on('click', '.categoryDiv', function () {
            lazadaListing.categoryClick(this);
        });

        //隐藏相关信息
        $(document).on('click', '.glyphicon-chevron-up', function () {
            lazadaListing.hide(this);
        });
        //显示相关信息
        $(document).on('click', '.glyphicon-chevron-down', function () {
            lazadaListing.show(this);
        });
        //skuRemove
        $(document).on('click', 'a[cid="remove"]', function () {
            $(this).closest('tr').remove();
        });

        //sku批量修改
        //批量弹层html生成
        $(document).on('click', 'a.lzdSkuBatchEdit', function () {
            lazadaListing.skuBatchEdit(this);
        });
        //弹层radio切换
        $(document).on('click', 'input[name="numRadio"]', function () {
            var val = $(this).attr('data-val');
            if (val == 1) {
                $("#num2").attr('disabled', true);
                $("#num1").attr('disabled', false);
            } else if (val == 2) {
                $("#num2").attr('disabled', false);
                $("#num1").attr('disabled', true);
            }

        });
        $(document).on('click', 'input[name="priceEditType"]', function () {
            $(this).closest('div.modal-body').find('input[type="text"],select').attr('disabled', true);
            if (this.checked) {
                $(this).closest('div').find('input[type="text"],select').attr('disabled', false);
            }
            ;
        });

        //时间插件
        $(document).on('mouseenter', ".form-control.Wdate", function () {
            $(this).datepicker({
                dateFormat: 'yy-mm-dd'// 2015-11-11
            });
        })

        $(document).on('change', 'select[data-names="retailPrice"]', function () {
            var num = $(this).val();
            if (num == 1) {
                $('span.danwei').html('');
            }
            ;
            if (num == 2) {
                $('span.danwei').html('%');
            }
        });
        //产品标题计数
        $(document).on('keyup', 'div.lzdProductTitle input', function () {
            var num = $(this).val().length;
            $(this).closest('div.lzdProductTitle').find('span.unm').html(num);
        });

        //标题首大写转换
//			$('[data-toggle="tooltip"]').tooltip();
        $('.productTitTextSize').click(function () {
            var str = $(this).closest('div').find('input[type="text"]').val();
            var newStr = str.replace(/\s[a-z]/g, function ($1) {
                return $1.toLocaleUpperCase()
            }).replace(/^[a-z]/, function ($1) {
                return $1.toLocaleUpperCase()
            }).replace(/\sOr[^a-zA-Z]|\sAnd[^a-zA-Z]|\sOf[^a-zA-Z]|\sAbout[^a-zA-Z]|\sFor[^a-zA-Z]|\sWith[^a-zA-Z]|\sOn[^a-zA-Z]/g, function ($1) {
                return $1.toLowerCase()
            });
            $(this).closest('div').find('input[type="text"]').val(newStr);
        });

        //批量弹层确定按钮
        $(document).on('click', 'button.lzdSkuBatchEdit', function () {
            lazadaListing.skuBatchEditConfirm(this);
        });


        // 初始化 品牌autocomplete
        $('[cid="Brand"]>.secondTd>input').autocomplete({
            source: function (request, response) {
                $.ajax({
                    type: "post",
                    url: "/listing/lazada-listing/get-brands",
                    data: {lazada_uid: $("#lazadaUid").val(), name: request.term},
                    dataType: 'json',
                }).done(function (data) {
                    function cmp(a, b) {
                        if (a > b) {
                            return 1;
                        }
                        if (a < b) {
                            return -1;
                        }
                        return 0;
                    }

                    function by(keyword) {
                        keyword = keyword.toLowerCase();
                        return function (a, b) {
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

                    if (data.code == 200) {
                        data.data.sort(by(request.term));
                        response(data.data);
                    } else {
                        console.log(data.message);
                    }
                });
            }

        });

        //加载页面编辑器
        KindEditor.ready(function (K) {
            window.editor = K.create('', lazadaListing.dataSet.kdeOption);
        });

        lazadaListing.pageBeginning();
        lazadaListing.dataSet.temporaryData.submitData = JSON.parse(attrStr || '{}');
        lazadaListing.dataSet.temporaryData.skuData = JSON.parse(skuArr || '[]');
        for (var i in lazadaListing.info_id) {
            if (typeof lazadaListing.dataSet.temporaryData.submitData[lazadaListing.info_id[i]] == 'undefined'
                || !lazadaListing.dataSet.temporaryData.submitData[lazadaListing.info_id[i]]
                || lazadaListing.dataSet.temporaryData.submitData[lazadaListing.info_id[i]].length == 0) {
                lazadaListing.dataSet.temporaryData.submitData[lazadaListing.info_id[i]] = JSON.parse('{}');
            }
        }
        if (attrStr == '') {
            lazadaListing.dataSet.temporaryData.submitData['store-info'] = JSON.parse('{}');
            lazadaListing.dataSet.temporaryData.submitData['base-info'] = JSON.parse('{}');
            lazadaListing.dataSet.temporaryData.submitData['variant-info'] = JSON.parse('{}');
            lazadaListing.dataSet.temporaryData.submitData['image-info'] = JSON.parse('{}');
            lazadaListing.dataSet.temporaryData.submitData['description-info'] = JSON.parse('{}');
            lazadaListing.dataSet.temporaryData.submitData['shipping-info'] = JSON.parse('{}');
            lazadaListing.dataSet.temporaryData.submitData['warranty-info'] = JSON.parse('{}');
        } else {
            lazadaListing.selectShop();//初始化商铺 
//				lazadaListing.pageBeginning();
            if (lazadaListing.initReference == true) {// 引用商品数据初始化 与编辑/复制页面不同
                lazadaListing.lzdSkuBorn();
                lazadaListing.pushlazadaData(lazadaListing.dataSet.temporaryData.submitData, lazadaListing.dataSet.temporaryData.skuData);
            } else {
                var categoryIdList = JSON.parse($('#productCategoryIds').val() || '[]');
                if (categoryIdList.length > 0) {
                    var categoryId = categoryIdList[0];
                    lazadaListing.initCategory(categoryIdList); //回填目录
                    lazadaListing.initEditProduct(categoryId);  // 回填信息
                } else {
                    bootbox.alert("选择目录初始化失败！");
                    return;
                }
                ;
            }
        }

        //滚动监听快捷
        $(window).scroll(function (event) {
            //获取每个监听节点的高度
            var winPos = $(window).scrollTop();
            var storeInfo = $('#store-info').offset().top - 20;
            var baseInfo = $('#base-info').offset().top - 20;
            var descriptionInfo = $('#description-info').offset().top - 20;
            var variantInfo = $('#variant-info').offset().top - 20;

            if (winPos > storeInfo && winPos < baseInfo) {
                showscrollcss('store-info');
            } else if (winPos > baseInfo && winPos < descriptionInfo) {
                showscrollcss('base-info');
            } else if (winPos > descriptionInfo && winPos < variantInfo) {
                showscrollcss('description-info');
            } else if (winPos > variantInfo) {
            	showscrollcss('variant-info');
            } 

        });

    },

    //类目属性展示
    dataSet: {
        brands: [],
        temporaryData: {
            submitData: {},
            skuData: []
        },
        lzdAttrTit_1: '<tr cid="#{name}" name="#{label}" attrType="#{attrType}" isMust="#{isMust}"><td class="firstTd">',
        isMust: '<span class="fRed">*</span>',
        lzdAttrTit_2: '#{label}:</td><td class="secondTd" cid="#{name}Content" >',
        lzdAttrEnd: '</td></tr>',
        input: '<input type="text" class="form-control" value="" />',
        input_2: '<input type="text" class="form-control" value="..." placeholder="..."/>',
        numeric :'<input type="text" class="form-control" onkeyup="lazadaListing.replaceNumber(this);" />',
        float :'<input type="text" class="form-control" onkeyup="lazadaListing.replaceFloat(this);" />',
        date :'<input type="text" class="form-control Wdate" style="padding-left:5px;" id="" name="" value="" placeholder="#{label}"/>',
        select: '<select class="form-control select-form-control"><option>请选择</option>#{optionStr}</select>',
        skuSelect: '<select class="eagle-form-control" style="height:30px;width:125px;"><option value="">请选择</option>#{optionStr}</select>',
        checkbox: '<label><input type="checkbox" value="#{name}" data-val="#{name}"/> #{name}</Label>',
        kindeditor: '<div class="mBottom10" data-name="kdeOutDiv" cid="#{name}">' +
        '<textarea id="#{kindeditorId}" name="content" style="width:100%;height:100%;"></textarea>' +
        '</div>' +
        '<div id="#{name}CacheDiv" style="display:none;">' +
        '</div>',
        option: '<option value="#{name}" data-val="#{name}">#{name}</option>',
        
        lzdSkuPanel: '<div id="#{id}" class="panel panel-default sku-panel">'+
        '<div class="panel-heading"><h3 class="panel-title">SKU:<span class="sku-text"></span>#{attrLabels}<div class="pull-right"><button type="button" class="btn btn-xs btn-default" onclick="lazadaListing.applytToAllSku(this)">应用到所有</button></div></h3><span class="glyphicon glyphicon-chevron-up"></span></div>'+
        '<div class="panel-body"><table class="left-table-location" cid="lzdSkuAttr"></table></div></div>',
        panelHeaderAttrLabel: '<span class="label label-primary" style="margin-left: 15px;" >#{attr}</span>',
        
        imgLibAlert:'<div class="row iv-alert alert-remind iv-image-lib-alert">'+
        '<p>1.图片大小，最小500*500像素，最大2000*2000像素</p>'+
        '<p>2.分辨率：不低于72dpi,产品必须清晰可见，产品轮廓必须清晰流畅，不可以模糊、有噪点或者像素化</p>'+
        '<p>3.图片须使用纯白背景，产品须占据画布最长边80%或以上</p>'+
        '<p>4.图片没有水印、Logo、文字或图案</p>'+
        '</div>'+
        '<div class="upload_image_info" style="color:red"></div>'
        ,
        
        // 浮点属性
        floatAttrs:['price','special_price','package_weight','package_length','package_width','package_height','product_weight','display_size_mobile'],
        // 其他已初始化的一般属性
        showAttr:[],
        //页面固定显示属性
    	commonShowAttr:["name","brand","model"],
    	//页面固定没有显示的属性
    	commonHideAttr:['name_ms','description_ms'],
        //用到的固定没有显示的属性
        isUseComHideAt:[],
        // 保存图片库对象
        imgLib:{},
        // sku panel id对象
        skuPanelId:{},
        //sku选中属性
        skuSelData:{},
    	//常用富文本id
    	commonKdId:['description','short_description','package_content'],
    	//待初始化富文本id
    	initKdId:['description','short_description','package_content'],
    	//属性里富文本id
    	kindeditorId: [],
    	
        //sku参数
    	// TODO dzt20170105 未把握到所有目录这些属性的规律，例如Mobiles & Tablets  > Mobiles  目录就有个storage_capacity_new 没有加到这个数组里
    	// 猜测可能是 非tax_class 其他的单选或者多选项都可以作为sku参数，但目前未总结到，当作一般属性处理，然后让客户自己添加变参，不自动添加
        skuParameter:["color_family"],//,"size" dzt20170321 从middan chen处获知，color_family 是变参属性，
        //otherSkuAttr其它sku属性
        otherSkuAttr:[],
        //allSkuParameter变种参数
        allSkuParameter:[],
    	//sku固定隐藏属性
    	skuCommonHideAttr:['__images__'],	
    	
        descriptionAttr:['description','description_ms','short_description','package_content','seller_promotion'],// 产品描述区域属性
        kdeOption: {
            items: [
                'bold', 'italic', 'underline', 'strikethrough', '|', 'forecolor', 'hilitecolor', '|', 'justifyleft', 'justifycenter', 'justifyright', 'justifyfull', '|', 'insertunorderedlist', 'insertorderedlist', '|', 'outdent', 'indent', '|', 'subscript', 'superscript', '|', 'selectall', 'removeformat', '|', 'undo', 'redo', '/',
                'fontname', 'fontsize', 'formatblock', '|', 'cut', 'copy', 'paste', 'plainpaste', 'wordpaste', '|', 'link', 'unlink', '|', 'moreImage', '|'/*,'lazadaImgSpace','|'*/, 'fullscreen', 'source'
            ],                                           //功能按钮
            width: '100%',
            height: '120px',
            themeType: 'default',                         //界面风格,可设置”default”、”simple”，指定simple时需要引入simple.css
            langType: 'zh_CN',                            //按钮提示语言（en为英语）
            newlineTag: 'br',                             //设置回车换行标签，“p” “br”
            dialogAlignType: 'page',                      //设置弹出框(dialog)的对齐类型，指定page时按当前页面居中，指定空时按编辑器居中
            shadowMode: 'true',                           //true时弹出层(dialog)显示阴影
            zIndex: '1039',                               //指定弹出层的基准z-index,默认值: 1040 ，覆盖了 kindeditorEdit.js里面的设置
            useContextmenu: 'false',                       //true时使用右键菜单，false时屏蔽右键菜单
            colorTable: [								 //指定取色器里的颜色
                ['#E53333', '#E56600', '#FF9900', '#64451D', '#DFC5A4', '#FFE500'],
                ['#009900', '#006600', '#99BB00', '#B8D100', '#60D978', '#00D5FF'],
                ['#337FE5', '#003399', '#4C33E5', '#9933E5', '#CC33E5', '#EE33EE'],
                ['#FFFFFF', '#CCCCCC', '#999999', '#666666', '#333333', '#000000']
            ],
            filterMode: false,
            cssData: 'kse\\:widget {display:block;width:120px;height:120px;background:url(http://b.hiphotos.baidu.com/image/pic/item/e4dde71190ef76c666af095f9e16fdfaaf516741.jpg);}'
        },
        descImageAlignleft: 'style="display: inline; float: left;"',
        descImageAlignright: 'style="display: inline; float: right;"',
        descImageAligncenter: 'style="clear: both; display: block; margin:auto;"',
        lzdSkuArr: [],
        isMustAttrArr: [],
        lzdSkuObjArr: {},
        spanCache: [],
        lzdSkuTrStar: '<tr class="sku-tr-color" id="0">',
        lzdSkuTrEnd: '</tr>',
        lzdSkuTh_1: '<th>#{name}<span cid="variationEditSpan"><br/><a href="javascript:;" class="lzdSkuBatchEdit" data-names="variation">【一键生成】</a></span></th>',
        lzdSkuTh_2: '<th class="bgTd">SKU<span class="fRed">*</span><br/><a href="javascript:;" class="lzdSkuBatchEdit" data-names="sku">【一键生成】</a></th>' +
        '<th class="bgTd">EAN/UPC/ISBN</th>' +
        '<th class="smTd">库存<span class="fRed">*</span><br/><a href="javascript:;" class="lzdSkuBatchEdit" data-names="quantity">【修改】</a></th>' +
        '<th class="smTd">价格<span class="fRed">*</span><br/><a href="javascript:;" class="lzdSkuBatchEdit" data-names="price">【修改】</a></th>' +
        '<th class="smTd">促销价<br/><a href="javascript:;" class="lzdSkuBatchEdit" data-names="salePrice">【修改】</a></th>' +
        '<th colspan="3">促销时间<br/><a href="javascript:;" class="lzdSkuBatchEdit" data-names="promotionTime">【修改】</a></th>' +
        '<th style="min-width:40px;">操作</th>',
        lzdSkuTd_1: '<td  style="width:90px;" data-id="sku" data-name="variation" data-type="#{attrType}">#{showHtml}</td>',
        lzdSkuTd_2: '<td data-name="sellerSku"><input type="text" class="form-control" name="" value="" placeholder=""/></td>' +
        '<td data-name="productGroup" name="EAN"><input type="text" class="form-control" name="" value="" placeholder=""/></td>' +
        '<td data-name="quantity" style="width:50px;"><input type="text" class="form-control" name="" value="" placeholder="" onkeyup="lazadaListing.replaceNumber(this);"/></td>' +
        '<td data-name="price" style="width:50px;"><input type="text" class="form-control" name="" value="" placeholder="" onkeyup="lazadaListing.replaceFloat(this);"/></td>' +
        '<td data-name="salePrice" style="width:50px;"><input type="text" class="form-control" name="" value="" placeholder="" onkeyup="lazadaListing.replaceFloat(this);"/></td>' +
        '<td class="borderNo" style="width:90px;" data-name="saleStartDate"><input type="text" class="form-control Wdate" style="padding-left:5px;" id="" name="" value="" placeholder="开始时间"/></td>' +
        '<td class="text-center borderNo" data-name="no">-</td>' +
        '<td class="borderNo" style="width:90px;" data-name="saleEndDate"><input type="text" class="form-control Wdate" style="padding-left:5px;" id="" name="" value="" placeholder="结束时间"/></td>' +
        '<td class="borderNo" data-name="no"><a href="javascript:;" cid="remove">移除</a></td>',
    },

    list_init: function () {
        //检查checkbox
        $(document).on('click', '#chk_all', function () {
            lazadaListing.checkAll(this);
        });
        $(document).on('click', 'input[name="parent_chk"]', function () {
            lazadaListing.check(this);
        });
        //变参商品选择
        $(document).on('click', 'input[name="productcheck"]', function () {
            lazadaListing.variationCheck(this);
        });
        //展示所有商品
        $(document).on('click', '.product_all_show', function () {
            lazadaListing.productAllShow(this);
        });
        //展开显示
        $(document).on('click', '.product_show', function () {
            lazadaListing.productShow(this);
        });
        $(document).on('mouseenter', ".eagle-form-control.Wdate", function () {
            $(this).datepicker({
                dateFormat: 'yy-mm-dd'// 2015-11-11
            });
        });
        if ($('#search_status').val() == "search") {
            $('.product_all_show').click();
        }

    },


    //批量修改
    editTypeChange: function (obj) {
        var type = $(obj).val();
        switch (type) {
            case 'quantity':
                $('#edit_method').html('');
                var str = '';
                str = '<option value="">修改方式</option><option value="1">按原库存调整</option><option value="0">替换</option><option value="3">按条件加</option><option value="4">按条件补货</option>'
                $('#edit_method').append(str);
                break;
            case 'price':
                $('#edit_method').html('');
                var str = '';
                str = '<option value="">修改方式</option><option value="1">按原价调整</option><option value="2">按原价百分比调整</option><option value="0">替换</option>'
                $('#edit_method').append(str);
                break;
            case 'sale_message':
                $('#edit_method').html('');
                var str = '';
                str = '<option value="">修改方式</option><option value="1">按原价调整</option><option value="2">按原价百分比调整</option><option value="0">替换</option>'
                $('#edit_method').append(str);
                break;
            default:
                $('#edit_method').html('');
                var str = '';
                str = '<option value="">请选择修改方式</option>'
                $('#edit_method').append(str);
        }
    },
    methodChange: function (obj) {
        var type = $('#edit_type').val()
        var method = $(obj).val();
        var normal_str2 = '<label for="edit_input" class="batch-label">替换：</label><input id="edit_input" name="edit_input" placehodler="" onkeyup="" class="eagle-form-control" style="width:260px;"><span class="percent"></span>';
        var normal_str = '<label for="edit_input" class="batch-label"></label><input id="edit_input" name="edit_input" placehodler="" onkeyup="" class="eagle-form-control" style="width:260px;"><span class="percent"></span>';
        if (type == 'quantity') {
            switch (method) {
                case '1'://调整
                    $('.input_replace').html('');
                    $('.input_replace').html(normal_str);
                    $(".remind").html('');
                    $('.sale_message').html('');
                    $(".remind").html('提示：如果减少，可输入负数。');
                    $('#edit_input').attr('placeholder', '示例：1');
                    $('#edit_input').attr("onkeyup", "value=value.replace(/[^0-9-]/g,'')");
                    break;
                case '0'://替换
                    $('.input_replace').html('');
                    $('.input_replace').html(normal_str2);
                    $(".remind").html('');
                    $('.sale_message').html('');
                    $('#edit_input').attr('placeholder', '示例：1');
                    $('#edit_input').attr("onkeyup", "value=value.replace(/[^0-9]/g,'')");
                    break;
                case '3'://按条件加
                    $(".remind").html('');
                    $('.sale_message').html('');
                    $('.input_replace').html('');
                    var input_str = '<label for="edit_input"  class="batch-label" data-type="add">库存少于：</label><input style="width:64px;" type="text" id="less_than" name="less_than" class="eagle-form-control" onkeyup="value=value.replace(/[^0-9]/g,\'\')" placeholder="示例：1">' +
                        '<label for="edit_input">&nbsp;则加：</label><input id="edit_input" name="edit_input" placehodler="" onkeyup="" class="eagle-form-control" style="width:157px;">';
                    $('.input_replace').html(input_str);
                    $('#edit_input').attr('placeholder', '示例：1');
                    $('#edit_input').attr("onkeyup", "value=value.replace(/[^0-9]/g,'')");
                    break;
                case '4'://按条件补货
                    $(".remind").html('');
                    $('.sale_message').html('');
                    $('.input_replace').html('');
                    var input_str = '<label for="edit_input"  class="batch-label" data-type="replace">库存少于：</label><input style="width:66px;" type="text" id="less_than" name="less_than" class="eagle-form-control" onkeyup="value=value.replace(/[^0-9]/g,\'\')" placeholder="示例：1">' +
                        '<label for="edit_input">&nbsp;补充到：</label><input id="edit_input" name="edit_input" placehodler="" onkeyup="" class="eagle-form-control" style="width:143px;">';
                    $('.input_replace').html(input_str);
                    $('#edit_input').attr('placeholder', '示例：1');
                    $('#edit_input').attr("onkeyup", "value=value.replace(/[^0-9]/g,'')");
                    break;
                default:
                    $('.input_replace').html('');
                    $('.input_replace').html(normal_str);
                    $('.sale_message').html('');
                    $(".remind").html('');
                    $('.sale_message').html('');
                    break;
            }
        } else if (type == 'price') {
            switch (method) {
                case '1'://金额
                    $('.input_replace').html('');
                    $('.input_replace').html(normal_str);
                    $(".remind").html('');
                    $(".remind").html('提示：如果减少，可输入负数。');
                    $('.sale_message').html('');
                    $('#edit_input').attr('placeholder', '示例：1.00');
                    $('#edit_input').attr("onkeyup", "value=value.replace(/[^0-9.-]/g,'')");
                    break;
                case '0'://直接修改
                    $('.input_replace').html('');
                    $('.input_replace').html(normal_str2);
                    $(".remind").html('');
                    $('.sale_message').html('');
                    $('#edit_input').attr('placeholder', '示例：1.00');
                    $('#edit_input').attr("onkeyup", "value=value.replace(/[^0-9.]/g,'')");
                    break;
                case '2'://百分比
                    $('.input_replace').html('');
                    $('.input_replace').html(normal_str);
                    $(".remind").html('');
                    $(".percent").html('%');
                    $('.sale_message').html('');
                    $(".remind").html('提示：如果减少，可输入负数。');
                    $('#edit_input').attr('placeholder', '示例：1.00');
                    $('#edit_input').attr("onkeyup", "value=value.replace(/[^0-9.-]/g,'')");
                    break;
                default:
                    $('.input_replace').html('');
                    $('.input_replace').html(normal_str);
                    $('.sale_message').html('');
                    $(".remind").html('');
                    $('.sale_message').html('');
                    break;
            }
        } else if (type == 'sale_message') {
            switch (method) {
                case '1'://金额
                    $('.input_replace').html('');
                    $('.input_replace').html(normal_str);
                    $(".remind").html('');
                    $('.sale_message').html('');
                    $(".remind").html('提示：如果减少，可输入负数。');
                    $('#edit_input').attr('placeholder', '此处输入促销价，示例：1.00');
                    $('#edit_input').attr("onkeyup", "value=value.replace(/[^0-9.-]/g,'')");
                    break;
                case '0'://直接修改
                    $('.input_replace').html('');
                    $('.input_replace').html(normal_str2);
                    $(".remind").html('');
                    $('.sale_message').html('');
                    $('#edit_input').attr('placeholder', '此处输入促销价，示例：1.00');
                    $('#edit_input').attr("onkeyup", "value=value.replace(/[^0-9.]/g,'')");
                    break;
                case '2'://百分比
                    $('.input_replace').html('');
                    $('.input_replace').html(normal_str);
                    $(".remind").html('');
                    $(".percent").html('%');
                    $('.sale_message').html('');
                    $(".remind").html('提示：如果减少，可输入负数。');
                    $('#edit_input').attr('placeholder', '此处输入促销价，示例：1.00');
                    $('#edit_input').attr("onkeyup", "value=value.replace(/[^0-9.-]/g,'')");
                    break;
                default:
                    $('.input_replace').html('');
                    $('.input_replace').html(normal_str);
                    $('.sale_message').html('');
                    $(".remind").html('');
                    $('.sale_message').html('');
                    break;
            }
            var sale_str = '<label for="saleStartDate">促销起始时间：</label><input type="text" onkeyup="value=value.replace(/[^0-9-]/g,\'\')" class="eagle-form-control Wdate" style="padding-left:5px;width:234px;" id="saleStartDate" name="saleStartDate" placeholder="开始时间" onClick="lazadaListing.datePicker(this)"/>' +
                '<br /><label for="saleEndDate" style="padding-left:106px">促销结束时间：</label><input onkeyup="value=value.replace(/[^0-9-]/g,\'\')" type="text" class="eagle-form-control Wdate" style="padding-left:5px;width:234px;" id="saleEndDate" name="saleEndDate" placeholder="结束时间" onClick="lazadaListing.datePicker(this)"/>';
            $('.sale_message').html('');
            $('.sale_message').html(sale_str);
        } else {
            $('.input_replace').html('');
            $('.input_replace').html(normal_str);
            $('.sale_message').html('');
            $(".remind").html('');
//				$(".percent").html('');
            $('.sale_message').html('');
//				$('#edit_input').attr('placeholder','');
//				$('#edit_input').attr("onkeyup","");
//				$('#edit_input').val('');
        }
    },
    checkAll: function (obj) {
        if ($(obj).prop('checked')) {
            $('input[type="checkbox"]').each(function (i) {
                $(this).prop("checked", true);
            });
        } else {
            $('input[type="checkbox"]').each(function (i) {
                $(this).prop("checked", false);
            });
        }
    },
    //展开显示
    productShow: function (obj) {
        var parentId = $(obj).parents("tr").data("id");
        if ($(obj).hasClass("glyphicon-plus")) {
            $(obj).removeClass("glyphicon-plus");
            $(obj).addClass("glyphicon-minus");
            $(".product_" + parentId).show();
        } else {
            $(obj).removeClass("glyphicon-minus");
            $(obj).addClass("glyphicon-plus");
            $(".product_" + parentId).hide();
        }
    },

    //变种选择
    variationCheck: function (obj) {
        var parentId = $(obj).attr("parentid");
        if ($(obj).is(":checked") && !$("#chk_one_" + parentId).is(":checked")) {
            $("#chk_one_" + parentId).prop("checked", true);
        } else {
            var type = false;
            $('input[parentid="' + parentId + '"]').each(function () {
                if ($(this).is(":checked")) {
                    type = true;
                }
            });
            if (!type) {
                $("#chk_one_" + parentId).removeAttr("checked");
            }
        }
    },
    //批量修改确认
    batchEditSubmit: function () {
        var post_url = '';
        var condition = '';
        var ids = $("#productIds").val();//获取修改的商品id
        var operation = $('#edit_method').val();//获取商品的运算方式
        var type = $('#edit_type').val();//获取修改的类型
        var value = $.trim($("#edit_input").val());//获取修改的数值

        if (ids == "") {
            bootbox.alert("需要修改商品的信息获取失败！");
            return;
        }

        if (type == "") {
            bootbox.alert("请选择修改选项！");
            return;
        } else {
            switch (type) {
                case 'price':
                    post_url = "/listing/lazada/batch-update-price";
                    break;
                case 'sale_message':
                    post_url = "/listing/lazada/batch-update-sales-info";
                    break;
                case 'quantity':
                    post_url = "/listing/lazada/batch-update-quantity";
                    break;
            }
        }

        if (operation === "") {
            bootbox.alert("请选择修改方式！");
            return;
        }
        //库存控制
        if ((type == 'quantity' && operation == "4") || (type == 'quantity' && operation == "3")) {
            if ($('input[id="less_than"]').val() == "") {
                bootbox.alert("库存少于的条件必须要填写");
                return;
            } else {
                condition = $('label[for="edit_input"]').data("type");
//					$('#condition').val(condition);
            }
        }
        //时间的比较
        if (type == 'sale_message' && operation != "") {
            if ($('#saleStartDate').val() == "") {
                bootbox.alert("促销起始时间不能为空");
                return;
            }
            if ($('#saleEndDate').val() == "") {
                bootbox.alert("促销结束时间不能为空");
                return;
            }
            var nowDate = new Date().format("yyyy-MM-dd");
            var compare1 = lazadaListing.editCompareDate(nowDate, $('#saleEndDate').val());//这个function只用于批量修改时间时的比较
            var compare2 = lazadaListing.compareDate($('#saleStartDate').val(), $('#saleEndDate').val());
            if (compare1 == 1) {
                bootbox.alert("促销结束时间需要大于当前时间！");
                return;
            }
            if (compare2 == 1) {
                bootbox.alert("促销开始时间不能少于促销结束时间！");
                return;
            }
        }

        if (value == undefined || value == "") {
            bootbox.alert("请输入修改值");
            return;
        }
        // 判断是否为数字
        if (isNaN(value)) {
            bootbox.alert("请输入数字");
            return;
        }
        $.showLoading();
        $.ajax({
            type: "POST",
            url: post_url,
            data: $('#edit-product').serialize(),
            dataType: 'json',
            success: function (result) {
                $.hideLoading();
                if (result.code == 200) {
                    // bootbox.alert(result.message);
                    // $('#edit_product').css("display","none");
                    bootbox.alert({
                        title: Translator.t('提示'), message: result.message, callback: function () {
                            window.location.reload();
                            $.showLoading();
                        }
                    });
                } else {
                    bootbox.alert(result.message);
                }
            },
            error: function () {
                $.hideLoading();
                bootbox.alert("网络错误！");
            }
        });
    },
    //parent check选择时
    check: function (obj) {
        var parent_id = $(obj).parents("tr").data("id");
        if ($(obj).prop('checked')) {
            $('input[parentid="' + parent_id + '"]').each(function (i) {
                $(this).prop("checked", true);
            });
        } else {
            $('input[parentid="' + parent_id + '"]').each(function (i) {
                $(this).prop("checked", false);
            });
        }
    },
    //批量修改的时候检查是否没有选商品
    checkBox: function () {
        var box = [];
        $('input[name="groupCheck"]:checked').each(function () {
        	$(this).closest(".lzd_body").find('tr[data-groupid="'+$(this).closest("tr").data("groupid")+'"]>td>[name="listingId"]').each(function () {
        		box.push($(this).val());
        	});
        });
        if (box.length == 0) {
            bootbox.alert("至少要选择一件商品！");
            return;
        } else {
            $("#productIds").val('');
            $("#productIds").val(box);
            $("#edit_product").modal('show');
        }

    },
    //批量下架
    batchPutOff: function () {
        var box = [];
        $('input[name="productcheck"]:checked').each(function () {
            box.push($(this).parents("tr").data("productid"));
        });
        if (box.length == 0) {
            bootbox.alert("至少要选择一件商品！");
            return;
        } else {
            $.showLoading();
            $.ajax({
                type: "POST",
                url: '/listing/lazada/put-off',
                data: {"productIds": box},
                dataType: 'json',
                success: function (result) {
                    $.hideLoading();
                    if (result.code == 200) {
                        bootbox.alert({
                            title: Translator.t('提示'), message: result.message, callback: function () {
                                $.showLoading();
                                window.location.reload();
                            }
                        });
                    } else {
                        bootbox.alert(result.message);
                    }
                },
                error: function () {
                    $.hideLoading();
                    bootbox.alert("网络错误！");
                }
            });
        }
    },
    //主产品下架（包括所有变参）
    parentProductPutOff: function (obj) {
        var box = [];
        var parent_id = $(obj).parents("tr").data("id");
        $('input[parentid="' + parent_id + '"]').each(function (i) {
            box.push($(this).parents("tr").data("productid"));
        });
        if (box.length > 0) {
            $.showLoading();
            $.ajax({
                type: "POST",
                url: '/listing/lazada/put-off',
                data: {"productIds": box},
                dataType: 'json',
                success: function (result) {
                    $.hideLoading();
                    if (result.code == 200) {
                        bootbox.alert({
                            title: Translator.t('提示'), message: result.message, callback: function () {
                                $.showLoading();
                                window.location.reload();
                            }
                        });
                    } else {
                        bootbox.alert(result.message)
                    }
                },
                error: function () {
                    $.hideLoading();
                    bootbox.alert("网络错误！");
                }
            });
        } else {
            bootbox.alert("获取产品信息失败！");
            return;
        }

    },
    //单个产品的下架
    productPutOff: function (id) {
        var box = [];
        box.push(id);
        if (box.length > 0) {
            $.showLoading();
            $.ajax({
                type: "POST",
                url: '/listing/lazada/put-off',
                data: {"productIds": box},
                dataType: 'json',
                success: function (result) {
                    $.hideLoading();
                    if (result.code == 200) {
                        bootbox.alert({
                            title: Translator.t('提示'), message: result.message, callback: function () {
                                $.showLoading();
                                window.location.reload();
                            }
                        });
                    } else {
                        bootbox.alert(result.message);
                    }
                },
                error: function () {
                    $.hideLoading();
                    bootbox.alert("网络错误！");
                }
            });
        } else {
            bootbox.alert("获取产品信息失败！");
            return;
        }
    },
    //批量上架
    batchPutOn: function () {
        var box = [];
        $('input[name="productcheck"]:checked').each(function () {
            box.push($(this).parents("tr").data("productid"));
        });
        if (box.length == 0) {
            bootbox.alert("至少要选择一件商品！");
            return;
        } else {
            $.showLoading();
            $.ajax({
                type: "POST",
                url: '/listing/lazada/put-on',
                data: {"productIds": box},
                dataType: 'json',
                success: function (result) {
                    $.hideLoading();
                    if (result.code == 200) {
                        bootbox.alert({
                            title: Translator.t('提示'), message: result.message, callback: function () {
                                $.showLoading();
                                window.location.reload();
                            }
                        });
                    } else {
                        bootbox.alert(result.message);
                    }
                },
                error: function () {
                    $.hideLoading();
                    bootbox.alert("网络错误！");
                }
            });
        }
    },
    //主产品上架（包括所有变参）
    parentProductPutOn: function (obj) {
        var box = [];
        var parent_id = $(obj).parents("tr").data("id");
        $('input[parentid="' + parent_id + '"]').each(function (i) {
            box.push($(this).parents("tr").data("productid"));
        });
        if (box.length > 0) {
            $.showLoading();
            $.ajax({
                type: "POST",
                url: '/listing/lazada/put-on',
                data: {"productIds": box},
                dataType: 'json',
                success: function (result) {
                    $.hideLoading();
                    if (result.code == 200) {
                        bootbox.alert({
                            title: Translator.t('提示'), message: result.message, callback: function () {
                                $.showLoading();
                                window.location.reload();
                            }
                        });
                    } else {
                        bootbox.alert(result.message);
                    }
                },
                error: function () {
                    $.hideLoading();
                    bootbox.alert("网络错误！");
                }
            });
        } else {
            bootbox.alert("获取产品信息失败！");
            return;
        }

    },
    //单个产品的上架
    productPutOn: function (id) {
        var box = [];
        box.push(id);
        if (box.length > 0) {
            $.showLoading();
            $.ajax({
                type: "POST",
                url: '/listing/lazada/put-on',
                data: {"productIds": box},
                dataType: 'json',
                success: function (result) {
                    $.hideLoading();
                    if (result.code == 200) {
                        bootbox.alert({
                            title: Translator.t('提示'), message: result.message, callback: function () {
                                $.showLoading();
                                window.location.reload();
                            }
                        });
                    } else {
                        bootbox.alert(result.message);
                    }
                },
                error: function () {
                    $.hideLoading();
                    bootbox.alert("网络错误！");
                }
            });
        } else {
            bootbox.alert("获取产品信息失败！");
            return;
        }
    },
    //批量发布
    batch: function (batchVal) {
        var ids = [];
        if (batchVal == "1") {
            $(".lzd_body>tr").each(function (i) {
                var id = '';
                if ($(this).find('input[id="chk_one"]').prop('checked')) {
                    id = $(this).data("id");
                    ids.push(id);
                }
            });
            if (ids.length > 0) {
                $.showLoading();
                $.ajax({
                    type: 'GET',
//						async:false,
                    url: '/listing/lazada-listing/do-publish',
                    data: {
                        ids: ids.join(',')
                    },
                    dataType: 'json',
                    success: function (data) {
                        $.hideLoading();
                        if (data.code == 200) {
                            bootbox.alert({
                                title: Translator.t('提示'), message: data.message, callback: function () {
                                    $.showLoading();
                                    window.location.reload();
                                }
                            });
                        }
                        if (data.code == 400) {
                            bootbox.alert(data.message);
                        }

                    },
                    error: function () {
                        $.hideLoading();
//							$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
                        bootbox.alert("网络错误, 请稍后再试！");
                    }
                });
            } else {
                $('select[name="batch"] option').eq(0).prop('selected', true);
                bootbox.alert("没有勾选选项");
                return null;
            }
            ;

        }
        ;
        if (batchVal == "2") {//批量删除
            $(".lzd_body>tr").each(function (i) {
                var id = '';
                if ($(this).find('input[id="chk_one"]').prop('checked')) {
                    id = $(this).data("id");
                    ids.push(id);
                }
            });
            if (ids.length > 0) {
                $.showLoading();
                $.ajax({
                    type: 'POST',
//						async:false,
                    url: '/listing/lazada-listing/delete',
                    data: {
                        ids: ids.join(',')
                    },
                    dataType: 'json',
                    success: function (data) {
                        $.hideLoading();
                        if (data.code == 200) {
                            bootbox.alert({
                                title: Translator.t('提示'), message: data.message, callback: function () {
                                    window.location.reload();
                                    $.showLoading();
                                }
                            });
                        }
                        if (data.code == 400) {
                            bootbox.alert(data.message);
                        }

                    },
                    error: function () {
                        $.hideLoading();
//							$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
                        bootbox.alert("网络错误, 请稍后再试！");
                    }
                });
            } else {
                $('select[name="batch"] option').eq(0).prop('selected', true);
                bootbox.alert("没有勾选选项");
                return null;
            }
            ;
        }
        ;
    },
    batchv2: function (batchVal) {
        var ids = [];
        if (batchVal == "1") {
            $(".lzd_body>tr").each(function (i) {
                var id = '';
                if ($(this).find('input[id="chk_one"]').prop('checked')) {
                    id = $(this).data("id");
                    ids.push(id);
                }
            });
            if (ids.length > 0) {
                $.showLoading();
                $.ajax({
                    type: 'GET',
//						async:false,
                    url: '/listing/lazada-listing-v2/do-publish',
                    data: {
                        ids: ids.join(',')
                    },
                    dataType: 'json',
                    success: function (data) {
                        $.hideLoading();
                        if (data.code == 200) {
                            bootbox.alert({
                                title: Translator.t('提示'), message: data.message, callback: function () {
                                    $.showLoading();
                                    window.location.reload();
                                }
                            });
                        }
                        if (data.code == 400) {
                            bootbox.alert(data.message);
                        }

                    },
                    error: function () {
                        $.hideLoading();
//							$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
                        bootbox.alert("网络错误, 请稍后再试！");
                    }
                });
            } else {
                $('select[name="batch"] option').eq(0).prop('selected', true);
                bootbox.alert("没有勾选选项");
                return null;
            }
            ;

        }
        ;
        if (batchVal == "2") {//批量删除
            $(".lzd_body>tr").each(function (i) {
                var id = '';
                if ($(this).find('input[id="chk_one"]').prop('checked')) {
                    id = $(this).data("id");
                    ids.push(id);
                }
            });
            if (ids.length > 0) {
                $.showLoading();
                $.ajax({
                    type: 'POST',
//						async:false,
                    url: '/listing/lazada-listing-v2/delete',
                    data: {
                        ids: ids.join(',')
                    },
                    dataType: 'json',
                    success: function (data) {
                        $.hideLoading();
                        if (data.code == 200) {
                            bootbox.alert({
                                title: Translator.t('提示'), message: data.message, callback: function () {
                                    window.location.reload();
                                    $.showLoading();
                                }
                            });
                        }
                        if (data.code == 400) {
                            bootbox.alert(data.message);
                        }

                    },
                    error: function () {
                        $.hideLoading();
//							$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
                        bootbox.alert("网络错误, 请稍后再试！");
                    }
                });
            } else {
                $('select[name="batch"] option').eq(0).prop('selected', true);
                bootbox.alert("没有勾选选项");
                return null;
            }
            ;
        }
        ;
    },

    //单个删除产品
    deleteProduct: function (id) {
        var ids = [];
        ids.push(id);
        if (ids.length > 0) {
            $.showLoading();
            $.ajax({
                type: 'POST',
//					async:false,
                url: '/listing/lazada-listing/delete',
                data: {
                    ids: ids.join(',')
                },
                dataType: 'json',
                success: function (data) {
                    $.hideLoading();
                    if (data.code == 200) {
                        bootbox.alert({
                            title: Translator.t('提示'), message: data.message, callback: function () {
                                window.location.reload();
                                $.showLoading();
                            }
                        });
                    }
                    if (data.code == 400) {
                        bootbox.alert(data.message);
                    }

                },
                error: function () {
                    $.hideLoading();
//						$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
                    bootbox.alert("网络错误, 请稍后再试！");
                }
            });
        } else {
            bootbox.alert("获取商品信息失败");
            return null;
        }
    },
    deleteProductV2: function (id) {
        var ids = [];
        ids.push(id);
        if (ids.length > 0) {
            $.showLoading();
            $.ajax({
                type: 'POST',
//					async:false,
                url: '/listing/lazada-listing-v2/delete',
                data: {
                    ids: ids.join(',')
                },
                dataType: 'json',
                success: function (data) {
                    $.hideLoading();
                    if (data.code == 200) {
                        bootbox.alert({
                            title: Translator.t('提示'), message: data.message, callback: function () {
                                window.location.reload();
                                $.showLoading();
                            }
                        });
                    }
                    if (data.code == 400) {
                        bootbox.alert(data.message);
                    }

                },
                error: function () {
                    $.hideLoading();
//						$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
                    bootbox.alert("网络错误, 请稍后再试！");
                }
            });
        } else {
            bootbox.alert("获取商品信息失败");
            return null;
        }
    },

    //同步商品
    SyncSubmit: function () {
        var remind = '';
        $(".success_message").html('');
        var lzd_uid = $("#Sync_lzd_uid").val();
        if (lzd_uid == "") {
            remind = "请选择店铺！";
            $(".success_message").html(remind);
        } else {
            $.showLoading();
            $.ajax({
                type: 'GET',
                url: '/listing/lazada/manual-sync',
                data: $('#Sync-product').serialize(),
                dataType: 'json',
                success: function (data) {
                    $.hideLoading();
                    if (data.success == true) {
                        remind = "成功同步商品" + data.num + "条";
//							$(".success_message").html(remind);
                        bootbox.alert({
                            title: Translator.t('提示'), message: remind, callback: function () {
                                window.location.reload();
                                $.showLoading();
                            }
                        });
                    }
                    if (data.success == false) {
                        remind = data.message;
                        $(".success_message").html(remind);
                    }
                },
                error: function () {
                    $.hideLoading();
//						$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
                    bootbox.alert("网络错误, 请稍后再试！");
                }
            });
        }
    },

    hide: function (obj) {
        $(obj).removeClass("glyphicon-chevron-up");
        $(obj).addClass("glyphicon-chevron-down");
        $(obj).parents('div[class="panel-heading"]').next().removeClass("show");
        $(obj).parents('div[class="panel-heading"]').next().addClass("hide");
    },
//		//创建商品的帐号检查
//		createProduct:function(obj){
//			if($(obj).val()){
//				var type = $(obj).parents('span').attr('id');
//				$('#'+type+'_comfirm').attr("disabled",false);
////				var text = $('#built_account').find("option:selected").text();
////				var account_value = $(obj).val();
////				var link = '/listing/lazada-listing/create-product?account_value='+account_value+'&text='+text;
////				$('#account_comfirm').parent('a').attr("href",link);
//			}else{
//				var type = $(obj).parents('span').attr('id');
//				$('#'+type+'_comfirm').attr("disabled","disabled");
////				$('#account_comfirm').parent('a').attr("href","#");
//			}
//		},
    //选择框重置
    reset: function () {
        $('.success_message').html('');
        $("#Sync_lzd_uid").find("option[value='']").attr("selected", true);
    },
//		//复制产品
//		copy:function(obj){
//			$('#copy_lzd_uid').val('');
//			$('#copy_comfirm').attr("disabled","disabled");
//			$('#product_id').val('');
//			var id = $(obj).parents('tr').data('id');
//			if(id == ''){
//				bootbox.alert("获取产品id失败！");
//			}else{
//				$('#product_id').val(id);
//			};
//		},

    // 发布产品
    publishOne: function (id) {
        $.showLoading();
        $.ajax({
            type: "get",
            url: "/listing/lazada-listing/do-publish",
            data: {ids: id},
            dataType: 'json',
            success: function (result) {
                $.hideLoading();
                if (result.code == 200) {//发布成功
                    bootbox.alert({
                        title: Translator.t('提示'), message: result.message, callback: function () {
                            window.location.reload();
                            $.showLoading();
                        }
                    });
                } else {
                    bootbox.alert(result.message);
                }
            },
            error: function () {
                $.hideLoading();
                bootbox.alert("网络错误！");
            }
        });
    },
    publishOneV2: function (id) {
        $.showLoading();
        $.ajax({
            type: "get",
            url: "/listing/lazada-listing/do-publish",
            data: {ids: id},
            dataType: 'json',
            success: function (result) {
                $.hideLoading();
                if (result.code == 200) {//发布成功
                    bootbox.alert({
                        title: Translator.t('提示'), message: result.message, callback: function () {
                            window.location.reload();
                            $.showLoading();
                        }
                    });
                } else {
                    bootbox.alert(result.message);
                }
            },
            error: function () {
                $.hideLoading();
                bootbox.alert("网络错误！");
            }
        });
    },
    show: function (obj) {
        $(obj).removeClass("glyphicon-chevron-down");
        $(obj).addClass("glyphicon-chevron-up");
        $(obj).parents('div[class="panel-heading"]').next().removeClass("hide");
        $(obj).parents('div[class="panel-heading"]').next().addClass("show");
    },
    //时间插件
//		datePicker:function(obj){
////			$(obj).datepicker({dateFormat:"yy-mm-dd"});
//		},
    //初始化店铺相关信息，服务模板、运费模板、分组
    initShopInfo: function (shopId) {
        if ($.trim(shopId) != "") {
            $("#lazadaUid").val(shopId);
            lazadaListing.selectShop(1);
        }
    },
    // 该sku数据应用到其他sku
    applytToAllSku: function (button) {
    	var dataSourceContainer = $(button).closest('.sku-panel') , 
    		kindeditorArr = [], dataSourcePanelId = $(dataSourceContainer).attr('id')
    		otherSkuAttr = lazadaListing.dataSet.otherSkuAttr;
    	
    	$(dataSourceContainer).siblings().each(function () {
    		var targetContainer = this,
    			tagetPanelId = $(targetContainer).attr('id'),
    			imgLibApi = lazadaListing.dataSet.imgLib[tagetPanelId].api;
    		// 一般非SellerSku 的sku 属性填充
    		$(otherSkuAttr).each(function (i) {
    			var name = otherSkuAttr[i].name,value='';
    			if('SellerSku' == name) return null;
    			
    			switch (otherSkuAttr[i].attrType) {
					case 'input':
		            case 'numeric':
		            case 'date':
		            	value = $(dataSourceContainer).find('tr[cid="' + name + '"] input').val();
		            	$(targetContainer).find('tr[cid="' + name + '"] input').val(value);
	                    break;
	                case 'kindeditor':
	                	KindEditor.ready(function(K) {
	     					window.editor = K.sync('#'+name+dataSourcePanelId);
	     				});
	                    value = $(dataSourceContainer).find('#'+name+dataSourcePanelId).val();
	                	
	                    var obj = {};
	                    obj.id = '#' + name+tagetPanelId;
	                    obj.value = value;
	                    kindeditorArr.push(obj);
	                    break;
	                case 'checkbox':
	                	value = $(dataSourceContainer).find('tr[cid="' + name + '"] input').val();
	                    var valueArr = value.split(',');
	                    $(valueArr).each(function (i) {
	                    	$(targetContainer).find('tr[cid="' + name + '"] input[value="' + valueArr[i] + '"]').prop('checked', true);
	                    });
	                    break;
	                case 'select':
	                	value = $(dataSourceContainer).find('tr[cid="' + name + '"] select').val();
	                    value == '' ? value = '请选择' : '';
	                    $(targetContainer).find('tr[cid="' + name + '"] select').val(value);
	                    break;
    			}
	    	});
    		
    		// 图片信息填充
    		var dataImageNum = $(dataSourceContainer).find('li.iv-image-box').length;
    		
    		// 删除多余图片
			if($(targetContainer).find('li.iv-image-box').length>dataImageNum){
				$(targetContainer).find('li.iv-image-box').each(function(i){
					if(i<dataImageNum) return null;
					imgLibApi.m('base').remove($(this));
				})
			}
			
    		$(dataSourceContainer).find('[name="extra_images[]"]').each(function(i){
    			var src = $(this).val(),thumb = $(this).next().val();
    				
    			if($(targetContainer).find('[name="extra_images[]"]').eq(i).length>0){
    				$(targetContainer).find('[name="extra_images[]"]').eq(i).val(src);
        			$(targetContainer).find('[name="extra_images[]"]').eq(i).next().val(thumb);
        			$(targetContainer).find('.iv-image-box-body>img').eq(i).attr('src',thumb);
    			}else{// 添加图片
    				imgLibApi.m('base').addOne(src,thumb);
    			}
    		});
    		
    	});
    	
    	lazadaListing.multiplekindeditorPush(kindeditorArr);
    	
    },
    //sku批量修改
    //批量弹层html生成
    skuBatchEdit: function (edit_obj) {
        var type = '',
            aName = $(edit_obj).attr('data-names');
        aName == 'price' || aName == 'salePrice' ? type = 'priceEdit' : type = aName;
        if (aName == 'variation') {
            $('div.lzdSkuInfo table tr').each(function () {
                $(this).find('td[data-name="variation"] input[type="text"]').val($(this).index());
            })
            return;
        }
        $('#lzdSkuBatchEdit').find('.modal-body').empty();
        $('#lzdSkuBatchEdit').find('#myModalLabel').html('');
        switch (type) {
            case 'priceEdit'://价格||促销价
                aName == 'price' ? $('#lzdSkuBatchEdit').find('#myModalLabel').html('修改价格') : $('#lzdSkuBatchEdit').find('#myModalLabel').html('修改促销价');
                $('#lzdSkuBatchEdit').find('.modal-dialog').css('width', '400px');
                var str = '<div style="width:85%;margin:0 auto;">' +
                    '<div class="mTop10">' +
                    '<input type="radio" name="priceEditType" value="1" checked style="margin-right:27px;position:relative;top:2px;">' +
                    '<input type="text" class="form-control" onkeyup="value=value.replace(/[^0-9.]/g,\'\')" style="display:inline-block;width:90px;margin-right:10px;">' +
                    '<span class="fColor2">(直接修改价格)</span>' +
                    '</div>' +
                    '<div class="mTop10"><input type="radio" name="priceEditType"  value="2" style="margin-right:10px;position:relative;top:2px;">按 ' +
                    '<select class="form-control" disabled data-names="retailPrice" style="display:inline-block;width:90px;">' +
                    '<option value="1">金额</option>' +
                    '<option value="2">百分比</option>' +
                    '</select> 增加' +
                    '&nbsp;&nbsp;&nbsp;<input type="text" disabled onkeyup="value=value.replace(/[^0-9.-]/g,\'\')" class="form-control" style="display:inline-block;width:90px;"/>' +
                    '&nbsp;<span class="danwei"></span>' +
                    '</div>' +
                    '<div class="mTop10 fColor2">小提示：如果减少，可以输入负数</div>' +
                    '</div>';
                break;
            case 'promotionTime'://促销时间
                $('#lzdSkuBatchEdit').find('#myModalLabel').html('修改促销时间');
                $('#lzdSkuBatchEdit').find('.modal-dialog').css('width', '340px');
                var str = '<div style="width:85%;margin:0 auto;">' +
                    '<table>' +
                    '<tr>' +
                    '<td>开始时间：</td>' +
                    '<td><input type="text" data-name="saleStartDate" class="form-control Wdate" style="padding-left:5px;" id="" name="" value="" placeholder="开始时间" onClick="lazadaListing.datePicker(this)"/></td>' +
                    '</tr>' +
                    '<tr>' +
                    '<td>结束时间：</td>' +
                    '<td><input type="text" data-name="saleEndDate" class="form-control Wdate" style="padding-left:5px;" id="" name="" value="" placeholder="结束时间" onClick="lazadaListing.datePicker(this)"/></td>' +
                    '</tr>' +
                    '</table>' +
                    '</div>';
                break;
            case 'quantity'://库存
                $('#lzdSkuBatchEdit').find('#myModalLabel').html('修改库存');
                $('#lzdSkuBatchEdit').find('.modal-dialog').css('width', '400px');
                var str = '<div style="width:95%;margin:0 auto;">' +
                    '<div class="numDiv">' +
                    '<input type="radio" name="numRadio" checked="true" class="radioIpt" data-val="2" style="margin-right:27px;position:relative;top:2px;" >' +
                    '<input id="num2" class="form-control text" type="text" onkeyup="value=value.replace(/[^0-9]/g,\'\')" placeholder="示例：1" style="display:inline-block;width:90px;margin-right:10px;">' +
                    '(直接修改库存量)' +
                    '</div>' +
                    '<div class="numDiv mTop10">' +
                    '<input type="radio" name="numRadio" class="radioIpt" data-val="1" style="margin-right:27px;position:relative;top:2px;"时>' +
                    '按现有库存量  增加   ' +
                    '<input id="num1" class="form-control text" type="text" disabled="true" onkeyup="value=value.replace(/[^0-9-]/g,\'\')" placeholder="示例：1" style="display:inline-block;width:90px;margin-right:10px;" >' +
                    '</div>' +
                    '<div style="clear:both;" class="fColor2"><span>提示：如果减少，可输入负数。</span></div>' +
                    '</div>';
                break;
            case 'sku'://sku
                $('#lzdSkuBatchEdit').find('#myModalLabel').html('SKU生成规则');
                $('#lzdSkuBatchEdit').find('.modal-dialog').css('width', '540px');
                var str = '<div style="width:510px;margin:0 5px;">' +
                    '<table style="width:100%;"><tr>' +
                    '<td style="width:50%;" class="vAlignTop">' +
                    '<p class="mBottom10">前缀：<input data-names="star" class="modalCommNavIpt" type="text" placeholder="示例：GX"/></p>' +
                    '<p class="m0">后缀：<input type="text" data-names="end" class="modalCommNavIpt" placeholder="示例：US"/></p>' +
                    '</td>' +
                    '<td style="width:50%;">' +
                    '<p class="mBottom10 fColor2">SKU生成格式=[前缀]-[Variation]-[后缀]</p>' +
                    '<p class="mBottom10 fColor2">生成示例：</p>' +
                    '<p class="m0 fColor2">前缀=BG0001，Variation=XL，后缀=CN</p>' +
                    '<p class="mBottom10 fColor2">生成：BG0001-XL-CN</p>' +
                    '</td>' +
                    '</tr></table>' +
                    '</div>';
                break;
        }
        ;
        $('#lzdSkuBatchEdit').find('.modal-body').html(str);
        $('#lzdSkuBatchEdit').find('button.lzdSkuBatchEdit').attr('data-names', aName);
        $('#lzdSkuBatchEdit').modal('show');
    },
    skuBatchEditConfirm: function (confirm_obj) {
        var type = '',
            btnName = $(confirm_obj).attr('data-names');//sku\quantity\price\salePrice\promotionTime
        btnName == 'price' || btnName == 'salePrice' ? type = 'priceEdit' : type = btnName;
        switch (type) {
            case 'priceEdit'://价格||促销价
                $(confirm_obj).closest('.modal-content').find('input[name="priceEditType"]').each(function () {
                    if (this.checked) {
                        var type = $(this).attr('value');
                        if (type == 1) {
                            var str = $(this).closest('div').find('input[type="text"]').val();
                            if (str != '' && str != undefined) {
                                if (!isNaN(str)) {
                                    str = parseFloat(str);
                                    $('div.lzdSkuInfo table td[data-name="' + btnName + '"]').each(function () {
                                        if (str > 0) {
                                            $(this).find('input').val(str.toFixed(2));
                                        } else {
                                            return;
                                        }
                                        ;
                                    });
                                }
                            }
                        } else if (type == 2) {
                            var num = $(this).closest('div').find('select[data-names="retailPrice"]').val(),
                                str = $(this).closest('div').find('input[type="text"]').val();
                            if (str != '' && str != undefined) {
                                if (!isNaN(str)) {
                                    if (num == 1)//金额
                                    {
                                        $('div.lzdSkuInfo table td[data-name="' + btnName + '"]').each(function () {
                                            var priceCell = $(this).find('input').val();
                                            if ($.trim(priceCell) == "") {
                                                priceCell = "0.00";
                                            }
                                            var priceCellArr = priceCell.split('-');//分离减号
                                            var priceStr = "";
                                            for (var i = 0; i < priceCellArr.length; i++) {
                                                var cell = (parseFloat(priceCellArr[i]) + parseFloat(str)).toFixed(2);
                                                if (i > 0) {
                                                    priceStr = priceStr + "-" + cell;
                                                } else {
                                                    priceStr = cell;
                                                }
                                            }
                                            $(this).find('input').val(priceStr);
                                        });

                                    }
                                    ;
                                    if (num == 2)//百分比
                                    {
                                        $('div.lzdSkuInfo table td[data-name="' + btnName + '"]').each(function () {
                                            var t = parseFloat($(this).html()) * (100 + parseFloat(str)) / 100;
                                            var priceCell = $(this).find('input').val();
                                            if ($.trim(priceCell) == "") {
                                                priceCell = "0.00";
                                            }
                                            var priceCellArr = priceCell.split('-');
                                            var priceStr = "";
                                            for (var i = 0; i < priceCellArr.length; i++) {
                                                var cell = (parseFloat(priceCellArr[i]) * (100 + parseFloat(str)) / 100).toFixed(2);
                                                if (i > 0) {
                                                    priceStr = priceStr + "-" + cell;
                                                } else {
                                                    priceStr = cell;
                                                }
                                            }

                                            $(this).find('input').val(priceStr);
                                        });
                                    }
                                    ;
                                }
                                ;
                            }
                            ;
                        }
                        ;
                    }
                });
                break;
            case 'promotionTime'://促销时间
                var starDate = $(confirm_obj).closest('.modal-content').find('input[data-name="saleStartDate"]').val(),
                    endDate = $(confirm_obj).closest('.modal-content').find('input[data-name="saleEndDate"]').val(),
                    value = '';
                if (starDate == '' || endDate == '') {
//					$.fn.message({type:"error", msg:"开始时间和结束时间不能为空!"});
                    bootbox.alert("开始时间和结束时间不能为空!");
                    return;
                }
                if (starDate != '' && endDate != '') {
                    value = lazadaListing.compareDate(starDate, endDate);
                    if (value == 1) {
//						$.fn.message({type:"error", msg:"结束时间必须大于等于开始时间!"});
                        bootbox.alert("结束时间必须大于等于开始时间!");
                        return;
                    } else {
                        $('div.lzdSkuInfo table td[data-name="saleStartDate"] input').each(function () {
                            $(this).val(starDate);
                        })
                        $('div.lzdSkuInfo table td[data-name="saleEndDate"] input').each(function () {
                            $(this).val(endDate);
                        })
                    }
                }
                break;
            case 'quantity'://库存
                var type = '', num = '';
                $('#lzdSkuBatchEdit').find('input[name="numRadio"]').each(function () {
                    if ($(this).is(':checked')) {
                        type = $(this).attr('data-val');
                        num = $(this).closest('div.numDiv').find('input[type="text"]').val();
                    }
                });
                if (type == 1) {
                    if (num != '') {
                        $('div.lzdSkuInfo table').find('tr').each(function () {
                            var newNum = Number($(this).find('td[data-name="quantity"] input[type="text"]').val()) + Number(num);
                            $(this).find('td[data-name="quantity"] input[type="text"]').val(newNum);
                        });
                    } else {
//						$.fn.message({type:"error",msg:"请输入库存增加数"});
                        bootbox.alert("请输入库存增加数");
                        return;
                    }
                    ;
                }
                if (type == 2) {
                    if (num != '') {
                        $('div.lzdSkuInfo table').find('td[data-name="quantity"] input[type="text"]').val(num);
                    } else {
//						$.fn.message({type:"error",msg:"请输入库存数"});
                        bootbox.alert("请输入库存数");
                        return;
                    }
                    ;
                }
                ;
                break;
        }
        ;
        //sku特殊处理
        if (type == 'sku') {
            var star = $('#lzdSkuBatchEdit').find('input[data-names="star"]').val(),
                end = $('#lzdSkuBatchEdit').find('input[data-names="end"]').val(),
                type = null,
                value = null;
            star != '' ? star = star + '-' : star = '';
            end != '' ? end = '-' + end : end = '';
            var isKong = lazadaListing.kong();
            if (isKong == 0) {
//					$.fn.message({type:"error",msg:"不能生成SKU,variation值不能为空！"});
                bootbox.alert("不能生成SKU,variation值不能为空！");
                return;
            }
            if (lazadaListing.variationIsRepeat() == 1) {
//					$.fn.message({type:"error",msg:"不能生成SKU,重复的variation值！"});
                bootbox.alert("不能生成SKU,重复的variation值！");
                return;
            }
            $('div.lzdSkuInfo table tr').each(function () {
                if ($(this).index() != 0) {
                    type = $(this).find('td[data-name="variation"]').attr('data-type');
                    value = $(this).find('td[data-name="variation"]').find(type).val();
                    $(this).find('td[data-name="sellerSku"] input[type="text"]').val(star.trim().replace(/(\s+|　+)/g, '_') + value.trim().replace(/(\s+|　+)/g, '_') + end.trim().replace(/(\s+|　+)/g, '_'));
                }
            });
        }
        $('#lzdSkuBatchEdit').modal('hide');
    },
    kong: function () {
        var typeA = 1;
        $('div.lzdSkuInfo table tr').each(function () {
            if ($(this).index() != 0) {
                var type = $(this).find('td[data-name="variation"]').attr('data-type');
                var value = $(this).find('td[data-name="variation"]').find(type).val();
                if (value == '' || value == '请选择') {
                    typeA = 0;
                }
            }
        });
        return typeA;
    },
    //variation是否重复
    variationIsRepeat: function () {
        var typeA = 0, arr = [];
        $('[cid="lzdVariant"]>.sku-panel table[cid="lzdSkuAttr"]').each(function () {
            if ($(this).index() != 0) {
                var type = $(this).find('td[data-name="variation"]').attr('data-type');
                var value = $(this).find('td[data-name="variation"]').find(type).val();
                if ($.inArray(value, arr) != -1) {
                    typeA = 1;
                } else {
                    arr.push(value);
                }
            }
        });
        return typeA;
    },
    //选择类目
    selectCategory: function () {
        var shopId = $("#lazadaUid").val();
        if ($.trim(shopId) == "") {
//				$.fn.message({type:"error", msg:"请选择lazada店铺!"});
//				bootbox.alert("请选择lazada店铺!");
            $('#select_shop_info').html("请选择lazada店铺!");
            $('html,body').animate({scrollTop: $('div[id="store-info"]').offset().top}, 800);
            return;
        }
        //保存选中前的类目数据
//			if($.trim($("#categoryId").val())){
//				editCategoryPreservationData();
//				editCategoryGetSkuListData();
//			}

        $('#categoryChoose').modal('show');
    },

    //选择店铺或初始化店铺
    selectShop: function (flag) {
        var shopId = $("#lazadaUid").val();
        //切换店铺
        if ($.trim(shopId) == "") {
            bootbox.alert("获取帐号失败！");
            return;
        } else {
            //切换店铺，由于站点不同，所以初始化类目和属性
            $('#select_shop_info').html("");
            $("#categoryId").val("");
            $("#categoryHistoryId").html('<option value="">---- 请选择分类 ----</option>');
            $(".category").html("未选择分类");

//				lazadaListing.pageBeginning();

            //初始化第一级类目
            lazadaListing.initCategory(null);
//				lazadaListing.browseNodeInitCategory(null);
        }

        //历史记录类目
        $.ajax({
            type: 'POST',
            async: false,
            url: '/listing/lazada-listing/get-selected-category-history',
            data: {
                lazada_uid: shopId
            },
            dataType: 'json',
            success: function (data) {
                if (data.code == 200) {
                    var historyList = data.data;
                    var categoryHistoryId = "";
                    if (historyList != undefined) {
                        categoryHistoryId = '<option value="">---- 请选择分类 ----</option>';
                        for (var i = 0; i < historyList.length; i++) {
                            var ch = historyList[i];
                            if (ch != undefined) {
                                var checkInfo = '';
                                categoryHistoryId += '<option value="' + ch.categoryId + '" ' + checkInfo + '>' + ch.categoryName + '</option>';
                            }
                        }
                        $("#categoryHistoryId").html(categoryHistoryId);
                    }
                    //切换店铺时已选中的类目自动选中
                    var historyCategoryId = $("#categoryId").val();
                    if ($.trim(historyCategoryId) != "") {
                        lazadaListing.getNewCategoryHistory(historyCategoryId);
                    }
                } else {
                    console.log(data.message);
                }

            }
        });
    },

    //选中类目
    getNewCategoryHistory: function (categoryId, categoryName) {
        var obj = $('#categoryHistoryId option[value="' + categoryId + '"]');

        //如果历史列表存在则直接选中
        if (obj.text() != undefined && obj.text() != "") {
            $('#categoryHistoryId option[value=""]').remove();
            $("#categoryHistoryId").val(categoryId);
            return;
        }

        var categoryHistoryId = '';
        categoryHistoryId += '<option value="' + categoryId + '">' + categoryName + '</option>';
        $("#categoryHistoryId").append(categoryHistoryId);
        $("#categoryHistoryId").val(categoryId);
        $('#categoryHistoryId option[value=""]').remove();
//			$.ajax({
//				type:'POST',
//				async:false,
//				url:'lazadaCategory/getByCategoryId.json',
//				data:{
//					shopId:$("#shopId").val(),
//					categoryId:categoryId
//				},
//				dataType:'json',
//				success:function(data){
//					if(data != null){
//						categoryHistoryId += '<option value="'+categoryId+'">'+data.categoryName+'</option>';
//						$("#categoryHistoryId").append(categoryHistoryId);
//						$("#categoryHistoryId").val(categoryId);
//						$('#categoryHistoryId option[value=""]').remove();
//					}
//				}
//			});				
    },
    /**
     * 选择产品分类历史记录
     */
    selectHistoryCategory: function (obj) {
        var primaryCategory = $(obj).val();
        var lazada_uid = $("#lazadaUid").val();
        if ($.trim(primaryCategory) == "") {

        } else {
            //清除请选择
            $('#categoryHistoryId option[value=""]').remove();

            $.ajax({
                type: 'POST',
                async: false,
                url: '/listing/lazada-listing/get-all-categoryids',
                data: {
                    lazada_uid: lazada_uid,
                    primaryCategory: primaryCategory
                },
                dataType: 'json',
                success: function (data) {
                    if (data.code == 200) {
                        lazadaListing.initEditProduct(primaryCategory);
                        lazadaListing.initCategory(data.data);
                        $('#select_info').html("");
                    }
                }
            });
        }
    },

    //初始化属性
    initEditProduct: function (categoryId) {
        //加载属性
        $.ajax({
            type: 'POST',
            async: false,
            url: '/listing/lazada-listing/get-category-attrs',
            data: {
                lazada_uid: $("#lazadaUid").val(),
                primaryCategory: categoryId
            },
            dataType: 'json',
            success: function (data) {
                $("#categoryId").val(categoryId);

                //填充产品属性
                lazadaListing.lazadaDataHandle(data);
            }
        });
    },

    //获取第一级
    initCategory: function (categoryList) {
        var pcid = "0";
        //获取第一级
        $.ajax({
            type: 'POST',
            async: false,
            url: '/listing/lazada-listing/get-category-tree',
            data: {
                lazada_uid: $("#lazadaUid").val(),
                parentCategoryId: pcid
            },
            dataType: 'json',
            success: function (data) {
                if (data.code == 200) {
                    lazadaListing.categoryShow(data);

                    $(".category").html($(".categoryChooseCrumbs").html());
                    categoryList = eval(categoryList);
                    if (categoryList != undefined) {
                        for (var i = categoryList.length - 1; i >= 0; i--) {
                            var categoryId = categoryList[i];
                            $(".categoryChooseOutDiv .categoryChooseInDiv span[categoryId=" + categoryId + "]").parent().click();
                            if (i == 0) {
                                $(".category").html($(".categoryChooseCrumbs").html());
                                lazadaListing.getNewCategoryHistory(categoryId);
//									$("#categoryHistoryId").val(categoryId);
                            }
                        }
                    }
                } else {
                    bootbox.alert(data.message);
                }
            },
            error: function () {
                bootbox.alert("网络错误！");
            }
        });
    },
    //类目显示,创建每一级项目调用，obj为空时，创建第一级目录
    categoryShow: function (arr, obj) {
        var str = lazadaListing.categoryHtml(arr);
        if (obj == '' || obj == undefined) {
            $('#categoryChoose').find('div.categoryChooseInDiv').hide();
            $('#categoryChoose').find('div.categoryChooseInDiv').empty();
            obj = $('#categoryChoose').find('div.categoryChooseInDiv:first').show();
            obj = $('#categoryChoose').find('div.categoryChooseInDiv:first').html(str);
            $('.categoryChooseCrumbs').find('span').each(function () {
                if ($(this).attr('data-level') != 1) {
                    $(this).empty();
                } else {
                    $(this).html('未选择分类');
                }
            });
        } else {
            $(obj).next().empty();
            $(obj).next().nextAll().hide();
            $(obj).next().nextAll().empty();
            $(obj).next().html(str);
            $(obj).next().show();
        }
    },

    //一级目录下子项点击事件
    categoryClick: function (click_obj) {
        //添加背景色
        $(click_obj).closest('div.categoryChooseInDiv').find('.categoryDiv').removeClass('bgColor5');
        $(click_obj).addClass('bgColor5');

        var isleaf = $(click_obj).find('span.glyphicon').attr('data-isleaf'),//判断有没子集，字符创
            obj = $(click_obj).closest('div.categoryChooseInDiv'),
            nameZh = $(click_obj).find('span.categoryNames').text(),
            id = $(click_obj).find('span.categoryNames').attr('categoryId'),
            level = $(obj).attr('data-level');
        //判断有没子集
        if (isleaf == "false")//有子集
        {
            $('.selectCategoryId').removeClass('selectCategoryId');//
            $(click_obj).addClass('selectCategoryId');
            //加载类目列表
            $.ajax({
                type: 'POST',
                async: false,
                url: '/listing/lazada-listing/get-category-tree',
                data: {
                    lazada_uid: $("#lazadaUid").val(),//站点
                    parentCategoryId: id
                },
                dataType: 'json',
                success: function (data) {
                    if (data.code == 200) {
                        lazadaListing.categoryShow(data, obj);
                    } else {
                        bootbox.alert(data.message);
                    }
                },
                error: function () {
                    bootbox.alert("网络错误！");
                }
            });

        } else {
            $('.selectCategoryId').removeClass('selectCategoryId');
            $(click_obj).addClass('selectCategoryId');
            $(obj).nextAll().hide();
            $(obj).nextAll().empty();
        }
        //生成路径
        if (level == 1) {
            var str = '<span id="' + id + '">' + nameZh + '</span>';
        } else {
            var str = '<span id="' + id + '">&nbsp;>&nbsp;' + nameZh + '</span>';
        }
        $('.categoryChooseCrumbs').find('span[data-level="' + level + '"]').html(str);
        $('.categoryChooseCrumbs').find('span[data-level="' + level + '"]').nextAll().empty();

        if (level <= 3) {
            $('.categoryChooseMiddleDiv').css('width', 'auto');
        } else if (level == 4) {
            $('.categoryChooseMiddleDiv').css('width', '1320px');
        } else {
            $('.categoryChooseMiddleDiv').css('width', '1570px');
        }
        //设滚动条位置 fuyi
        var width = $('.categoryChooseMiddleDiv').width();
        $('.categoryChooseOutDiv').scrollLeft('1300');
    },
    //选择键的funtion
    selectedClik: function () {

        var categoryId = $(".selectCategoryId .categoryNames").attr("categoryId");//选中目录的ID
        var oldCategoryId = $("#categoryId").val();
        var categoryName = $(".selectCategoryId .categoryNames").text();//选中目录的name

        if (categoryId == oldCategoryId) {
            $('#categoryChoose').modal('hide');//确定选择的为最后一项时关闭窗口
        } else {
            //判断是否是叶子节点
            var obj = $('#categoryChoose .categoryChooseOutDiv .categoryChooseInDiv span[categoryid="' + categoryId + '"]');
            var isleaf = $(obj).next().attr('data-isleaf');
            if (isleaf == "false") {
                var cateName = $(obj).text();
//						$.fn.message({type:"error",msg:"您选择的类目 \“"+cateName+"\” 还有子类目,请选择子类目！"});
                bootbox.alert("您选择的类目 \“" + cateName + "\” 还有子类目,请选择子类目！");
                return;
            }
            $.showLoading();
            $.ajax({
                type: 'POST',
//						async:false,
                url: '/listing/lazada-listing/get-category-attrs',
                data: {
                    lazada_uid: $("#lazadaUid").val(),//站点
                    primaryCategory: categoryId
                },
                dataType: 'json',
                success: function (data) {
                    $.hideLoading();
                    if (data.code == 200) {
                        $('#categoryChoose').modal('hide');
                        $("#categoryId").val(categoryId);

                        $(".category").html($(".categoryChooseCrumbs").html());
//								var cateName = $(obj).text();
//								var option_str = "<option value='"+cateName+"'>"+cateName+"</option>";
//								$("#categoryHistoryId").html(option_str);
                        //显示类目属性
                        lazadaListing.lazadaDataHandle(data);
                        $('#select_info').html(" ");
                        //选中下拉
                        lazadaListing.getNewCategoryHistory(categoryId, categoryName);
                    } else {
                        bootbox.alert(data.message);
                    }
                },
                error: function () {
                    $.hideLoading();
                    bootbox.alert("网络错误！");
                }
            });
        }
        ;

    },


    //类目html生成
    categoryHtml: function (arr) {
        var str = '';
        for (var i in arr.data) {
            if (arr.data.hasOwnProperty(i)) {
                str += '<div class="categoryDiv"><span class="categoryNames" categoryId="' + arr.data[i].categoryId + '">' + arr.data[i].categoryName + '</span>';
                if (arr.data[i].isLeaf == 0)//数据库的bool值
                {
                    str += '<span class="glyphicon glyphicon-chevron-right" data-isleaf="' + arr.data[i].isLeaf + '"></span></div>';
                } else {
                    str += '<span class="glyphicon glyphicon-chevron-right" data-isleaf="' + arr.data[i].isLeaf + '" style="display:none;"></span></div>';
                }
            }
        }
        ;
        return str;
    },

    initPhoto: function (imageLibObj,panelId,callback) {
    	lazadaListing.dataSet.imgLib[panelId] = {'ready':false,'api':null};
    	// 由于这个插件不是立即初始化完成的，有些部件是异步加载，所以当回填数据的时候，这个插件可能还没有ready，这里将带回填的图片放到其他地方
    	$(imageLibObj).registerPlugin("SelectImageLib",function(ImageLib){
    		var data=[];
    		var option={maxLength:8,name:'extra_images',primary:'main_image',checkbox:false};
			var imgLib = new ImageLib(this,data,$.extend({
				modules:[
					'iv-local-upload',
					'iv-online-url',
					'iv-lb-lib',
					'copy-url',
					'remove',
				]
			},option));
			
			lazadaListing.dataSet.imgLib[panelId].api = imgLib;// imgLib.m('base').addOne(img.src,img.thumb);
			lazadaListing.dataSet.imgLib[panelId].ready = true;
			if(typeof lazadaListing.dataSet.toPushImages[panelId] != 'undefined')
				callback(lazadaListing.dataSet.toPushImages[panelId],panelId);

		});

    },
    
    //页面初始化方法
    pageBeginning: function () {
        $('table[cid="lzdProductAttr"]').empty();
        $('table[cid="descriptionAttr"]').empty();
        $('table[cid="lzdVariantAttr"]').empty();
    	$('div.panel-group[cid="lzdVariant"]').empty();
        
        lazadaListing.dataSet.otherSkuAttr = [];//otherSkuAttr其它sku属性
        lazadaListing.dataSet.allSkuParameter = [];//allSkuParameter变种参数
        lazadaListing.dataSet.skuSelData = {};//还原选中sku属性集合
        lazadaListing.dataSet.isUseComHideAt = [];//还原已显示的固定隐藏属性
        lazadaListing.dataSet.showAttr = [];// 已显示一般属性
        lazadaListing.dataSet.imgLib = {};// 保留图片库object 后面使用图片库接口后补添加图片
        lazadaListing.dataSet.toPushImages = {};// 由于图片库是异步加载的，数据回填的时候可能还没加载完，所以先标记哪些图片可以随时回填
        lazadaListing.dataSet.skuPanelId = {};// 一个变参的panel id
        
    },
    
    //多个编辑器加载
    showKindeditor: function (arr) {
        var kdeId = '';
        $(arr).each(function (i) {
            kdeId == '' ? kdeId += ('#' + arr[i]) : kdeId += (', #' + arr[i]);
        });
        KindEditor.ready(function (K) {
            window.editor = K.create(kdeId, lazadaListing.dataSet.kdeOption);
        });
    },
    //单个编辑器加载
    showOneKindeditor: function (id) {
        var kdeId = '#' + id;
        var options = JSON.parse(JSON.stringify(lazadaListing.dataSet.kdeOption));
        if (id.indexOf('description') != -1) {
            options.height = '360px';
        }

        KindEditor.ready(function (K) {
            window.editor = K.create(kdeId, options);
        });
    },
    //数据处理
    lzdGetType: function (data) {
        var type = null, attrType = null, isMust = null;
        //input
        if (data.inputType == 'text') {
            data.attrType = 'input';
            attrType = 'input';
        }
        // numeric input
        if (data.inputType == 'numeric') {
            data.attrType = 'numeric';
            attrType = 'numeric';
        }
        // date input
        if (data.inputType == 'date') {
            data.attrType = 'date';
            attrType = 'date';
        }
        //kindeditor
        if (data.inputType == 'richText') {
            data.attrType = 'kindeditor';
            attrType = 'kindeditor';
        }
        //checkbox
        if (data.inputType == 'multiSelect') {
            data.attrType = 'checkbox';
            attrType = 'checkbox';
        }
        if (data.inputType == 'multiEnumInput') {
            data.attrType = 'checkbox';
            attrType = 'checkbox';
        }
        //select
        if (data.inputType == 'singleSelect') {
            data.attrType = 'select';
            attrType = 'select';
        }
        
        isMust = 0;
        if(typeof data.isMandatory != "undefined"){
        	data.isMandatory == 1 ? isMust = 1 : isMust = 0;
        }else if(typeof data.ismandatory != "undefined"){
        	data.mandatory == 1 ? isMust = 1 : isMust = 0;
        }else if(typeof data.mandatory != "undefined"){// dzt20170324 lazada又改了字段，导致必填判断失败
        	data.mandatory == 1 ? isMust = 1 : isMust = 0;
        }
        
        type = {attrType: attrType, isMust: isMust}
        data.isMust = isMust
        return type;
    },
    
    // 变参属性数据处理
    lzdGetVariantInputType: function (data) {
        var type = null, attrType = null, isMust = null;
        //input
        if (data.inputType == 'text' || data.name == 'std_search_keywords') {
            data.attrType = 'input';
            attrType = 'input';
            data.inputType = 'text';// dzt20170925 for std_search_keywords格式修改
        }
        // numeric input
        if (data.inputType == 'numeric') {
            data.attrType = 'numeric';
            attrType = 'numeric';
        }
        // date input
        if (data.inputType == 'date') {
            data.attrType = 'date';
            attrType = 'date';
        }
        //kindeditor
        if (data.inputType == 'richText') {
            data.attrType = 'kindeditor';
            attrType = 'kindeditor';
        }
        //checkbox
        if (data.inputType == 'multiSelect') {
            data.attrType = 'checkbox';
            attrType = 'checkbox';
        }
        if (data.inputType == 'multiEnumInput') {
            data.attrType = 'checkbox';
            attrType = 'checkbox';
        }
        //select
        if (data.inputType == 'singleSelect') {
        	if(data.name == 'tax_class'){//tax_class这个属性不属于变参属性，所以不用特殊处理
        		data.attrType = 'select';
                attrType = 'select';
        	}else{
        		data.attrType = 'checkbox';
                attrType = 'checkbox';
        	}
            
        }
        
        isMust = 0;
        if(typeof data.isMandatory != "undefined"){
        	data.isMandatory == 1 ? isMust = 1 : isMust = 0;
        }else if(typeof data.ismandatory != "undefined"){
        	data.mandatory == 1 ? isMust = 1 : isMust = 0;
        }else if(typeof data.mandatory != "undefined"){// dzt20170324 lazada又改了字段，导致必填判断失败
        	data.mandatory == 1 ? isMust = 1 : isMust = 0;
        }
        type = {attrType: attrType, isMust: isMust};
        data.isMust = isMust;
        return type;
    },
    // 处理数据
    lazadaDataHandle: function (data) {
    	lazadaListing.pageBeginning();//还原数据
    	
        // 固定显示属性已经写死在页面上不需动态插入
        // 初始化固定属性
        $.each(lazadaListing.dataSet.commonHideAttr,function(i){
            var name = lazadaListing.dataSet.commonHideAttr[i];
            var tr = $('tr[cid="'+name+'"]');
            tr.addClass('hidden');
            if(name == 'name_ms' || name == 'description_ms'){
                tr.find('input[type="text"]').attr('ismust',0);
            }else{
                tr.find('td.secondTd').empty();
            }
        });
        
        // 遍历挑出未固定的产品属性，sku属性和变种参数
        $(data.data).each(function (i) {
            if (data.data[i].attributeType != "sku") {// attributeType目前见是只有normal与sku
            	if($.inArray(data.data[i].name,lazadaListing.dataSet.commonShowAttr) == -1){//不在固定显示列表里的
					if($.inArray(data.data[i].name,lazadaListing.dataSet.commonHideAttr) == -1){// 非固定显示，非固定隐藏的产品属性
						var type = lazadaListing.lzdGetType(data.data[i]);
						if($.inArray(data.data[i].name,lazadaListing.dataSet.descriptionAttr) != -1){
							lazadaListing.lzdOneDataHandle(data.data[i], type, "descriptionAttr");
						}else{
							lazadaListing.lzdOneDataHandle(data.data[i], type, "lzdProductAttr");
						}
					}else{
						var tr = $('tr[name="'+data.data[i].name+'"]');
						if(data.data[i].name != 'name_ms'){
							var type = lazadaListing.lzdGetType(data.data[i]);
							var valueObjHtml = lazadaListing.lzdAttrHtmlBorn(data.data[i], type);
							tr.find('.secondTd').append(valueObjHtml);
						}else{
							tr.find('input[type="text"]').attr('ismust',1);
                        }
						
						lazadaListing.dataSet.isUseComHideAt.push(data.data[i].name);
					}
					
					lazadaListing.dataSet.showAttr.push(data.data[i].name);
                }else{
                	lazadaListing.commonAttrHandle(data.data[i]);
                }
            } else {// sku属性
            	if($.inArray(data.data[i].name,lazadaListing.dataSet.skuCommonHideAttr) == -1){
            		var type = lazadaListing.lzdGetVariantInputType(data.data[i]);
//                  if($.inArray(data.data[i].name,lazadaListing.dataSet.skuParameter) == -1){
                	if('checkbox' == type.attrType && 1 == type.isMust || $.inArray(data.data[i].name,lazadaListing.dataSet.skuParameter) != -1){
                    	lazadaListing.dataSet.allSkuParameter.push(data.data[i]);
                    }else{
                    	lazadaListing.dataSet.otherSkuAttr.push(data.data[i]);
                    }
                }
            }
        });
        
        lazadaListing.lzdSkuBorn();
        // sku属性里面有富文本属性，所以在sku属性初始化再初始化富文本
        var arr = lazadaListing.dataSet.initKdId.concat(lazadaListing.dataSet.commonKdId);
        if (arr.length > 0) {
            for (var i = 0; i < arr.length; i++) {
                lazadaListing.showOneKindeditor(arr[i]);
            }
        }
        lazadaListing.dataSet.kindeditorId = lazadaListing.dataSet.kindeditorId.concat(lazadaListing.dataSet.initKdId.splice(0,lazadaListing.dataSet.initKdId.length));
        
        lazadaListing.pushlazadaData(lazadaListing.dataSet.temporaryData.submitData, lazadaListing.dataSet.temporaryData.skuData);
    },
    //单条数据处理
    lzdOneDataHandle: function (data, type, location) {
        var obj = lazadaListing.lzdAttrHtmlBorn(data, type), arr = obj.defaultValue;
        $('table[cid="' + location + '"]').append(obj.str);
        if (arr != null && arr.length > 0) {
            lazadaListing.lzdDefaultValueHandle(data, type, obj.defaultValue);
        }
        
        if($.inArray(type.attrType,['checkbox','kindeditor']) != -1){
       	 	$('[cid="' + data.name + '"]>.firstTd').addClass('vAlignTop')
        }
    },
    //sku属性单条数据处理
    lzdSkuAttrOneDataHandle: function (data, type, container ,location) {
        var obj = lazadaListing.lzdAttrHtmlBorn(data, type, container), arr = obj.defaultValue;
        $(container).find('table[cid="' + location + '"]').append(obj.str);
        
        // dzt20170925 for std_search_keywords 填写
        if(data.name == 'std_search_keywords')
        	$(container).find('table[cid="' + location + '"]').find('[cid="' + data.name + '"] input').attr('placeholder',"关键词单词用“,”分隔，最多支持6个")
        
        if (arr != null && arr.length > 0) {
            lazadaListing.lzdDefaultValueHandle(data, type, obj.defaultValue);
        }
        
        if($.inArray(type.attrType,['checkbox','kindeditor']) != -1){
        	 $(container).find('[cid="' + data.name + '"]>.firstTd').addClass('vAlignTop')
        }
    },
    //默认值处理
    lzdDefaultValueHandle: function (data, type, value) {
        switch (type.attrType) {
            case 'input':
            case 'numeric':
            case 'date':
                $('tr[cid="' + data.name + '"]').find('input[type="text"]').val(value[0]);
                break;
            
            case 'kindeditor':
                break;
            case 'checkbox':
                $(value).each(function (i) {
                    $('tr[cid="' + data.name + '"]').find('input[value="' + value[i] + '"]').prop('checked', true);
                })
                break;
            case 'select':
                if ('ReturnPolicies' == data.name) break;//dzt20160114 2930客户 上传到 3220目录 时填入了ReturnPolicies的默认值，导致上传失败。 
                $('tr[cid="' + data.name + '"]').find('select').val(value[0]);
                break;
        }
    },
    //html生成
    lzdAttrHtmlBorn: function (data, type, container) {
        var str = '', arr = data.options, optionStr = '', defaultValue = null;
        str += lazadaListing.dataSet.lzdAttrTit_1.formatOther(data);
        if (type.isMust == 1) {
            str += lazadaListing.dataSet.isMust;
        }
        str += lazadaListing.dataSet.lzdAttrTit_2.formatOther(data);
        switch (type.attrType) {
            case 'input':
                str += lazadaListing.dataSet.input;
                break;
            case 'numeric':
            	if($.inArray(data.name,lazadaListing.dataSet.floatAttrs) != -1)
            		str += lazadaListing.dataSet.float;
            	else
            		str += lazadaListing.dataSet.numeric;
                break;
            case 'date':
                str += lazadaListing.dataSet.date.formatOther(data);
                break;     
            case 'kindeditor':
            	if(data.attributeType == 'sku'){
            		var panelId = $(container).attr('id');
            		data.kindeditorId = data.name+panelId;
            	}else{
            		data.kindeditorId = data.name;
            	}
                str += lazadaListing.dataSet.kindeditor.formatOther(data);
                lazadaListing.dataSet.initKdId.push(data.kindeditorId);
                break;
            case 'checkbox':
                defaultValue = [];
                $(arr).each(function (i) {
                    str += lazadaListing.dataSet.checkbox.formatOther(arr[i]);
                    if (arr[i].isDefault == 1) {
                        defaultValue.push(arr[i].name)
                    }
                })
                break;
            case 'select':
                defaultValue = [];
                $(arr).each(function (i) {
                    optionStr += lazadaListing.dataSet.option.formatOther(arr[i]);
                    if (arr[i].isDefault == 1) {
                        defaultValue.push(arr[i].name)
                    }
                })
                data.optionStr = optionStr
                str += lazadaListing.dataSet.select.formatOther(data);
                break;
        }
        str += lazadaListing.dataSet.lzdAttrEnd;
        return {str: str, defaultValue: defaultValue};
    },
    
    // 常用属性（即已存在与页面上的属性要不要根据数据更新属性 ）处理，例如初始化各种选项 。 新版比较少这些属性了
    commonAttrHandle: function (data) {
        if ($.inArray(data.name, lazadaListing.dataSet.commonAttr) != -1) {
            $('tr[cid="' + data.name + '"]').show();
            lazadaListing.commonAttrOptionHandle(data);
            lazadaListing.dataSet.showAttr.push(data.name);
        }
        if ($.inArray(data.name, lazadaListing.dataSet.shippingTime) != -1) {
            $('tr[cid="shippingTime"]').show();
            if ($.inArray('shippingTime', lazadaListing.dataSet.showAttr) == -1) {
                lazadaListing.dataSet.showAttr.push('shippingTime');
            }
        }
        if ($.inArray(data.name, lazadaListing.dataSet.packingSize) != -1) {
            $('tr[cid="packingSize"]').show();
            if ($.inArray('packingSize', lazadaListing.dataSet.showAttr) == -1) {
                lazadaListing.dataSet.showAttr.push('packingSize');
            }
        }
    },
    
    //常用属性select的option处理
    commonAttrOptionHandle: function (data) {
        if (data.name == 'Warranty' || data.name == 'WarrantyType' || data.name == 'TaxClass' || data.name == 'ColorFamily') {
            var optionStr = '';
            $(data.Options.Option).each(function (i) {
                optionStr += lazadaListing.dataSet.option.formatOther(data.Options.Option[i]);
            });
            $('tr[cid="' + data.name + '"] select').append(optionStr);
            if (data.name == 'Warranty') {
                $('tr[cid="' + data.name + '"] select').val('No Warranty');
            }
            if (data.name == 'WarrantyType') {
                $('tr[cid="' + data.name + '"] select').val('-');
            }
        }
    },
    
    //变参属性生成
    lzdSkuBorn: function () {
        var allSkuParameter = lazadaListing.dataSet.allSkuParameter;
        if(allSkuParameter.length>0){
        	$(allSkuParameter).each(function (i) {
        		var type = {attrType: allSkuParameter[i].attrType, isMust: allSkuParameter[i].isMust}; 
        		lazadaListing.lzdOneDataHandle(allSkuParameter[i], type, "lzdVariantAttr");
    		});
        }else{// 不存在变参属性的目录，直接初始化一次其他 sku属性
        	lazadaListing.allSkuPanelBorn();
        }
    },
    
    // 所有变参sku页面html生成
    allSkuPanelBorn: function () {
    	var str = '',otherSkuAttr = lazadaListing.dataSet.otherSkuAttr;
    	if(!lazadaListing.isEmptyObject(lazadaListing.dataSet.skuSelData)){
    		$.each(lazadaListing.dataSet.skuSelData,function (name,value) {
    			lazadaListing.addOneSkuAttr(name,value);
        	});
    	}else{
    		lazadaListing.addOneSkuAttr('attr','noSel');
    	}
    },
    
    // skuSelData:变参属性组 如： {color_family:Black,storage_capacity_new:300MB}
    getPanelId: function (skuSelData){
    	var skuSelDataStr='';
    	skuSelDataStr = lazadaListing.getSkuSelDataStr(skuSelData);
    	return lazadaListing.dataSet.skuPanelId[skuSelDataStr];
    },
    
    // skuSelData:变参属性组 如： {color_family:Black,storage_capacity_new:300MB}
    setPanelId: function (skuSelData){
    	var seed = new Date().getTime();
    	var skuSelDataStr='';
    	skuSelDataStr = lazadaListing.getSkuSelDataStr(skuSelData);
    	lazadaListing.dataSet.skuPanelId[skuSelDataStr] = seed;
    },	
    
    // skuSelData:变参属性组 如： {color_family:Black,storage_capacity_new:300MB}
    removePanelId: function (skuSelData){
    	var skuSelDataStr='';
    	skuSelDataStr = lazadaListing.getSkuSelDataStr(skuSelData);
    	delete lazadaListing.dataSet.skuPanelId[skuSelDataStr];
    },	
    
    // 添加选择一个变参属性的处理 
    addOneSkuAttr: function (attrName, attrVal){
    	var toAddAttrs = [];
    	if(typeof lazadaListing.dataSet.skuSelData[attrName] == 'undefined')
    		lazadaListing.dataSet.skuSelData[attrName] = []

    	if($.inArray(attrVal, lazadaListing.dataSet.skuSelData[attrName]) != -1){
    		console.log(attrName, attrVal + ' :已存在');
    		return false;
    	}

    	lazadaListing.dataSet.skuSelData[attrName].push(attrVal);
    	$.each(lazadaListing.dataSet.skuSelData,function(name,vals){
    		if(lazadaListing.dataSet.allSkuParameter.length > 1){
    			if(attrName == name) return true;
        		
        		$(vals).each(function(i){
        			var toAddAttr = {};
        			toAddAttr[attrName] = attrVal;
        			toAddAttr[name] = vals[i];
        			toAddAttrs.push(toAddAttr);
        		});
    		}else{
    			var toAddAttr = {};
    			toAddAttr[attrName] = attrVal;
    			toAddAttrs.push(toAddAttr)
    		}
    		
        });
    		
		$(toAddAttrs).each(function(i){
			lazadaListing.setPanelId(toAddAttrs[i]);
			lazadaListing.addSkuPanel(attrName, attrVal, toAddAttrs[i]);
		});
    },	
    
    // 去除选择一个变参属性的处理 
    removeOneSkuAttr: function (attrName, attrVal){
    	var toRemAttrs = [];
    	if(typeof lazadaListing.dataSet.skuSelData[attrName] == 'undefined'){// 不应进这里
    		console.log(attrName, attrVal + ' :已去除');
    		return true;
    	}

    	var attrIndex = $.inArray(attrVal, lazadaListing.dataSet.skuSelData[attrName]);
    	if(attrIndex == -1){
    		console.log(attrName, attrVal + ' :已去除');
    		return true;
    	}

    	if(lazadaListing.dataSet.allSkuParameter.length > 1){
    		$.each(lazadaListing.dataSet.skuSelData,function(name,vals){
    			if(attrName == name) return true;
        		
        		$(vals).each(function(i){
        			var toRemAttr = {};
        			toRemAttr[attrName] = attrVal;
        			toRemAttr[name] = vals[i];
        			toRemAttrs.push(toRemAttr);
        		});
            });
    	}else{
			var toRemAttr = {};
			toRemAttr[attrName] = attrVal;
			toRemAttrs.push(toRemAttr)
		}	
    	
		$(toRemAttrs).each(function(i){
			lazadaListing.removeSkuPanel(attrName, attrVal, toRemAttrs[i]);
			lazadaListing.removePanelId(toRemAttrs[i]);
		});
		
		// 使用delete 删除数组长度不变，所以用splice
    	lazadaListing.dataSet.skuSelData[attrName].splice(attrIndex,1);
    	if(lazadaListing.dataSet.skuSelData[attrName].length == 0)
    		delete lazadaListing.dataSet.skuSelData[attrName];
    	
    },
    
    // 单个sku页面生成
    addSkuPanel: function (attrName, attrVal, skuSelData){
    	var otherSkuAttr = lazadaListing.dataSet.otherSkuAttr;
		var panelId = lazadaListing.getPanelId(skuSelData);
		var attrLabels = '';
		$.each(skuSelData,function (name, attr) {
			if('attr' == name && 'noSel' == attr)
				return null;
			
			attrLabels += lazadaListing.dataSet.panelHeaderAttrLabel.formatOther({attr:attr});
		});
		
		var str = lazadaListing.dataSet.lzdSkuPanel.formatOther({id:panelId,attrLabels:attrLabels});
		$('[cid="lzdVariant"]').append(str);
		
		var container = $('[cid="lzdVariant"]').find('#'+panelId);
		if($('[cid="lzdVariant"]').find('.sku-panel').length > 1)
			$(container).find('.glyphicon-chevron-up').trigger('click');
			
		$(otherSkuAttr).each(function (i) {
    		var type = lazadaListing.lzdGetType(otherSkuAttr[i]);// 需要重新get lzdGetType ，之前get的lzdGetVariantInputType的值不适用于这里
    		lazadaListing.lzdSkuAttrOneDataHandle(otherSkuAttr[i], type, container, "lzdSkuAttr");
		});

		// sku属性里面的富文本属性
        var arr = lazadaListing.dataSet.initKdId;
        if (arr.length > 0) {
            for (var i = 0; i < arr.length; i++) {
                lazadaListing.showOneKindeditor(arr[i]);
            }
        }
		lazadaListing.dataSet.kindeditorId = lazadaListing.dataSet.kindeditorId.concat(lazadaListing.dataSet.initKdId.splice(0,lazadaListing.dataSet.initKdId.length));
		
		$(container).find('.panel-body').append('<div class="iv-image-lib"></div>'+lazadaListing.dataSet.imgLibAlert);
		lazadaListing.initPhoto($(container).find('.iv-image-lib') , panelId , lazadaListing.setPicAttr);
		
		
		$(container).find('[name="SellerSKU"] input').on('keyup',function(){
			$(container).find('.panel-heading>.panel-title>.sku-text').html($(this).val());
		});
		
		// 先将所选变参属性填入Sku
		var nameArr = [],attr='',sellerSku='';
    	$.each(skuSelData, function (name,val) {
    		nameArr.push(name);
    	});
    	
    	nameArr.sort();
    	$(nameArr).each(function (i) {
    		sellerSku += '_' + skuSelData[nameArr[i]];
    	});
		$(container).find('[name="SellerSKU"] input').val(sellerSku).trigger('keyup');
		
    },
    
    // 去除单个sku页面
    removeSkuPanel: function (attrName,attrVal, skuSelData){
    	var otherSkuAttr = lazadaListing.dataSet.otherSkuAttr;
		var panelId = lazadaListing.getPanelId(skuSelData);
		container = $('[cid="lzdVariant"]').find('#'+panelId).remove();
		console.log(container)
		
    },
    
    //变种列表TdHtml生成
    lzdSkuTdHtmlBorn: function (skuData) {
        var str = '', showHtml = '', optionStr = '', dataType = '', showType = lazadaListing.dataSet.lzdSkuObjArr.skuShowType;
        if (skuData != null) {
            dataType = skuData.attrType;
            if (dataType == 'input') {
                showHtml = lazadaListing.dataSet.input_2;
                skuData.showHtml = showHtml;
            }
            
            if (dataType == 'select') {
                // dzt20160425 发现lazada也是属性 option只有一个的时候Option的结构不是二维数组，而是一维
                if (typeof skuData.Options.Option != "undefined" && typeof skuData.Options.Option.Name != "undefined") {
                    var tempOptionArr = [];
                    tempOptionArr.push(skuData.Options.Option);
                    skuData.Options.Option = tempOptionArr;
                }

                $(skuData.Options.Option).each(function (i) {
                    optionStr += lazadaListing.dataSet.option.formatOther(skuData.Options.Option[i])
                });
                skuData.optionStr = optionStr;
                showHtml = lazadaListing.dataSet.skuSelect.formatOther(skuData);
                skuData.showHtml = showHtml;
            }
        }
        str += lazadaListing.dataSet.lzdSkuTrStar;
        showType == 1 ? str += lazadaListing.dataSet.lzdSkuTd_1.formatOther(skuData) + lazadaListing.dataSet.lzdSkuTd_2 : str += lazadaListing.dataSet.lzdSkuTd_2;
        str += lazadaListing.dataSet.lzdSkuTrEnd;
        return str;
    },
    //数据回填  start 
    //多个富文本同时赋值arr为数组对象
    multiplekindeditorPush: function (arr) {
        KindEditor.ready(function (K) {
            $(arr).each(function (i) {
                window.editor = K.html(arr[i].id, arr[i].value);
            })
        });
    },
    //单个富文本赋值id和value为int型(没有用到)
    singlekindeditorPush: function (id, value) {
        KindEditor.ready(function (K) {
            window.editor = K.html(arr[i].id, arr[i].value);
        });
    },
    pushlazadaData: function (submitData, skuData) {
        var type = null,allSkuParameter = lazadaListing.dataSet.allSkuParameter,
        kindeditorArr = [],otherSkuAttr = lazadaListing.dataSet.otherSkuAttr;
        
        //属性回填
        $.each(submitData,function(position,attrs){
        	$.each(attrs,function(name,value){
        		 type = $('tr[cid="' + name + '"]').attr('attrtype');
                 switch (type) {
                     case 'input':
                     case 'numeric':
                     case 'date':
                         $('tr[cid="' + name + '"] input').val(value);
                         break;
                     case 'kindeditor':
                         var obj = {};
                         obj.id = '#' + name;
                         obj.value = value;
                         kindeditorArr.push(obj);
                         break;
                     case 'checkbox':
                         var valueArr = value.split(',');
                         $(valueArr).each(function (i) {
                             $('tr[cid="' + name + '"] input[value="' + valueArr[i] + '"]').prop('checked', true);
                         });
                         break;
                     case 'select':
                         value == '' ? value = '请选择' : '';
                         $('tr[cid="' + name + '"] select').val(value);
                         break;
                 }
        	})
        });
        
        //ipt计数
        lazadaListing.iptValLength();
        
        //变种回填
		$(skuData).each(function(i){
			var skuSelData = {},isSkip=false;
			
			if(allSkuParameter.length>0){// 未初始化sku界面
				$(allSkuParameter).each(function(j){
					var name = allSkuParameter[j].name
					var value = skuData[i][name];
					// dzt20170213 可能是从旧数据copy 导致sku出现没有 选中的 变参attribute
					if(typeof value == "undefined"){
						isSkip = true;
						return null;
					}
						
					skuSelData[name] = value;
					$('#variant-info table[cid="lzdVariantAttr"] tr[cid="' + name + '"] input[value="' + value + '"]').prop('checked', true);
		        	lazadaListing.addOneSkuAttr(name, value);
				});
			}else{
				$.each(lazadaListing.dataSet.skuSelData,function (name,values) {
					skuSelData[name] = values[0];
		        });
			}
			
			if(isSkip)return null;
			
			var panelId = lazadaListing.getPanelId(skuSelData);
			var container = $('[cid="lzdVariant"]').find('#'+panelId);
			
			$(otherSkuAttr).each(function(j){
				var name = otherSkuAttr[j].name;
				if(typeof skuData[i][name] == 'undefined')// 跳过这个属性
					return null;
				var value = skuData[i][name];
				switch (otherSkuAttr[j].attrType) {
					case 'input':
		            case 'numeric':
		            case 'date':
		            	$(container).find('tr[cid="' + name + '"] input').val(value);
	                    break;
	                case 'kindeditor':
	                    var obj = {};
	                    obj.id = '#' + name+panelId;
	                    obj.value = value;
	                    kindeditorArr.push(obj);
	                    break;
	                case 'checkbox':
	                    var valueArr = value.split(',');
	                    $(valueArr).each(function (i) {
	                    	$(container).find('tr[cid="' + name + '"] input[value="' + valueArr[i] + '"]').prop('checked', true);
	                    });
	                    break;
	                case 'select':
	                    value == '' ? value = '请选择' : '';
	                    $(container).find('tr[cid="' + name + '"] select').val(value);
	                    break;
	            }
			});
			
			lazadaListing.setPicAttr(skuSelData,panelId);
			$(container).find('.panel-heading>.panel-title>.sku-text').html($(container).find('tr[cid="SellerSku"] input').val());
		});
        
		lazadaListing.multiplekindeditorPush(kindeditorArr);
    },
    
    //页面计数方法
    iptValLength: function () {
        var num = '';
        $('div.lzdProductTitle').each(function () {
            num = $(this).find('input[type="text"]').val().length;
            $(this).find('span.unm').html(num);
        })
    },
    //保存产品
    save: function (op) {
        //店铺id
        if ($('#productId').val() == '') {
            var productId = 0;
        } else {
            var productId = $('#productId').val();
        }
        var shopId = $("#lazadaUid").val();
        if ($.trim(shopId) == "") {
            $('#select_shop_info').html("请选择lazada店铺!");
            $('html,body').animate({scrollTop: $('div[id="store-info"]').offset().top}, 800);
            return;
        }

        // dzt20160115 for 复制产品有原来目录属性的值留在submitData，导致最后也保存到复制后的目录里面，所以这里保存之前先清空数组
        lazadaListing.dataSet.temporaryData.submitData['store-info'] = JSON.parse('{}');
        lazadaListing.dataSet.temporaryData.submitData['base-info'] = JSON.parse('{}');
        lazadaListing.dataSet.temporaryData.submitData['variant-info'] = JSON.parse('{}');
        lazadaListing.dataSet.temporaryData.submitData['image-info'] = JSON.parse('{}');
        lazadaListing.dataSet.temporaryData.submitData['description-info'] = JSON.parse('{}');
        lazadaListing.dataSet.temporaryData.submitData['shipping-info'] = JSON.parse('{}');
        lazadaListing.dataSet.temporaryData.submitData['warranty-info'] = JSON.parse('{}');

        //类目
        var categoryId = $("#categoryId").val();
        if ($.trim(categoryId) == "" || $.trim(categoryId) == 0) {
            $('#select_info').html("请选择类目！");
            $('html,body').animate({scrollTop: $('div[id="store-info"]').offset().top}, 800);
            return;
        }

        //类目属性
        var ret = lazadaListing.getSubmitData();

        if (ret != true) {// dzt20160104 这个return 在检查标题字数之前，否则name为空的检查显示不了
            //$.fn.message({type:"error",msg:"产品属性不能为空！"});
            return;
        }

        $('div.lzdProductTitle').parents('tr').each(function () {//检查标题字数是否超过255
            var tr_name = $(this).attr('name');
            $('span[id="' + tr_name + '"]').parents('tr').remove();
        })
        var title_check = "NO";
        $('div.lzdProductTitle').parents('tr').each(function () {//检查标题字数是否超过255
            if ($(this).css('display') != "none") {
                if ($(this).find('input').val().length > 255) {
                    var tr_name = $(this).attr('name');
                    var alert_title_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="' + tr_name + '" style="color:red;"></span></td></tr>';
                    $(this).after(alert_title_message);
                    $('span[id="' + tr_name + '"]').html(tr_name + "长度超过255！");
                    $('html,body').animate({scrollTop: $(this).offset().top}, 800);
                    title_check = "YES";
                    return false;
                }
            }
        });
        if (title_check == "YES") {//检查标题字数是否超过255
            return;
        }

        lazadaListing.validBrand = false;
        // 检查品牌是否存在品牌库
        $.showLoading();
        $.ajax({
            async: false,
            type: "post",
            url: "/listing/lazada-listing/get-brands",
            data: {lazada_uid: $("#lazadaUid").val(), name: $('[cid="brand"]>.secondTd>input').val(), mode: 'eq'},
            dataType: 'json',
            success: function (data) {
                $.hideLoading();
                if (data.code == 400) {
                    bootbox.alert("您添加的品牌不在Lazada规定范围内，请发信到imp.sellercenter@linio.com与Lazada官方联系");
                    lazadaListing.validBrand = false;
                } else {//刊登成功
                    // 拼接提示框信息
                    lazadaListing.validBrand = true;
                }
            },
            error: function () {
                $.hideLoading();
                bootbox.alert("网络错误, 请稍后再试！");
            }
        });

        if (lazadaListing.validBrand == false) return false;

        //类目顺序
        var category_array = new Array();
        $('.category span[data-level]').each(function () {
            if ($(this).html() != "") {
                var la = $(this).find("span").eq(0).attr("id");
                category_array.push(la);
            }
        });
        if (category_array.lenght == 0) {
            $('#select_info').html("没有选择类目顺序！");
            $('html,body').animate({scrollTop: $('div[id="store-info"]').offset().top}, 800);
            return;
        } else {
            var re_category_array = category_array.reverse(); //倒序
            var categories = JSON.stringify(re_category_array);
        }

        //变种
        ret = lazadaListing.getSkuListData();
        if (ret != true) {
            return;
        }
        
        // 在最后生成json字符串，因为getSkuListData里面有对 image-info的修改
        var productDataStr = JSON.stringify(lazadaListing.dataSet.temporaryData.submitData);
        var skus = JSON.stringify(lazadaListing.dataSet.temporaryData.skuData);
        
        $.showLoading();
        $.ajax({
            type: 'POST',
            url: "/listing/lazada-listing/save-product",
            data: {
                id: productId,
                categories: categories,
                lazada_uid: shopId,
                primaryCategory: categoryId,
                productDataStr: productDataStr,
                skus: skus,
                op: op
            },
            dataType: 'json',
            timeout: 60000,
            success: function (data) {
                $.hideLoading();
                if (data != null) {
                    if (data.code == 400) {//刊登失败
                        bootbox.alert(data.message);
                    } else {//刊登成功
                        // 拼接提示框信息
                        bootbox.alert({
                            title: Translator.t('提示'), message: data.message, callback: function () {
                                window.location.href = "/listing/lazada-listing/publish";
                                $.showLoading();
                            }
                        });
                    }
                }
            },
            error: function () {
                $.hideLoading();
//					$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
                bootbox.alert("网络错误, 请稍后再试！");
            }
        });
    },
    
    // 获取图片的地址
    getPicAttr: function (attrs,panelObj) {
    	var returnVal = false;
    	$(panelObj).find('[name="extra_images[]"]').each(function(){
    		if(typeof lazadaListing.dataSet.temporaryData.submitData['image-info']['product_photos'] == 'undefined'){
    			lazadaListing.dataSet.temporaryData.submitData['image-info']['product_photos'] = {};
    			lazadaListing.dataSet.temporaryData.submitData['image-info']['product_photo_thumbnails'] = {};
    		}
    		if(typeof lazadaListing.dataSet.temporaryData.submitData['image-info']['product_photos'][attrs] == 'undefined'){
    			lazadaListing.dataSet.temporaryData.submitData['image-info']['product_photos'][attrs] = [];
    			lazadaListing.dataSet.temporaryData.submitData['image-info']['product_photo_thumbnails'][attrs] = [];
    		}
    			
    		returnVal = true;
    		lazadaListing.dataSet.temporaryData.submitData['image-info']['product_photos'][attrs].push($(this).val());
    		lazadaListing.dataSet.temporaryData.submitData['image-info']['product_photo_thumbnails'][attrs].push($(this).next().val());
    	});
    	
    	return returnVal;
    },
    // 回填图片
    setPicAttr: function (skuSelData,panelId) {
		var skuSelDataStr = '',imgLib = lazadaListing.dataSet.imgLib[panelId];
		skuSelDataStr = lazadaListing.getSkuSelDataStr(skuSelData);
    	
		if(typeof lazadaListing.dataSet.temporaryData.submitData['image-info']['product_photos'][skuSelDataStr] == 'undefined')
			return false;
		
		var images = lazadaListing.dataSet.temporaryData.submitData['image-info']['product_photos'][skuSelDataStr];
		var thumbs = lazadaListing.dataSet.temporaryData.submitData['image-info']['product_photo_thumbnails'][skuSelDataStr];
		if(images.length > 0){
			if(imgLib.ready){
				$(images).each(function (i) {
					imgLib.api.data.push({src:images[i],thumb:thumbs[i]});
				});
				delete lazadaListing.dataSet.toPushImages[panelId];
			}else{
				lazadaListing.dataSet.toPushImages[panelId] = skuSelData;
			}
		}else{
			return false;
		}
		
		return true;
    },
    getSkuSelDataStr: function (skuSelData) {
    	var nameArr = [],attr='',valArr=[];
    	$.each(skuSelData, function (name,val) {
    		nameArr.push(name);
    	});
    	
    	nameArr.sort();
    	$(nameArr).each(function (i) {
    		valArr.push(skuSelData[nameArr[i]]);
    	});
    	attr = valArr.join('==');
    	return attr;
    },
    getSubmitData: function () {
        var attrType = null;
        lazadaListing.dataSet.temporaryData.skuData = [];
        lazadaListing.dataSet.isMustAttrArr = [];
        if (lazadaListing.dataSet.spanCache.length > 0) {
            $('span[id="' + lazadaListing.dataSet.spanCache[0] + '"]').closest('tr').remove();
            lazadaListing.dataSet.spanCache = [];
        }
        
        $(lazadaListing.dataSet.showAttr).each(function (i) {
            attrType = $('tr[cid="' + lazadaListing.dataSet.showAttr[i] + '"]').attr('attrtype');
            lazadaListing.subimtDataBorn(attrType, $('tr[cid="' + lazadaListing.dataSet.showAttr[i] + '"]'));
        });

        // 常用属性性get
        $(lazadaListing.dataSet.commonShowAttr).each(function (i) {
            attrType = $('tr[cid="' + lazadaListing.dataSet.commonShowAttr[i] + '"]').attr('attrtype');
            lazadaListing.subimtDataBorn(attrType, $('tr[cid="' + lazadaListing.dataSet.commonShowAttr[i] + '"]'));
        });
        
        //验证
        if (lazadaListing.dataSet.isMustAttrArr.length > 0) {
            $(lazadaListing.dataSet.isMustAttrArr).each(function (k) {
                var tr_parent = $('tr[cid="' + lazadaListing.dataSet.isMustAttrArr[k] + '"]');
                var alert_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="' + lazadaListing.dataSet.isMustAttrArr[k] + '" style="color:red;"></span></td></tr>';
                $(tr_parent).after(alert_message);
                $('span[id="' + lazadaListing.dataSet.isMustAttrArr[k] + '"]').html(tr_parent.attr("name") + "不能为空！");
                $('html,body').animate({scrollTop: $(tr_parent).offset().top}, 800);
                lazadaListing.dataSet.spanCache.push(lazadaListing.dataSet.isMustAttrArr[k]);
                return false;
            })
            
            return null;
        }

        if (lazadaListing.dataSet.temporaryData.submitData.Description != undefined) {
            if (lazadaListing.isContainChinese(lazadaListing.dataSet.temporaryData.submitData.Description) == 1) {
                bootbox.alert("保存失败！产品描述中不能包含中文字符!");
                return null;
            }
        }
        if (lazadaListing.dataSet.temporaryData.submitData.DescriptionMs != undefined) {
            if (lazadaListing.isContainChinese(lazadaListing.dataSet.temporaryData.submitData.DescriptionMs) == 1) {
                bootbox.alert("保存失败！产品描述（马来语）中不能包含中文字符!");
                return null;
            }
        }
        if (lazadaListing.dataSet.temporaryData.submitData.DescriptionEn != undefined) {
            if (lazadaListing.isContainChinese(lazadaListing.dataSet.temporaryData.submitData.DescriptionEn) == 1) {
                bootbox.alert("保存失败！产品描述（英语）中不能包含中文字符!");
                return null;
            }
        }
        if (lazadaListing.dataSet.temporaryData.submitData.Name != undefined) {
            if (lazadaListing.isContainChinese(lazadaListing.dataSet.temporaryData.submitData.Name) == 1) {
                bootbox.alert("保存失败！产品标题中不能包含中文字符!");
                return null;
            }
        }
        if (lazadaListing.dataSet.temporaryData.submitData.NameMs != undefined) {
            if (lazadaListing.isContainChinese(lazadaListing.dataSet.temporaryData.submitData.NameMs) == 1) {
                bootbox.alert("保存失败！产品标题（马来语）中不能包含中文字符!");
                return null;
            }
        }
        if (lazadaListing.dataSet.temporaryData.submitData.NameEn != undefined) {
            if (lazadaListing.isContainChinese(lazadaListing.dataSet.temporaryData.submitData.NameEn) == 1) {
                bootbox.alert("保存失败！产品标题（英语）中不能包含中文字符!");
                return null;
            }
        }
        //验证end
        return true;
    },
    subimtDataBorn: function (type, obj) {
        var name = '', value = null, attrObj = {}, isMust = $(obj).attr('isMust'), label = $(obj).attr('cid');
        switch (type) {
            case 'input':
            case 'numeric':
            case 'date':
                name = $(obj).attr('cid');
                info_id = $(obj).parents(".search-info").attr("id");
                if (info_id == "product-info") {//将商品属性保存到base-info中
                    info_id = "base-info";
                }
                value = '';
                value = $(obj).find('input[type="text"]').val();
                break;
            case 'kindeditor':
            	 KindEditor.ready(function(K) {
 					window.editor = K.sync('#'+name);
 				});
            	 
                name = $(obj).attr('cid');
                info_id = $(obj).parents(".search-info").attr("id");
                if (info_id == "product-info") {//将商品属性保存到base-info中
                    info_id = "base-info";
                }
                value = $(obj).find('[name="content"]').val();
                break;
            case 'checkbox':
                name = $(obj).attr('cid');

                info_id = $(obj).parents(".search-info").attr("id");
                if (info_id == "product-info") {//将商品属性保存到base-info中
                    info_id = "base-info";
                }

                value = '';
                $(obj).find('input[type="checkbox"]').each(function () {
                    if (this.checked) {
                        value == '' ? value += $(this).val() : value += "," + $(this).val();
                    }
                });
                break;
            case 'select':
                name = $(obj).attr('cid');

                info_id = $(obj).parents(".search-info").attr("id");
                if (info_id == "product-info") {//将商品属性保存到base-info中
                    info_id = "base-info";
                }

                value = '';
                $(obj).find('select').val() == '请选择' ? value = '' : value = $(obj).find('select').val();
                break;
        }
        if (isMust == 1 && value == '') {
            lazadaListing.dataSet.isMustAttrArr.push(label);
        }
        
        if($(obj).closest('search-info').attr('id') != 'variant-info') // 变参属性必填提示要加，但不写入 submitData
        	lazadaListing.dataSet.temporaryData.submitData[info_id][name] = value;
    },
    subimtSkuDataBorn: function (type, docObj, dataObj) {
        var name = '', value = null, attrObj = {}, isMust = $(docObj).attr('isMust'), label = $(docObj).attr('cid');
        switch (type) {
            case 'input':
            case 'numeric':
            case 'date':
                name = $(docObj).attr('cid');
                value = $(docObj).find('input[type="text"]').val();
                break;
            case 'kindeditor':
            	KindEditor.ready(function(K) {
 					window.editor = K.sync('#'+name);
 				});
                name = $(docObj).attr('cid');
                value = $(docObj).find('[name="content"]').val();
                break;
            case 'checkbox':
                name = $(docObj).attr('cid');
                value = '';
                $(docObj).find('input[type="checkbox"]').each(function () {
                    if (this.checked) {
                        value == '' ? value += $(this).val() : value += "," + $(this).val();
                    }
                });
                break;
            case 'select':
                name = $(docObj).attr('cid');
                $(docObj).find('select').val() == '请选择' ? value = '' : value = $(docObj).find('select').val();
                break;
        }
        if (isMust == 1 && value == '') {
            lazadaListing.dataSet.isMustAttrArr.push(label);
        }
        
        dataObj[name] = value;
        return dataObj;
    },
    
    //中文判断
    isContainChinese: function (value) {
        var typeA = 0, validate = /[^\x00-\xff]/ig;//中文和全角字符 
        if (value.match(validate)) {
            typeA = 1;
        }
        return typeA;
    },
    //属性get
    //sku列表get
    getSkuListData: function () {
    	lazadaListing.dataSet.isMustAttrArr = [];
        var nowDate = new Date().format("yyyy-MM-dd");
        var selAttrs = [],valid=true,message='',position='',skuSelData={};
        
        // 没有sku信息
        if(lazadaListing.isEmptyObject(lazadaListing.dataSet.skuPanelId)){
        	$(lazadaListing.dataSet.allSkuParameter).each(function (i) {
	    		var attrType = lazadaListing.dataSet.allSkuParameter[i].attrType;
	            lazadaListing.subimtDataBorn(attrType, $('tr[cid="' + lazadaListing.dataSet.allSkuParameter[i].name + '"]'));
	    	});
     		
            //验证
            if (lazadaListing.dataSet.isMustAttrArr.length > 0) {
                $(lazadaListing.dataSet.isMustAttrArr).each(function (k) {
                    var tr_parent = $('tr[cid="' + lazadaListing.dataSet.isMustAttrArr[k] + '"]');
                    var alert_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="' + lazadaListing.dataSet.isMustAttrArr[k] + '" style="color:red;"></span></td></tr>';
                    $(tr_parent).after(alert_message);
                    $('span[id="' + lazadaListing.dataSet.isMustAttrArr[k] + '"]').html(tr_parent.attr("name") + "不能为空！");
                    $('html,body').animate({scrollTop: $(tr_parent).offset().top}, 800);
                    lazadaListing.dataSet.spanCache.push(lazadaListing.dataSet.isMustAttrArr[k]);
                    return false;
                })
                
                return null;
            }
        }
        
        $.each(lazadaListing.dataSet.skuSelData,function (name,values) {
        	selAttrs.push(name);
        });
        selAttrs.sort();
        $.each(lazadaListing.dataSet.skuPanelId,function (attrs,panelId) {
	    	var obj = {},attrArr = attrs.split('=='),panelObj=$('#'+panelId);
	    	
	    	$(selAttrs).each(function (i) {
	    		obj[selAttrs[i]] = attrArr[i];
	    		skuSelData[selAttrs[i]] = attrArr[i];
	    	 });
	    	
	    	obj.skuSelData = attrs;
	    	$(panelObj).removeClass('panel-danger');
	    	$(panelObj).removeClass('panel-success');
	    	if (lazadaListing.dataSet.spanCache.length > 0) {
	    		$(panelObj).find('span[id="' + lazadaListing.dataSet.spanCache[0] + '"]').closest('tr').remove();
	            lazadaListing.dataSet.spanCache = [];
	        }
	    	
	    	$(lazadaListing.dataSet.otherSkuAttr).each(function (i) {
	    		var attrType = lazadaListing.dataSet.otherSkuAttr[i].attrType;
	            lazadaListing.subimtSkuDataBorn(attrType, $(panelObj).find('tr[cid="' + lazadaListing.dataSet.otherSkuAttr[i].name + '"]'), obj);
	    	});
        	
	    	if (lazadaListing.dataSet.isMustAttrArr.length > 0) {
	    		valid = false;
	    		$(lazadaListing.dataSet.isMustAttrArr).each(function (k) {
	    			position = lazadaListing.dataSet.isMustAttrArr[k];
	    			message = "不能为空！";
		    		return false;
	    		})
    		}
        	
	    	if(valid){
	    		if (lazadaListing.quantityIsNullVerification(panelObj) == 1) {
	         		valid = false;
	         		position = 'quantity';
	         		message = "保存失败！库存不能为空！";
	     		}
	     		if (lazadaListing.priceIsNullVerification(panelObj) == 1) {
	     			valid = false;
	     			position = 'price';
	     			message = "保存失败！价格不能为空！";
	     		}
	     		if (lazadaListing.skuIsRepeat(panelObj) == 1) {
	     			valid = false;
	     			position = 'SellerSku';
	     			message = "保存失败！产品SKU不能重复！";
	     		}
	     		if (lazadaListing.skuIsContainChinese(panelObj) == 1) {
	     			valid = false;
	     			position = 'SellerSku';
	     			message = "保存失败！SKU中不能包含中文字符！";
	     		}
	     		if (lazadaListing.priceComparisonSalePrice(panelObj) == 1) {
	     			valid = false;
	     			position = 'special_price';
	     			message = "保存失败！促销价必须小于价格！";
	     		}
	     		if (lazadaListing.dateVerificationA(panelObj) == 1) {
	     			valid = false;
	     			position = 'special_price';
	 	    		message = "保存失败！请完整输入促销价格以及促销时间！";
	     		}
	     		if (lazadaListing.dateVerificationB(panelObj) == 1) {
	     			valid = false;
	     			position = 'special_to_date';
	     			message = "保存失败！促销结束时间不能小于当前时间！";
	     		}
	     		if (lazadaListing.dateVerificationC(panelObj) == 1) {
	     			valid = false;
	     			position = 'special_to_date';
	     			message = "保存失败！促销结束时间必须大于促销开始时间！";
	     		}
	    	}
	    	
	    	if(valid == false){
     			if(position != ''){
     				var tr_parent = $(panelObj).find('tr[cid="' + position + '"]');
                     var alert_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="' + position + '" style="color:red;"></span></td></tr>';
                     $(tr_parent).after(alert_message);
                     $(panelObj).find('span[id="' + position + '"]').html(tr_parent.attr("name") + message);
                     $('html,body').animate({scrollTop: $(tr_parent).offset().top}, 800);
                     lazadaListing.dataSet.spanCache.push(position);
     			}
     			
     			$(panelObj).addClass('panel-danger');
     			$(panelObj).find('.panel-body').removeClass('hide');
     			
     			return false;
     		}
	    	
        	lazadaListing.dataSet.temporaryData.skuData.push(obj);
        	
        	//产品图片
        	$(panelObj).find('.upload_image_info').html('');
            var returnVal = lazadaListing.getPicAttr(attrs,panelObj);
            if (returnVal == false) {
            	valid = false;
            	position = 'div.upload_image_info';
            	$(panelObj).find(position).html("请上传图片！");
                $('html,body').animate({scrollTop: $(panelObj).find(position).offset().top}, 800);
                $(panelObj).addClass('panel-danger');
                $(panelObj).find('.panel-body').removeClass('hide');
                return false;
            } 
            
            $(panelObj).addClass('panel-success');
        });
        
        if(valid == false){
        	if(position == ''){
        		bootbox.alert(message);
        	}
        	return null;
        }
        //console.log(lazadaListing.dataSet.temporaryData)
        return true;
    },

    //库存是否为空
    quantityIsNullVerification: function (panelObj) {
        var typeA = 0;
        $(panelObj).find('tr[cid="quantity"] input[type="text"]').each(function () {
            var num = $(this).val();
            if (num == '' || num == undefined) {
                typeA = 1;
            }
        });
        return typeA;
    },
    priceIsNullVerification: function (panelObj) {
        var typeA = 0;
        $(panelObj).find('tr[cid="price"] input[type="text"]').each(function () {
            if ($(this).index() != 0) {
                var num = $(this).val();
                if (num == '' || num == undefined) {
                    typeA = 1;
                }
            }
        });
        return typeA;
    },
//		//productID是否为空
//		productIdIsNullVerification:function(){
//			var typeA = 0;
//			$('[cid="lzdVariant"]>.sku-panel table[cid="lzdSkuAttr"]').each(function(){
//				if($(this).index() != 0){
//					var num = $(this).find('td[data-name="productGroup"] input[type="text"]').val();
//					if (num == '' || num == undefined){
//						typeA = 1;
//					}
//				}
//			});
//			return typeA;
//		},
    //sku是否为空
    skuIsNullVerification: function () {
        var typeA = 0;
        $('[cid="lzdVariant"]>.sku-panel table[cid="lzdSkuAttr"]').each(function () {
            if ($(this).index() != 0) {
                var sku = $(this).find('td[data-name="sellerSku"] input[type="text"]').val();
                if (sku == '' || sku == undefined) {
                    typeA = 1;
                }
            }
        });
        return typeA;
    },
    //sku是否重复
    skuIsRepeat: function (panelObj) {
        var typeA = 0, SellerSku = $(panelObj).find('tr[cid="SellerSku"] input[type="text"]').val();
        $(panelObj).siblings().find('tr[cid="SellerSku"] input[type="text"]').each(function () {
            if (SellerSku == $(this).val()) {
                typeA = 1;
            } 
        });
        return typeA;
    },
    //价格和促销价比对
    priceComparisonSalePrice: function (panelObj) {
        var typeA = 0;
        var price = $(panelObj).find('tr[cid="price"] input[type="text"]').val(),
            salePrice = $(panelObj).find('tr[cid="special_price"] input[type="text"]').val();
        if (Number(salePrice) > Number(price)) {
            typeA = 1;
        }
        return typeA;
    },
    //sku是否有中文
    skuIsContainChinese: function (panelObj) {
        var typeA = 0;
        var value = $(panelObj).find('tr[cid="SellerSku"] input[type="text"]').val();
        if (lazadaListing.isContainChinese(value) == 1) {
            typeA = 1;
        }
        return typeA;
    },
    //促销时间验证
    dateVerificationA: function (panelObj) {
        var typeA = 0;
        
        var salePrice = $(panelObj).find('tr[cid="special_price"] input[type="text"]').val(),
            saleStartDate = $(panelObj).find('tr[cid="special_from_date"] input[type="text"]').val(),
            saleEndDate = $(panelObj).find('tr[cid="special_to_date"] input[type="text"]').val();
        
        // 有SalePrice SaleStartDate SaleEndDate 三个必须同时存在,否则报错
		if((!(salePrice || saleStartDate || saleEndDate)) || (salePrice && saleStartDate && saleEndDate)){
			return typeA;
		}
		
		typeA = 1;
        return typeA;
    },
    dateVerificationB: function (panelObj) {
        var typeA = 0;
        var saleStartDate = $(panelObj).find('tr[cid="special_to_date"] input[type="text"]').val(),
            nowDate = new Date().format("yyyy-MM-dd"),
            verificationVal = lazadaListing.compareDate(nowDate, saleStartDate);
        if (verificationVal == 1) {
            typeA = 1;
        }
        return typeA;
    },
    dateVerificationC: function (panelObj) {
        var typeA = 0;
        var saleStartDate = $(panelObj).find('tr[cid="special_from_date"] input[type="text"]').val(),
            saleEndDate = $(panelObj).find('tr[cid="special_to_date"] input[type="text"]').val(),
            verificationVal = 0;
        
        if (saleStartDate == '' && saleEndDate == '') {
        	return typeA;
        }
        verificationVal = lazadaListing.compareDate(saleStartDate, saleEndDate)
        if (verificationVal != -1) {
            typeA = 1;
        }
        return typeA;
    },
    ///////////////////引用相关//////////////////
    replaceFloat: function (e) {
        var value = $(e).val().replace(/[^0-9.]/g, '');
        $(e).val(value);
    },

    replaceNumber: function (e) {
        var value = $(e).val().replace(/[^0-9]/g, '');
        $(e).val(value);
    },
    replaceSize: function (e) {
        var value = $(e).val().replace(/[^0-9.x]/g, '');
        $(e).val(value);
    },
    //时间比较
    compareDate: function (startTime, endTime) {
        var start = new Date(startTime.replace("-", "/").replace("-", "/"));
        var end = new Date(endTime.replace("-", "/").replace("-", "/"));
        if (start.getTime() == end.getTime()) {
            return 0;
        } else if (start.getTime() > end.getTime()) {
            return 1;
        } else if (start.getTime() < end.getTime()) {
            return -1;
        }
    },
    editCompareDate: function (startTime, endTime) {
        var start = new Date(startTime.replace("-", "/").replace("-", "/"));
        var end = new Date(endTime.replace("-", "/").replace("-", "/"));
        if (start.getTime() < end.getTime()) {
            return -1;
        } else {
            return 1;
        }
    },
    // 引用商品弹窗
    listReferences: function () {
        $.showLoading();
        if ($("#select-reference-table").parents('.modal').length > 0) {
            $("#select-reference-table").parents('.modal').remove();
        }
        $.ajax({
            type: 'get',
            url: "/listing/lazada-listing/list-references",
            timeout: 60000,
            success: function (data) {
                $.hideLoading();
                if (data != null) {
                    bootbox.dialog({
                        title: Translator.t("引用商品"),
                        className: "lazada-listing-reference",
                        buttons: {
                            Cancel: {
                                label: Translator.t("取消"),
                                className: "btn-default",
                            },
                            OK: {
                                label: Translator.t("确定"),
                                className: "btn-primary",
                                callback: function () {
                                    lazadaListing.userReference();
                                    return false;
                                }
                            }
                        },
                        message: data,
                    });

                    $("#btn-select-reference-search").click(function () {
                        var search_type = $('[name="search_type"]').val();
                        var search_val = $('[name="search_val"]').val();
                        $("#select-reference-table").queryAjaxPage({
                            "search_type": search_type,
                            "search_val": search_val
                        });
                    });

                    $('[name="search_val"]').keypress(function () {
                        if (event.keyCode == "13")
                            $("#btn-select-reference-search").click();
                    });
                }
            },
            error: function () {
                $.hideLoading();
                bootbox.alert("网络请求错误, 请稍后再试！");
            }
        });
    },
    // 引用商品搜索
    queryReferences: function (obj) {
        var o = {}; //动态的参数o
        var name = $(obj).attr("name");
        var value = $(obj).val();
        o[name] = value;
        $("#select-reference-table").queryAjaxPage(o);
    },

    // 确认引用商品
    userReference: function () {
        var listingId = "";
        $('[name="listing_id"]').each(function () {
            if ($(this).prop("checked") == true) {
                listingId = $(this).val();
            }
        });

        if (listingId == "") {
            bootbox.alert("请选择要引用的产品");
            return false;
        }

        $.showLoading();
        window.location.href = "/listing/lazada-listing/use-reference?listing_id=" + listingId;
    },
    addDecriptionPic: function (editorPic) {
        editor = editorPic;
        $('#lazada-add-decs-pic #divimgurl').empty();
        lazadaListing.addImageUrlInput();
        $('#lazada-add-decs-pic').modal('show');

    },
    showDecriptionPic: function () {
        var imgHtml = '';
        // 设置 对齐和宽度
        var localPicWidth = $("#localPicWidth").val();
        var localPicAlign = $("input[name='localPicAlign']:checked").val();
        var width = $.trim(localPicWidth) != "" ? ' width="' + localPicWidth + '"' : '';

        // align: center 不起效，所以这里希望统一改水平 排位
//			if(localPicAlign == 'center'){
//				var align = lazadaListing.dataSet.descImageAligncenter;
//			}else if(localPicAlign == 'right'){
//				var align = lazadaListing.dataSet.descImageAlignright
//			}else{
//				var align = lazadaListing.dataSet.descImageAlignleft;
//			}
        // 图片描述的图片不并排展示的话 ，这个居中就没问题
//			if(localPicAlign == 'center'){
//				var align = lazadaListing.dataSet.descImageAligncenter;
//			}else{
//				var align = $.trim(localPicAlign)!=""?' align="'+localPicAlign+'"':'';
//			}

        // kindeditor编辑器里面对图片右键修改图片 居中什么的 也是通过修改align属性，如果这里要修改，kindeditor 右键的修改图片也要修改对齐的逻辑。
        // 所以不建议独立fix align: center 不起效 问题，客户选align: center 后可以通过空格居中
        var align = $.trim(localPicAlign) != "" ? ' align="' + localPicAlign + '"' : '';
        $('#lazada-add-decs-pic #divimgurl>div>img').each(function () {
            var src = $(this).attr("src");
            if (src)
                imgHtml += '<img src="' + src + '"' + width + align + ' />';
        });
        if (editor)
            editor.insertHtml(imgHtml);

        $('#lazada-add-decs-pic').modal('hide');
    },
    localUpOneImg: function (obj) {
        var tmp = '';
        $('#img_tmp').unbind('change').on('change', function () {
            $.showLoading();
            $.uploadOne({
                fileElementId: 'img_tmp', // input 元素 id
                //当获取到服务器数据时，触发success回调函数 
                //data: 上传图片的原图和缩略图的amazon图片库链接{original:... , thumbnail:.. } 
                onUploadSuccess: function (data) {
                    $.hideLoading();
                    tmp = data.original;
                    $(obj).parent().children('input[type="text"]').val(tmp);
                    $(obj).parent().children('img').attr('src', tmp);
                },

                // 从服务器获取数据失败时，触发error回调函数。  
                onError: function (xhr, status, e) {
                    $.hideLoading();
                    alert(e);
                }
            });
        });
        $('#img_tmp').click();
    },
    addImageUrlInput: function (src) {
        if (typeof (src) == 'undefined') {
            src = '';
        }
        $('#divimgurl')
            .append(
                "<div><img src='"
                + src
                + "' width='50' height='50'> <input type='text' id='imgurl"
                + (Math.random() * 10000).toString()
                    .substring(0, 4)
                + "' name='imgurl[]' size='80' style='width: 300px;' onblur='javascript:lazadaListing.blurImageUrlInput(this)' value="
                + src
                + "> <input type='button' value='删除' onclick='javascript:lazadaListing.delImageUrlInput(this)'> <input type='button' value='本地上传' onclick='javascript:lazadaListing.localUpOneImg(this)' ></div>");
    },
    delImageUrlInput: function (imgdiv) {
        $(imgdiv).parent().empty();
    },
    blurImageUrlInput: function (obj) {
        var t = $(obj).val();
        $(obj).parent().children('img').attr('src', t);
    },
    
    //判断obj是不是空的 空的返回true,不空返回false;
    isEmptyObject : function(e){
    	var t;
    	for(t in e)
    		return !1;
    	return !0;
    },
    
    confirmUploaded: function (id) {
    	bootbox.confirm({  
	        title : Translator.t('确认产品已发布'),
			message : Translator.t('此操作之后，系统将把该产品设置为已发布的产品，当产品再发布的时候会直接覆盖已在线的产品数据，您确定操作?'),  
	        callback : function(r) {  
	        	if (r){
					$.ajax({
						type:'post',
						url: "/listing/lazada-listing/confirm-uploaded-product",
						data: {id:id},
						timeout:60000,
						dataType:'json',
						success:function(data){
							$.hideLoading();
							bootbox.alert(data.message);
						},
						error:function(){
							$.hideLoading();
							bootbox.alert("网络请求错误, 请稍后再试！");
						}
					});
				}
	        },  
        });
    },
}