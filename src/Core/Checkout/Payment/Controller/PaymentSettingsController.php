<?php declare(strict_types=1);

namespace SegPlatesCommerce\Core\Checkout\Payment\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class PaymentSettingsController extends AbstractController
{

    private const CONFIG_DOMAIN = 'StripeShopwarePayment.config';

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @RouteScope(scopes={"store-api"})
     * @Route("/store-api/v3/payment/settings/stripe", name="payment.settings.stripe", methods={"GET"})
     */
    public function getStripeSettings(Request $request): Response
    {
        $rawConfig = $this->systemConfigService->get(self::CONFIG_DOMAIN, null);

        $config = array(
            'stripePublicKey' => $rawConfig['stripePublicKey']
        );

        return new JsonResponse($config, Response::HTTP_OK);
    }

}
