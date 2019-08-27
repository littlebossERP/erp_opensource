<?php 

use yii\helpers\Html;
?>

<table>
<tr>
	<?php foreach($cnames as $key => $k): ?>
      <td>
      	<?=Html::dropDownList('newfitments['.$k.']','',isset($cvalues[$k])?$cvalues[$k]:[],['onchange'=>'selectNextCompatibilitySearchValues(this,"'.$k.'")','sequence'=>$key,'prompt'=>$k,'id'=>'newfitments['.$k.']'])?>
      </td>
	<?php endforeach;?>
	<td style="padding-top:7px;"><input type="button" name="add" value="添加" onclick='addoccurrence()' class="btn btn-primary"></td>
</tr>	
</table>
<script language='javascript'>
  	//把具体值添加到<input>
  	function addoccurrence()
  	{
		//判断用户选择的值是否完全，需要5个<select>值
		//动态添加到<input>
		//具备删除功能,批量删除
		var f_arr = new Array();
		var year = $("#newfitments\\[Year\\]").val();f_arr['year'] = year;
		var make = $("#newfitments\\[Make\\]").val();f_arr['make'] = make;
		var model = $("#newfitments\\[Model\\]").val();f_arr['model'] = model;
		var trim = $("#newfitments\\[Trim\\]").val();f_arr['trim'] = trim;
		var engine = $("#newfitments\\[Engine\\]").val();f_arr['engine'] = engine;
		var submodel = $("#newfitments\\[Submodel\\]").val();f_arr['submodel'] = submodel;
		var platform = $("#newfitments\\[Platform\\]").val();f_arr['platform'] = platform;
		var type = $("#newfitments\\[Type\\]").val();f_arr['type'] = type;
		var productionperiod = $("#newfitments\\[ProductionPeriod\\]").val();f_arr['productionperiod'] = productionperiod;
		var variant = $("#newfitments\\[Variant\\]").val();f_arr['variant'] = variant;
		var carmake = $("#newfitments\\[CarMake\\]").val();f_arr['carmake'] = carmake;
		var carstype = $("#newfitments\\[CarsType\\]").val();f_arr['carstype'] = carstype;
		var carsyear = $("#newfitments\\[CarsYear\\]").val();f_arr['carsyear'] = carsyear;
		var bodystyle = $("#newfitments\\[BodyStyle\\]").val();f_arr['bodystyle'] = bodystyle;
		
		if(year == "" || make == "" || model == "")
		{
			bootbox.alert("还有其他选项您没有选择");return false;
		}

		var PropertyNames= eval( <?=json_encode(array_values($cnames))?> );

		var _str='';
		_str+='<tr><td><input type="checkbox" name="occurrence"><a href="javascript:void(0)" title="删除" onclick=deloccurrence(this,"one")><span class="glyphicon glyphicon-remove"></span></a></td>';
		for(pn in PropertyNames){
			var _name = PropertyNames[pn];
			_name = _name.toLocaleLowerCase();
			_str+='<td><input readonly="readonly" size="20" name = "itemcompatibilitylist['+_name+'][]" value="'+f_arr[_name]+'"></td>';
		}
		_str+='</tr>';
		$("#add").append(_str);
		//用于增强用户体验而添加的界面
// 		if(count==1005){
// 			$("#add").append('<tr><td><input type="checkbox" name="occurrence"><a href="javascript:void(0)" title="删除" onclick=deloccurrence(this,"one")><img src="/link/img/action/trash.png"></a></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[make][]" value="'+make+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[model][]" value="'+model+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[year][]" value="'+year+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[trim][]" value="'+trim+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[engine][]" value="'+engine+'"></td></tr>');
// 		}else if(count==1004){
// 			$("#add").append('<tr><td><input type="checkbox" name="occurrence"><a href="javascript:void(0)" title="删除" onclick=deloccurrence(this,"one")><img src="/link/img/action/trash.png"></a></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[make][]" value="'+make+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[model][]" value="'+model+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[year][]" value="'+year+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[submodel][]" value="'+submodel+'"></td></tr>');
// 		}else if(count==776){
// 			$("#add").append('<tr><td><input type="checkbox" name="occurrence"><a href="javascript:void(0)" title="删除" onclick=deloccurrence(this,"one")><img src="/link/img/action/trash.png"></a></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[make][]" value="'+make+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[model][]" value="'+model+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[platform][]" value="'+platform+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[type][]" value="'+type+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[engine][]" value="'+engine+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[productionperiod][]" value="'+productionperiod+'"></td></tr>');
// 		}else if(count==156){
// 			$("#add").append('<tr><td><input type="checkbox" name="occurrence"><a href="javascript:void(0)" title="删除" onclick=deloccurrence(this,"one")><img src="/link/img/action/trash.png"></a></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[make][]" value="'+make+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[model][]" value="'+model+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[year][]" value="'+year+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[submodel][]" value="'+submodel+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[variant][]" value="'+variant+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[engine][]" value="'+engine+'"></td></tr>');
// 		}else if(count==37){
// 			$("#add").append('<tr><td><input type="checkbox" name="occurrence"><a href="javascript:void(0)" title="删除" onclick=deloccurrence(this,"one")><img src="/link/img/action/trash.png"></a></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[carmake][]" value="'+carmake+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[model][]" value="'+model+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[variant][]" value="'+variant+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[bodystyle][]" value="'+bodystyle+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[carstype][]" value="'+carstype+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[carsyear][]" value="'+carsyear+'"></td><td><input readonly="readonly" size="10" name = "itemcompatibilitylist[engine][]" value="'+engine+'"></td></tr>');
// 		}
		//用于提交后，controller获取值
		/*$('<input>', {type: 'text', name: 'itemcompatibilitylist[year][]', val: year}).appendTo("#add");
		$('<input>', {type: 'text', name: 'itemcompatibilitylist[make][]', val: make}).appendTo("#add");
		$('<input>', {type: 'text', name: 'itemcompatibilitylist[model][]', val: model}).appendTo("#add");
		$('<input>', {type: 'text', name: 'itemcompatibilitylist[trim][]', val: trim}).appendTo("#add");
		$('<input>', {type: 'text', name: 'itemcompatibilitylist[engine][]', val: engine}).appendTo("#add");*/
	}

      function selectNextCompatibilitySearchValues(e,PropertyName){
		var PropertyNames= eval( <?=json_encode(array_values($cnames))?> );

		//{0:'year', 1:'make', 2:'Model' ... ...}
		var propertyFilter='';
		for(pn in PropertyNames){
			propertyFilter+='&propertyFilter['+PropertyNames[pn]+']='+$('#newfitments\\['+ PropertyNames[pn]+'\\]').val();
			if(PropertyNames[pn]==PropertyName){
				pn_next=parseInt(pn)+1;
				break;
			}
		}
		
		if(PropertyNames[pn_next]){
			PropertyName_next=PropertyNames[pn_next];
		}else{
			 return ;
		}
		
		str= '&categoryid='+$('#primarycategory').val()+
			 '&siteid='+$('select[name=site]').val()+
			 '&PropertyName='+PropertyName+
			 '&value='+$(e).val()+
			 '&PropertyName_next='+PropertyName_next+
			 '&propertyFilter='+propertyFilter;
		$.post(global.baseUrl+"listing/ebaycompatibility/ajaxgetcompatibilitysearchvalues",str,function(r){
				var d=$.parseJSON(r);
				$('#newfitments\\['+PropertyName_next+'\\]').html('');
				$('#newfitments\\['+PropertyName_next+'\\]').append('<option value="">select '+PropertyName_next+'</option>');
		
				//var propertyNamearr=new Array('Make','Model','Year','Trim','Engine');
				var propertyNamearr=new Array(PropertyNames);
				var i = $(e).attr('sequence');
				var i=parseInt(i)
				for(i+=1;i<=4;i++){
					$('#newfitments\\['+propertyNamearr[i]+'\\]').html('<option value="">select '+propertyNamearr[i]+'</option>');
				}
				for(var pi in d ){
					for(var pi2 in d[pi])
					{
						$("#newfitments\\["+PropertyName_next+"\\]").append('<option value="'+pi2+'">'+pi2+'</option>');
					}
				}
			});
		}
</script>


