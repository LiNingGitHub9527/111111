<?php

namespace App\Services;

use App\Support\SimpleHtmlDom\SimpleHtmlDom;

class HtmlService
{
    private static $instance = null;

    public static function instance(): HtmlService
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $instance = new self();
        self::$instance = $instance;
        return $instance;
    }

    public function getLayoutRenderHtml($layoutHtml, $componentHtml)
    {
        $dom = new SimpleHtmlDom();
        $html = $dom->str_get_html($componentHtml);
        $dom->load($html);
        $element = $dom->find('div[nocode-component-container=container]', 0);
        $renderHtml = $layoutHtml;
        if (!empty($element)) {
            $element->innertext = $layoutHtml;
            $renderHtml = $dom->innertext;
        }

        return $renderHtml;
    }
}
