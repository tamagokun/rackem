<?php

class ClassLoader
{
	private $_paths=array();

	public function __construct($paths)
	{
		$this->_paths = array_map(function($p) { return rtrim($p,'/').'/'; }, $paths);
	}

	public function load($className)
	{
		if(!class_exists($className))
		{
			foreach($this->_paths as $path)
			{
				$file = $path . str_replace('\\','/',$className).'.php';

				if(file_exists($file))
				{
					require $file;
					break;
				}
			}

			if(!class_exists($className) && !interface_exists($className))
				return false;
		}
	}

	public function register()
	{
		spl_autoload_register(array($this, 'load'));
		return $this;
	}
}
