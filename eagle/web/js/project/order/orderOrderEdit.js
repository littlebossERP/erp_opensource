$(".ystep1").loadStep({
    //ystep的外观大小
    //可选值：small,large
    size: "large",
    //ystep配色方案
    //可选值：green,blue
    color: "green",
    //ystep中包含的步骤
    steps: [{
      //步骤名称
      title: "未付款",
      //步骤内容(鼠标移动到本步骤节点时，会提示该内容)
      content: "小老板系统内是未付款状态"
    },{
      title: "已付款",
      content: "小老板系统内是已付款状态"
    },{
      title: "发货中",
      content: "小老板系统内是发货中状态"
    },{
      title: "已发货",
      content: "小老板系统内是已发货状态"
    }]
  });

var sta;
if($('#statushide').val()=='100'){
	sta=1;
}else if($('#statushide').val()=='200'){
	sta=2;
}else if($('#statushide').val()=='300'){
	sta=3;
}else if($('#statushide').val()=='500'){
	sta=4;
}else{
	sta=1;
}
$(".ystep1").setStep(sta);