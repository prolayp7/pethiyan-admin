<?php

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;

function prepareCategoryRequest(string $requestClass, array $data): \Illuminate\Http\Request
{
    $request = $requestClass::create('/categories', 'POST', $data);
    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    return $request;
}

dataset('category_faq_request_classes', [
    'StoreCategoryRequest' => [StoreCategoryRequest::class],
    'UpdateCategoryRequest' => [UpdateCategoryRequest::class],
]);

it('normalizes valid faq rows into metadata.faqs, trimming whitespace', function (string $requestClass) {
    $request = prepareCategoryRequest($requestClass, [
        'title' => 'Jars',
        'faqs' => json_encode([
            ['question' => '  Are jars food-grade?  ', 'answer' => '  Yes, BPA-free.  '],
            ['question' => 'What sizes?', 'answer' => '250g, 500g, 1kg'],
        ]),
    ]);

    expect($request->input('metadata')['faqs'])->toBe([
        ['question' => 'Are jars food-grade?', 'answer' => 'Yes, BPA-free.'],
        ['question' => 'What sizes?', 'answer' => '250g, 500g, 1kg'],
    ]);
})->with('category_faq_request_classes');

it('drops rows with an empty question or answer', function (string $requestClass) {
    $request = prepareCategoryRequest($requestClass, [
        'title' => 'Jars',
        'faqs' => json_encode([
            ['question' => '', 'answer' => 'Yes'],
            ['question' => 'What sizes?', 'answer' => '   '],
            ['question' => 'Valid?', 'answer' => 'Yes'],
        ]),
    ]);

    expect($request->input('metadata')['faqs'])->toBe([
        ['question' => 'Valid?', 'answer' => 'Yes'],
    ]);
})->with('category_faq_request_classes');

it('returns an empty array when faqs input is missing, blank, or invalid json', function (string $requestClass) {
    expect(prepareCategoryRequest($requestClass, ['title' => 'Jars'])->input('metadata')['faqs'])->toBe([]);
    expect(prepareCategoryRequest($requestClass, ['title' => 'Jars', 'faqs' => ''])->input('metadata')['faqs'])->toBe([]);
    expect(prepareCategoryRequest($requestClass, ['title' => 'Jars', 'faqs' => 'not-json'])->input('metadata')['faqs'])->toBe([]);
})->with('category_faq_request_classes');

it('normalizes faq_schema_json_ld by trimming surrounding whitespace', function (string $requestClass) {
    $request = prepareCategoryRequest($requestClass, [
        'title' => 'Jars',
        'faq_schema_json_ld' => '  {"@context":"https://schema.org","@type":"FAQPage","mainEntity":[]}  ',
    ]);

    expect($request->input('metadata')['faq_schema_json_ld'])
        ->toBe('{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[]}');
})->with('category_faq_request_classes');

it('returns null for faq_schema_json_ld when input is missing or blank', function (string $requestClass) {
    expect(prepareCategoryRequest($requestClass, ['title' => 'Jars'])->input('metadata')['faq_schema_json_ld'])->toBeNull();
    expect(prepareCategoryRequest($requestClass, ['title' => 'Jars', 'faq_schema_json_ld' => '   '])->input('metadata')['faq_schema_json_ld'])->toBeNull();
})->with('category_faq_request_classes');
