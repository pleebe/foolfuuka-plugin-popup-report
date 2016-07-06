<?php use Foolz\FoolFrame\Model\Form; $form = New Form($this->getRequest()); ?>
<div class="alert alert-danger" style="margin:5% 10%;">
    <h4 class="alert-heading">Report » Post No.<?= $num ?></h4>
    <br>
    <?= $form->open(['onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $this->uri->base().$this->radix->shortname.'/report/'.$num]); ?>
    <?= _i('Reason for your report.') ?>
    <br>
    <?= $form->hidden('csrf_token', $this->security->getCsrfToken()) ?>
    <?= $form->textarea([ 'name' => 'reason', 'style' => 'width: 100%; height: 100px; margin: 10px 0']) ?>
    <?= $form->submit(['name' => 'submit', 'value' => _i('Submit'), 'class' => 'btn btn-inverse']) ?>
    <?= $form->close(); ?>
</div>