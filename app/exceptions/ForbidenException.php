<?php

namespace app\exceptions;

class ForbidenException extends \Exception
{
    protected $message = "You don't have permission to access to this page";
    protected $code = 403;
}