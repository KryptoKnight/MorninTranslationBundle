<?php

namespace Mornin\Bundle\TranslationBundle\Controller;

use Mornin\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class TranslationController extends Controller
{
    /**
     * Display an overview of the translation status per domain.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function overviewAction()
    {
        /** @var StorageInterface $storage */
        $storage = $this->get('Mornin_translation.translation_storage');

        $stats = $this->get('Mornin_translation.overview.stats_aggregator')->getStats();

        return $this->render('MorninTranslationBundle:Translation:overview.html.twig', array(
            'layout'         => $this->container->getParameter('Mornin_translation.base_layout'),
            'locales'        => $this->getManagedLocales(),
            'domains'        => $storage->getTransUnitDomains(),
            'latestTrans'    => $storage->getLatestUpdatedAt(),
            'stats'          => $stats,
        ));
    }

    /**
     * Display the translation grid.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function gridAction()
    {
        $tokens = null;
        if ($this->container->getParameter('Mornin_translation.dev_tools.enable')) {
            $tokens = $this->get('Mornin_translation.token_finder')->find();
        }

        return $this->render('MorninTranslationBundle:Translation:grid.html.twig', array(
            'layout'         => $this->container->getParameter('Mornin_translation.base_layout'),
            'inputType'      => $this->container->getParameter('Mornin_translation.grid_input_type'),
            'autoCacheClean' => $this->container->getParameter('Mornin_translation.auto_cache_clean'),
            'toggleSimilar'  => $this->container->getParameter('Mornin_translation.grid_toggle_similar'),
            'locales'        => $this->getManagedLocales(),
            'tokens'         => $tokens,
        ));
    }

    /**
     * Remove cache files for managed locales.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function invalidateCacheAction(Request $request)
    {
        $this->get('translator')->removeLocalesCacheFiles($this->getManagedLocales());

        $message = $this->get('translator')->trans('translations.cache_removed', array(), 'MorninTranslationBundle');

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(array('message' => $message));
        }

        $this->get('session')->getFlashBag()->add('success', $message);

        return $this->redirect($this->generateUrl('mornin_translation_grid'));
    }

    /**
     * Remove domain/s without key
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeDomainAction(Request $request)
    {
        $message = $this->get('translator')->trans('translations.domain_removed', array(), 'MorninTranslationBundle');
        $storage = $this->get('Mornin_translation.translation_storage');
        if ($storage->removeDomain()) {
            return new JsonResponse(array('message' => $message));
        }

        $this->get('session')->getFlashBag()->add('success', $message);

        return $this->redirect($this->generateUrl('mornin_translation_grid'));
    }

    /**
     * Add a new trans unit with translation for managed locales.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $handler = $this->get('Mornin_translation.form.handler.trans_unit');
        $params  = $request->request->all();

        $defaults = array('domain' => isset($params['domain']) ? $params['domain'] : '');

        $form = $this->createForm('Mornin\Bundle\TranslationBundle\Form\Type\TransUnitType',
            $handler->createFormData(),
            $handler->getFormOptions($defaults));

        if ($handler->process($form, $request)) {
            $message = $this->get('translator')->trans('translations.successfully_added', array(), 'LexikTranslationBundle');

            $this->get('session')->getFlashBag()->add('success', $message);

            $redirectUrl = $form->get('save_add')->isClicked() ? 'mornin_translation_new' : 'mornin_translation_grid';

            if(!$form->get('save_add')->isClicked()){
                return $this->redirect($this->generateUrl($redirectUrl));
            }
        }

        return $this->render('MorninTranslationBundle:Translation:new.html.twig', array(
            'layout' => $this->container->getParameter('Mornin_translation.base_layout'),
            'form'   => $form->createView(),
        ));
    }


    /**
     * Add a new domain.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function domainAction(Request $request)
    {
        $params  = $request->request->all();
        $storage = $this->get('Mornin_translation.translation_storage');
        try{
            if($request->isMethod("POST")) {
                if (!empty($params) && $storage->addDomain($params['domain'])) {
                     $message = $this->get('translator')->trans('translations.successfully_domain_added', array(), 'LexikTranslationBundle');
                     $this->get('session')->getFlashBag()->add('success', $message);
                }else {
                    $message = $this->get('translator')->trans('translations.domain_failed', array(), 'LexikTranslationBundle');
                    $this->get('session')->getFlashBag()->set('error', $message);
                }
            }
        }catch(\Exception $exception){
            $message = $this->get('translator')->trans('translations.domain_failed', array(), 'LexikTranslationBundle');
            $this->get('session')->getFlashBag()->set('error', $message);
        }

        return $this->render('MorninTranslationBundle:Translation:domain.html.twig', array(
            'layout' => $this->container->getParameter('Mornin_translation.base_layout'),
            'domains'        => $storage->getTransUnitDomains(),
        ));
    }

    /**
     * Returns managed locales.
     *
     * @return array
     */
    protected function getManagedLocales()
    {
        return $this->get('Mornin_translation.locale.manager')->getLocales();
    }
}