lock "~> 3.17"

set :application, "brodev-store"
set :repo_url, "git@github.com:username/brodev-store.git"

# Default branch
set :branch, "main"

# Default deploy_to directory is /var/www/my_app_name
set :deploy_to, "/var/www/brodev-store"

# Linked files and directories that persist between releases
append :linked_files, ".env"
append :linked_dirs, "storage/app", "storage/framework/cache", "storage/framework/sessions", "storage/framework/views", "storage/logs", "bootstrap/cache"

# Keep only the last 5 releases
set :keep_releases, 5

# Custom tasks for Laravel deployment
namespace :laravel do
  desc "Run Composer Install"
  task :composer_install do
    on roles(:web) do
      within release_path do
        execute :composer, "install --no-dev --optimize-autoloader --no-interaction --prefer-dist"
      end
    end
  end

  desc "Run NPM Install and Build"
  task :npm_build do
    on roles(:web) do
      within release_path do
        execute :npm, "install"
        execute :npm, "run build"
      end
    end
  end

  desc "Run Database Migrations"
  task :migrate do
    on roles(:db) do
      within release_path do
        execute :php, "artisan migrate --force"
      end
    end
  end

  desc "Clear and Cache Laravel Configuration"
  task :optimize do
    on roles(:web) do
      within release_path do
        execute :php, "artisan optimize"
      end
    end
  end

  desc "Set Permissions"
  task :set_permissions do
    on roles(:web) do
      execute :chmod, "-R 775 #{release_path}/storage"
      execute :chmod, "-R 775 #{release_path}/bootstrap/cache"
    end
  end
end

namespace :deploy do
  after :updated, "laravel:composer_install"
  after :updated, "laravel:npm_build"
  after :updated, "laravel:set_permissions"
  after :compile_assets, "laravel:migrate"
  after :publishing, "laravel:optimize"
  
  desc "Reload PHP-FPM to clear OPcache"
  task :reload_php do
    on roles(:web) do
      # Reload PHP-FPM to refresh PHP OPcache for zero-downtime
      execute :sudo, "systemctl reload php8.3-fpm"
    end
  end
  after :finished, "deploy:reload_php"
end
