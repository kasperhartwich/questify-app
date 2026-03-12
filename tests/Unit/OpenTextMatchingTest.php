<?php

it('matches case-insensitively', function () {
    $expected = 'Paris';
    $input = 'paris';

    expect(strtolower(trim($expected)))->toBe(strtolower(trim($input)));
});

it('matches with uppercase input', function () {
    $expected = 'paris';
    $input = 'PARIS';

    expect(strtolower(trim($expected)))->toBe(strtolower(trim($input)));
});

it('matches with mixed case', function () {
    $expected = 'New York';
    $input = 'new york';

    expect(strtolower(trim($expected)))->toBe(strtolower(trim($input)));
});

it('trims leading whitespace', function () {
    $expected = 'answer';
    $input = '   answer';

    expect(strtolower(trim($expected)))->toBe(strtolower(trim($input)));
});

it('trims trailing whitespace', function () {
    $expected = 'answer';
    $input = 'answer   ';

    expect(strtolower(trim($expected)))->toBe(strtolower(trim($input)));
});

it('trims both leading and trailing whitespace', function () {
    $expected = 'answer';
    $input = '  answer  ';

    expect(strtolower(trim($expected)))->toBe(strtolower(trim($input)));
});

it('handles combined case and whitespace differences', function () {
    $expected = 'Eiffel Tower';
    $input = '  eiffel tower  ';

    expect(strtolower(trim($expected)))->toBe(strtolower(trim($input)));
});

it('does not match different answers', function () {
    $expected = 'Paris';
    $input = 'London';

    expect(strtolower(trim($expected)))->not->toBe(strtolower(trim($input)));
});

it('handles empty strings', function () {
    $input = '   ';

    expect(trim($input))->toBe('');
});

it('handles tab and newline whitespace', function () {
    $expected = 'answer';
    $input = "\t answer \n";

    expect(strtolower(trim($expected)))->toBe(strtolower(trim($input)));
});
