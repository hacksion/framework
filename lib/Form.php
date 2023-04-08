<?php
namespace TM;

class Form
{
	private $options = [];

	private $class_name = '';

	private $Session;

	public function __construct(array $options = [])
    {
		$this->options = $options;
		$this->class_name = str_replace(__NAMESPACE__ . '\\', '', get_class());
		$session_name = $options['session_name'] ?? $this->class_name;
		$this->Session = new Session($session_name);
    }

	public function exec() : array
	{
		$result = [];
		foreach($this->options as $tag => $value){
			$method = $value['method'] ?? '';
			$result[$tag] = '';
			if($method && method_exists(get_class(), $method)){
				$result[$tag] = $this->$method($value);
			}	
		}
		return $result;
	}

	public function getSes(array $options)
	{
		$result = $this->Session->get($options['name']);
		if(isset($options['unset']))$this->unset([$options['name']]);
		return $result;
	}

	public function setSes(array $options) : void
	{
		$this->Session->set($options);
	}

	public function post() : void
    {
        if(isset($_POST)){
            foreach($_POST as $name => $value){
				$this->Session->set(['name' => $name, 'value' => $value]);
			}
		}
    }

	public function format(array $options) : array
    {
		$result = [];
		foreach($options as $option){
			$name = ['name' => $option['name']];
			$unset = isset($option['unset']) ? ['unset' => 1]:[];
			$sep = isset($option['sep']) ? $option['sep']:null;
			$nl = $option['nl'] ?? null;
			$file = $option['file'] ?? null;
			$error = isset($option['error']) ? ['error' => $option['error']]:[];
			$braces = isset($option['braces']) ? BRACES:['', ''];
			$value = $this->getSes($name += $unset);
			if($sep)$value = implode($sep, $value);
			if($nl)$value = nl2br($value);
			if($file)$value = $this->withFile($name += $error);
			$result += [$braces[0].$option['name'].$braces[1] => $value];
		}
        return $result;
    }

	public function unset(array $options = []) : void
	{
		if($options){
			foreach($options as $name)
			$this->Session->unset($name);
		}else{
			$_SESSION = array();
			if (isset($_COOKIE["PHPSESSID"]))setcookie("PHPSESSID", '', time() - 1800, '/');
			session_destroy();
		}	
	}

	public function resetFiles(array $file_name) : void
	{
		foreach($file_name as $name){
			if($this->getSes(['name' => $name])){
				$this->rmdirAll([
					'dir' => $this->getSes(['name' => $this->getSes(['name' => $name]).'dir', 'unset' => 1])
				]);
				$this->unset([$name]);
			}
		}
	}

	public function withFile(array $options)
	{
		$result = '';
		$name = isset($options['name']) ? $options['name']:'';
		$error = isset($options['error']) ? $options['error']:'';
		$path = isset($options['path']) ? $options['path']:SERVER_DIR['TMP'];
		$max = isset($_POST['MAX_FILE_SIZE']) ? $_POST['MAX_FILE_SIZE']:$this->returnBytes(ini_get('upload_max_filesize'));
		if($name && isset($_FILES[$name]) && !empty($_FILES[$name]['name'])){
			if($max > $_FILES[$name]['size']){
				$result = $_FILES[$name]['name'];
				$dir = basename($_FILES[$name]['tmp_name']);
				if(!file_exists($path.$dir))mkdir($path.$dir, 0755, true);
				move_uploaded_file($_FILES[$name]['tmp_name'], $path.$dir.'/'.$result);
				$this->setSes(['name' => $name, 'value' => $result]);
				$this->setSes(['name' => $result.'dir', 'value' => $dir]);
			}else{
				$this->setSes(['name' => 'error_msg', 'value' => 'ファイルサイズは'.($max / 1000).'KB までです']);
				header("HTTP/1.1 301 Moved Permanently");
				header("Location:" . ($error ? $error:'./'));
				exit;
			}
        }
		return $result;
	}

	public function returnBytes(string $val)
	{
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		$val = substr($val, 0, -1);
		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

	public function rmdirAll(array $options) : void
	{
		$path = isset($options['path']) ? $options['path']:SERVER_DIR['TMP'];
		$dir = isset($options['dir']) ? $options['dir']:'';
		if($dir){
			chdir($path);
			if(file_exists($dir)){
				$res = glob($dir.'/*');
				foreach ($res as $f) {
					if (is_file($f)) {
						unlink($f);
					} else {
						$this->rmdirAll(['dir' => $f]);
					}
				}
				rmdir($dir);
			}
		}
	}

	public function sendMail(array $options) : void
    {
		try {
			$PHPMailer = new \PHPMailer\PHPMailer\PHPMailer();
			//$PHPMailer->SMTPDebug = 2;//デバッグをする場合
			$PHPMailer->isSMTP();
			$PHPMailer->Host        = MAIL_SERVER['HOST'];
			$PHPMailer->Username    = MAIL_SERVER['USER'];
			$PHPMailer->Password    = MAIL_SERVER['PASS'];
			$PHPMailer->SMTPAuth    = true;
			$PHPMailer->SMTPSecure  = MAIL_SERVER['ENCRPT'];
			$PHPMailer->Port        = MAIL_SERVER['PORT'];
			$PHPMailer->Encoding    = MAIL_SERVER['ENCODING'];
			$PHPMailer->CharSet     = MAIL_SERVER['CHARSET'];
			$PHPMailer->XMailer     = null;
			$PHPMailer->Sender      = MAIL_SERVER['EMAIL'];
			//添付ファイルがある場合
			$del_dir = false;
			if(isset($options['attachment_name'])){
				$file_dir = [];
				foreach($options['attachment_name'] as $key){
					$name = $this->Session->get($key);
					$dir = $this->Session->get($name.'dir');
					array_push($file_dir, $dir);
					if(file_exists(SERVER_DIR['TMP'].$dir.'/'.$name)){
						$PHPMailer->addAttachment(SERVER_DIR['TMP'].$dir.'/'.$name);
						$del_dir = true;
					}
				}
			}
			//管理者へ送信
			$PHPMailer->setFrom(MAIL_SERVER['EMAIL'], MAIL_SERVER['FROM_NAME']);
			$PHPMailer->addAddress(MAIL_SERVER['EMAIL'], MAIL_SERVER['FROM_NAME']);
			$PHPMailer->Subject     = SITE_NAME.'よりお問合せをいただきました';
			$PHPMailer->Body        = isset($options['receive']) ? $options['receive']:'';
			$PHPMailer->send();
			$PHPMailer->clearAddresses();
			$PHPMailer->clearAttachments();
			//問合せ者への送信内容確認メール
			$PHPMailer->Subject     = 'お問合せありがとうございます';
			$PHPMailer->Body        = isset($options['confirm']) ? $options['confirm']:'';
			$PHPMailer->setFrom(MAIL_SERVER['EMAIL'], MAIL_SERVER['FROM_NAME']);
			$PHPMailer->addAddress($this->Session->get('email'));
			$PHPMailer->addReplyTo(MAIL_SERVER['FROM_NAME'], SITE_NAME.' お問合せフォーム');
			$PHPMailer->send();
			$PHPMailer->clearAddresses();
			if($del_dir){
				foreach($file_dir as $dir){
					$this->rmdirAll(['dir' => $dir]);
				}
			}
			$this->unset();
        } catch (\Exception $e) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location:" . isset($options['error_redirect']) ? $options['error_redirect']:'./');
            exit;
        }
    }

	private function input(array $options) : string
	{
		$result = '<input class="form-control" id="'.($options['id'] ?? '').'" name="'.$options['name'].'" type="'.($options['type'] ?? 'text').'" value="'.($this->Session->get($options['name']) ?? '').'" placeholder="'.($options['placeholder'] ?? '').'"'.(isset($options['length']) ? ' maxlength="'.$options['length'].'"':'').(isset($options['required']) ? ' required':'').(isset($options['accept']) ? ' accept="'.$options['accept'].'"':'').'>';
		if(isset($options['file_size'])){
			$result .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.$options['file_size'].'">';
		}
		return $result;
	}

	private function radio(array $options) : string
	{
		$result = '';
		$title = explode(',', $options['title'] ?? '');
		$value = $this->Session->get($options['name']) ?? ($title[0] ?? '');
		for($i = 0; count($title) > $i; $i++){
			$checked = $value == $title[$i] ? ' checked':'';
			$result .= '
			<div class="form-check form-check-inline">
				<input class="form-check-input" type="radio" name="'.$options['name'].'" id="'.$options['name'].'_'.$i.'" value="'.$title[$i].'"'.$checked.'>
				<label class="form-check-label" for="'.$options['name'].'_'.$i.'">'.$title[$i].'</label>
			</div>
			';
		}
		return $result;
	}

	private function checkbox(array $options) : string
	{
		$result = '';
		$title = explode(',', $options['title'] ?? '');
		$value = $this->Session->get($options['name']) ? $this->Session->get($options['name']):[];
		for($i = 0; count($title) > $i; $i++){
			$checked = in_array($title[$i], $value) ? ' checked':'';
			$result .= '
			<div class="form-check form-check-inline">
				<input class="form-check-input" type="checkbox" name="'.$options['name'].'[]" id="'.$options['name'].'_'.$i.'" value="'.$title[$i].'"'.$checked.'>
				<label class="form-check-label" for="'.$options['name'].'_'.$i.'">'.$title[$i].'</label>
			</div>
			';
		}
		return $result;
	}

	private function textarea(array $options) : string
	{
		return '<textarea class="form-control" id="'.($options['id'] ?? '').'" name="'.$options['name'].'" rows="'.($options['row'] ?? 5).'"'.(isset($options['required']) ? ' required':'').'>'.($this->Session->get($options['name']) ?? '').'</textarea>';
	}

	private function selectOne(array $options) : string
	{
		$result = '
		<select class="form-select" name="'.$options['name'].'"'.(isset($options['required']) ? ' required':'').'>
		<option value="">'.(isset($options['default']) ? $options['default']:'-----').'</option>';
		$title = explode(',', $options['title'] ?? '');
		$value = $this->Session->get($options['name']) ?? null;
		for($i = 0; count($title) > $i; $i++){
			$selected = $value == $title[$i] ? ' selected':'';
			$result .= '<option value="'.$title[$i].'"'.$selected.'>'.$title[$i].'</option>';
		}
		$result .= '</select>';
		return $result;
	}

	private function selectMultiple(array $options) : string
	{
		$result = '
		<select class="form-select" multiple name="'.$options['name'].'[]"'.(isset($options['required']) ? ' required':'').'>';
		$title = explode(',', $options['title'] ?? '');
		$value = $this->Session->get($options['name']) ? $this->Session->get($options['name']):[];
		for($i = 0; count($title) > $i; $i++){
			$selected = in_array($title[$i], $value) ? ' selected':'';
			$result .= '<option value="'.$title[$i].'"'.$selected.'>'.$title[$i].'</option>';
		}
		$result .= '</select>';
		return $result;
	}
	
}