#!/bin/bash

pid_nums1=`ps aux | grep "/bin/bash.*onePerDay_v2.sh" | grep -v grep | wc -l`
if [ $pid_nums1 -gt "2" ]
then
	echo " I exit"
	exit
fi

function checkprocess() {
	nowdate=`date +%y%m%d%H`
	#当前时间戳
	nowtimestamp=`date +%s`
	pid_nums=`ps aux | grep "$*" | grep -v grep | wc -l`
	if [ $pid_nums -lt "1" ]
        then
                echo " $pid_nums  begin $*"
                if [ "${#3}" -gt "0" ]
                then
                   action=${2/\//-}
                   param=${3}
                   php -c /etc/php5/cli/php.ini $@ >> "log/${action:0:124}${param:0:55}_${nowdate}.log" 2>&1 &
                elif [ "${#2}" -gt "0" ]
                then
                    action=${2/\//-}
                    echo "log/${action:0:124}_${nowdate}.log" 
                    php -c /etc/php5/cli/php.ini $@ >> "log/${action:0:124}_${nowdate}.log" 2>&1 &
                fi
        else
		for pid in `ps aux | grep "$*" | grep -v grep|awk '{print $2}'`; do
			echo "${pid}";
			rundate=`ps -p "${pid}" -o lstart|grep ':'`;
			timestamp2=`date +%s -d"$rundate"` ;
			#如果进程已经跑了10个小时，强制退出
			if [  `expr $timestamp2 + 36000 ` -lt $nowtimestamp ]
			then
				echo "kill ${pid}"
				kill -9 "${pid}";
			else
				echo "run ${pid}"
			fi
		done
		echo "  $pid_nums exit $* "
	fi
}

#aliexpress检查是否存在30天之内过期的账号，有的话，延长token过期事件
checkprocess ../../yii aliexpress/postpone-token

#CD OMS 检查处理时间太长，可能异常的订单
checkprocess ../../yii cdiscount/auto-add-tag-to-cdiscount-order-job0
checkprocess ../../yii cdiscount/auto-add-tag-to-cdiscount-order-job1
checkprocess ../../yii cdiscount/auto-add-tag-to-cdiscount-order-job2

#统计cd oms 昨日的订单量，昨天，是指订单拉取下来insert的日子，不是paid date
checkprocess ../../yii cdiscount/user-cdiscount-order-daily-summary
checkprocess ../../yii priceminister/user-priceminister-order-daily-summary
checkprocess ../../yii aliexpress/cron-aliexpress-order-summary
checkprocess ../../yii bonanza/user-bonanza-order-daily-summary

# 更新linio,jumia目录树
checkprocess ../../yii lazada/refresh-all-site-category-tree
	
# 更新linio,jumia目录属性
checkprocess ../../yii lazada/refresh-category-attrs

# 更新linio,jumia品牌
checkprocess ../../yii lazada/refresh-site-brands

# 更新更新lazada,linio,jumia客户运输方式
checkprocess ../../yii lazada/refresh-shipment-providers


# 更新lazada 新接口目录树
checkprocess ../../yii lazada/refresh-all-site-category-tree-v2
	
# 更新lazada 新接口目录属性
checkprocess ../../yii lazada/refresh-category-attrs-v2

# 更新lazada 新接口品牌
checkprocess ../../yii lazada/refresh-site-brands-v2

# 更新lazada access_token
checkprocess ../../yii lazada/refresh-token


# 监控速卖通type=time,status=1的异常数据脚本
checkprocess ../../yii aliexpress/get-alisys-error-list


# CD offer 跟卖终结者，这个job去检查normal的商品的被跟卖情况
checkprocess ../../yii cdiscount/cd-offer-terminator-normal-refresh

#删除 自动检查成功的 订单 队列数据
checkprocess ../../yii order-analysis/delete-queue-auto-order-check-data

#同步一次速卖通的类目信息
checkprocess ../../yii aliexpress-auto-order/update-cate

#Cdiscount跟卖终结者 每日统计并且发送report by email给seller
checkprocess ../../yii cdiscount/terminator-daily-statistics

#OMS利润自动每天计算
checkprocess ../../yii order-user-statistic/profit-order

#订单禁用标记健康检查
checkprocess ../../yii order-analysis/order-item-delivery-status-health-check

#订单 root sku 健康检查
checkprocess ../../yii order-analysis/order-item-rootsku-health-check

#amazon order item拉取队列记录清理
checkprocess ../../yii amazonv2/clear-amazon-lowpriority-queue
checkprocess ../../yii amazonv2/clear-amazon-highpriority-queue
checkprocess ../../yii amazonv2/clear-amazon-order-submit-queue
#清理queue的数据
checkprocess ../../yii aliexpress-auto-order/del-queue

#ebay订单同步自动关闭长时间没有登录的账号
checkprocess ../../yii queue/stop-sync-ebay-order

#Aliexpress 重新拉取两天内新建的订单
checkprocess ../../yii aliexpress-v2/get-order-list-manual-by-uid