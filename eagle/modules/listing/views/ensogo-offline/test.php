<?php 
	$str = '<table class="table table-bordered">';
        $str .= "<tr>";
        foreach($list['filed_array'] as $kt => $kv){
            $str .= "<th style='text-align:center;'>".$kv."</th>";
        }
        $str .= "</tr>";
        foreach($list['data_array'] as $value){
            $value['parent_id'] = isset($value['parent_id'])?$value['parent_id']:'0';
            $str .= "<tr>";
            $str .= "<td style='text-align:center;'>".$value['name_zh_tw']."</td>"; 
            $str .= "<td style='text-align:center;'>".$value['name']."</td>"; 
            $str .= "<td style='text-align:center;'>".$value['id']."</td>"; 
            $str .= "</tr>";
        }
        $str .="</tr>";
        $str .="</table>";
?>
<div><?=$str?></div>