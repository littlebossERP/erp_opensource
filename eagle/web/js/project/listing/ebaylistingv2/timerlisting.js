/**
 * [定时刊登专用js]
 * @author willage 2017-04-07T13:38:23+0800
 * @update willage  2017-04-07T13:38:23+0800
 */
if (typeof autoTimerListing === 'undefined')  autoTimerListing = new Object();

autoTimerListing = {
    init : function(){//初始化

    },

    create_setting:function(mkey){//新增设置
        var handle= $.openModal("/listing/ebay-auto-timer-listing/create",{draft_id:mkey,type:'setting'},'设置定时刊登','post');
        handle.done(function($window){
        });
    },
    save:function(){// 新增/修改 保存
        // $.loading(true);
        $.showLoading();
        $.ajax({
            type: 'POST',
            url: $("form").attr("action"),
            data: $("form").serialize(),
            dataType: 'json',
            //or your custom data either as object {foo: "bar", ...} or foo=bar&...
            success: function(data) {
                $.hideLoading();
                if (data.code == 200) {
                    $.alertBox(data.message);
                    location.reload();
                } else if (data.code == 201) {
                    $.alertBox(data.message);
                } else if (data.code == 202) {
                    $event = $.alert('[check result]<br>'+data.message,'danger');
                    $event.then(function(){
                        // $('#address_name').focus();
                    });

                    // $.alertBox(data.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $.hideLoading();
                alert(textStatus);
                alert(errorThrown);
            },
        });
        // $.ajax({//与下面的保存一样
        //     type: 'POST',
        //     url: '/listing/ebay-auto-inventory/create',
        //     data:params,
        //     dataType: 'json',
        //     success: function (data) {
        //         if (data.code == 200) {
        //             $.alertBox(data.message);
        //             window.location.href=global.baseUrl +'listing/ebay-auto-inventory/create';
        //         } else {
        //             $.alertBox(data.message);
        //         }

        //     },

        //     error: function(jqXHR, textStatus, errorThrown) {
        //         alert(textStatus);
        //         alert(errorThrown);
        //     },
        // });
    },
    update_one:function(id){//单个修改
        var handle= $.openModal("/listing/ebay-auto-timer-listing/update",{id:id,type:'setting'},'设置定时刊登','post');
        handle.done(function($window){
        });
    },

    delete_one:function(invId){//单个删除
        var event=$.confirmBox("确定删除？");
        event.then(function(){
        $.ajax({//与下面的保存一样
            type: 'POST',
            url: '/listing/ebay-auto-timer-listing/delete',
            data:{id:invId},
            dataType: 'json',
            success: function (data) {
                if (data.code == 200) {
                    $.alertBox(data.message);
                    window.location.href=global.baseUrl +'listing/ebay-auto-timer-listing/index';
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
    selectOne:function(obj){
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

