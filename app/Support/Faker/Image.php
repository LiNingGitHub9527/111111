<?php

namespace App\Support\Faker;

class Image
{

    /**
     * Generate the URL that will return a random image
     *
     * Set randomize to false to remove the random GET parameter at the end of the url.
     *
     * @example 'https://via.placeholder.com/640x480/?12345'
     *
     * @param integer $width
     * @param integer $height
     * @param bool $randomize
     *
     * @return string
     */
    public static function simpleImageUrl($width = 640, $height = 480, $randomize = true): string
    {
        $baseUrl = "https://via.placeholder.com/";
        $url = "{$width}x{$height}/";

        if ($randomize) {
            $url .= '?' . mt_rand();
        }

        return $baseUrl . $url;
    }

    /**
    * Generate the URL that will return a random image
    *
    * Set randomize to false to remove the random GET parameter at the end of the url.
    *
    * @example 'https://picsum.photos/id/237/200/300'
    *
    * @param integer $width
    * @param integer $height
    * @param string|null $_category NOT USED
    * @param bool $randomize
    * @param string|null $_word NOT USED
    * @param bool $gray
    *
    * @return string
    * @see vendor/fzaninotto/faker/src/Faker/Provider/Image.php > imageUrl
    */
    public static function imageUrl($width = 640, $height = 480, $_category = null, $randomize = true, $_word = null, $gray = false)
    {
        $baseUrl = 'https://picsum.photos/';
        $url = '';

        if (!$randomize) {
          $url .= 'id/' . rand(1, 1000) . '/';
        }

        $url .= "$width/$height/";

        if ($gray) {
          $url .= '?grayscale';
        }

        if ($randomize) {
          $url .= str_contains($url, '?') ? '&' : '?';
          $url .= 'random=' . rand(1, 1000);
        }

        return $baseUrl . $url;
    }

    /**
     * Download a remote random image to disk and return its location
     *
     * Requires curl, or allow_url_fopen to be on in php.ini.
     *
     * @param null $dir
     * @param int $width
     * @param int $height
     * @param bool $fullPath
     * @param bool $randomize
     * @return bool|\RuntimeException|string
     * @example '/path/to/dir/13b73edae8443990be1aa8f1a483bc27.jpg'
     */
    public static function image($dir = null, $width = 640, $height = 480, $fullPath = true, $randomize = true)
    {
        $dir = is_null($dir) ? sys_get_temp_dir() : $dir; // GNU/Linux / OS X / Windows compatible
        // Validate directory path
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to directory "%s"', $dir));
        }

        // Generate a random filename. Use the server address so that a file
        // generated at the same time on a different server won't have a collision.
        $name = md5(uniqid(empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'], true));
        $filename = $name .'.jpg';
        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

        $url = static::imageUrl($width, $height, $randomize);

        // save file
        if (function_exists('curl_exec')) {
            // use cURL
            $fp = fopen($filepath, 'w');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
            fclose($fp);
            curl_close($ch);

            if (!$success) {
                unlink($filepath);

                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        } elseif (ini_get('allow_url_fopen')) {
            // use remote fopen() via copy()
            $success = copy($url, $filepath);
        } else {
            return new \RuntimeException('The image formatter downloads an image from a remote HTTP server. Therefore, it requires that PHP can request remote hosts, either via cURL or fopen()');
        }

        return $fullPath ? $filepath : $filename;
    }

}
