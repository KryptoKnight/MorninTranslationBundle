<?php

namespace Mornin\Bundle\TranslationBundle\Twig;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;

class TransTwig extends \Twig_Extension
{
    protected $container;
    protected $request;
    protected $locales;

    public function __construct(Container $container, RequestStack $request, $locales)
    {
        $this->container = $container;
        $this->request = $request->getCurrentRequest();
        $this->locales = $locales;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('mgtrans', [$this, 'fn_trans'], ["is_safe" => ["html" => true]]),
        );
    }

    /**
     * @param $translate
     * @param array $options
     * @param string $domain
     * @param null $locale
     * @return mixed
     * @throws \Exception
     */
    public function fn_trans($translate, array $options = [], $domain="messages", $locale=null)
    {

        $currentLocale = null;
        if($this->request->getLocale() !== null && $locale === null){
            $currentLocale = $this->request->getLocale();
        } else if($locale === null &&
            method_exists($this->request, "getSession") &&
            $this->request->getSession()->has("_locale")) {
            $currentLocale = $this->request->getSession()->get("_locale");
        }else if ($locale !== null){
            $currentLocale = $locale;
        }else{
            $currentLocale = $this->container->getParameter("default_locale");
        }

        return $this->container->get("twig")->render("MorninTranslationBundle:Twig:trans.html.twig", [
            "translate" => $translate,
            "options" => $options,
            "domain" => $domain,
            "locale" => $currentLocale,
            "locales" => $this->locales
        ]);

    }

    public function fn_modal()
    {

    }
}