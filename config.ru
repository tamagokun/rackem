require 'rubygems'
require 'json'

class RubyBridge
	def call(env)
		response = []
		data = JSON.dump(env)
		req = Rack::Request.new(env)
		req.params()
		IO.popen("./rackup.php", 'r+') do |io|
			io.write data
			io.close_write
			response = io.read
		end
		JSON.load(response)
	end
end


use Rack::Reloader
run Rack::Cascade.new([
	Rack::File.new('public'),
	RubyBridge.new
])
