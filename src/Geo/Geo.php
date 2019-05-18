<?php

namespace Geo;

use Phalcon\Mvc\User\Component;

class Geo extends Component
{
    private $debug = false;

    private $path;

    private $urls = [];

    private $types = [];

    private $typesFiles = [
        'Country' => 'SxGeo.dat',
        'City' => 'SxGeoCity.dat',
        'Max' => 'SxGeoMax.dat',
    ];

    public function __construct()
    {
        // checking extensions
        $extension = [
            'phalcon',
            'curl',
            'zip'
        ];
        foreach ($extension as $item) {
            if (!extension_loaded($item)) {
                throw new GeoException('PHP ' . $item . ' module is not loaded');
            }
        }

        $this->path = $this->config->geo->path;

        if (empty($this->path))
            throw new GeoException('Error configuration');

        if (realpath($this->config->geo->path))
            $this->path = realpath($this->config->geo->path);
        if (!file_exists($this->path)) {
            mkpath($this->path, 0777, true);
            $this->path = realpath($this->config->geo->path);
        }

        if (!empty($this->config->geo->debug))
            $this->debug = (is_bool($this->config->geo->debug)) ? $this->config->geo->debug : false;

        $this->types = (!empty($this->config->geo->types)) ? $this->config->geo->types : 0;

        $this->urls = [
            'country' => 'https://sypexgeo.net/files/SxGeoCountry.zip',
            'city' => 'https://sypexgeo.net/files/SxGeoCity_utf8.zip',
        ];
    }

    /**
     * Download files by url or name
     *
     * downloadFiles() // all
     * downloadFiles('city') // city
     * downloadFiles(['city']) // city
     * downloadFiles('https://sypexgeo.net/files/SxGeoCity_utf8.zip') // city
     * downloadFiles(['https://sypexgeo.net/files/SxGeoCity_utf8.zip', 'country']) // city and country
     *
     * @param string|array $files
     */
    public function downloadFiles($files = null)
    {
        $urls = [];
        if (!empty($files)) { // is array
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (!empty($this->urls[$file])) {
                        $urls[] = $this->urls[$file];
                    } else {
                        $urls[] = $file;
                    }
                }
            } else { // is string
                if (!empty($this->urls[$files])) {
                    $urls[] = $this->urls[$files];
                } else {
                    $urls[] = $files;
                }
            }
        }

        if (empty($urls)) {
            foreach ($this->urls as $url) {
                $urls[] = $url;
            }
        }

        foreach ($urls as $url) {
            if ($this->debug) {
                echo "******************************\n";
                echo "Start download...\n";
                echo "URL: " . $url . "\n";
                echo "Path: " . $this->path . "\n";
            }
            $this->downloadFile($url);
            if ($this->debug) {
                echo "******************************\n";
            }
        }
    }

    /**
     * Download zip file
     *
     * downloadFile('https://sypexgeo.net/files/SxGeoCity_utf8.zip')
     *
     * @param $url
     */
    public function downloadFile($url)
    {
        set_time_limit(600);

        chdir($this->path);

        preg_match("/(Country|City|Max)/", pathinfo($url, PATHINFO_BASENAME), $m);
        $type = $m[1];
        $dat_file = $this->typesFiles[$type];

        $last_updated_file = $this->path . '/lastUpdate_' . $type . '.upd';

        if ($this->debug) echo "Download the archive from the server...";
        $fp = fopen($this->path . '/SxGeoTmp.zip', 'wb');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_HTTPHEADER => file_exists($last_updated_file) ? ["If-Modified-Since: " . file_get_contents($last_updated_file)] : [],
        ]);
        if (!curl_exec($ch))
            throw new GeoException('Error downloading archive');
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if ($code == 304) {
            @unlink($this->path . '/SxGeoTmp.zip');
            if ($this->debug) echo "\nThe archive has not been updated since the last download.\n";
            exit;
        } else {
            if ($this->debug) echo "completed\n";
        }

        $fp = fopen('zip://' . $this->path . '/SxGeoTmp.zip#' . $dat_file, 'rb');
        $fw = fopen($dat_file, 'wb');
        if (!$fp)
            throw new GeoException('Don\'t open archive');

        if ($this->debug) echo "Unpack the archive...";
        stream_copy_to_stream($fp, $fw);
        fclose($fp);
        fclose($fw);
        if (filesize($dat_file) == 0)
            throw new GeoException('Error unpacking the archive');

        @unlink($this->path . '/SxGeoTmp.zip');
        file_put_contents($last_updated_file, gmdate('D, d M Y H:i:s') . ' GMT');
        if ($this->debug) echo "completed\n";

        if ($this->debug) echo "File moved to {$this->path}/{$dat_file}\n";
    }

    /**
     * Open city base
     *
     * @return SxGeo
     */
    public function getCity()
    {
        $SxGeoClass = new SxGeo($this->path . '/' . $this->typesFiles['City'], $this->types);

        return $SxGeoClass;
    }

    /**
     * Open country base
     *
     * @return SxGeo
     */
    public function getCountry()
    {
        $SxGeoClass = new SxGeo($this->path . '/' . $this->typesFiles['Country'], $this->types);

        return $SxGeoClass;
    }
}