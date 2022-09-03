<?php
namespace AjdMetadata;

use AJD_validation\Contracts\Validation_provider;
use AjdMetadata\Macros\AjdMetadataMacro;

class AjdMetadataServiceProvider extends Validation_provider
{
	/**
     * Registers this packages custom rules -> exceptions, macros, logics, filters, validations, extensions.
     *
     * @return void
     */
	public function register()
	{
		$this
			->setDefaults([
				'baseDir' => __DIR__,
				'baseNamespace' => __NAMESPACE__
			])
			
			->mixin(AjdMetadataMacro::class);
	}
}