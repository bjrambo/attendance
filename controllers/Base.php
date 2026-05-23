<?php
namespace Rhymix\Modules\Attendance\Controllers;

/**
 * Attendance module base controller.
 * All module controllers and the Install class extend this.
 */
class Base extends \ModuleObject
{
	/**
	 * Compatibility shim for BaseObject/Object.
	 */
	public function makeObject($code = 0, $msg = 'success')
	{
		return class_exists('BaseObject') ? new \BaseObject($code, $msg) : new \Object($code, $msg);
	}

	/**
	 * Returns true when an object cache backend is available.
	 */
	protected function _getCacheType(): bool
	{
		if (defined('RX_BASEDIR'))
		{
			return config('cache.type') ? true : false;
		}
		$db_info = \Context::getDbInfo();
		return isset($db_info->use_object_cache) ?: false;
	}

	/**
	 * Returns the template filename for the current skin path,
	 * preferring .blade.php over .html for forward compatibility.
	 */
	protected function _resolveSkinTemplate(string $name): string
	{
		if (file_exists($this->getTemplatePath() . '/' . $name . '.blade.php'))
		{
			return $name . '.blade.php';
		}
		return $name . '.html';
	}
}
