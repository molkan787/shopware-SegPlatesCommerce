<?php declare(strict_types=1);

namespace SegPlatesCommerce\Core\Content\Order;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class OrderTrackingRoute extends AbstractController
{

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    public function __construct(EntityRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @RouteScope(scopes={"store-api"})
     * @Route("/store-api/v3/order-tracking/get-tracking-details", name="order-tracking.details", methods={"GET"})
     */
    public function getTrackingDetails(Request $request): Response
    {
        $email = $request->get('email');
        $orderNo = $request->get('orderNo');

        if(empty($email) || empty($orderNo)){
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        $order = $this->fetchOrderByNoAndEmail($email, $orderNo);
        if(empty($order)){
            return new JsonResponse(array('error' => 'not_found'), Response::HTTP_OK);
        }else{
            $trackDetails = $this->getOrderTrackingDetails($order);
            return new JsonResponse($trackDetails, Response::HTTP_OK);
        }

    }

    private function fetchOrderByNoAndEmail($email, $orderNo){
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('orderNumber', $orderNo));
        $criteria->addFilter(new EqualsFilter('orderCustomer.email', $email));
        $criteria->addAssociation('deliveries.shippingMethod');

        $order = $this->orderRepository->search($criteria, Context::createDefaultContext())->first();

        return $order;
    }

    private function getOrderTrackingDetails($order){
        $status = $order->getStateMachineState()->getName();
        $deliveryStatus = '';
        $trackingLink = null;
        $delivery = $order->getDeliveries()->first();
        if(!empty($delivery)){
            $deliveryStatus = $delivery->getStateMachineState()->getName();
            $delStatus = $delivery->getStateMachineState()->getTechnicalName();
            $isShipped = ($delStatus == 'shipped' || $delStatus == 'shipped_partially');
            if($isShipped){
                $trackingCode = $delivery->getTrackingCodes()[0];
                $trackingUrl = $delivery->getShippingMethod()->getTrackingUrl();
                if(!empty($trackingCode) && !empty($trackingUrl)){
                    $trackingLink = str_replace('%s', $trackingCode, $trackingUrl);
                }
            }
        }
        return array(
            'orderNumber' => $order->getOrderNumber(),
            'status' => $status,
            'deliveryStatus' => $deliveryStatus,
            'trackingLink' => $trackingLink
        );
        
    }

}
