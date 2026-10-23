<?php
    namespace WISECP\Modules\Servers\DNAHosting;

    defined("CORE_FOLDER") or exit("You can not get in here!");

    /**
     * Every failure the module reports. It extends \Exception on purpose: the core
     * (Services::run_module) catches \Exception only, so anything else would escape
     * as a fatal error instead of reaching the operator as a message.
     */
    class ModuleException extends \Exception
    {
    }
