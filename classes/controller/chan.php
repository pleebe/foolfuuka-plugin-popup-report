<?php

namespace Foolz\FoolFuuka\Controller\Chan;

use Foolz\FoolFrame\Model\Preferences;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Foolz\FoolFuuka\Model\Board;
use Foolz\FoolFuuka\Model\ReportCollection;
use Foolz\Inet\Inet;
use Foolz\FoolFrame\Model\Security;

class PopupReport extends \Foolz\FoolFuuka\Controller\Chan
{
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
        $this->report_coll = $this->getContext()->getService('foolfuuka.report_collection');
        $this->security = $this->getContext()->getService('security');
        $this->preferences = $this->getContext()->getService('preferences');
        parent::before();
    }

    public function radix_report($num = 0)
    {
        $this->response = new StreamedResponse();
        $content = null;
        if (!$num) {
            return $this->error(_i('The post number is missing.'));
        }
        if (!Board::isValidPostNumber($num)) {
            return $this->error(_i('Invalid post number.'));
        }
        try {
            $comment = Board::forge($this->getContext())
                ->getPost($num)
                ->setRadix($this->radix)
                ->getComment();
        } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
            return $this->error(_i('Post not found.'));
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->error(_i($e->getMessage()));
        }
        if ($this->getPost()) {
            if (!$this->security->checkCsrfToken($this->getRequest())) {
                return $this->error(_i('The security token wasn\'t found. Try resubmitting.'));
            }

            try {
                $this->report_coll->add(
                    $this->radix,
                    $comment->comment->doc_id,
                    $this->getPost('reason'),
                    Inet::ptod($this->getRequest()->getClientIp())
                );
            } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
                return $this->error(_i($e->getMessage()));
            }
            $content = _i('<div class="alert alert-success">You have successfully submitted a report for this post.</div>');
        }
        $this->builder->getProps()->addTitle(_('Report Post No.' . $num));

        ob_start();
        include __DIR__ . '/../Partial/report.php';
        $content .= ob_get_clean();

        $this->builder->createPartial('body', 'plugin')
            ->getParamManager()->setParam('content', $content);

        $this->response->setCallback(function () {
            $this->builder->stream();
        });

        return $this->response;
    }
}
