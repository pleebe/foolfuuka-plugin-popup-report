<?php

namespace Foolz\FoolFuuka\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Foolz\FoolFrame\Model\Preferences;
use Foolz\FoolFuuka\Model\Board;
use Foolz\FoolFuuka\Model\ReportCollection;
use Foolz\Inet\Inet;

class PopupReport extends \Foolz\FoolFuuka\Controller\Api\Chan
{
    /**
     * @var ReportCollection
     */
    protected $report_coll;

    /**
     * @var Preferences
     */
    protected $preferences;

    public function before()
    {
        $this->report_coll = $this->getContext()->getService('foolfuuka.report_collection');
        $this->preferences = $this->getContext()->getService('preferences');
        parent::before();
    }

    protected function access_key_is_ok()
    {
        $check = $this->getPost('access_key');
        $valid_keys = explode(',', $this->preferences->get('foolfuuka.plugins.offsitereports.accesskey'));
        foreach ($valid_keys as $key) {
            if ($check == $key) {
                return true;
            }
        }
        return false;
    }

    public function post_offsite_report()
    {
        $this->response = new JsonResponse();

        if (!$this->access_key_is_ok()) {
            return $this->response->setData(['error' => _i('Invalid access key.')])->setStatusCode(403);
        }
        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board selected.')])->setStatusCode(422);
        }
        $num = $this->getPost('num');
        if ($num === null) {
            return $this->response->setData(['error' => _i('The post number is missing.')])->setStatusCode(422);
        }
        if (!Board::isValidPostNumber($num)) {
            return $this->response->setData(['error' => _i('Invalid post number.')])->setStatusCode(422);
        }

        try {
            $comment = Board::forge($this->getContext())
                ->getPost($num)
                ->setRadix($this->radix)
                ->getComment();
        } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
            return $this->response->setData(['error' => _i('Post not found.')])->setStatusCode(404);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => _i($e->getMessage())])->setStatusCode(500);
        }

        if ($this->getPost('ip')) {
            $ip = Inet::ptod($this->getPost('ip'));
        } else {
            $ip = Inet::ptod($this->getRequest()->getClientIp());
        }

        try {
            $this->report_coll->add(
                $this->radix,
                $comment->comment->doc_id,
                $this->getPost('reason'),
                $ip
            );
        } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
            return $this->response->setData(['error' => _i($e->getMessage())]);
        }
        $this->response->setData(['success' => _i('You have successfully submitted a report for this post.')]);
        return $this->response;
    }
}