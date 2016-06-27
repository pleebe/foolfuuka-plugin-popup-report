<?php

namespace Foolz\FoolFrame\Controller\Admin\Plugins;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Foolz\FoolFrame\Model\Validation\ActiveConstraint\Trim;

class ReportsAdmin extends \Foolz\FoolFrame\Controller\Admin
{
    public function before()
    {
        parent::before();

        $this->param_manager->setParam('controller_title', _i('Plugins'));
    }

    public function security()
    {
        return $this->getAuth()->hasAccess('maccess.admin');
    }

    protected function structure()
    {
        $arr = [
            'open' => [
                'type' => 'open',
            ],
            'foolfuuka.plugins.offsitereports.accesskey' => [
                'preferences' => true,
                'type' => 'input',
                'label' => _i('Offsite reports API access key.'),
                'help' => _i(''),
                'class' => 'span8',
            ],
            'separator1' => [
                'type' => 'separator-short',
            ],
            'submit' => [
                'type' => 'submit',
                'class' => 'btn-primary',
                'value' => _i('Submit')
            ],
            'close' => [
                'type' => 'close'
            ],
        ];

        return $arr;
    }

    public function action_manage()
    {
        $this->param_manager->setParam('method_title', [_i('FoolFuuka'), _i("Offsite Reports"),_i('Manage')]);

        $data['form'] = $this->structure();

        $this->preferences->submit_auto($this->getRequest(), $data['form'], $this->getPost());

        // create a form
        $this->builder->createPartial('body', 'form_creator')
            ->getParamManager()->setParams($data);

        return new Response($this->builder->build());
    }
}
