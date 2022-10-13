<?php

namespace TeamTeaTime\Forum;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use TeamTeaTime\Forum\Console\Commands\Seed;
use TeamTeaTime\Forum\Console\Commands\SyncStats;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Post;
use TeamTeaTime\Forum\Models\Thread;

class ForumServiceProvider extends ServiceProvider
{
    public function boot(Router $router, GateContract $gate)
    {
        $this->publishes([
            __DIR__.'/../config/api.php' => config_path('forum.api.php'),
            __DIR__.'/../config/web.php' => config_path('forum.web.php'),
            __DIR__.'/../config/general.php' => config_path('forum.general.php'),
            __DIR__.'/../config/integration.php' => config_path('forum.integration.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'migrations');

        $this->publishes([
            __DIR__.'/../translations/' => resource_path('lang/vendor/forum'),
        ], 'translations');

        foreach (['api', 'web', 'general', 'integration'] as $name) {
            $this->mergeConfigFrom(__DIR__."/../config/{$name}.php", "forum.{$name}");
        }

        if (config('forum.api.enable')) {
            $router->group(config('forum.api.router'), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
            });

            $this->registerApiRouteBindings();
        }

        if (config('forum.web.enable')) {
            $this->publishes([
                __DIR__.'/../views/' => resource_path('views/vendor/forum'),
            ], 'views');

            $router->group(config('forum.web.router'), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });

            $this->registerWebRouteBindings();

            $this->loadViewsFrom(__DIR__.'/../views', 'forum');
        }

        $this->loadTranslationsFrom(__DIR__.'/../translations', 'forum');

        $this->registerPolicies($gate);

        // Make sure Carbon's locale is set to the application locale
        Carbon::setLocale(config('app.locale'));

        $loader = AliasLoader::getInstance();
        $loader->alias('Forum', config('forum.web.utility_class'));

        View::composer('forum::master', function ($view) {
            if (Auth::check()) {
                $nameAttribute = config('forum.integration.user_name');
                $view->username = Auth::user()->{$nameAttribute};
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                Seed::class,
                SyncStats::class,
            ]);
        }
    }

    private function registerPolicies(GateContract $gate)
    {
        $forumPolicy = config('forum.integration.policies.forum');
        foreach (get_class_methods(new $forumPolicy()) as $method) {
            $gate->define($method, "{$forumPolicy}@{$method}");
        }

        foreach (config('forum.integration.policies.model') as $model => $policy) {
            $gate->policy($model, $policy);
        }
    }

    private function registerApiRouteBindings()
    {
        Route::bind('category', function ($value) {
            return Category::find($value);
        });

        Route::bind('thread', function ($value) {
            $query = Thread::with('category');

            if (Gate::allows('viewTrashedThreads')) {
                $query->withTrashed();
            }

            return $query->find($value);
        });

        Route::bind('post', function ($value) {
            $query = Post::with(['thread', 'thread.category']);

            if (Gate::allows('viewTrashedPosts')) {
                $query->withTrashed();
            }

            return $query->find($value);
        });
    }

    private function registerWebRouteBindings()
    {
        Route::bind('category', function ($value) {
            return Category::findOrFail($value);
        });

        Route::bind('thread', function ($value) {
            $query = Thread::with('category');

            if (Gate::allows('viewTrashedThreads')) {
                $query->withTrashed();
            }

            $thread = $query->find($value);

            if ($thread === null) {
                abort(404);
            }

            return $thread;
        });

        Route::bind('post', function ($value) {
            $query = Post::with(['thread', 'thread.category']);

            if (Gate::allows('viewTrashedPosts')) {
                $query->withTrashed();
            }

            $post = $query->find($value);

            if ($post === null) {
                abort(404);
            }

            return $post;
        });
    }
}
