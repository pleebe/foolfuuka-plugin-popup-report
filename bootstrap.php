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
                $autoloader->addClassMap([
                    'Foolz\FoolFuuka\Controller\Chan\PopupReport' => __DIR__.'/classes/controller/chan.php',
                    'Foolz\FoolFrame\Controller\Admin\Plugins\ReportsAdmin' => __DIR__ . '/classes/controller/admin.php',
                ]);

                Event::forge('Foolz\FoolFrame\Model\Context::handleWeb#obj.routing')
                    ->setCall(function ($result) use ($context) {
                        if ($context->getService('auth')->hasAccess('maccess.admin')) {
                            Event::forge('Foolz\FoolFrame\Controller\Admin::before#var.sidebar')
                                ->setCall(function ($result) {
                                    $sidebar = $result->getParam('sidebar');
                                    $sidebar[]['plugins'] = [
                                        "content" => ["reports/manage" => ["level" => "admin", "name" => _i("Offsite Reports"), "icon" => 'icon-file']]
                                    ];
                                    $result->setParam('sidebar', $sidebar);
                                });

                            $context->getRouteCollection()->add(
                                'foolfuuka.plugin.reports.admin', new Route(
                                    '/admin/plugins/reports/{_suffix}',
                                    [
                                        '_suffix' => 'manage',
                                        '_controller' => '\Foolz\FoolFrame\Controller\Admin\Plugins\ReportsAdmin::manage'
                                    ],
                                    [
                                        '_suffix' => '.*'
                                    ]
                                )
                            );
                        }
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
