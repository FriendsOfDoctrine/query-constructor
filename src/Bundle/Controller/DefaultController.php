<?php

namespace FOD\QueryConstructor\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/fod_query_constructor/", name="fod.query_constructor.index")
     *
     * @return JsonResponse
     */
    public function indexAction()
    {
        return new JsonResponse([
            'result' => 'success',
            'entities' => $this->get('query_constructor.registry')->getEntityTitles(),
            'aggregateFunctions' => $this->get('query_constructor.creator')->getAggregateFunctionTitles(),
        ]);
    }

    /**
     * @Route("/fod_query_constructor/properties/", name="fod.query_constructor.properties")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function propertiesAction(Request $request)
    {
        try {
            $properties = $this->get('query_constructor.registry')->get($request->get('entity'));
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'result' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return new JsonResponse([
            'result' => 'success',
            'properties' => $properties,
        ]);
    }
}
