<?php

use League\CommonMark\CommonMarkConverter;

function markdown_to_html(string $text): string
{
    static $converter = null;

    if ($converter === null) {
        $converter = new CommonMarkConverter([
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    return $converter->convert($text)->getContent();
}
