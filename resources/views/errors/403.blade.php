@include('errors.minimal', [
    'icon'       => 'fa-ban',
    'bg'         => '#FEF2F2',
    'color'      => '#DC2626',
    'title'      => 'غير مصرح لك بالدخول',
    'message'    => $exceptionMessage ?? '' ?: 'معندكش صلاحية للوصول للصفحة دي. لو الأمر ده مش متوقع، تواصل مع مسؤول النظام.',
    'statusCode' => 403,
    'showBack'   => true,
])
