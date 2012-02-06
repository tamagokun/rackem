require 'rubygems'
require 'json'

class RubyBridge
	def call(env)
		response = []
		data = JSON.dump(env)
		IO.popen("./rackup.php", 'r+') do |io|
			io.write data
			io.close_write
			response = io.read
		end
		JSON.load(response)
	end
end

class RubyMiddlewareBridge
	def initialize(app)
		@app = app
	end

	def call(env)
		env['rack.ruby_bridge_response'] = @app.call(env)

		response = []
		data = JSON.dump(env)
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