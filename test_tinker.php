<?php
$controller = new \App\Http\Controllers\IklanBudgetController();
$request = new \Illuminate\Http\Request();
$request->merge(['years' => [2025, 2024]]);
$response = $controller->getYearlyComparison($request);
echo json_encode($response->getData(), JSON_PRETTY_PRINT);
