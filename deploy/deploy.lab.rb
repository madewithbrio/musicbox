load 'deploy' if respond_to?(:namespace) # cap2 differentiator

require 'rubygems'
require 'rexml/document'
#require 'bells/recipes/apache' # This one requires the Bells gem

set :application, "musicbox"
set :domain, "stg.mobile" 
role :app, "stg.mobile"

# Set the Unix user and group that will actually perform each task
# on the remote server. The defaults are set to 'deploy'
set :user, "www-data" 
set :group, "www-data" 

# Deployment Settings
set :repository_base, 'git@github.com'
set :app_repository,  'hellcore/musicbox.git'
set :repository, "#{repository_base}:#{app_repository}"
set :deploy_app_to, "/fasmounts/sapo/mobile/#{application}" # This is where your project will be deployed.

#other directories
set :tmp_dir, "/var/tmp/#{application}"


ssh_options[:username] = 'www-data'

#before :deploy, :choose_tag
#after :deploy, :create_svn_release_tag

namespace :deploy do

	# Overwritten to provide flexibility for people who aren't using Rails.
	task :setup do

		# create release dir
		dirs = [deploy_app_to, "#{deploy_app_to}/releases/"] #, "/var/www/#{application}"
		run "umask 02 && mkdir -p #{dirs.join(' ')}"
		run "umask 02 && mkdir -p #{tmp_dir}"
		run "umask 02 && mkdir -p #{tmp_dir}/smarty_cache/"
		run "umask 02 && mkdir -p #{tmp_dir}/smarty_templates/"
		run "umask 02 && mkdir -p #{tmp_dir}/mtemplates/"
	end

	# Also overwritten to remove Rails-specific code.
	task :finalize_update do
	end

	task :update_code do
		exportApp()
		runRemote "cd #{deploy_app_to}/releases && chown -R www-data:www-data #{$app_export_dirname}"
		runRemote "cd #{deploy_app_to}/releases/#{$app_export_dirname} && cp bootstrap.lab.php bootstrap.php"
		runRemote "cd #{deploy_app_to}/ && rm -f current && ln -s releases/#{$app_export_dirname} current"
		runRemote "rm -f /var/www/#{application} && ln -s #{deploy_app_to}/current/web /var/www/#{application}"
	end

	# Each of the following tasks are Rails specific. They're removed.
	task :migrate do
	end

	task :migrations do
	end

	task :cold do
	end

	task :start do
	end

	task :stop do
	end

	task :symlink do
	end

	# Do nothing in apache (To restart apache, run 'cap deploy:apache:restart')
	task :restart do
		#recreate soft link
	end

	task :create_symlink do
	end
end

def getCurrentTime()
	if (!$currtime) then
		$currtime=`date +%Y%m%d%H%M%S`
		$currtime.strip!
	end
	return $currtime
end

def runLocal(cmd)
	printf("\n  - Running Locally: %s\n", cmd);
	system cmd
end

def runRemote(cmd)
	printf("\n  @ Running remotely: %s\n", cmd);
	run cmd
end

def exportApp()
	getCurrentTime()
	$app_export_dirname="#{$currtime}"
	runLocal "git archive --remote=#{repository} master | gzip > /tmp/#{$app_export_dirname}.tar.gz"
	runLocal "scp /tmp/#{$app_export_dirname}.tar.gz #{user}@#{domain}:/tmp/"
	runRemote "mkdir #{deploy_app_to}/releases/#{$app_export_dirname} && tar xzf /tmp/#{$app_export_dirname}.tar.gz -C #{deploy_app_to}/releases/#{$app_export_dirname} && rm /tmp/#{$app_export_dirname}.tar.gz"
end
