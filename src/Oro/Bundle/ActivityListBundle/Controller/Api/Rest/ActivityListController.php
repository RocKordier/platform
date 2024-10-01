<?php

namespace Oro\Bundle\ActivityListBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The REST API resources for the activity lists.
 */
class ActivityListController extends RestController
{
    /**
     * Get filtered activity lists for given entity
     *
     * @ApiDoc(
     *      description="Returns an array with collection of ActivityList objects and count of all records",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *      }
     * )
     * @param Request $request
     * @param string  $entityClass Entity class name
     * @param integer $entityId    Entity id
     * @return JsonResponse
     */
    #[QueryParam(
        name: 'pageFilter',
        description: 'Array with pager filters, e.g. [first|last item date, array of ids with same date, action type]',
        nullable: true
    )]
    #[QueryParam(
        name: 'filter',
        description: 'Array with Activity type and Date range filters values',
        nullable: true
    )]
    public function cgetAction(Request $request, $entityClass, $entityId)
    {
        $entityClass = $this->container->get('oro_entity.routing_helper')->resolveEntityClass($entityClass);
        $filter      = $request->get('filter');
        $pageFilter  = $request->get('pageFilter', []);

        $results = $this->getManager()->getListData(
            $entityClass,
            $entityId,
            $filter,
            $pageFilter
        );

        return new JsonResponse($results);
    }

    /**
     * Get ActivityList single object
     *
     * @param integer $entityId Entity id
     *
     * @ApiDoc(
     *      description="Returns an ActivityList object",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *          404="Activity association was not found",
     *      }
     * )
     * @return Response
     */
    public function getActivityListItemAction(int $entityId)
    {
        $activityListEntity = $this->getManager()->getItem($entityId);
        if (!$activityListEntity) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($activityListEntity);
    }

    /**
     * Get ActivityList option
     *
     * @ApiDoc(
     *      description="Returns ActivityList option",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *      }
     * )
     * @return Response
     */
    public function getActivityListOptionAction()
    {
        $results = $this->getActivityListProvider()->getActivityListOption($this->container->get('oro_config.user'));

        return new JsonResponse($results);
    }

    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_activity_list.manager');
    }

    #[\Override]
    public function getForm()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }

    #[\Override]
    public function getFormHandler()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }

    /**
     * @return ActivityListChainProvider
     */
    protected function getActivityListProvider()
    {
        return $this->container->get('oro_activity_list.provider.chain');
    }
}
