@include('errors.page', [
    'statusCode' => '429',
    'title' => 'Please slow down',
    'message' => isset($message) ? $message : 'Too many requests. Please wait a moment and try again.',
    'primaryHref' => '/',
    'primaryLabel' => 'Return to home',
    'secondaryHref' => '/report',
    'secondaryLabel' => 'Report an incident',
])