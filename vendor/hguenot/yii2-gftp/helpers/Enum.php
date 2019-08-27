<?php

namespace gftp\helpers;

use \ReflectionClass;

/**
 * Description of Enum
 *
 * @author herve
 */
abstract class Enum extends \yii\base\Object {
	
	private $name;
	private $ordinal;
	
	private static $_instances = [];
		
	final public static function __callStatic($method, array $args)
    {
		return self::getEnums()['byName'][$method];
    }
	
	final public static function values($byName = false)
    {
		return self::getEnums()[$byName ? 'byName' : 'byOrdinal'];
    }
	
	final private static function getEnums() 
	{
        $class = get_called_class();
		if (!isset(self::$_instances[$class])) {
			self::$_instances[$class] = [
				'byName' => [],
				'byOrdinal' => []
			];
		
			$reflection = new ReflectionClass($class);
			$ordinal = 0;
			foreach ($reflection->getConstants() as $name => $value) {
				$enum = new $class($value);
				$enum->name = $name;
				$enum->ordinal = $ordinal;
				self::$_instances[$class]['byName'][$name] = 
						self::$_instances[$class]['byOrdinal'][$ordinal] = $enum;
				$ordinal++;
			}
		}
		
		return self::$_instances[$class];
	}
	
	public final function getName() {
		return $this->name;
	}
	
	public final function getOrdinal() {
		return $this->ordinal;
	}
		
}
