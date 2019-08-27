#!/bin/bash


LOG_DIRS1="/var/www/erp_opensource/console/runtime/logs /var/www/erp_opensource/eagle/runtime/logs"


for LOG_HOME in $LOG_DIRS1
do
  echo $LOG_HOME;
  for rmfile in ` find $LOG_HOME -maxdepth 1 -name "*log*[0-9]"  -mtime +2  -type f `
  do 
     rm -f $rmfile
  done
done





LOG_DIRS2="/var/www/erp_opensource/console/script/log /var/www/erp_opensource/console/script/multi_process_log"


for LOG_HOME in $LOG_DIRS2
do
  echo $LOG_HOME;
  for rmfile in ` find $LOG_HOME -maxdepth 1 -name "*log"  -mtime +0  -type f `
  do
     rm -f $rmfile
  done
done


