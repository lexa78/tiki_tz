<?php

//��� ����� �� ������������ ������

private static function mainCurlWork($headers, $nobody, $sites, $isInner)
{
    if($isInner) {
        $returnedArr = [];
    }
// �������������� "���������" ��� ��������� ���������� (����������)
    $cmh = curl_multi_init();

// ������ ������� ��� �����������
    $tasks = array();
// ���������� ���� ����
    foreach ($sites as $index => $site) {
        if($isInner) {
            $url = $site;
        } else {
            $url = $index;
        }
        // �������������� ��������� ���������� (�����)
        $ch = curl_init($url);
        // ���� ����� �������� - ������� �� ����
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // ������������ ���������� ����������
        curl_setopt($ch, CURLOPT_MAXREDIRS, self::MAX_REDIRECTS_AMOUNT);
        // ���������� ���������
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // ���������� http-��������� ��� ���
        curl_setopt($ch, CURLOPT_HEADER, $headers);
        // ���������� ���� ������ ��� ���
        curl_setopt($ch, CURLOPT_NOBODY, $nobody);
        // ������� ����������
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        // ������� ��������
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // ��������� ���������� ������ � ������ �������
        $tasks[$url] = $ch;
        // ��������� ���������� ������ � ����������
        curl_multi_add_handle($cmh, $ch);
    }
// ���������� �������� �������
    $active = null;
// ��������� ���������� �������
    do {
        $mrc = curl_multi_exec($cmh, $active);
    }
    while ($mrc == CURLM_CALL_MULTI_PERFORM);
// ���������, ���� ���� �������� ������
    while ($active && ($mrc == CURLM_OK)) {
        // ���� �����-���� ����� ����� � ���������
      if (curl_multi_select($cmh) != -1) {
        // ����, ���� ���-������ ���������
        do {
            $mrc = curl_multi_exec($cmh, $active);
            // �������� ���������� � ������
            $info = curl_multi_info_read($cmh);
            // ���� ����� ����������
            if ($info['msg'] == CURLMSG_DONE) {
                $ch = $info['handle'];
                // ���� ��� �������� �� ����������� ������ � ������� �������
                $url = array_search($ch, $tasks);
                // �������� ����������
                if($isInner) { //���� �������� � ����������� ����������
                    $returnedArr[$url] = curl_multi_getcontent($ch);
                } else {
                    if($nobody) { //���� ����������� ����������� �����, ����� ������ ����������
                        $sites[$url]->analyse(curl_getinfo($ch));
                    } else { //�������� ���������� ������� �������� � ��������� ������� ��������� ������
                        $sites[$url]->getInnerUrls(curl_multi_getcontent($ch));
                    }
                }
                // ������� ����� �� �����������
                curl_multi_remove_handle($cmh, $ch);
                // ��������� ��������� ���������� (�����)
                curl_close($ch);
            }
        }
        while ($mrc == CURLM_CALL_MULTI_PERFORM);
      }
    }

// ��������� ����������
    curl_multi_close($cmh);

    if($isInner) {
        return $returnedArr;
    }
}