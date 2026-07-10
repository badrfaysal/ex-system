<?php
// ⚠️ احذف هذا الملف فوراً بعد الاستخدام
$target = dirname(__DIR__) . '/storage/app/public';
$link   = __DIR__ . '/storage';

if (is_link($link)) {
    echo '✅ الرابط موجود بالفعل: ' . readlink($link);
} elseif (file_exists($link)) {
    echo '❌ المجلد /public/storage موجود كمجلد حقيقي — احذفه يدوياً أولاً.';
} else {
    if (symlink($target, $link)) {
        echo '✅ تم إنشاء الرابط بنجاح! الصور ستظهر الآن.';
    } else {
        echo '❌ فشل إنشاء الرابط — جرب الطريقة اليدوية من cPanel.<br>';
        echo 'Target: ' . $target . '<br>';
        echo 'Link:   ' . $link;
    }
}
