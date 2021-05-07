<?php declare(strict_types=1);

namespace SegPlatesCommerce\Core\Checkout\Payment\Controller;

use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Checkout\Payment\Exception\PaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\TokenExpiredException;
use Shopware\Core\Checkout\Payment\Exception\UnknownPaymentMethodException;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\Checkout\Payment\Cart\Token\TokenFactoryInterfaceV2;

class PaymentController extends AbstractController
{
    /**
     * @var PaymentService
     */
    private $paymentService;
    
    /**
     * @var TokenFactoryInterfaceV2
     */
    private $tokenFactory;

    private $salesChannelContextFactory;

    private $salesChannelContextPersister;

    public function __construct(PaymentService $paymentService,
                                SalesChannelContextFactory $salesChannelContextFactory,
                                SalesChannelContextPersister $salesChannelContextPersister,
                                TokenFactoryInterfaceV2 $tokenFactory
                            )
    {
        $this->paymentService = $paymentService;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->salesChannelContextPersister = $salesChannelContextPersister;
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * @Since("6.0.0.0")
     * @RouteScope(scopes={"store-api"})
     * @Route("/store-api/v3/payment/finalize-transaction", defaults={"auth_required"=false}, name="payment.finalize.transaction", methods={"GET", "POST"})
     *
     * @throws AsyncPaymentFinalizeException
     * @throws CustomerCanceledAsyncPaymentException
     * @throws InvalidTransactionException
     * @throws TokenExpiredException
     * @throws UnknownPaymentMethodException
     */
    public function finalizeTransaction(Request $request): Response
    {
        $paymentToken = $request->get('_sw_payment_token');
        $tokenStruct = $this->tokenFactory->parseToken($paymentToken);
        $finishUrl = $tokenStruct->getFinishUrl();
        $finishUrlParams = $this->getUrlParams($finishUrl);
        // die(json_encode($finishUrlParams));

        $contextToken = $finishUrlParams['contextToken'];
        $salesChannelId = $finishUrlParams['salesChannelId'];

        $payload = $this->salesChannelContextPersister->load($contextToken);
        $payload[SalesChannelContextService::LANGUAGE_ID] = "2fbb5fe2e29a4d70aa5854ce7ce3e20b";

        $salesChannelContext = $this->createSalesChannelContext($contextToken, $salesChannelId, $payload);
        // return new Response('finalizeTransaction', Response::HTTP_OK);

        $result = $this->paymentService->finalizeTransaction(
            $paymentToken,
            $request,
            $salesChannelContext
        );

        $exception = $result->getException();

        if ($exception !== null) {
            $url = $result->getErrorUrl();

            if ($url !== null && $exception instanceof PaymentProcessException) {
                $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'error-code=' . $exception->getErrorCode();

                return new RedirectResponse($url);
            }
        }

        if ($result->getFinishUrl()) {
            return new RedirectResponse($result->getFinishUrl());
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function createSalesChannelContext (string $token, string $salesChannelId, array $payload)
    {
        return $this->salesChannelContextFactory->create($token, $salesChannelId, $payload);
    }

    private function getUrlParams(string $url){
        $query = explode('?', $url);
        $paramsKVS = explode('&', $query[1]);
        $paramsKV = array_map(function ($kv){
            return explode('=', $kv);
        }, $paramsKVS);
        $params = array();
        foreach($paramsKV as $kv){
            $params[$kv[0]] = $kv[1];
        }
        return $params;
    }
}
