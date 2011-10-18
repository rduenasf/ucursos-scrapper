<?php
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
        $this->user['name'] = pq('#usuario h1 a')->text();
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
            $element->nombre = pq('a > span:first', $item)->text();
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