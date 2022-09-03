<?php 

namespace AjdMetadata\Traits;

use AJD_validation\AJD_validation;
use AJD_validation\Factory as Factory;
use \Exception;

trait ValidationTrait
{
	protected $ajd;
	protected $annotation = false;
	protected static $group_value = [];

	protected function ajd_ins()
	{
		if( IS_NULL( $this->ajd ) )
		{
			$this->ajd = new AJD_validation;
		}

		return $this->ajd;
	}

	public function setAnnotation()
	{
		$this->annotation = true;
	}

	public function validatorInstance()
	{
		return $this->ajd_ins();
	}

	public function errors()
	{
		return AJD_validation::errors()->all();
	}

	abstract public function setInstance();

	public function validate()
	{
		$obj = $this->setInstance();		

		$options = ( $this->annotation ) ? $this->annotation() : $this->validator;
		
		if( ISSET( $options ) AND !EMPTY( $options ) )
		{
			foreach( $options as $field => $rules )
			{
				foreach( $rules['rules'] as $rule => $args )
				{
					
					$satisfier = ISSET( $args[0] ) ? $args[0] : NULL;
					$custom_err = ISSET( $args[1] ) ? $args[1] : NULL;
					$client_side = ISSET( $args[2] ) ? $args[2] : NULL;
					$logic = ISSET( $args[3] ) ? $args[3] : AJD_validation::LOG_AND;

					AJD_validation::addRule( $rule, $satisfier, $custom_err, $client_side, $logic );
				}

				if( !EMPTY( $this->filters ) )
				{
					if( ISSET( $this->filters[ $field ] ) )
					{
						foreach( $this->filters[ $field ] as $filter => $filter_args )
						{
							$filter_satis = ISSET( $filter_args[0] ) ? $filter_args[0] : NULL;
							$pre_filter = ISSET( $filter_args[1] ) ? $filter_args[1] : FALSE;

							AJD_validation::addFilter( $filter, $filter_satis, $pre_filter );
						}
					}
				}

				$this->ajd_ins()->check( $field, $obj->{ $field } );
			}
		}

	}

	protected function groupValidation( $group = NULL, $extra = NULL )
	{
		$obj = $this->setInstance();	
		$value = array();	

		if( !EMPTY( $group ) )
		{
			$group_arr = $group;	
		}
		else 
		{
			$group_arr = ISSET( $this->groupValidator ) ? $this->groupValidator : array(); 
		}

		$str = '';

		if( ISSET( $group_arr ) AND !EMPTY( $group_arr ) )
		{
			foreach( $group_arr['rule'] as $superRule => $values )
			{
				$logic = ISSET( $values['args'][0] ) ? $values['args'][0] : AJD_validation::LOG_AND;
				$satis = ISSET( $values['args'][1] ) ? $values['args'][1] : NULL;
				$custom_err = ISSET( $values['args'][2] ) ? $values['args'][2] : NULL;
				$client_side = ISSET( $values['args'][3] ) ? $values['args'][3] : NULL;
				$end = ( ISSET( $values['end'] ) AND !EMPTY( $values['end'] ) ) ? 1 : 0;

				AJD_validation::superRule( $superRule, $logic, $satis, $custom_err, $client_side );

				if( ISSET( $values['rule'] ) )
				{
					$this->groupValidation( $values, $group_arr );
				}	

				foreach( $values['fields'] as $key => $field )
				{
					AJD_validation::field( $field );
					
					static::$group_value[ $field ] 		= $obj->{ $field };
				}

				if( $end )
				{
					AJD_validation::endSuperRule();
				}

			}

		}

		if( !EMPTY( $extra ) )
		{
			if( ISSET( $extra['fields'] ) AND !EMPTY( $extra['fields'] ) )
			{
				foreach( $extra['fields'] as $key => $field )
				{
					AJD_validation::field( $field );
					
					static::$group_value[ $field ] = $obj->{ $field };
				}
			}

			if( ISSET( $extra['end'] ) AND !EMPTY( $extra['end'] ) )
			{
				 AJD_validation::endSuperRule();
			}
		}

	}

	public function filtered_value( $key = NULL )
	{
		return AJD_validation::filter_value( $key );
	}

	public function bindToProp()
	{
		$obj = $this->setInstance();

		$filtered_value = $this->filtered_value();

		if( !EMPTY( $filtered_value ) )
		{
			foreach( $filtered_value as $property => $value )
			{
				if( property_exists( $obj, $property ) )
				{
					if( !is_array( $value ) )
					{
						$obj->{ $property } = $value;
					}
				}
			}
		}

	}

	public function runValidationGroup()
	{
		$this->groupValidation();

		if( !EMPTY( static::$group_value ) )
		{ 
			$this->ajd_ins()->checkGroup( static::$group_value );
		}
	}

	public function validation_fails( $key = NULL, $err_key = NULL )
	{
		return AJD_validation::validation_fails( $key, $err_key );
	}

	public function assert()
	{
		$ajd = $this->validatorInstance();

		$this->runValidationGroup();
		$this->validate();
		
		if( $ajd->validation_fails() )
		{
			if( !EMPTY( $ajd->errors()->toStringErr() ) )
				throw new Exception( $ajd->errors()->toStringErr() );
		}
	}


	public function annotation()
	{
		$factory = new Factory\Class_factory;
		$reflect = $factory->reflection( $this->setInstance() );

		$properties = $reflect->getProperties();	
		$master_arr = [];

		foreach ( $properties as $property ) 
		{
			if( preg_match( '/@AJD\\\/', $property->getDocComment(), $match ) != false )
			{
				$rules = str_replace( 
								array( '*', '/', '/', '@AJD\\' ), 
								array( '', '', '', '' ), 
								$property->getDocComment()
							);
				
				$rules = preg_replace('/[\n\r]/', '|', trim( $rules ) );
				$rules = preg_replace('/[\s]/', '', $rules );

				if( !EMPTY( $rules ) )
				{
					$rules = explode( '|', $rules );

					foreach( $rules as $rule ) 
					{
						if( !EMPTY( $rule ) )
						{
							if( $this->expression_has_args( $rule ) )
							{
								$master_arr[ $property->getName() ][ 'rules' ][ $this->clean_name( $rule ) ] = $this->get_expr_args( $rule );	
							}
							else 
							{
								$master_arr[ $property->getName() ][ 'rules' ][ $rule ] = [];		
							}
						}
					}
				}
			}
		}
	
		return $master_arr;

	}

	protected function expression_has_args( $str )
	{
		$check = explode( '(', $str );

		return ( ISSET( $check[1] ) AND !EMPTY( $check[1] ) );

	}

	protected function clean_name( $name )
	{
		if( !$this->expression_has_args( $name ) ) 
		{
			return $name;
		}

		$name_clean = preg_replace( '/\([\s\S]*/', '', $name );

		return $name_clean;

	}

	protected function get_expr_args( $name, $dont_get_value = FALSE )
	{
		$ret_args = [];

		if( !$this->expression_has_args( $name ) ) 
		{
			return [];
		}

	    list( $meth_name, $args_with_bracket_end ) 	= explode( '(', $name );

        $args = rtrim( $args_with_bracket_end, ')' );
        $args = preg_replace( '/\s+/', '', $args );
        $args = explode( ',', $args );

        if( $dont_get_value ) 
        {
        	$ret_args = $args;
        } 
        else 
        {        
    		$ret_args = $this->get_value_args( $args );
        }

        return $ret_args;

	}

	protected function get_value_args( array $args )
	{
		$ret_args = [];

		if( EMPTY( $args ) ) 
		{
			return $ret_args;
		}

		foreach( $args as $arg_key => $arg_value ) 
		{
			$ret_args[] = $arg_value;
		}

		return $ret_args;
	}
}