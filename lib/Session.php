<?php
namespace TM;

class Session
{
    private $session_name;

	public function __construct(string $session_name)
    {
		$this->session_name = $session_name;
    }

    public function get(string $name) : mixed
    {
        return isset($_SESSION[$this->session_name][$name]) ? $_SESSION[$this->session_name][$name]:null;
    }

    public function set(array $options) : void
    {
        $_SESSION[$this->session_name][$options['name']] = $options['value'];
    }

    public function unset(string $name) : void
    {
        if(isset($_SESSION[$this->session_name][$name]))
            unset($_SESSION[$this->session_name][$name]);
    }
}