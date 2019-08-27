/**
 * [自动补货专用js]
 * @author willage 2017-03-14T17:25:25+0800
 * @editor willage  2017-03-14T17:25:25+0800
 * @return {[type]} [description]
 */
if (typeof autoInventory === 'undefined')  autoInventory = new Object();

autoInventory = {
    init : function(){//初始化

    },

    created:function(){//新增
        window.open(global.baseUrl +'listing/ebay-auto-inventory/create');
    },
    created_setting:function(mkey){//新增
        // 打开窗口命令
        // var param=$('input[name="'+mkey+'"]');
        // alert(mkey);
        var handle= $.openModal("/listing/ebay-auto-inventory/create",{item_id:mkey},'设置自动补货','post');
        handle.done(function($window){
        });
    },
    created_save:function(){//当前页面修改保存
        var varId = [];
        var itemSel='';
        var sellerSel='';
        var invS=[];
        var chkBoxes = $('#cSave_body').find('input:checked');
        if (chkBoxes.length == 0) {
            $.alertBox('未勾选');
            return false;
        }
        $(chkBoxes).each(function() {//根据勾选checkbox找到variation和inventory
            varId.push($('p[name="vari-spec-'+$(this).attr('value')+'"]').text());
            invS.push($('input[name='+$(this).attr('value')+']').prop("value"));
        });

        sellerSel=$('p[name="seller_id"]').text();
        itemSel=$('a[name="item_id"]').text();
        // var parent_id = $(obj).parents("tr").data("id");
        // $('input[parentid="' + parent_id + '"]').each(function (i) {
        //     selected.push($(this).parents("tr").data("productid"));
        // });
        var params={
            seller_id:sellerSel,
            item_id:itemSel,
            varisation:varId,
            inventory:invS
        };
        $.ajax({//与下面的保存一样
            type: 'POST',
            url: '/listing/ebay-auto-inventory/create',
            data:params,
            dataType: 'json',
            success: function (data) {
                if (data.code == 200) {
                    $.alertBox(data.message);
                    // winobj.close();
                    window.location.href=global.baseUrl +'listing/ebay-auto-inventory/create';
                    // var Url=global.baseUrl +'order/od-lt-message/custom_product_list';
                    // $.location.state(Url,'小老板',$("#customListSearch").serialize(),0,'post',false);
                } else {
                    $.alertBox(data.message);
                }

            },
            // error: function () {
            //     // $.alertBox("网络错误！");
            //      alert(XMLHttpRequest.readyState + XMLHttpRequest.status + XMLHttpRequest.responseText);  
            // }
            error: function(jqXHR, textStatus, errorThrown) {
                alert(textStatus);
                alert(errorThrown);
            },
        });
    },
    oneUpdate:function(id){//单个修改
        var handle= $.openModal("/listing/ebay-auto-inventory/update",{id:id},'设置自动补货','post');
        handle.done(function($window){
        });
        // $.ajax({//与下面的保存一样
        //     type: 'POST',
        //     url: '/tracking/tracking-recommend-product/save-product',
        //     data:$('#custom_product').serialize(),
        //     dataType: 'json',
        //     success: function (data) {
        //         if (data.code == 200) {
        //             $.alertBox(data.message);
        //             winobj.close();
        //             //location.reload();
        //             var Url=global.baseUrl +'order/od-lt-message/custom_product_list';
        //             $.location.state(Url,'小老板',$("#customListSearch").serialize(),0,'post',false);
        //         } else {
        //             $.alertBox(data.message);
        //         }

        //     },
        //     error: function () {
        //         $.alertBox("网络错误！");
        //     }
        // });
    },
    batchUpdate:function(){//批量修改
        // 打开窗口命令
        var handle= $.openModal("/listing/ebay-auto-inventory/update",{},'自定义商品','post');
        handle.done(function($window){
        });
    },
    oneDelete:function(invId){//单个删除
        var event=$.confirmBox("确定删除？");
        event.then(function(){
        $.ajax({//与下面的保存一样
            type: 'POST',
            url: '/listing/ebay-auto-inventory/delete',
            data:{id:invId},
            dataType: 'json',
            success: function (data) {
                if (data.code == 200) {
                    $.alertBox(data.message);
                    window.location.href=global.baseUrl +'listing/ebay-auto-inventory/index';
                } else {
                    $.alertBox(data.message);
                }

            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert(textStatus);
                alert(errorThrown);
            },
        });

        })

    },
    batchDelete:function(val){//批量删除
    },

    switchStatus:function(obj){
        if ($(obj).attr("value")==1) {
            var sts=0;
        }else if($(obj).attr("value")==0){
            var sts=1;
        }else{
            $.alertBox('不能操作');
            return;
        }

        var params={
            id:$(obj).attr("name"),
            status:sts,
        };
        // $.alert($(obj).attr("value"));
        // $.alert(params.status);
        // return;
        $.ajax({//与下面的保存一样
            type: 'POST',
            url: '/listing/ebay-auto-inventory/update',
            data:params,
            dataType: 'json',
            success: function (data) {
                if (data.code == 200) {
                    if (sts==0) {
                        $(obj).html('<button type="button" class="btn btn-default" style="width:40px">&nbsp;&nbsp;</button>'+
                        '<button type="button" class="btn btn-info" style="width:40px">暂停</button>');
                        $(obj).attr("value",sts);
                        // $(obj).attr("name","iv-btn btn-info");
                    }else if(sts==1){
                        // $(obj).html('暂停');
                        $(obj).html('<button type="button" class="btn btn-success" style="width:40px">开启</button>'+
                            '<button type="button" class="btn btn-default" style="width:40px">&nbsp;&nbsp;</button>');
                        $(obj).attr("value",sts);
                    }
                    $.alertBox(data.message);
                } else {
                    $.alertBox(data.message);
                }

            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert(textStatus);
                alert(errorThrown);
            },
        });
        // $(obj).html('kaiqi');
        // // $(obj).removeClass("btn-success")
        // // $(obj).addClass("btn-info");
        // $(obj).attr("class","iv-btn btn-info");
        // $.alertBox($(obj).text());
    },
    swiradio:function(){
        if ($("#tab0").prop("checked")) {
            $("#div0").show();
            $("#div1").hide();
        }
        else if ($("#tab1").prop("checked")) {
            $("#div1").show();
            $("#div0").hide();
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

    selectAll:function(){
        if($("#cSave_all").attr("ck")=="2"){
            $(".cSave_ck").prop("checked","checked");
            $("#cSave_all").attr("ck","1");
        }else{
            $(".cSave_ck").prop("checked",false);
            $("#cSave_all").attr("ck","2");
        }
    },
    selectlvOne:function(obj){
        var checkedCnt=0;
        var emptyCnt=0;
        var val_t="";
        var cls=$(obj).prop('class');
        $('input[class="'+cls+'"]').each(function (i) {//统计同级checkbox
            if ($(this).prop('checked')) {
                checkedCnt++;
            }else{
                emptyCnt++;
            }
        });

        val='var'+$(obj).prop('value');
        if ($(obj).prop('checked')) {
            if (cls=="create_ck") {
                $('input[name="'+val+'"]').each(function (i) {//遍历子checkbox
                    $(this).prop("checked", true);
                });
            }
            if (!emptyCnt) {//全选则勾选上级
                $('input[id="'+cls+'0"]').prop("checked", true);
            }
        } else {
            if (cls=="create_ck") {
                $('input[name="'+val+'"]').each(function (i) {
                    $(this).prop("checked", false);
                });
            }
            //全没选则去勾选上级
            $('input[id="'+cls+'0"]').prop("checked", false);
        }

    },

    selectlvTwo:function(obj){
        var checkedCnt=0;
        var emptyCnt=0;
        var feature="";

        feature=$(obj).prop('name');//同级特征码

        $('input[name="'+feature+'"]').each(function (i) {//统计同级checkbox
            if ($(this).prop('checked')) {
                checkedCnt++;
            }else{
                emptyCnt++;
            }
        });

        feature=feature.substring(3);//上级特征码
        feature='itemid'+feature;

        if ($(obj).prop('checked')) {
            $('input[name="'+feature+'"]').prop("checked", true);
        } else {
            if (!checkedCnt) {//全没选则去勾选上级
                $('input[name="'+feature+'"]').prop("checked", false);
            }
        }
    }

}


// function dobatch(val){
//     var keys = $("#grid").yiiGridView("getSelectedRows");
//     if (keys.length===0) {
//         bootbox.alert("请选择要操作的订单gdfd");
//         alert("请选择要操作的订单gdfd");
//     }
//     switch(val){
//         case 'update':
//         break;
//         case 'delete':
//         if(confirm('您确定要删除吗12ew？')){
//             $.ajax({
//                 url: '/listing/ebayautoinventory/batchdelete',
//                 data: {ids:keys},
//                 type: 'post',
//                 success: function (t) {
//                     t = JSON.parse(t);
//                     if (t.status == 1) {
//                         window.location.href= window.location.href;
//                     }
//                 },
//                 error: function () {
//                     // alert("删除失败！");
//                     bootbox.alert("删除失败！");
//                 }
//             })
//         }
//         break;
//         default:
//         break;
//     }
    
//     // $(".gridviewdelete").on("click", function () {

//     // });
// }

// function dobatch(val){
//     var keys = $("#grid").yiiGridView("getSelectedRows");
//     if (keys.length===0) {
//         bootbox.alert("请选择要操作的订单gdfd");
//     }else{
//         switch(val){
//             case 'update':
//                 function () {
//                     $.get(
//                         '{$requestUpdateUrl}',
//                         {
//                             id: $(this).closest('tr').data('key')
//                         },
//                         function (data) {
//                             $('.modal-body').html(data);
//                         }
//                     );
//                 };
//             break;
//             case 'delete':
//                 var event1 = $.confirmBox('您确定要删除吗?');
//                 event1.then(
//                     function(){
//                         $.ajax({
//                             url: '/listing/ebayautoinventory/delete-batch',
//                             data: {ids:keys},
//                             type: 'post',
//                             success: function (t) {
//                                 t = JSON.parse(t);
//                                 if (t.status == 1) {
//                                     window.location.href= window.location.href;
//                                 }
//                             },
//                             error: function () {
//                                 // alert("删除失败！");
//                                 bootbox.alert("删除失败！");
//                             }
//                         })
//                     },
//                     function(){}
//                 );
//             break;
//             default:
//             break;
//         }
//     }



    
    // $(".gridviewdelete").on("click", function () {

    // });
// }

