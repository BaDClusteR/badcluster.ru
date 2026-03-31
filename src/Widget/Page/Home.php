<?php

namespace BC\Widget\Page;

use BC\Widget\AWidget;
use BC\Widget\Page\Home\Pulse;

class Home extends APage
{

    public function getHeader(): string
    {
        return 'Привет!';
    }

    public function getTitle(): string
    {
        return 'BaD ClusteR';
    }

    public function getDescription(): array
    {
        return [
            "Добро пожаловать в цифровой уголок BaD ClusteR'a.",
            "Здесь я выкладываю то, чем мне хотелось бы поделиться с миром. На сайте царит творческий хаос: переводы книг соседствуют с сейвами для игр, а самописные скрипты — с экспериментальной музыкой. Но главное — в каждый байт информации здесь вложена частичка души.",
            "Чувствуйте себя как дома, берите что нужно, оставайтесь сколько захотите :)",
            "Если захочется поделиться идеей или просто поболтать — пишите! Меня можно найти в <a href=\"https://t.me/bad_cluster\">Telegram</a> или по старинке написать на <a href=\"mailto:admin@badcluster.ru\">Email</a>. А если интересно, в какую сингловую бродилку я сейчас залипаю по ночам — заглядывайте в <a href=\"https://steamcommunity.com/id/bad_cluster\">профиль Steam</a>. Добавляйтесь в друзья, померяемся ачивками :)"
        ];
    }

    public function getMainWidget(): AWidget
    {
        return new Pulse();
    }
}
