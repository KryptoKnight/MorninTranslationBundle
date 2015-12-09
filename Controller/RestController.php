<?php

namespace Mornin\Bundle\TranslationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class RestController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction(Request $request)
    {
        list($transUnits, $count) = $this->get('Mornin_translation.data_grid.request_handler')->getPage($request);

        return $this->get('Mornin_translation.data_grid.formatter')->createListResponse($transUnits, $count);
    }

    /**
     * @param Request $request
     * @param $token
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listByProfileAction(Request $request, $token)
    {
        list($transUnits, $count) = $this->get('Mornin_translation.data_grid.request_handler')->getPageByToken($request, $token);

        return $this->get('Mornin_translation.data_grid.formatter')->createListResponse($transUnits, $count);
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function updateAction(Request $request, $id)
    {
        if (!$request->isMethod('PUT')) {
            throw $this->createNotFoundException(sprintf('Invalid request method %s, PUT only.', $request->getMethod()));
        }

        $transUnit = $this->get('Mornin_translation.data_grid.request_handler')->updateFromRequest($id, $request);

        return $this->get('Mornin_translation.data_grid.formatter')->createSingleResponse($transUnit);
    }
}
