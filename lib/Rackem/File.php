<?php
namespace Rackem;

class File
{
	public $root,$path,$cache_control;
	protected $range;

	public function __construct($root, $cache_control = null)
	{
		$this->root = $root;
		$this->cache_control = $cache_control;
	}

	public function call($env)
	{
		$path_info = $env['PATH_INFO'];
		$parts = explode(DIRECTORY_SEPARATOR,$path_info);
		if(in_array("..",$parts)) return $this->fail(403,"Forbidden");
		$this->path = rtrim($this->root,DIRECTORY_SEPARATOR).$path_info;
		$available = is_file($this->path) && is_readable($this->path);
		if($available) return $this->serving($env);
		return $this->fail(404,"File not found: $path_info");
	}

	public function serving($env)
	{
		$size = filesize($this->path);
		$response = array(200,array(
			"Last-Modified" => filemtime($this->path),
			"Content-Type" => Mime::mime_type(pathinfo($this->path,PATHINFO_EXTENSION),"text/plain")
			),array());
		if($this->cache_control) $response[1]["Cache-Control"] = $this->cache_control;
		$ranges = Utils::byte_ranges($env, $size);
		if(is_null($ranges) || count($ranges) > 1)
		{
			$response[0] = 200;
			$this->range = array(0,$size-1);
		}elseif( empty($ranges) )
		{
			$response = $this->fail(416, "Byte range unsatisfiable");
			$response[1]["Content-Range"] = "bytes */$size";
			return $response;
		}else
		{
			$this->range = $ranges[0];
			$response[0] = 206;
			$response[1]["Content-Range"] = "bytes {$this->range[0]}-{$this->range[1]}/$size";
			$size = $this->range[1] - $this->range[0] + 1;
		}
		$response[1]["Content-Length"] = "$size";
		$response[2] = $this->prepare();
		return $response;
	}

	//protected
	protected function fail($status, $body)
	{
		$body .= "\n";
		return array($status,array(
			"Content-Type"=>"text/plain",
			"Content-Length"=>"{strlen($body)}",
			"X-Cascade"=>"pass"
			),array($body));
	}

	protected function prepare()
	{
		$parts = array();
		$handle = fopen($this->path,"rb");
		fseek($handle,$this->range[0]);
		$remaining_len = $this->range[1] - $this->range[0] + 1;
		while($remaining_len > 0)
		{
			$part = fread($handle, min(8192,$remaining_len));
			if(!$part) break;
			$remaining_len -= strlen($part);
			$parts[] = $part;
		}
		return $parts;
	}
}