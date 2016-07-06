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

    protected function generate_key()
    {
        $bytes = openssl_random_pseudo_bytes(16, $cstrong);
        if ($cstrong !== false) {
            // should be random enough
            return bin2hex($bytes);
        } else {
            // not so random fallback
            mt_srand();
            $dec = dechex(mt_rand(0, mt_getrandmax()));
            mt_srand();
            $dec .= dechex(mt_rand(0, mt_getrandmax()));
            mt_srand();
            $dec .= dechex(mt_rand(0, mt_getrandmax()));
            mt_srand();
            $dec .= dechex(mt_rand(0, mt_getrandmax()));
            return $dec;
        }
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
                'label' => _i('Insert offsite reports API access keys.'),
                'help' => _i('Example <pre>key1,key2,key3</pre>'),
                'class' => 'span8',
            ],
            'foolfuuka.plugins.offsitereports.accesskey.generate' => [
                'type' => 'checkbox',
                'label' => _i(''),
                'help' => _i('Generate new key automatically.')
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
        $this->param_manager->setParam('method_title', [_i('FoolFuuka'), _i("Offsite Reports"), _i('Manage')]);

        $data['form'] = $this->structure();

        if ($this->getPost() && $this->getPost('foolfuuka,plugins,offsitereports,accesskey,generate')) {
            $orig = $this->preferences->get('foolfuuka.plugins.offsitereports.accesskey');
            $post['foolfuuka.plugins.offsitereports.accesskey'] = ($orig ? $orig . ',' : '') . $this->generate_key();
            $this->preferences->submit_auto($this->getRequest(), $data['form'], $post);
        } else {
            $this->preferences->submit_auto($this->getRequest(), $data['form'], $this->getPost());
        }

        // create a form
        $this->builder->createPartial('body', 'form_creator')
            ->getParamManager()->setParams($data);

        return new Response($this->builder->build());
    }
}
