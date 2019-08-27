<?php 
// 发布新商品
$data = $v3->getProduct($v3->request());
$product = $v3->callApi('createVariant',[],$data);
return $product;