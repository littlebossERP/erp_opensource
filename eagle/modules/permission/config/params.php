<?php 
// 注意！！！
// 1.没有在这里配置的页面，默认认为是可以任意访问
// 定义像 “/catalog” 这样，则没有产品权限的，不能访问catalog的module里面的所有controller，其他没有定义在这里的页面则可访问
// 同样道理 如果定义像 “/catalog/product”，没有产品权限的不能访问/catalog/product controller里面的所有 action，其他没有定义在这controller里的页面则可访问
// 
// 2.配置 模块的key时，请查看 UserHelper::$modulesKeyNameMap 里面对应的模块的key

return [
	"catalog"=>array("/catalog/product","/catalog/brand/index"),

];