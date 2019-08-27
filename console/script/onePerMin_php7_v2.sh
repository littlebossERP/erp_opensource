#!/bin/bash

pid_nums1=`ps aux | grep "/bin/bash.*onePerMin_php7_v2.sh" | grep -v grep | wc -l`
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
                   php -c /etc/php/7.0/cli/php.ini $@ >> "log/${action:0:124}${param:0:55}_${nowdate}.log" 2>&1 &
                elif [ "${#2}" -gt "0" ]
                then
                    action=${2/\//-}
                    echo "log/${action:0:124}_${nowdate}.log" 
                    php -c /etc/php/7.0/cli/php.ini $@ >> "log/${action:0:124}_${nowdate}.log" 2>&1 &
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



#速卖通订单催款成功回写
checkprocess ../../yii assistant/success


#FR121 Cdiscount OMS order
checkprocess ../../yii cdiscount/get-cdiscount-offer-list-paginated


#这3个是拉取list
checkprocess ../../yii dhgatev2/get-order-list-by-day120
checkprocess ../../yii dhgatev2/get-order-list-by-finish
checkprocess ../../yii dhgatev2/get-order-list-by-time
#这4个是拉取detail信息
checkprocess ../../yii dhgatev2/get-order-detail-by-day120
checkprocess ../../yii dhgatev2/get-order-detail-by-finish
checkprocess ../../yii dhgatev2/get-order-detail-by-time
checkprocess ../../yii dhgatev2/get-order-detail-by-daily
#这个是转队列的
checkprocess ../../yii dhgatev2/move-pendingorderqueue-to-getorderqueue


#推荐商品计算
#第一次计算。 只有打开了推荐商品开关的账号才触发该逻辑
#checkprocess ../../yii recommend-product/generate-recommend-products-firsttime  
#常规的更新-- 每个账号，每天更新一次
#checkprocess ../../yii recommend-product/generate-recommend-products-update   


#10个不会打架的 html capture job
checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info00
# checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info01
# checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info02
# checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info03
# checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info04
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info05
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info06
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info07
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info08
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info09
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info10
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info11
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info12
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info13
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info14
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info15
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info16
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info17
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info18
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info19
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info20
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info21
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info22
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info23
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info24
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info25
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info26
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info27
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info28
#checkprocess ../../yii html-catcher/cron-catch-cdiscount-product-info29


#App 中央推送队列， 从队列拉取 app 之间需要push的异步请求，然后通过 eval 执行，执行的 function里面，自己判断 puid 是否等于 db current，不等就自己change user db
checkprocess ../../yii queue/app-push-data0
# checkprocess ../../yii queue/app-push-data1

#FR121 Cdiscount OMS order
checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job00
# 需要加速再开其他job
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job01
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job02
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job03
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job04
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job05
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job06
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job07
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job08
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job09
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job10
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job11
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job12
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job13
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job14
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job15
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job16
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job17
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job18
# checkprocess ../../yii cdiscount/fetch-recent-order-list-eagle2-job19

checkprocess ../../yii cdiscount/manual-fetch-order-eagle2

#EDM sener queue handler
#checkprocess ../../yii edm/queue-handler


#rumall OMS fetch orders
checkprocess ../../yii rumall/fetch-recent-order-list-eagle2-job0
checkprocess ../../yii rumall/fetch-new-account-orders-eagle2

#send notification email to vip customer about the CD Terminator
checkprocess ../../yii cdiscount/auto-send-terminator-announce

#Newegg OMS fetch orders
checkprocess ../../yii newegg/update-order-by-queue
checkprocess ../../yii newegg/get-new-order
checkprocess ../../yii newegg/get-order-old-unshipped
checkprocess ../../yii newegg/get-order-old-partially-shipped
checkprocess ../../yii newegg/get-order-old-shipped



#amz fetch report and fba inventory
checkprocess ../../yii amazon/cron-get-request-report-id
checkprocess ../../yii amazon/cron-get-report-data



checkprocess ../../yii edm/queue-ses-handler




#速卖通订单手动同步
checkprocess ../../yii manual-sync/run smt:push
checkprocess ../../yii manual-sync/run smt:push


checkprocess ../../yii fulfill/route-manager 
checkprocess ../../yii fulfill/pdf-handler

