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
        $this->repTags(['test' => 'TEST!']);
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
        //添付ファイルがある場合リセットする
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
        //POSTされてきたデータをセッションに変換
        $Form->setSesFromPost();
        //トークンチェック　第2オプションにエラー時のリダイレクト先
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
        //POSTされてきたデータをセッションに変換
        $Form->setSesFromPost();
        //トークンチェック　第2オプションにエラー時のリダイレクト先
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
        $dir = SERVER_DIR['VIEW'].'email.tpl/';
        $receive_tpl = $dir.'receive'.TPL_EXT;
        $confirm_tpl = $dir.'confirm'.TPL_EXT;
        $Form->sendMail([
            //管理者へのメール送信内容
            'receive' => str_replace(
                array_keys($replace),
                array_values($replace),
                (file_exists($receive_tpl) ? file_get_contents($receive_tpl):'')
            ),
            //入力者への確認内容メール
            'confirm' => str_replace(
                array_keys($replace),
                array_values($replace),
                (file_exists($confirm_tpl) ? file_get_contents($confirm_tpl):'')
            ),
            //サンクスページでのエラーがあった場合、リダイレクト先
            'error_redirect' => 'contact',
            //添付ファイルが複数ある場合配列にファイルエレメントname属性をセット
            'attachment_name' => ['attachment']
        ]);
    }

}