<?php
namespace TM;

class ControllerService
{
    protected $html;

    protected $method;

    protected $replace_init = [];

    protected $replace_tags = [];

    protected $instanceof = [];

    public function __construct(array $options)
    {
        $this->replace_init = $options['public_url'] ?? [];
        $router = preg_replace('/\?.+$/', '', $options['router']);
        $this->method = !isset($router[0]) ? 'index':end($router);
        if($this->method  == ADMIN_DIR)$this->method = 'admin';
        $filepath = SERVER_DIR['VIEW'].($router ? implode('.', $router):$this->method).TPL_EXT;
        $this->html = file_get_contents(file_exists($filepath) ? $filepath:SERVER_DIR['VIEW'].'_404'.TPL_EXT);
        $this->getTags();
    }

    public function view() : void
    {
        echo str_replace( array_keys( $this->replace_tags ), array_values( $this->replace_tags ), $this->html);
    }

    protected function repTags(array $options) : void
    {
        foreach($options as $key => $value)
        $this->replace_tags[BRACES[0].$key.BRACES[1]] = $value;
    }

    private function getTags() : void
    {
        preg_match_all('/\{\{[\s\S]*?\}\}/us', $this->html, $matches);
        if($matches[0]){ 
            $found_options = [];
            foreach($matches[0] as $m){
                $val = ltrim($m, BRACES[0]);
                $val = rtrim($val, BRACES[1]);
                preg_match('/^\[[\s\S]*?\]$/us', trim($val), $options);
                if($options)$found_options[$m] = $this->str2Array($options[0]);
                $this->replace_tags[$m] = array_key_exists($val, $this->replace_init) ? $this->replace_init[$val]:$m;
            }
            if($found_options)$this->addOn($found_options);
        }
        //共通テンプレートを読み込む
        $tpl_dir = SERVER_DIR['VIEW'].'tpl/';
        if (is_dir($tpl_dir)) {
            if ($dh = opendir($tpl_dir)) {
                while (($file = readdir($dh)) !== false) {
                    if(filetype($tpl_dir . $file) == 'file'){
                        $this->replace_tags[BRACES[0].basename($file, TPL_EXT).BRACES[1]] = file_get_contents($tpl_dir.$file);
                    }
                }
                closedir($dh);
            }
        }
    }

    private function addOn(array $found_options) : void
    {
        if($found_options){
            $options = [];
            foreach($found_options as $key => $value){
                $options[$value['class']][$key] = $value;
            }
            foreach($options as $ClassName => $tags){
                $Class = __NAMESPACE__.'\\'.$ClassName;
                if (class_exists($Class)){
                    $Object = new $Class($tags);
                    $this->instanceof += [str_replace(__NAMESPACE__ . '\\', '', $Class) => $Object];
                    $result = $Object->exec();
                    if($result){
                        foreach($result as $key => $value)
                        if(isset($this->replace_tags[$key]))$this->replace_tags[$key] = $value;
                    }
                }
            }
        }
    }

    private function str2Array(string $string) : array
    {
        $result = [];
        $val = ltrim($string, '[');
        $val = rtrim($val, ']');
        $val = preg_replace('/\r\n|\r|\n/', '', $val);
        $val = explode(';', $val);
        foreach($val as $values){
            $value = explode(':', $values);
            $result[trim($value[0])] = isset($value[1]) ? trim($value[1]):true;
        }
        return $result;
    }
}