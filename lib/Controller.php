<?php
namespace TM;

class Controller extends ControllerService
{

    public function __construct(array $options)
    {
        parent::__construct($options);
        
        if(method_exists(get_class(), $this->method))$this->{$this->method}();
    }

    private function _404() : void
    {
        /*
        ビジネスモデルやリプレイスタグがテンプレートにない場合は
        メソッドの記述はしなくてもテンプレート（htmlファイル）のみで動作可能
        */
    }

    private function index() : void
    {
        
    }

    private function admin() : void
    {
        
    }

    private function admin_dashboard() : void
    {
        
    }

    private function contact() : void
    {
        $Form = $this->instanceof['Form'];
        $Form->resetFiles(['attachment']);
        $format = $Form->format([
            ['name' => 'error_msg', 'unset' => 1]
        ]);
        $format += ['token' => Token::setToken()];
        $this->repTags($format);
    }

    private function contact_confirm() : void
    {
        $Form = new Form;
        $Form->setSesFromPost();
        Token::verification($Form->getSes(['name' => 'token']), 'contact');
        $format = $Form->format([
            ['name' => 'name'],
            ['name' => 'email'],
            ['name' => 'radio'],
            ['name' => 'checkbox', 'sep' => ','],
            ['name' => 'selectOne'],
            ['name' => 'selectMultiple', 'sep' => ','],
            ['name' => 'message', 'nl' => true],
            ['name' => 'attachment', 'file' => true, 'error' => 'contact']
        ]);
        $format += ['token' => Token::setToken()];
        $this->repTags($format);
    }

    private function contact_thanks() : void
    {
        $Form = new Form;
        $Form->setSesFromPost();
        Token::verification($Form->getSes(['name' => 'token']), 'contact');
        $replace = $Form->format([
            ['name' => 'name', 'braces' => true],
            ['name' => 'email', 'braces' => true],
            ['name' => 'radio', 'braces' => true],
            ['name' => 'checkbox', 'sep' => ',', 'braces' => true],
            ['name' => 'selectOne', 'braces' => true],
            ['name' => 'selectMultiple', 'sep' => ',', 'braces' => true],
            ['name' => 'message', 'braces' => true],
        ]);
        $dir = $this->SERVER_DIR['VIEW'].'email.tpl/';
        $receive_tpl = $dir.'receive'.$this->TPL_EXT;
        $confirm_tpl = $dir.'confirm'.$this->TPL_EXT;
        $Form->sendMail([
            'receive' => str_replace(
                array_keys($replace),
                array_values($replace),
                (file_exists($receive_tpl) ? file_get_contents($receive_tpl):'')
            ),
            'confirm' => str_replace(
                array_keys($replace),
                array_values($replace),
                (file_exists($confirm_tpl) ? file_get_contents($confirm_tpl):'')
            ),
            'error_redirect' => 'contact',
            'attachment_name' => ['attachment']
        ]);
    }

}