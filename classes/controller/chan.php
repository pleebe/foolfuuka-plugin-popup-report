<?php

namespace Foolz\FoolFuuka\Controller\Chan;

use Foolz\FoolFrame\Model\Plugins;
use Foolz\FoolFrame\Model\Uri;
use Foolz\FoolFrame\Model\Preferences;
use Foolz\Plugin\Plugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Foolz\FoolFuuka\Model\Board;
use Foolz\FoolFuuka\Model\ReportCollection;
use Foolz\Inet\Inet;
use Foolz\FoolFrame\Model\DoctrineConnection;
use Foolz\FoolFrame\Model\Form;
use Foolz\FoolFrame\Model\Security;

class PopupReport extends \Foolz\FoolFuuka\Controller\Chan
{
    /**
     * @var Plugin
     */
    protected $plugin;

    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var ReportCollection
     */
    protected $report_coll;

    /**
     * @var Security
     */
    protected $security;

    /**
     * @var Preferences
     */
    protected $preferences;

    public function before()
    {
        $this->plugin = $this->getContext()->getService('plugins')->getPlugin('foolz/foolfuuka-plugin-popup-report');
        $this->uri = $this->getContext()->getService('uri');
        $this->dc = $this->getContext()->getService('doctrine');
        $this->report_coll = $this->getContext()->getService('foolfuuka.report_collection');
        $this->security = $this->getContext()->getService('security');
        $this->preferences = $this->getContext()->getService('preferences');
        parent::before();
    }

    public function radix_report($num = 0)
    {
        if($this->getPost() && $this->getPost('api')==='true') {
            $this->response = new JsonResponse();
            $is_api = true;
        } else {
            $this->response = new StreamedResponse();
            $is_api = false;
        }
        $content = null;
        if (!$num) {
            if($is_api)
                return $this->response->setData(['error' => _i('The post number is missing.')])->setStatusCode(422);
            else
                return $this->error(_i('The post number is missing.'));
        }
        if (!Board::isValidPostNumber($num)) {
            if($is_api)
                return $this->response->setData(['error' => _i('Invalid post number.')])->setStatusCode(422);
            else
                return $this->error(_i('Invalid post number.'));
        }
        try {
            $comment = Board::forge($this->getContext())
                ->getPost($num)
                ->setRadix($this->radix)
                ->getComment();
        } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
            if($is_api)
                return $this->response->setData(['error' => _i('Post not found.')])->setStatusCode(404);
            else
                return $this->error(_i('Post not found.'));
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            if($is_api)
                return $this->response->setData(['error' => _i($e->getMessage())])->setStatusCode(500);
            else
                return $this->error(_i($e->getMessage()));
        }
        if ($this->getPost()) {
            if (!$this->security->checkCsrfToken($this->getRequest())&&!$is_api) {
                return $this->error(_i('The security token wasn\'t found. Try resubmitting.'));
            }
            if($is_api&&$this->getPost('access_key')!==$this->preferences->get('foolfuuka.plugins.offsitereports.accesskey')) {
                return $this->response->setData(['error' => _i('Invalid access key.')])->setStatusCode(500);
            }
            try {
                $this->report_coll->add(
                    $this->radix,
                    $comment->comment->doc_id,
                    $this->getPost('reason'),
                    Inet::ptod($this->getRequest()->getClientIp())
                );
            } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
                if($is_api)
                    return $this->response->setData(['error' => _i($e->getMessage())]);
                else
                    return $this->error(_i($e->getMessage()));
            }
            if($is_api)
                return $this->response->setData(['success' => _i('You have successfully submitted a report for this post.')]);
            else
                $content = _i('<div class="alert alert-success">You have successfully submitted a report for this post.</div>');
        }
        if(!$is_api) {
            $this->builder->getProps()->addTitle(_('Report Post No.' . $num));

            $form = New Form($this->getRequest());
            ob_start();
            include __DIR__ . '/../Partial/report.php';
            $content .= ob_get_clean();

            $this->builder->createPartial('body', 'plugin')
                ->getParamManager()->setParam('content', $content);
        }

        $this->response->setCallback(function() {
            $this->builder->stream();
        });

        return $this->response;
    }
}
