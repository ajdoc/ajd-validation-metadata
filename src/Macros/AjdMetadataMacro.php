<?php 

namespace AjdMetadata\Macros;

use AJD_validation\Contracts\CanMacroInterface;
use AJD_validation\Vefja\Vefja;
use AjdMetadata\Helpers\Metadata;

class AjdMetadataMacro implements CanMacroInterface
{

	/**
     * Returns an array of method name to be made as ajd validation macro.
     *
     * @return array
     *
     */
	public function getMacros()
	{
		return [
			'checkProperty',
			'checkMethod',
			'checkClass',
		];
	}

	 /**
     * Checks if a property returns satisfy constraint.
     *
     * @param  object  $object
     * @param  string  $propertyName
     * @param  bool  $check_arr
     * @return \AJD_validation\Async\PromiseValidator
     */
	public function checkProperty()
	{
		$that = $this;
		return function($object, $propertyName, $check_arr = true) use ($that)
		{
			return $that::getMetadata()->checkMetadata( Metadata::PROP_PROPERTY, $object, $propertyName, $check_arr );
		};
		
	}

	 /**
     * Checks if a method returns satisfy constraint.
     *
     * @param  object  $object
     * @param  string  $methodName
     * @param  bool  $check_arr
     * @return \AJD_validation\Async\PromiseValidator
     */
	public function checkMethod()
	{
		$that = $this;
		return function($object, $methodName, $check_arr = true ) use ($that)
		{
			return $that::getMetadata()->checkMetadata( Metadata::METH_PROPERTY, $object, $methodName, $check_arr );
		};
		
	}

	 /**
     * Checks if a class returns satisfy constraint.
     *
     * @param  object  $object
     * @param  string  $className
     * @param  bool  $check_arr
     * @return \AJD_validation\Async\PromiseValidator
     */
	public function checkClass()
	{
		$that = $this;
		return function($object, $className = null, $check_arr = true) use ($that)
		{
			return $that::getMetadata()->checkMetadata( Metadata::CLASS_PROPERTY, $object, $className, $check_arr );
		};
	}

	/**
     * Returns AjdMetadata\Helpers\Metadata instance.
     *
     * @return AjdMetadata\Helpers\Metadata
     *
     */
	public static function getMetadata()
	{
		return Vefja::singleton('AjdMetadata\Helpers\Metadata');
	}
}