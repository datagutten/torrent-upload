<?php

namespace torrentupload\tests;

use torrentupload\site;

class TestSite extends site
{
    public static string $site_slug = 'test';
    public static string $site_url = 'https://httpbin.org';

    public function getSiteFolder(): bool|string
    {
        return $this->site_folder;
    }
}