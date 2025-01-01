<?php

function loadNotificationLog($filePath = 'notification_log.txt')
{
    if (file_exists($filePath)) {
        return file_get_contents($filePath);
    }
    return '';
}

function saveNotificationLog($data, $filePath = 'notification_log.txt')
{
    file_put_contents($filePath, $data);
}


?>