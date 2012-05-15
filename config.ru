require 'rubygems'
require 'json'

class RubyBridge
	def call(env)
		response = []
		req = Rack::Request.new(env)
		req.params()
		data = JSON.dump(env)
		IO.popen("./rackup.php", 'r+') do |io|
			io.write data
			io.close_write
			response = io.readlines
		end
		[response.shift,JSON.load(response.shift),response]
	end
end

use Rack::Reloader
run Rack::Cascade.new([
	Rack::File.new('public'),
	RubyBridge.new
])
