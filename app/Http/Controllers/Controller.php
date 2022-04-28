<?php

namespace App\Http\Controllers;

use Auth;
use Lang;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Jenssegers\Agent\Agent;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $guard = '';

    protected $viewPathPrefix = '';

    protected $user = null;

    protected $agent = null;

    protected $isMobile = -1;

    // page custom setting

    protected $pageTitle = '';

    protected $pageTitleParams = [];

    protected $pageDescription = '';

    protected $pageDescriptionParams = [];

    protected $pageKeywords = '';

    protected $pageKeywordsParams = [];

    protected $pageCanonical = '';

    public function success($data = null, $message = 'SUCCESS', $code = 200)
    {
        return response()->json([
            'code' => $code,
            'status' => 'OK',
            'data' => $data,
            'message' => $message,
        ], 200);
    }

    public function error($message = null, $codeStatus = 400, $codeHeader = 200)
    {
        return response()->json([
            'code' => $codeStatus,
            'status' => 'FAIL',
            'message' => $message,
        ], $codeHeader);
    }

    protected function guard()
    {
        return Auth::guard($this->guard);
    }

    protected function authed()
    {
        return $this->guard()->check();
    }

    protected function user()
    {
        if (!empty($this->user)) {
            return $this->user;
        }
        if (!empty($guard = $this->guard())) {
            $this->user = $guard->user();
        }
        return $this->user;
    }

    protected function initPage()
    {
        if ($this->guard == '') {
            return;
        }

        if (request()->ajax()) {
            return;
        }

        if (!empty($route = request()->route()) && !empty($as = $route->getName())) {
            $path = 'webtdk-' . $this->guard . '.' . $as;
            $defaultPath = 'webtdk-' . $this->guard . '.default';
            if (!Lang::has($path)) {
                $path = $defaultPath;
            }
            $pageInfo = trans($path);
            $defaultPageInfo = trans($defaultPath);
            if (!empty($this->pageTitle)) {
                $pageInfo['title'] = $this->pageTitle;
            }
            if (!empty($this->pageTitleParams)) {
                foreach ($this->pageTitleParams as $k => $v) {
                    $pageInfo['title'] = str_replace('%'.$k.'%', $v, $pageInfo['title']);
                }
            }
            if (!empty($this->pageDescription)) {
                $pageInfo['description'] = $this->pageDescription;
            }
            if (!empty($this->pageDescriptionParams)) {
                foreach ($this->pageDescriptionParams as $k => $v) {
                    $pageInfo['description'] = str_replace('%'.$k.'%', $v, $pageInfo['description']);
                }
            }
            if (!empty($this->pageKeywords)) {
                $pageInfo['keywords'] = $this->pageKeywords;
            }
            if (!empty($this->pageKeywordsParams)) {
                foreach ($this->pageKeywordsParams as $k => $v) {
                    $pageInfo['keywords'] = str_replace('%'.$k.'%', $v, $pageInfo['keywords']);
                }
            }
            if (!isset($pageInfo['title']) || empty($pageInfo['title'])) {
                $pageInfo['title'] = $defaultPageInfo['title'];
            } else {
                $pageInfo['title'] = str_replace('%default%', $defaultPageInfo['title'], $pageInfo['title']);
            }
            if (!isset($pageInfo['description']) || empty($pageInfo['description'])) {
                $pageInfo['description'] = $defaultPageInfo['description'];
            } else {
                $pageInfo['description'] = str_replace('%default%', $defaultPageInfo['description'], $pageInfo['description']);
            }
            if (!isset($pageInfo['keywords']) || empty($pageInfo['keywords'])) {
                $pageInfo['keywords'] = $defaultPageInfo['keywords'];
            } else {
                $pageInfo['keywords'] = str_replace('%default%', $defaultPageInfo['keywords'], $pageInfo['keywords']);
            }
            $page = [
                'lang' => app()->getLocale(),
                'url' => request()->url(),
                'host' => request()->getHost(),
                'title' => $pageInfo['title'] ?? '',
                'description' => $pageInfo['description'] ?? '',
                'keywords' => $pageInfo['keywords'] ?? '',
                'canonical' => $this->pageCanonical,
            ];
            view()->composer($this->guard . '.*', function ($view) use ($page) {
                $view->with('__page', $page);
            });
        }
    }

    protected function handleView($view)
    {
        //todo
    }

    protected function view($viewPath, $data = [], $mergeData = [])
    {
        if (!empty($this->viewPathPrefix)) {
            $viewPath = $this->viewPathPrefix . '.' . $viewPath;
        }

        $view = view($viewPath, $data, $mergeData);

        $this->initPage();

        $this->handleView($view);

        return $view;
    }

    protected function getViewData($key, $default = null)
    {
        $view = view();
        if ($view->offsetExists($key)) {
            return $view->offsetGet($key);
        }

        return $default;
    }

    protected function setViewData($key, $value)
    {
        $view = view();
        $view->with($key, $value);
        return $view;
    }

    protected function agent()
    {
        if (empty($this->agent)) {
            $this->agent = new Agent();
        }
        return $this->agent;
    }

    protected function isMobile()
    {
        if ($this->isMobile == -1) {
            $this->isMobile = $this->agent()->isMobile() ? 1 : 0;
        }
        return ($this->isMobile == 1);
    }
}
