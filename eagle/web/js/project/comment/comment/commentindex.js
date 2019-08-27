// 自动好评
$.fn.extend({
    _selfajax: function (url, data, success_msg, selfcallbackfunc, redirectUrl) {
        if (success_msg != undefined) success_msg = ',' + success_msg;
        else success_msg = '';
        $.ajax({
            url: url,
            type: 'post',
            data: data,
        }).done(selfcallbackfunc ? selfcallbackfunc : function (result) {
            //        	result = $.parseJSON(result);
            var re = result.error ? '失败,原因:' + result.msg : '成功' + success_msg;
            $.alert('操作结果：' + re, result.error ? 'danger' : 'success').then(function () {
                if (!result.error) redirectUrl != null || redirectUrl != undefined ? location.href = redirectUrl : location.reload();
            });
        }).fail(function () {
            $.alert('网络错误，请重试', 'danger')
        });
    }
});
/* ========================================未评价订单======================================== */

$.domReady(function ($el) {
    //添加好评模板
    $el("#comment-template-add-form").on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        $form._selfajax('/comment/comment/doaddtemplate-v2', $form.serialize());
    })

})


$('#listcommentsubmit').on('click', function () {
    //获取已选中的id
    var str = getcheckedids();
    if (!str) {
        $.alert('请先选择订单!', 'danger');
        return false;
    }
    $('#comment-add-textarea').val(str);
    $('#myModalLabel').text('批量评价');
    return true;
})
$('#listcommentsubmitV2').on('click', function () {
    //获取已选中的id
    var str = getcheckedids();
    if (!str) {
        $.alert('请先选择订单!', 'danger');
        return false;
    }
    editcomment(str, '批量评价');
    return true;
})

function editcomment(orderstr, title) {
    var handle = $.openModal('/comment/comment/edit-comment', {orderSourceOrderId: orderstr}, title, 'post');
    handle.done(function ($window) {
        // 窗口载入完毕事件
        //radio变动时出发事件
        $window.find("input:radio[name='content']").on('click', function () {
            var $selected = $(this).val();
            if ($selected == "customized") {
                $window.find("#edit-comment-customized-content").show();
                $window.find("#edit-comment-comment-template").hide();
                $window.find("#edit-comment-comment-blank").hide();
            } else if ($selected == "template") {
                $window.find("#edit-comment-customized-content").hide();
                $window.find("#edit-comment-comment-template").show();
                $window.find("#edit-comment-comment-blank").hide();
            } else if ($selected == "uncomment") {
                $window.find("#edit-comment-customized-content").hide();
                $window.find("#edit-comment-comment-template").hide();
                $window.find("#edit-comment-comment-blank").show();
            }
        });
        $window.find("#commentsubmit").on('click', function () {
            // $window.close();
            var $orderSourceOrderId = $window.find("#order-source-order-id").val();
            var $orderSourceOrderIds = $orderSourceOrderId.split(',');
            for (var i = 0; i < $orderSourceOrderIds.length; i++) {
                $("#tr-comment-" + $orderSourceOrderIds[i]).remove();
            }
            // 关闭当前模态框
            var radioVal = $window.find("input:radio[name='content']:checked").val();
            var score = $window.find("#select-score ").val();
            var content = "";
            var customizedContent=$window.find("#textarea-customized-content").val();
            var defaultCustomizedContentId=$window.find("#default-customized-content-id").val();
            if (radioVal == "customized") {
                content = customizedContent;
            } else if (radioVal == "template") {
                content = $window.find("input:radio[name='template']").val();
            }
            $window.close();
            $window._selfajax('/comment/comment/addcomment-v2', {
                "content": content,
                "score": score,
                "orderSourceOrderIds": $orderSourceOrderIds,
                "defaultCustomizedContentId":defaultCustomizedContentId,
                "customizedContent":customizedContent
            }, " ", function (result) {
                //        	result = $.parseJSON(result);
                var re = result.error ? '失败,原因:' + result.msg : '成功';
                $.alert('操作结果：' + re, result.error ? 'danger' : 'success');
            });
        })
        $window.find("#close").on('click', function () {
            $window.close();       // 关闭当前模态框
        })
    });
}

//获取已选择id
function getcheckedids(isid) {
    var checks = $('.order_checkbox:checked'),
        ids = [],
        str = '',
        lbid = '';
    if (checks.length == 0) return false;
    //处理id
    checks.each(function (index, ele) {
        if (isid) lbid = $(ele).attr('littlebossid');
        else lbid = $(ele).val();
        ids.push(lbid);
    });
    //拼接成字符串
    str = ids.join(',');
    //返回ids
    return str;
}
//好评按钮设置订单值
function commentsubmit(id) {
    $('#comment-add-textarea').val(id);
    $('#myModalLabel').text('评价');
    return true;
}
//
$("input[name=content]").click(function () {
        var val = $(this).val();
        if (val == "content")
            $('#index-v2-comment-content').html('<textarea style="margin-left: 71px;margin-top: 24px;width: 265px" rows="10"></textarea>');
        else if (val == "template")
            $('#index-v2-comment-content').html("template");
        else
            $('#index-v2-comment-content').html("");
    }
)


//全选
$('#comment-checkall').click(function () {
    $('.order_checkbox').prop('checked', $(this).prop('checked'));
})

//发送好评
$('#submit-ok').click(function () {
    //收集数据
    var $form = $(this).closest('.modal-content').find('form'),
        data = $form.serialize();
    //通过ajax提交
    $form._selfajax('/comment/comment/addcomment', data, '好评已发送,稍后可查看');
})

//不予处理 - 单个
function commentignore(source_id) {
    $.alert('确定忽略不处理该订单吗？', 'warn').then(function () {
        if (source_id != '') sendIgnore(source_id);
    });
}
//不予处理 - 批量
$('#listignore-button').click(function () {
    //获取已选中的id
    var str = getcheckedids(1);
    if (!str) {
        $.alert('请先选择订单!', 'primary');
        return false;
    }
    commentignore(str);
})

//发送不予处理的数据
function sendIgnore(ids) {
    if (ids == '') return false;
    $(this)._selfajax('/comment/comment/ignoreorder', {
        source_id: ids
    }, '订单已忽略不作处理');
}

/* ========================================模板页======================================== */
//获取模版页modal
function template_request_content(id) {
    $.fn._selfajax('/comment/comment/addtemplate', {
        template_id: id
    }, null, function (result) {
        $('#template_modal_content').html(result);
    })
}

//删除模版
function comment_template_deleteV2(id) {
    $.alert('您确定要删除该模版吗？', 'warn')
        .then(function () {
            $(this)._selfajax('/comment/comment/deletetemplate-v2', {
                id: id
            }, null, function () {
                location.reload(true);
            });
        })
}

/* ========================================好评记录======================================== */
function comment_log_recomment(id) {
    if (id == '') return false;
    $.alert('确认执行该订单的再次好评吗？', 'warn')
        .then(function () {
            $(this)._selfajax('/comment/comment/recomment', {
                ids: id
            }, '系统正在处理该订单的好评');
        })
}
$('#comment-log-recomment').click(function () {
    $.alert('确认执行该订单的再次好评吗？', 'warn')
        .then(function () {
            //获取以选择的id
            var selected = $('.order_checkbox:checked'),
                ids = [];
            selected.each(function (index, ele) {
                ids.push($(ele).val());
            })
            $(this)._selfajax('/comment/comment/recomment', {
                ids: ids.join(',')
            }, '系统正在处理该订单的好评');
        })
})


//验证内容合法性
function checkValueAccess(c) {
    if (c.length == 0) return '该内容不能为空';
    //验证是否包含中文
    var reg = new RegExp("[\\u4E00-\\u9FFF]+", "g");
    if (reg.test(c)) {
        return '不能输入汉字！';
    }
    //检查中文标点
    var biaodian = ['！￥…（）—｝｛“：【】‘；？》《，。、·'];
    for (var i = 0; i < biaodian[0].length; i++) {
        if (c.indexOf(biaodian[0][i]) != -1) {
            return '不能含有中文标点！';
        }
    }
    return true;
}

//当输入好评内容时进行验证
$('#addrule-content').on('blur', function () {
    var content = $('#addrule-content').val(),
        addRuleSubmitButton = $('#addrule-form input:submit'),
        result = checkValueAccess(content);

    if (result !== true) {
        addRuleSubmitButton.attr('disabled', true);
        $('#add-rule-put-result').html(result).show();
    } else {
        addRuleSubmitButton.attr('disabled', false);
        $('#add-rule-put-result').hide();
    }
})

var xlb_template_cn =
        // ['请选择', '感谢您的光临，希望以后继续支持我们，我们会继续为您提供更好的商品.','很高兴您已经顺利收到货物，期待您以后多多支持哦~', '自定义..']
        ['请选择', '感谢您的光临，希望以后继续支持我们，我们会继续为您提供更好的商品.', '很高兴您已经顺利收到货物，期待您以后多多支持哦~']
    ;
var xlb_template = {
    'en': ['请选择', 'Thanks for your visit, we sincerely hope acquire your lasting support and will provide better commodities for you.', 'We are pleased to know that you have received the goods smoothly and looking forward your lasting supports.', '自定义..'],
    // 'de': ['请选择', 'Danke für Ihre Bestellung, Auch freuen wir uns auf Ihre nächte Bestellungen. Wir werden Ihnen geradeaus qualitätive Angebote und besten Service anbieten.','Es freut uns, dass Sie schon Ihre Produkte erhalten haben, Auch freuen wir uns auf Ihre nächte Bestellungen.', '自定义..'],
    // 'fr': ['请选择', 'Merci de votre venu. Espérons que vous pourriez nous donner votre soutien à l\'avenir. Nous allons vous fournir des produit de bonne qualité et d\'un prix raisonnable.','Nous sommes heureux d\'entendre que vous avez bien reçu le colis.  Nous tenons à vous remercier pour votre soutien.', '自定义..'],
};

$(".lb-select").on('change', function () {
    // var $lan = $('#comment_language'),
    // 	selectValue = $lan.val(),
    selectValue = 'en';
    $this = $(this),
        $textarea = $('#addrule-content');
    // if ($this.val() == 0) $textarea.val('').attr('readonly',true);
    if ($this.val() == 0) $textarea.val('');
    else if (xlb_template[selectValue][$this.val()] == '自定义..') {
        $textarea.val('').attr({
            placeholder: '请输入好评内容',
            readonly: false
        });
    } else {
        $textarea.val(xlb_template[selectValue][$this.val()]).attr('readonly', false).blur();
    }
});

$('#addrule-form').on('submit', function () {
    if ($(this).attr("version") != undefined) {
        var version = $(this).attr("version");
        if (version == "V2") {
            $(this)._selfajax('/comment/comment/doaddrule-v2', $(this).serialize(), null, null, 'rule-v2');
        }
    } else {
        $(this)._selfajax('/comment/comment/doaddrule', $(this).serialize(), null, null, 'rule');
    }
    return false;
})

//删除规则
function deleteRule(id) {
    $.alert('您确定要删除该规则吗？', 'warn')
        .then(function () {
            $(this)._selfajax('/comment/comment/dodeleterule', {
                id: id
            }, null, function () {
                location.reload(true);
            });
        })
}

function deleteRuleV2(id) {
    $.alert('您确定要删除该规则吗？', 'warn')
        .then(function () {
            $(this)._selfajax('/comment/comment/dodeleterule-v2', {
                id: id
            }, null, function () {
                location.reload(true);
            });
        })
}


$(function () {
    //初始化
    $(".lb-select").lbSelectRender(xlb_template_cn);
    $('.select-viewer').html(xlb_template_cn[0]);

    // introJs().start();

})