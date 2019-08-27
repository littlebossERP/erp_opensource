#!/bin/bash

pid_nums1=`ps aux | grep "/bin/bash.*onePerMin_v2.sh" | grep -v grep | wc -l`
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
			#如果进程已经跑了6个小时，强制退出
			if [  `expr $timestamp2 + 21600 ` -lt $nowtimestamp ]
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

#标记发货
checkprocess ../../yii queue/cron-auto-shipped 
checkprocess ../../yii queue/cron-cdiscount-auto-shipped
checkprocess ../../yii queue/cron-priceminister-auto-shipped 
checkprocess ../../yii queue/cron-ebay-auto-shipped 
checkprocess ../../yii queue/cron-amazon-auto-shipped 
checkprocess ../../yii queue/cron-aliexpress-auto-shipped 



#amazon order list
#1. 拉取所有unshipped的旧订单(FBA和非FBA)的list：
checkprocess ../../yii amazonv2/auto-fetch-order-list-header-one
#2. 拉取最近更新的非FBA(状态变化/新订单)的list：./yii amazonv2/auto-fetch-order-list-header-two
checkprocess ../../yii amazonv2/auto-fetch-order-list-header-two
#3. 最近更新的FBA(状态变化/新订单)：./yii amazonv2/auto-fetch-order-list-header-three
checkprocess ../../yii amazonv2/auto-fetch-order-list-header-three
#4. 30天非FBA-非unshipper的订单：./yii amazonv2/auto-fetch-order-list-header-four
checkprocess ../../yii amazonv2/auto-fetch-order-list-header-four
#5. 30天FBA-非unshipper的订单：./yii amazonv2/auto-fetch-order-list-header-five 
checkprocess ../../yii amazonv2/auto-fetch-order-list-header-five

#amazon order items-by-time
checkprocess ../../yii amazonv2/auto-fetch-order-items-highpriority
checkprocess ../../yii amazonv2/auto-fetch-order-items-lowpriority


 
#amazon 发货 检查完成情况
# checkprocess ../../yii amazon/cron-submit-amazon-order
checkprocess ../../yii amazon/cron-batch-submit-amazon-order
#checkprocess ../../yii amazon/cron-check-amazon-submit
checkprocess ../../yii amazon/cron-batch-check-amazon-submit

#amazon 解绑清除数据异步job
checkprocess ../../yii platform-unbind/amazon-unbind-clear-data

#wish orders 实际上Wish会检查 订单半小时内没有同步过的才做同步，所以这个检查1分钟起来一次也没有太大问题
checkprocess ../../yii wish/fetch-new-account-orders-eagle2
#wish 同步 订单多进程拉取 目前5个进程0，1，2，3，4
checkprocess ../../yii wish/fetch-recent-changed-orders-eagle20
# checkprocess ../../yii wish/fetch-recent-changed-orders-eagle21
# checkprocess ../../yii wish/fetch-recent-changed-orders-eagle22
# checkprocess ../../yii wish/fetch-recent-changed-orders-eagle23
# checkprocess ../../yii wish/fetch-recent-changed-orders-eagle24

checkprocess ../../yii wish/cron-manual-retrieve-wish-order-eagle2

#wish product
#checkprocess ../../yii wish/sync-wish-product-queue  	# wish商品队列
#checkprocess ../../yii wish/auto-sync-product 			# 自动定时同步


#ebay order 目前3个进程0，1 , 2
checkprocess ../../yii queue/cron-request-order0
# checkprocess ../../yii queue/cron-request-order1
# checkprocess ../../yii queue/cron-request-order2

#ebay 新订单  目前5个进程0，1,2,3,4
checkprocess ../../yii queue/sync-queue-ebay-order0
# checkprocess ../../yii queue/sync-queue-ebay-order1
# checkprocess ../../yii queue/sync-queue-ebay-order2
# checkprocess ../../yii queue/sync-queue-ebay-order3
# checkprocess ../../yii queue/sync-queue-ebay-order4

#ebay 旧订单  目前5个进程0，1,2,3,4
checkprocess ../../yii queue/sync-queue-ebay2-order0
# checkprocess ../../yii queue/sync-queue-ebay2-order1
# checkprocess ../../yii queue/sync-queue-ebay2-order2
# checkprocess ../../yii queue/sync-queue-ebay2-order3
# checkprocess ../../yii queue/sync-queue-ebay2-order4

#ebay 特殊订单  
checkprocess ../../yii queue/sync-queue-ebay9-order

#ebay 手工同步订单  
checkprocess ../../yii queue/sync-queue-ebay8-order

#ebay bestoffer
checkprocess ../../yii queue/cron-sync-bestoffer

#同步feature信息
checkprocess ../../yii ebay-common-info/cron-sync-ebay-feature 
#同步Specific信息
checkprocess ../../yii ebay-common-info/cron-sync-ebay-specific 



#有效列表
#checkprocess ../../yii aliexpress/get-order-list-by-time
checkprocess ../../yii aliexpress-v2/get-order-list-by-time
#checkprocess ../../yii aliexpress/get-order-list-by-day120
checkprocess ../../yii aliexpress-v2/get-order-list-by-day120
#checkprocess ../../yii aliexpress/get-order-list-by-finish
#checkprocess ../../yii aliexpress/get-order-insert-queue
#恢复机制队列
checkprocess ../../yii aliexpress-clear-queue/push-order-info
checkprocess ../../yii aliexpress-clear-queue/retry-account
#checkprocess ../../yii aliexpress/get-order-finish
#改为多线程
#checkprocess ../../yii aliexpress/get-order-half

 
# checkprocess ../../yii aliexpress/first-to-db 
checkprocess ../../yii aliexpress-v2/update-to-db 
# checkprocess ../../yii aliexpress/get-order-finish 
# checkprocess ../../yii aliexpress/get-order-list-by-finish-day30 


#aliexpress拉取在线商品。同一个账号间隔半天同步一次
#checkprocess ../../yii aliexpress/get-listing-on-selling
checkprocess ../../yii aliexpress-v2/get-listing-on-selling
#aliexpress拉取在线商品的详情。目前每个账号只拉取一次
checkprocess ../../yii aliexpress/get-listing-detail
#aliexpress 手动删除订单后同步订单队列
checkprocess ../../yii  aliexpress/get-order-manual-queue

#aliexpress 推送结果表中,处理几个状态的订单
checkprocess ../../yii  aliexpress-auto-order/get-finish-ali-auto-order 

#速卖通刊登
checkprocess ../../yii manual-sync/run smt:product
checkprocess ../../yii manual-sync/run smt:productpush



checkprocess ../../yii cdiscount/fetch-new-account-orders-eagle2

#Priceminister OMS order
checkprocess ../../yii priceminister/fetch-new-account-orders-eagle2
checkprocess ../../yii priceminister/fetch-recent-order-list-eagle2-job0
# checkprocess ../../yii priceminister/fetch-recent-order-list-eagle2-job1
# checkprocess ../../yii priceminister/fetch-recent-order-list-eagle2-job2


checkprocess ../../yii priceminister/sync-priceminister-order-item-status-job0
# checkprocess ../../yii priceminister/sync-priceminister-order-item-status-job1
# checkprocess ../../yii priceminister/sync-priceminister-order-item-status-job2

checkprocess ../../yii priceminister/manual-fetch-order-eagle2


#FR121 Bonanza OMS order
#新订单拉取
checkprocess ../../yii bonanza/fetch-new-account-orders-eagle2
#最近订单拉取
checkprocess ../../yii bonanza/fetch-recent-order-list-eagle2-job0
#自动ship订单
checkprocess ../../yii queue/cron-bonanza-auto-shipped


#Lazada 新接口订单拉取
checkprocess ../../yii lazada/get-order-list-new-create-v2
checkprocess ../../yii lazada/get-order-list-new-update-v2
checkprocess ../../yii lazada/get-order-list-old-first-v2
checkprocess ../../yii lazada/get-order-list-by-day2-v2
checkprocess ../../yii lazada/batch-auto-fetch-order-items-v2 


#Linio,Jumia 订单拉取
checkprocess ../../yii linio/get-order-list-new-create
checkprocess ../../yii linio/get-order-list-new-update
checkprocess ../../yii linio/get-order-list-old-first
#checkprocess ../../yii linio/get-order-list-old-second
checkprocess ../../yii linio/get-order-list-by-day2
checkprocess ../../yii linio/batch-auto-fetch-order-items 

# 导入任务
checkprocess ../../yii linio/handle-import-job1
checkprocess ../../yii linio/handle-import-job2
checkprocess ../../yii linio/check-import-feed
 


#采购模块：采购建议的生成
checkprocess ../../yii purchasesug-queue/cron-calculate-purchasesug

#拉取ebay在线刊登 (已弃用)
#checkprocess ../../yii queue/cron-request-item

#第一次ebay在线刊登拉取
checkprocess ../../yii ebay-auto-get-items/cron-first-getitems
#ebay在线刊登的更新
checkprocess ../../yii ebay-auto-get-items/cron-auto-getitems
#ebay拉取图片URL
checkprocess ../../yii ebay-items-part-info/auto-getmultitem-photourl

#ebay自动补货数量检查
checkprocess ../../yii ebay-auto-listing/check-inventory
#ebay自动补货
checkprocess ../../yii ebay-auto-listing/auto-inventory
#ebay定时刊登
checkprocess ../../yii ebay-auto-listing/timer-listing
#App 中央推送队列， 从队列拉取 app 之间需要push的异步请求，然后通过 eval 执行，执行的 function里面，自己判断 puid 是否等于 db current，不等就自己change user db
#checkprocess ../../yii queue/app-push-data-eagle2


#图片服务器提取队列的request然后做图片缓存到中国本地。 yzq 2016-2-22
checkprocess ../../yii queue/image-cacher-run-eagle2
checkprocess ../../yii queue/image-cacher-run-eagle2-job2
checkprocess ../../yii queue/image-cacher-run-eagle2-job3


#获取高仿面单API和配货单PDF合并
checkprocess ../../yii carrierconversion/carrier-label-api-and-items-by-time


#通用同步队列  hqf 2016-3-17
checkprocess ../../yii manual-sync/run 				# 主函数
checkprocess ../../yii manual-sync/auto-task 		# 定时自动同步

#Cdiscount 平台订单自动检测标记发货是否成功。
checkprocess ../../yii cdiscount/hc-cdiscount-order-sing-shipped

#lazada linio jumia 刊登
#lazada 图片上传
checkprocess ../../yii lazada/auto-upload-images
#lazada 新接口产品同步
checkprocess ../../yii lazada/get-updated-listing-v2


#linio jumia 产品同步
checkprocess ../../yii linio/get-updated-listing
#linio jumia 图片上传
checkprocess ../../yii linio/auto-upload-images
#linio jumia feed check
checkprocess ../../yii linio/check-feed-status


#速卖通订单催款成功回写
#checkprocess ../../yii assistant/success

#CD跟卖终结者，commit manager，把product info commit回去 user库
checkprocess ../../yii cdiscount/cd-offer-terminator-commit-l-p
checkprocess ../../yii cdiscount/cd-offer-terminator-commit-h-p

#CD跟卖终结者，起来看看有没有 关注的产品到期，譬如6个小时 需要继续看看的
checkprocess ../../yii cdiscount/cd-offer-terminator-followed-refresh

# 订单自动检测
checkprocess ../../yii order-analysis/queue-auto-order-check0
# checkprocess ../../yii order-analysis/queue-auto-order-check1

# 自动检测检查任务
checkprocess ../../yii auto-check/check-all-job
checkprocess ../../yii excel/cron-export-excel

#paypal的交易抓取
checkprocess ../../yii queue/sync-queue-paypal-transaction

#paypal地址专用抓取
checkprocess ../../yii queue/cron-sync-paypal-address

#shopee订单拉取
checkprocess ../../yii shopee/get-order-list-by-time
checkprocess ../../yii shopee/update-to-db
checkprocess ../../yii shopee/get-order-list-by-un-finish

#aliexpress 接收推送消息
checkprocess ../../yii aliexpress-v2/receive-ali-order-push
#aliexpress 推送结果表中,处理几个状态的订单
checkprocess ../../yii aliexpress-v2/get-ali-auto-order





 

 

 