<?php
namespace common\api\multiThreadQueueInterface;

use common\mongo\lljListing\LLJListingDBConfig;
use common\mongo\lljListing\LLJListingManagerFactory;
use common\mongo\lljListing\TaskQueue;
use common\mongo\lljListing\TaskStatus;
use console\helpers\LogType;

/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/7/29
 * Time: 上午10:31
 */
class MultiThreadQueueInterface
{
    const LOG_ID = "common-api-multi-thread-queue-interface-MultiThreadQueueInterface";

    private static function localLog($msg, $type = LogType::INFO)
    {
        CommonHelper::log($msg, self::LOG_ID, $type);
    }

    /**
     * 设置指定路径下启动的线程数量
     * @param $url 完整的路径
     * @param $quantity 这个路径下启动的线程数量
     * @return bool 是否设置成功
     */

    public static function setUrlThreadQuantity($url, $quantity)
    {
        $defaultInfoManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::DEFAULT_INFO);
        $rlt = $defaultInfoManager->update(
            array("infoType" => 3, "content.url" => $url), array('$set' => array("content.quantity" => $quantity)), array("upsert" => true, "updateTime" => time()));
        if ($rlt["ok"] == 1) {
            return true;
        }
        return false;
    }

    /**
     * 向队列中插入请求
     * @param $config proxy接收到的config内容
     * @param $reqParams proxy接收的reqParams
     * @param $action proxy接收的action字段,如果没有,则传null
     * @param $foreignTaskId string用户自己识别任务的唯一标识
     * @param $url 完整的url,proxy请求完整路径
     * @return bool 是否插入成功
     */
    public static function createdTask($config, $reqParams, $action = null, $foreignTaskId, $url)
    {
        try {
            $task = new TaskQueue();
            $task->config = $config;
            $task->reqParams = $reqParams;
            $task->foreignTaskId = $foreignTaskId;
            $task->url = $url;
            $task->isRoot = true;
            if ($action != null) {
                $task->action = $action;
            }
            LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::TASK_QUEUE)->insert($task);
            return true;
        } catch (MongoCursorException $e) {
            self::localLog($e->getMessage(), LogType::ERROR);
            return false;
        }
    }

    /**
     * 根据插入任务时的id获取返回值
     * @param $foreignTaskIds array,插入是指定的id
     * @return array
     * [
     *      $foreignTaskId=>array()proxy返回值的数组
     * ]
     */
    public static function getTaskResponse($foreignTaskIds = array())
    {
        $rps = array();
        foreach ($foreignTaskIds as $foreignTaskId) {
            $oldTask = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::TASK_QUEUE)->findAndModify(
                array("foreignTaskId" => $foreignTaskId, "taskStatus" => array('$in' => array(TaskStatus::PROCESS_SUCCESS, TaskStatus::RETRY_END)), "isRoot" => true),
                array('$set' => array("taskStatus" => TaskStatus::RETRIEVING)));
            if (!empty($oldTask)) {
                $taskId = array();
                if ($oldTask["taskStatus"] == TaskStatus::RETRY_END) {
                    $taskId = $oldTask["customized.retryTaskIds"];
                } else {
                    $taskId[] = $oldTask["_id"];
                }
                $rpCursor = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::RESPONSE_DATA)->find(array("_id" => array('$in' => $taskId)));
                $rps[] = array($foreignTaskId => array());
                foreach ($rpCursor as $rp) {
                    $rps[$foreignTaskId][] = $rp["data"];
                }
            }
        }
        return $rps;
    }
}