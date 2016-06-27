<?php

use Foolz\FoolFrame\Model\Autoloader;
use Foolz\FoolFrame\Model\Context;
use Foolz\FoolFuuka\Model\RadixCollection;
use Foolz\Plugin\Event;
use Symfony\Component\Routing\Route;

class HHVM_Popup
{
    public function run()
    {
        Event::forge('Foolz\Plugin\Plugin::execute#foolz/foolfuuka-plugin-popup-report')
            ->setCall(function ($plugin) {
                /** @var Context $context */
                $context = $plugin->getParam('context');

                /** @var Autoloader $autoloader */
                $autoloader = $context->getService('autoloader');
                $autoloader->addClass('Foolz\FoolFuuka\Controller\Chan\PopupReport', __DIR__.'/classes/controller/chan.php');

                Event::forge('Foolz\FoolFrame\Model\Context::handleWeb#obj.routing')
                    ->setCall(function ($result) use ($context) {
                        $radix_collection = $context->getService('foolfuuka.radix_collection');
                        $radices = $radix_collection->getAll();
                        $routes = $result->getObject();

                        foreach ($radices as $radix) {
                            $routes->getRouteCollection()->add(
                                'foolfuuka.plugin.popup-report.chan.radix.'.$radix->shortname, new Route(
                                    '/'.$radix->shortname.'/report/{_suffix}',
                                    [
                                        '_controller' => '\Foolz\FoolFuuka\Controller\Chan\PopupReport::report',
                                        '_default_suffix' => '',
                                        '_suffix' => '',
                                        'radix_shortname' => $radix->shortname
                                    ],
                                    [
                                        '_suffix' => '.*'
                                    ]
                                )
                            );
                        }
                    });
            });
    }
}

(new HHVM_Popup())->run();
