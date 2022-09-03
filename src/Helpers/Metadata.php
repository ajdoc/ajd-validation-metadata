<?php 

namespace AjdMetadata\Helpers;

use AJD_validation\Contracts\Abstract_common;
use AJD_validation\AJD_validation;

class Metadata extends AJD_validation
{
	const PROP_PROPERTY = 'propertyConstraints';
	const METH_PROPERTY = 'methodConstraints';
	const CLASS_PROPERTY = 'classConstraints';

	protected static $methodMapping = 'setValidatorMetada';
	protected static $classMethodMapping = 'validateClass';

	protected static $currentClassName;

	public function __construct()
	{

	}

	 /**
     * Set currentClassName
     *
     * @param  string  $currentClassName
     * @return void
     */
	public static function setCurrentClassName( $currentClassName )
	{
		static::$currentClassName = $currentClassName;
	}

	/**
     * Set setMethodMapping
     *
     * @param  string  $methodMapping
     * @return void
     */
	public static function setMethodMapping( $methodMapping )
	{
		static::$methodMapping = $methodMapping;
	}

	/**
     * Set classMethodMapping
     *
     * @param  string  $classMethodMapping
     * @return void
     */
	public static function setClassMethodMapping( $classMethodMapping )
	{
		static::$classMethodMapping = $classMethodMapping;
	}

	/**
     * process property for validation
     *
     * @param  object  $object
     * @param  string  $metadataName
     * @param  \ReflectionProperty $reflect
     * @param  self  $static
     * @param  string  $methodFactory
     * @param  bool  $check_arr
     * @param  bool  $return
     * @return \AJD_validation\Async\PromiseValidator|void
     */
	protected static function processProperty( $object, $metadataName, $reflect, $static, $methodFactory, $check_arr = true, $return = false )
	{
		if( property_exists( $object, $metadataName ) )
		{
			$propertyObject = $reflect->getProperty( $metadataName );

			if( !EMPTY( $propertyObject ) )
			{
				$propertyObject->setAccessible(true);

				$checkObj = $static->check( $propertyObject->getName(), $propertyObject->getValue($object), $check_arr );

				if( $return )
				{
					return $checkObj;
				}
			}
		}
	}

	/**
     * process method for validation
     *
     * @param  object  $object
     * @param  string  $metadataName
     * @param  \ReflectionMethod $reflect
     * @param  self  $static
     * @param  string  $methodFactory
     * @param  bool  $check_arr
     * @param  bool  $return
     * @return \AJD_validation\Async\PromiseValidator|void
     */
	protected static function processMethod( $object, $metadataName, $reflect, $static, $methodFactory, $check_arr = true, $return = false )
	{
		if( method_exists( $object, $metadataName ) )
		{
			if( !EMPTY( $methodFactory ) )
			{
				$reflectMethod = $methodFactory->reflection( [ $object, $metadataName ] );

				$methodValue = $reflectMethod->invokeArgs( $object, [] );

				$name = $reflectMethod->getName();
				$value = $methodValue;

				if( is_array( $methodValue ) )
				{
					if( isset( $methodValue['value'] ) )
					{
						$value = $methodValue['value'];
					}

					if( isset( $methodValue['name'] ) )
					{
						$name = $methodValue['name'];
					}
				}

				$checkObj = $static->check( $name, $value, $check_arr );

				if( $return )
				{
					return $checkObj;
				}
			}
		}
	}

	/**
     * process class for validation
     *
     * @param  object  $object
     * @param  string  $metadataName
     * @param  \ReflectionMethod $reflect
     * @param  self  $static
     * @param  string  $methodFactory
     * @param  bool  $check_arr
     * @param  bool  $return
     * @return \AJD_validation\Async\PromiseValidator|void
     */
	protected static function processClass( $object, $metadataName, $reflect, $static, $methodFactory, $check_arr = true, $return = false )
	{
		$args = [$reflect, $static];

		$value = null;
		$name = $metadataName;
		$invoke = '__invoke';
		$realMethod  = null;
		$classValue = null;

		if( method_exists( $object, $invoke ) )
		{
			$realMethod = $invoke;
		}
		else
		{
			if( method_exists( $object, static::$classMethodMapping ) )
			{
				$realMethod = static::$classMethodMapping;
			}		
		}

		// if class is invokable or has __invoke method $realMethod will be '__invoke' else $realMethod will be 'static::$classMethodMapping'

		if(!empty($realMethod))
		{
			$reflectMethod = $methodFactory->reflection( [ $object, $realMethod ] );

			$classValue = $reflectMethod->invokeArgs( $object, $args );
		}

		if( is_array( $classValue ) )
		{
			if( isset( $classValue['value'] ) )
			{
				$value = $classValue['value'];
			}

			if( isset( $classValue['name'] ) )
			{
				$name = $classValue['name'];
			}
		}
		else
		{
			$value = $classValue;
		}

		$checkObj = $static->check( $name, $value, $check_arr );

		if( $return )
		{
			return $checkObj;
		}
	}

	/**
     * Checks the metadata for validation
     *
     * @param  string  $property
     * @param  object  $object
     * @param  string  $metadataName
     * @param  bool  $check_arr
     * @return \AJD_validation\Async\PromiseValidator|void
     */
	public static function checkMetadata( $property, $object, $metadataName = null, $check_arr = true )
	{
		$factory = static::get_factory_instance();
		$classFactory = $factory->get_instance(true);
		$methodFactory = $factory->get_instance(false, false, true);

		$reflect = $classFactory->reflection( $object );
		$static = new static;

		switch( $property )
		{
			case self::PROP_PROPERTY :
				return $static->processProperty( $object, $metadataName, $reflect, $static, $methodFactory, $check_arr, TRUE );
			break;

			case self::METH_PROPERTY :
				return $static->processMethod( $object, $metadataName, $reflect, $static, $methodFactory, $check_arr, TRUE );
			break;

			case self::CLASS_PROPERTY :

				$cName = $reflect->getName();

				if( !EMPTY( $metadataName ) )
				{
					$cName  = $metadataName;
				}

				return $static->processClass( $object, $cName, $reflect, $static, $methodFactory, $check_arr, TRUE );
			break;
		}
	}
}