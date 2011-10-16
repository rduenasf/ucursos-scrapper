<?php
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