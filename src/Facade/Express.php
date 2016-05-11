<?php namespace Debuglee\Express\Facade;
 
use Illuminate\Support\Facades\Facade;
 
class Express extends Facade {
 
    protected static function getFacadeAccessor() { return 'express'; }
 
}