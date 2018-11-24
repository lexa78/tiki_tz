<?php

//Ёто метод из действующего класса

private static function mainCurlWork($headers, $nobody, $sites, $isInner)
{
    if($isInner) {
        $returnedArr = [];
    }
// инициализируем "контейнер" дл€ отдельных соединений (мультикурл)
    $cmh = curl_multi_init();

// массив заданий дл€ мультикурла
    $tasks = array();
// перебираем наши урлы
    foreach ($sites as $index => $site) {
        if($isInner) {
            $url = $site;
        } else {
            $url = $index;
        }
        // инициализируем отдельное соединение (поток)
        $ch = curl_init($url);
        // если будет редирект - перейти по нему
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // максимальное количество редиректов
        curl_setopt($ch, CURLOPT_MAXREDIRS, self::MAX_REDIRECTS_AMOUNT);
        // возвращать результат
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // возвращать http-заголовок или нет
        curl_setopt($ch, CURLOPT_HEADER, $headers);
        // возвращать тело ответа или нет
        curl_setopt($ch, CURLOPT_NOBODY, $nobody);
        // таймаут соединени€
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        // таймаут ожидани€
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // добавл€ем дескриптор потока в массив заданий
        $tasks[$url] = $ch;
        // добавл€ем дескриптор потока в мультикурл
        curl_multi_add_handle($cmh, $ch);
    }
// количество активных потоков
    $active = null;
// запускаем выполнение потоков
    do {
        $mrc = curl_multi_exec($cmh, $active);
    }
    while ($mrc == CURLM_CALL_MULTI_PERFORM);
// выполн€ем, пока есть активные потоки
    while ($active && ($mrc == CURLM_OK)) {
        // если какой-либо поток готов к действи€м
      if (curl_multi_select($cmh) != -1) {
        // ждем, пока что-нибудь изменитс€
        do {
            $mrc = curl_multi_exec($cmh, $active);
            // получаем информацию о потоке
            $info = curl_multi_info_read($cmh);
            // если поток завершилс€
            if ($info['msg'] == CURLMSG_DONE) {
                $ch = $info['handle'];
                // ищем урл страницы по дескриптору потока в массиве заданий
                $url = array_search($ch, $tasks);
                // забираем содержимое
                if($isInner) { //если работаем с внутренними страницами
                    $returnedArr[$url] = curl_multi_getcontent($ch);
                } else {
                    if($nobody) { //если провер€етс€ доступность сайта, берем только информацию
                        $sites[$url]->analyse(curl_getinfo($ch));
                    } else { //получаем содержимое главной страницы и запускаем выборку внутрених ссылок
                        $sites[$url]->getInnerUrls(curl_multi_getcontent($ch));
                    }
                }
                // удал€ем поток из мультикурла
                curl_multi_remove_handle($cmh, $ch);
                // закрываем отдельное соединение (поток)
                curl_close($ch);
            }
        }
        while ($mrc == CURLM_CALL_MULTI_PERFORM);
      }
    }

// закрываем мультикурл
    curl_multi_close($cmh);

    if($isInner) {
        return $returnedArr;
    }
}