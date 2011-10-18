<?php
class CursosScrapper extends UcursosScrapper {
    var $cursos;

    function getNotas() {
        if($this->cursos != null) return $this->cursos;
        parent::process();

        $this->cursos = array();
        $identifier = null;
        foreach(pq('table > *:not(thead)') as $bloques) {
            if ($bloques->tagName == 'tr') {
                if(!$identifier) {
                    $identifier = pq('td', $bloques)->text();
                }
            }
            else {
                $identifier = $identifier ? $identifier : '';
                if (!isset($this->notas[$identifier])) $this->notas[$identifier] = array();
                foreach(pq('tr', $bloques) as $tr) {
                    $curso = new stdClass();
                    $curso->id = pq('td:nth-child(3)', $tr)->html();
                    $curso->nombre = mb_convert_encoding(pq('td:nth-child(4) > a', $tr)->html(), 'UTF-8');
                    $curso->url = pq('td:nth-child(4) > a', $tr)->attr('href');
                    $curso->cargo = UcursosScrapper::toUserType(pq('td:nth-child(1) > img', $tr)->attr('title'));
                    $curso->institucion = new stdClass();
                    $curso->institucion->nombre = pq('td:nth-child(2) > img', $tr)->attr('title');
                    $curso->institucion->icono = pq('td:nth-child(2) > img', $tr)->attr('src');
                    $this->cursos[$identifier][] = $curso;
                }
                $identifier = null;
            }
        }
        return $this->cursos;
    }
}