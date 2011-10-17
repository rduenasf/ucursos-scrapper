<?php
require('phpQuery/phpQuery.php');
class UcursosScrapper {

    var $url;
    var $status_code;
    var $curl_handler;
    var $content;
    var $phpQueryDocument;
    var $processed;
    var $nombre_seccion;

    function __construct($user_id) {
        $this->user_id = $user_id;
        $this->curl_handler = curl_init();
        curl_setopt($this->curl_handler, CURLOPT_HEADER, 0);
        curl_setopt($this->curl_handler, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curl_handler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl_handler, CURLOPT_COOKIEJAR, dirname(__FILE__)."/cookies/".$this->user_id.".txt");
        curl_setopt($this->curl_handler, CURLOPT_COOKIEFILE, dirname(__FILE__)."/cookies/".$this->user_id.".txt");
        curl_setopt($this->curl_handler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl_handler, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    }

    public function is_authorized() {
        // En el login me mostró el login
        if (strpos($this->content, '<script src="https://www.u-cursos.cl/upasaporte/javascript?servicio=ucursos&UCURSOS_SERVER') !== false) {
            return false;
        }
        // Me tiró un no autorizado
        else if ($this->status_code == '403') {
            return false;
        }
        else {
            return true;
        }
    }

    public function is_connected() {
        return $this->status_code != '0';
    }

    public function exists() {
        // Si lo encontró con 200, existe
        return $this->status_code == '200';
    }

    function fetch($url, $output_format = 'UTF-8') {
        $this->url = $url;
        curl_setopt($this->curl_handler, CURLOPT_URL, $this->url);
        $this->content = curl_exec($this->curl_handler);
        $info = curl_getinfo($this->curl_handler);
        curl_close($this->curl_handler);

        $this->status_code = $info['http_code'];
        $this->charset = substr($info['content_type'], strpos($info['content_type'], 'charset=')+strlen('charset='));

        if(!$this->is_authorized() || !$this->exists()) {
            return false;
        }

        $this->content = mb_convert_encoding($this->content, $output_format, $this->charset);

        return true;
    }

    function process() {
        if (!$this->processed) {
            $this->processed = true;
            $this->phpQueryDocument = phpQuery::newDocumentHTML($this->content);
            phpQuery::selectDocument($this->phpQueryDocument);
        }
    }

    function getSeccion() {
        if ($this->nombre_seccion != null) return $this->nombre_seccion;
        $this->process();
        $this->nombre_seccion = pq('h2.ucursos')->html();
        return $this->nombre_seccion;
    }

    public static function toUserType($str, $default = 'administrador_de_comunidad', $nan = 'miembro_de_comunidad') {
        // $default: cuando no tiene tipo
        // $nan: cuando no es un tipo conocido
        $types = array('profesor_de_catedra', 'auxiliar', 'ayudante', 'alumno', 'miembro_de_comunidad', 'administrador_de_comunidad');
        $str = UcursosScrapper::toAscii($str);
        return $str ? (in_array($str, $types) ? $str : $nan ) : $default;
    }

    public static function toAscii($str, $delimiter='_') {
        //$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = $str;
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        return $clean;
    }

}

require('scrapper.resources/ucursos.foro.scrapper.php');
require('scrapper.resources/ucursos.home.scrapper.php');
require('scrapper.resources/ucursos.horario.scrapper.php');
require('scrapper.resources/ucursos.notas.scrapper.php');
require('scrapper.resources/ucursos.novedades.scrapper.php');
