<?php
namespace MirSport;

final class CheckoutConsent
{
    public static function validate($order, array &$userResult, $request, array &$params, array &$result): void
    {
        // Validate only storefront saves; recalculation and CommerceML use other paths.
        if (!$request->isPost() || ($request->get('action') !== 'saveOrderAjax' && $request->get('confirmorder') !== 'Y')) {
            return;
        }
        $consents = $params['USER_CONSENTS'] ?? [];
        $submitted = $request->get('userConsents');
        $submitted = is_array($submitted) ? $submitted : [];
        $required = [];
        foreach ($consents as $consent) {
            if (($consent['REQUIRED'] ?? 'N') === 'Y') {
                $required[] = (int)$consent['ID'];
            }
        }
        // The project's checkout cannot save with consent disabled or unconfigured.
        if (($params['USER_CONSENT'] ?? 'N') !== 'Y' || !$required) {
            $result['ERROR']['MAIN'][] = 'Оформление временно недоступно: согласие не настроено.';
            return;
        }
        foreach ($required as $id) {
            $accepted = false;
            foreach ($submitted as $consent) {
                if (is_array($consent) && (int)($consent['id'] ?? 0) === $id && ($consent['checked'] ?? 'N') === 'Y') {
                    $accepted = true;
                    break;
                }
            }
            if (!$accepted) {
                $result['ERROR']['MAIN'][] = 'Подтвердите согласие, чтобы оформить заказ';
                return;
            }
        }
    }
}
