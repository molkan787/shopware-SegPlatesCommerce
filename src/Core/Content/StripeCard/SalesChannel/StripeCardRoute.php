<?php declare(strict_types=1);

namespace SegPlatesCommerce\Core\Content\StripeCard\SalesChannel;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Stripe\ShopwarePayment\Session\StripePaymentMethodSettings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @RouteScope(scopes={"store-api"})
 */
class StripeCardRoute extends AbstractStripeCardRoute
{
    /**
     * @var StripePaymentMethodSettings
     */
    private $stripePaymentMethodSettings;

    public function __construct(StripePaymentMethodSettings $stripePaymentMethodSettings)
    {
        $this->stripePaymentMethodSettings = $stripePaymentMethodSettings;
    }

    /**
     * @RouteScope(scopes={"store-api"})
     * @Route(
     *     "/store-api/v3/shopware-pwa/payment-method-settings",
     *     name="store-api.shopware-pwa.payment-method-settings",
     *     options={"seo"="false"},
     *     methods={"PATCH"},
     *     defaults={"XmlHttpRequest"=true}
     * )
     * @param Request $request
     * @return Response
     */
    public function updatePaymentMethodSettings(Request $request): Response
    {
        if ($request->get('card')) {
            $this->stripePaymentMethodSettings->setSelectedCard($request->get('card'));
            return new Response(json_encode($this->stripePaymentMethodSettings->getSelectedCard()), Response::HTTP_OK);
        }
        if ($request->get('saveCardForFutureCheckouts')) {
            $this->stripePaymentMethodSettings->setIsSaveCardForFutureCheckouts(
                $request->get('saveCardForFutureCheckouts')
            );
        }
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function getDecorated(): AbstractStripeCardRoute
    {
        throw new DecorationPatternException(self::class);
    }
}
