<?php
/**
 * Created by PhpStorm.
 * User: darrjone
 * Date: 17/07/2018
 * Time: 15:06
 */

namespace Mornin\Bundle\TranslationBundle\Controller;


use Mornin\Bundle\TranslationBundle\Entity\Translation;
use Mornin\Bundle\TranslationBundle\Entity\TransUnit;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;

class TwigController extends Controller
{
    /**
     * @param $key
     * @param $domain
     * @return Response
     */
    public function getTranslationAction($key, $domain)
    {
        try {
            /**
             * @var TransUnit $transUnit
             */
            $transUnit = $this->getDoctrine()->getRepository(TransUnit::class)->findOneBy([
                "key" => $key,
                "domain" => $domain
            ]);

            if (!$transUnit instanceof TransUnit) {
                throw new \Exception("Trans Unit not found with key: {$key} and domain: {$domain}");
            }

            $translations = [];
            foreach ($transUnit->getTranslations() as $translation) {
                /**
                 * @var Translation $translation
                 */
                $translations[$translation->getLocale()] = $translation->getContent();
            }

            $translations["id"] = $transUnit->getId();

            return new JsonResponse($translations, 200);
        }catch(\Exception $e){
            return new JsonResponse($e->getMessage(), 404);
        }
    }

    /**
     * @param $data
     * @return Response
     */
    public function serializeResult($data)
    {
        /**
         * @var Serializer $serializer
         */
        $serializer = $this->get("serializer");
        $result = $serializer->serialize($data, "json");
        return new Response($result, 200);
    }
}