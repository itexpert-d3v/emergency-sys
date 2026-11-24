@include('errors.page', [
    'statusCode' => '404',
    'title' => 'Page not found',
    'message' => 'This address does not lead to a page in the Emergency Response Portal. Check the link or return to the home page.',
    'primaryHref' => '/',
    'primaryLabel' => 'Return to home',
    'secondaryHref' => '/report',
    'secondaryLabel' => 'Report an incident',
])