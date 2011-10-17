<?php
class NotasScrapper extends UcursosScrapper {
    var $notas;

    function getNotas() {
        if($this->notas != null) return $this->notas;
        parent::process();

        $this->notas = array();
        $identifier = null;
        foreach(pq('table > *:not(thead)') as $bloques) {
            if ($bloques->tagName == 'tr') {
                if(!$identifier) {
                    $identifier = pq('td', $bloques)->html();
                }
            }
            else {
                $identifier = $identifier ? $identifier : '';
                if (!isset($this->notas[$identifier])) $this->notas[$identifier] = array();
                foreach(pq('tr:not(.foot)', $bloques) as $tr) {
                    $nota = new stdClass();
                    $nota->nombre = pq('td:nth-child(1) > a', $tr)->html();
                    $tmp_id = pq('td:nth-child(1) > dl', $tr)->attr('id');
                    $nota->id = substr($tmp_id, strrpos($tmp_id, "_") + 1);
                    $nota->nuevo = pq($tr)->hasClass('nuevo') ? true : false;
                    $nota->fecha = pq('td:nth-child(2)', $tr)->html();

                    $detail_scrapper = new NotasDetalleScrapper($this->user_id);
                    $detail_scrapper->fetch($this->url."_detalle?escala=0&id_evaluacion=".$nota->id);
                    $tmp_cantidad_preguntas = $detail_scrapper->getCantidadPreguntas();

                    $nota->preguntas = array();
                    $i = 0;
                    $tmp_preguntas = pq('td:gt(1):has(span):not(.opciones)', $tr);
                    foreach($tmp_preguntas as $tmp_pregunta) {
                        if($i++ == $tmp_cantidad_preguntas) break;
                        $nota->preguntas[$i] = $this->getPreguntaDetail($tmp_pregunta);
                    }
                    $nota->preguntas['promedio'] = $this->getPreguntaDetail(array_pop($tmp_preguntas->elements));
                    $this->notas[$identifier][] = $nota;
                }
                $identifier = null;
            }
        }
        if(count($this->notas['']) == 0) unset($this->notas['']);
        return $this->notas;
    }

    function getPreguntaDetail($tmp_pregunta) {
        $pregunta = new stdClass();
        $pregunta->nota = pq('span', $tmp_pregunta)->html();
        $pregunta->ponderacion = trim(pq('em', $tmp_pregunta)->html(), '()');
        return $pregunta;
    }
}

class NotasDetalleScrapper extends UcursosScrapper {
    var $cantidad;
    function getCantidadPreguntas() {
        if($this->cantidad != null) return $this->cantidad;
        parent::process();
        return pq('thead th')->size()-2;
    }
}