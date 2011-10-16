<?php
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