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

    function __construct($user_id) {
        $this->curl_handler = curl_init();
        curl_setopt($this->curl_handler, CURLOPT_HEADER, 0);
        curl_setopt($this->curl_handler, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curl_handler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl_handler, CURLOPT_COOKIEJAR, dirname(__FILE__)."/cookies/".$user_id.".txt");
        curl_setopt($this->curl_handler, CURLOPT_COOKIEFILE, dirname(__FILE__)."/cookies/".$user_id.".txt");
        curl_setopt($this->curl_handler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl_handler, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    }

    function fetch($url, $output_format = 'UTF-8') {
        curl_setopt($this->curl_handler, CURLOPT_URL, $url);
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
}

class HomeScrapper extends UcursosScrapper {
    var $user;
    var $cursos;
    var $comunidades;
    var $instituciones;

    function fetch() {
        return parent::fetch('https://www.u-cursos.cl/');
    }

    function getUserInfo() {
        if($this->user != null) return $this->user;
        parent::process();

        $this->user = array();
        $this->user['name'] = pq('#usuario h1 a')->html();
        $tmp_url = pq('#usuario li:nth-child(2) a')->attr('href');
        $this->user['url'] = substr($tmp_url, 0, strrpos($tmp_url, '/', -2)+1);

        return $this->user;
    }

    function getList($id) {
        if(!property_exists($this, $id)) return null;
        if($this->$id != null) return $this->$id;
        parent::process();

        $this->$id = array();
        foreach(pq('div#'.$id.' > ul > li') as $item) {
            $element = new stdClass();
            $element->tipo = UcursosScrapper::toAscii((pq('img', $item)->attr('alt')));
            list($element->codigo, $element->nombre) = explode(" ", pq('span', $item)->html(), 2);
            $element->url = substr(pq('a', $item)->attr('href'), strlen('https://www.u-cursos.cl'));
            $element->nuevos = array();
            foreach(pq('div.nuevo > a', $item) as $new_item) {
                $new_count = pq($new_item)->html();
                $element->nuevos[substr(pq($new_item)->attr('href'), strlen('https://www.u-cursos.cl'. $element->url), -1)] = substr($new_count, strpos($new_count, '(')+1, -1);
            }
            $this->{$id}[$element->codigo] = $element;
        }
        return $this->$id;
    }

    function getUserCursos() {
        return $this->getList('cursos');
    }

    function getUserComunidades() {
        return $this->getList('comunidades');
    }

    function getUserInstituciones() {
        $id = 'instituciones';
        if(!property_exists($this, $id)) return null;
        if($this->$id != null) return $this->$id;
        parent::process();

        $this->$id = array();
        foreach(pq('div#'.$id.' > ul > li') as $item) {
            $element = new stdClass();
            $element->tipo = UcursosScrapper::toAscii((pq('img', $item)->attr('alt')));
            $element->nombre = pq('a > span:first', $item)->html();
            $element->url = substr(pq('a', $item)->attr('href'), strlen('https://www.u-cursos.cl'));
            $element->nuevos = array();
            foreach(pq('div.nuevo > a', $item) as $new_item) {
                $new_count = pq($new_item)->html();
                $element->nuevos[substr(pq($new_item)->attr('href'), strlen('https://www.u-cursos.cl'. $element->url), -1)] = substr($new_count, strpos($new_count, '(')+1, -1);
            }
            $this->{$id}[] = $element;
        }
        return $this->$id;
    }

}

class HorarioScrapper extends UcursosScrapper {
    var $horario;

    function getHorario() {
        if($this->horario != null) return $this->horario;
        parent::process();

        $dotw = array('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');

        $this->horario = array('Lunes' => array()
                       , 'Martes' => array()
                       , 'Miércoles' => array()
                       , 'Jueves' => array()
                       , 'Viernes' => array()
                       , 'Sábado' => array());

        foreach(pq('td.dia') as $day => $bloques) {
            foreach(pq('div', $bloques) as $tmp_bloque) {
                $bloque = new stdClass();
                $bloque->codigo = pq('a', $tmp_bloque)->html();
                list(,$tmp_tipo,, $tmp_horario) = pq($tmp_bloque)->contents()->elements;
                $bloque->tipo = trim(pq($tmp_tipo)->text());
                list($bloque->sala, $bloque->hora) = explode(chr(194).chr(160), pq($tmp_horario)->text());
                $this->horario[$dotw[$day]][] = $bloque;
            }
        }

        return $this->horario;
    }
}

class ForoScrapper extends UcursosScrapper {
    var $threads;
    var $default;
    var $nan;

    private function getAuthorInfo($tmp_autor) {
        $autor = new stdClass();
        $autor->nombre = trim(pq('a.usuario', $tmp_autor)->html());
        $autor->tipo = UcursosScrapper::toUserType(pq('img.icono', $tmp_autor)->attr('alt'), $this->default, $this->nan);
        $autor->avatar = pq('img.icono', $tmp_autor)->attr('src');
        return $autor;
    }

    private function getPostContent($post) {
        $tmp_texto = pq(pq($post)->children('div.texto'))->html();
        return substr($tmp_texto, strpos($tmp_texto, '>')+1, strpos($tmp_texto, '<ul id="opciones') - strpos($tmp_texto, '>')-1);
    }

    private function getPostInfo($post) {
        $tmp_texto = pq(pq($post)->children('div.texto'))->html();
        $content = substr($tmp_texto, strpos($tmp_texto, '<ul id="opciones'));
        $href = pq('li > a:contains("Permalink")', pq($content))->attr('href');
        $responder = pq('li > a:contains("Responder")', pq($content))->attr('href');
        return array(substr($href, strlen('objeto/')), $responder ? true : false);
    }

    private function getThreadInfo($tmp_thread) {
        $thread = new stdClass();
        $thread->id = pq($tmp_thread)->attr('id');
        $thread->nuevo = pq($tmp_thread)->hasClass('connuevo');
        list($thread->respuestas, $thread->adhesion) = explode(chr(194).chr(160), trim((pq(pq($tmp_thread)->children('em'))->text())));
        $thread->titulo = pq('h1', $tmp_thread)->text();
        return $thread;
    }

    function getThreads($default = 'alumno', $nan = 'miembro_de_comunidad') {
        if($this->threads != null) return $this->threads;
        parent::process();

        $this->default = $default;
        $this->nan = $nan;

        $this->threads = array();
        foreach (pq('div.msg.raiz') as $tmp_thread) {
            $thread = $this->getThreadInfo($tmp_thread);

            $tmp_content = pq($tmp_thread)->children('div.container:first');

            $tmp_autor = pq($tmp_content)->children('span.autor', $tmp_thread);
            $thread->autor = $this->getAuthorInfo($tmp_autor);
            $thread->fecha = trim(pq('em', $tmp_autor)->text());

            $tmp_post = new stdClass();
            $tmp_post->autor = $thread->autor;
            $tmp_post->fecha = trim(pq('em', $tmp_autor)->html());
            $tmp_post->texto = $this->getPostContent($tmp_content);
            list($tmp_post->id, $tmp_post->responder) = $this->getPostInfo($tmp_content);

            $tmp_post->primero = true;

            $thread->posts = array();
            $thread->posts[] = $tmp_post;

            foreach (pq('div.hijo', $tmp_thread) as $reply) {
                $tmp_post = new stdClass();
                $tmp_post->nuevo = pq($reply)->hasClass('nuevo');

                $reply = pq($reply)->children('div.container');
                $tmp_post->texto = $this->getPostContent($reply);
                list($tmp_post->id, $tmp_post->responder) = $this->getPostInfo($reply);

                $tmp_autor = pq(pq($reply)->children('span.autor'));
                $tmp_post->autor = $this->getAuthorInfo($tmp_autor);

                $tmp_post->fecha = trim(pq('em', $tmp_autor)->html());
                $tmp_post->primero = false;

                $thread->posts[] = $tmp_post;
            }
            $this->threads[] = $thread;
        }

        return $this->threads;
    }
}

class NovedadesScrapper extends UcursosScrapper {
    var $novedades;

    function getNovedades($url) {
        if($this->novedades != null) return $this->novedades;
        parent::process();

        $this->novedades = array();
        foreach(pq('div.blog') as $item) {
            $novedad = new stdClass();
            $tmp_autor = pq('em', $item);
            $tmp_autor_i = strpos($tmp_autor, '<a class');
            $tmp_autor_f = strpos($tmp_autor, '</a>') + 4;

            $novedad->texto = pq($item)->children('p');
            foreach(pq('a', $novedad->texto) as $href) {
                $href_url = pq($href)->attr('href');
                if(substr($href_url, 0, 2) == 'r/')
                    pq($href)->attr('href', $url . $href_url);
            }
            foreach(pq('img', $novedad->texto) as $href) {
                $img_src = pq($href)->attr('src');
                if(substr($img_src, 0, 2) == 'r/')
                    pq($href)->attr('src', $url . $img_src);
            }

            $novedad->texto = pq($novedad->texto)->html();
            $novedad->titulo = trim(pq(pq($item)->children('h1'))->text());
            $novedad->fecha = trim(substr($tmp_autor, $tmp_autor_f));
            $novedad->autor = new stdClass();
            $novedad->autor->nombre = pq(substr($tmp_autor, $tmp_autor_i, $tmp_autor_f - $tmp_autor_i + 1))->html();
            $novedad->autor->avatar = pq('img')->attr('src');
            $this->novedades[] = $novedad;
        }
        return $this->novedades;
    }
}
