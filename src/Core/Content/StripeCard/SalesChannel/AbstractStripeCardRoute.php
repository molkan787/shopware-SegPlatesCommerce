<?php declare(strict_types=1);

namespace SegPlatesCommerce\Core\Content\StripeCard\SalesChannel;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractStripeCardRoute
{
    abstract public function getDecorated(): AbstractStripeCardRoute;

    abstract public function updatePaymentMethodSettings(Request $request): Response;
}