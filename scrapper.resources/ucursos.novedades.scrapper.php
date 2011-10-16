<?php
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
