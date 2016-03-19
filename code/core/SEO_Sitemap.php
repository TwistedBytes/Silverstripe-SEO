<?php

/**
 * Generates an HTML sitemap list
 *
 * @package silverstripe-seo
 * @license MIT License https://github.com/cyber-duck/silverstripe-seo/blob/master/LICENSE
 * @author  <andrewm@cyber-duck.co.uk>
 **/
class SEO_Sitemap {

    /**
     * @since version 1.2
     *
     * @var array $objects An array of objects with pages to include in the sitemap
     **/
    private $objects;

    /**
     * @since version 1.2
     *
     * @var string $url The URL to use for the current sitemap page
     **/
    private $url;

    /**
     * @since version 1.2
     *
     * @var string $xml The XML to output
     **/
    private $xml;

    /**
     * @since version 1.2
     *
     * @var string $html The HTML to output
     **/
    private $html;

    /**
     * Initialise config
     *
     * @since version 1.2
     *
     * @return void
     **/
    public function __construct()
    {
        $this->objects = Config::inst()->get('SEO_Sitemap', 'objects');

        $this->url = substr(Director::AbsoluteBaseURL(),0,-1);
    }

    /**
     * Return an encoded string compliant with XML sitemap standards
     *
     * @since version 1.2
     *
     * @param string $value A sitemap value to encode
     *
     * @return string
     **/
    public function Encode($value)
    {
        return trim(urlencode($value));
    }

    /**
     * Return the sitemap XML
     *
     * @since version 1.2
     *
     * @return void
     **/
    public function getSitemapXML()
    {
        return Controller::curr()->customise(array(
            'Pages' => $this->getPages(),
            'URL'  => $this->url
        ))->renderWith('SitemapXML');
    }

    /**
     * Return the sitemap HTML
     *
     * @since version 1.2
     *
     * @return string The sitemap HTML
     **/
    public function getSitemapHTML()
    {
        $pages = Page::get()->filter(array(
            'ClassName:not' => 'ErrorPage',
            'Robots:not' => 'noindex,nofollow',
            'ParentID' => 0
        ))->Sort('Sort','ASC');

        $this->getChildPages($pages);

        return $this->html;
    }

    /**
     * Merge an objects pages to the current page set
     *
     * @since version 1.2
     *
     * @return string
     **/
    private function getPages()
    {
        $pages = new ArrayList();
        foreach($this->objects as $object => $value){

            $object = $object::get();

            foreach($object as $page){
                $pages->push($page);
            }
        }
        $pages->Sort('Priority DESC');
        return $pages;
    }

    /**
     * Iterate through child pages
     *
     * @since version 1.2
     *
     * @param $pages
     *
     * @return void
     **/
    private function getChildPages($pages)
    {
        $this->html .= '<ul>';

        foreach($pages as $page){
            $this->html .= '<li><a href="'.$this->url.$page->Link().'">'.$page->Title.'</a>';

            foreach($this->objects as $className => $config){
                if($config['parent_id'] == $page->ID && $config['parent_id'] !== 0){
                    $pages = $className::get()->sort('Priority DESC');
                    $prefix = isset($config['prefix']) ? $config['prefix'] : false;
                    $this->getObjectPages($pages, $prefix);
                }
            }
            $children = Page::get()->filter(array(
                'ParentID' => $page->ID
            ))->Sort('ID','ASC');

            if($children) $this->getChildPages($children);

            $this->html .= '</li>';
        }

        $this->html .= '</ul>';

    }

    /**
     * 
     *
     * @since version 1.2
     *
     * @param $pages
     *
     * @return void
     **/
    private function getObjectPages($pages, $prefix = false)
    {
        $this->html .= '<ul>';

        $prefix = $prefix !== false ? '/'.$prefix.'/' : '';

        foreach($pages as $page):
            $this->html .= '<li><a href="'.$this->url.$prefix.$page->URLSegment.'">'.$page->Title.'</a></li>';
        endforeach;

        $this->html .= '</ul>';
    }
}