@include('errors.page', [
    'statusCode' => '500',
    'title' => 'We could not complete that request',
    'message' => 'Something went wrong on our end. Please try again in a moment. If you were submitting a report, check for a confirmation before sending it again.',
    'primaryHref' => '/',
    'primaryLabel' => 'Return to home',
    'secondaryHref' => '/report',
    'secondaryLabel' => 'Open report form',
])