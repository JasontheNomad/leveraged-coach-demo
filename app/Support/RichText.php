<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class RichText
{
    /**
     * Sanitize stored Filament RichEditor HTML for safe {!! !!} output.
     *
     * Allowlist mirrors the RichEditor toolbar (bold/italic/underline/strike,
     * links, headings, lists). TipTap stores bold/italic as <strong>/<em>, so
     * both those and <b>/<i> are allowed. <a> keeps href/target with rel forced
     * to noopener noreferrer. Bare <div> is allowed because TipTap wraps stored
     * content in one — Symfony drops disallowed elements WITH their children, so
     * omitting <div> would blank out wrapped lessons (div attributes are still
     * stripped). Everything else — <script>, <img>, <iframe>, <style>, on*
     * handlers, javascript: links — is stripped.
     */
    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return (new HtmlSanitizer(self::config()))->sanitize($html);
    }

    /**
     * Escape raw user text, then wrap http(s) URLs and emails in anchor tags.
     * Plain-text path (DM bodies) — NOT the HTML sanitizer. Escape runs FIRST,
     * so anchors are the only unescaped output. Newlines preserved (no nl2br;
     * CSS whitespace-pre-line renders them).
     */
    public static function linkify(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }
        $escaped = e($text);
        return preg_replace_callback(
            "#(https?://[^\s<]+[^\s<.,:;!?)'])|([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})#i",
            function ($m) {
                if (!empty($m[1])) {
                    $url = $m[1];
                    return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="underline">' . $url . '</a>';
                }
                $email = $m[2];
                return '<a href="mailto:' . $email . '" class="underline">' . $email . '</a>';
            },
            $escaped
        );
    }

    private static function config(): HtmlSanitizerConfig
    {
        return (new HtmlSanitizerConfig())
            ->allowElement('div')
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('b')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('h1')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('a', ['href', 'target'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowRelativeLinks();
    }
}
