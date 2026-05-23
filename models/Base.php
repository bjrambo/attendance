<?php
namespace Rhymix\Modules\Attendance\Models;

/**
 * Attendance module base model.
 * Model classes do not extend ModuleObject; this base provides shared helpers.
 */
class Base
{
	public function init(): void {}

	public function makeObject(int $code = 0, string $msg = 'success'): object
	{
		return class_exists('BaseObject') ? new \BaseObject($code, $msg) : new \Object($code, $msg);
	}
}
