<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午6:02
 */

namespace eagle\modules\comment\helpers;


class CommentStatus
{
    const NOT_COMMENT = 0;
    const SUCCESS = 1;
    const COMMENTING = 2;
    const NOT_RETRY_FAILED = 3;
    const RETRY_FAILED = 4;
    const SELLER_LOGIN_ID_NOT_EXIST=5;
}