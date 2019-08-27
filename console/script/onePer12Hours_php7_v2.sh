#!/bin/bash

pid_nums1=`ps aux | grep "/bin/bash.*onePer12Hours_php7_v2.sh" | grep -v grep | wc -l`
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
			#如果进程已经跑了60个小时，强制退出
			if [  `expr $timestamp2 + 216000 ` -lt $nowtimestamp ]
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



#amz fetch report and fba inventory
checkprocess ../../yii amazon/cron-request-fba-inventory-report

#update shipping methods for all carriers
checkprocess ../../yii carrierconversion/update-shipping-method





