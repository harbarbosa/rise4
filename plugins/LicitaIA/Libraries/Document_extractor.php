<?php

namespace LicitaIA\Libraries;

class Document_extractor
{
    public function extract_text($file_path, $file_type)
    {
        $file_path = (string) $file_path;
        $file_type = strtolower(trim((string) $file_type));

        if ($file_path === '' || !is_file($file_path)) {
            return array(
                'success' => false,
                'text' => '',
                'message' => 'Arquivo invalido.',
                'needs_ocr' => false,
            );
        }

        switch ($file_type) {
            case 'pdf':
                return $this->extract_pdf($file_path);

            case 'docx':
                return $this->extract_docx($file_path);

            case 'xlsx':
                return $this->extract_xlsx($file_path);

            case 'doc':
                return $this->extract_doc($file_path);

            default:
                return array(
                    'success' => false,
                    'text' => '',
                    'message' => 'Formato sem extracao automatica.',
                    'needs_ocr' => in_array($file_type, array('png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'), true),
                );
        }
    }

    public function extract_pdf($file_path)
    {
        $file_path = (string) $file_path;
        if ($file_path === '' || !is_file($file_path)) {
            return $this->failedResult('Arquivo PDF invalido.');
        }

        $text = $this->tryPdftotext($file_path);
        if (trim($text) === '') {
            $text = $this->extractPdfEmbeddedText($file_path);
        }
        if (trim($text) === '') {
            $text = $this->extractPdfStreamText($file_path);
        }
        if (trim($text) !== '') {
            return array(
                'success' => true,
                'text' => trim($text),
                'message' => 'Texto extraido com sucesso.',
                'needs_ocr' => false,
            );
        }

        return array(
            'success' => false,
            'text' => '',
            'message' => 'PDF sem texto extraivel. OCR sera necessario futuramente.',
            'needs_ocr' => true,
        );
    }

    public function extract_docx($file_path)
    {
        $file_path = (string) $file_path;
        if ($file_path === '' || !is_file($file_path)) {
            return $this->failedResult('Arquivo DOCX invalido.');
        }

        if (!class_exists(\ZipArchive::class)) {
            return $this->failedResult('ZipArchive indisponivel para ler DOCX.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($file_path) !== true) {
            return $this->failedResult('Nao foi possivel abrir o DOCX.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xml) {
            return $this->failedResult('Nao foi possivel localizar o conteudo do DOCX.');
        }

        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim((string) $text));

        return array(
            'success' => trim((string) $text) !== '',
            'text' => trim((string) $text),
            'message' => trim((string) $text) !== '' ? 'Texto extraido com sucesso.' : 'DOCX sem texto extraivel.',
            'needs_ocr' => false,
        );
    }

    public function extract_xlsx($file_path)
    {
        $file_path = (string) $file_path;
        if ($file_path === '' || !is_file($file_path)) {
            return $this->failedResult('Arquivo XLSX invalido.');
        }

        if (!class_exists(\ZipArchive::class)) {
            return $this->failedResult('ZipArchive indisponivel para ler XLSX.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($file_path) !== true) {
            return $this->failedResult('Nao foi possivel abrir o XLSX.');
        }

        $shared_strings = $this->loadSharedStrings($zip);
        $sheet_names = array();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (strpos($name, 'xl/worksheets/sheet') === 0 && substr($name, -4) === '.xml') {
                $sheet_names[] = $name;
            }
        }

        $pieces = array();
        foreach ($sheet_names as $sheet_name) {
            $xml = $zip->getFromName($sheet_name);
            if (!$xml) {
                continue;
            }

            if (preg_match_all('/<c[^>]*r="([A-Z]+[0-9]+)"[^>]*?(?:t="([^"]+)")?[^>]*>(.*?)<\/c>/si', $xml, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $cell_type = $match[2] ?? '';
                    $cell_inner = $match[3] ?? '';
                    $value = '';

                    if ($cell_type === 's' && preg_match('/<v>(.*?)<\/v>/si', $cell_inner, $value_match)) {
                        $shared_index = (int) trim($value_match[1]);
                        $value = $shared_strings[$shared_index] ?? '';
                    } elseif (preg_match('/<v>(.*?)<\/v>/si', $cell_inner, $value_match)) {
                        $value = $value_match[1];
                    } elseif (preg_match('/<is>.*?<t[^>]*>(.*?)<\/t>.*?<\/is>/si', $cell_inner, $inline_match)) {
                        $value = $inline_match[1];
                    }

                    $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $value = trim((string) $value);
                    if ($value !== '') {
                        $pieces[] = $value;
                    }
                }
            }
        }

        $zip->close();

        $text = trim(implode("\n", array_slice($pieces, 0, 1000)));
        return array(
            'success' => $text !== '',
            'text' => $text,
            'message' => $text !== '' ? 'Texto extraido com sucesso.' : 'XLSX sem texto extraivel.',
            'needs_ocr' => false,
        );
    }

    private function extract_doc($file_path)
    {
        $content = @file_get_contents($file_path);
        if ($content === false || $content === '') {
            return $this->failedResult('Nao foi possivel ler o DOC.');
        }

        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]+/', ' ', $content);
        $text = preg_replace('/\s+/', ' ', trim((string) $text));

        if ($text === '') {
            return array(
                'success' => false,
                'text' => '',
                'message' => 'DOC sem texto extraivel. OCR sera necessario futuramente.',
                'needs_ocr' => true,
            );
        }

        return array(
            'success' => true,
            'text' => $text,
            'message' => 'Texto extraido com sucesso.',
            'needs_ocr' => false,
        );
    }

    private function tryPdftotext($file_path)
    {
        $binary = $this->findPdftotextBinary();
        if (!$binary) {
            return '';
        }

        $output_file = tempnam(sys_get_temp_dir(), 'licitaia_pdf_');
        if (!$output_file) {
            return '';
        }

        $cmd = escapeshellarg($binary) . ' -layout -enc UTF-8 -nopgbrk ' . escapeshellarg($file_path) . ' ' . escapeshellarg($output_file);
        @shell_exec($cmd);

        $text = @file_get_contents($output_file);
        @unlink($output_file);

        $text = is_string($text) ? $text : '';
        return $this->normalizeExtractedText($text);
    }

    private function findPdftotextBinary()
    {
        $candidates = array(
            'C:\\Program Files\\Xpdf\\bin64\\pdftotext.exe',
            'C:\\Program Files (x86)\\Xpdf\\bin32\\pdftotext.exe',
            'C:\\poppler\\Library\\bin\\pdftotext.exe',
        );

        foreach ($candidates as $candidate) {
            if (strpos($candidate, DIRECTORY_SEPARATOR) !== false && is_file($candidate)) {
                return $candidate;
            }
        }

        if (trim((string) @shell_exec('where pdftotext 2>NUL')) !== '') {
            return 'pdftotext';
        }

        return '';
    }

    private function loadSharedStrings(\ZipArchive $zip)
    {
        $strings = array();
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if (!$xml) {
            return $strings;
        }

        if (preg_match_all('/<si>(.*?)<\/si>/si', $xml, $matches)) {
            foreach ($matches[1] as $item) {
                $text = '';
                if (preg_match_all('/<t[^>]*>(.*?)<\/t>/si', $item, $text_matches)) {
                    $text = implode('', $text_matches[1]);
                }
                $strings[] = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }

        return $strings;
    }

    private function extractPdfEmbeddedText($file_path)
    {
        $content = @file_get_contents($file_path);
        if ($content === false || $content === '') {
            return '';
        }

        $text_chunks = array();

        if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)\s*Tj/s', $content, $matches)) {
            foreach ($matches[0] as $match) {
                if (preg_match('/\((.*?)\)\s*Tj/s', $match, $text_match)) {
                    $text_chunks[] = $this->cleanupPdfTextChunk($text_match[1]);
                }
            }
        }

        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $content, $matches)) {
            foreach ($matches[1] as $match) {
                if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $match, $segments)) {
                    foreach ($segments[0] as $segment) {
                        $segment = trim($segment, '()');
                        $text_chunks[] = $this->cleanupPdfTextChunk($segment);
                    }
                }
            }
        }

        $text = trim(implode(' ', array_filter($text_chunks)));
        if ($text !== '') {
            return $this->normalizeExtractedText($text);
        }

        return '';
    }

    private function extractPdfStreamText($file_path)
    {
        $content = @file_get_contents($file_path);
        if ($content === false || $content === '') {
            return '';
        }

        $text_chunks = array();
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded_candidates = array($stream);

                if (function_exists('gzuncompress')) {
                    $uncompressed = @gzuncompress($stream);
                    if (is_string($uncompressed) && $uncompressed !== '') {
                        $decoded_candidates[] = $uncompressed;
                    }
                }
                if (function_exists('gzinflate')) {
                    $inflated = @gzinflate($stream);
                    if (is_string($inflated) && $inflated !== '') {
                        $decoded_candidates[] = $inflated;
                    }
                }
                if (function_exists('gzdecode')) {
                    $decoded = @gzdecode($stream);
                    if (is_string($decoded) && $decoded !== '') {
                        $decoded_candidates[] = $decoded;
                    }
                }

                foreach ($decoded_candidates as $candidate) {
                    if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)\s*Tj/s', $candidate, $candidate_matches)) {
                        foreach ($candidate_matches[0] as $match) {
                            if (preg_match('/\((.*?)\)\s*Tj/s', $match, $text_match)) {
                                $text_chunks[] = $this->cleanupPdfTextChunk($text_match[1]);
                            }
                        }
                    }

                    if (preg_match_all('/\[(.*?)\]\s*TJ/s', $candidate, $candidate_matches)) {
                        foreach ($candidate_matches[1] as $match) {
                            if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $match, $segments)) {
                                foreach ($segments[0] as $segment) {
                                    $segment = trim($segment, '()');
                                    $text_chunks[] = $this->cleanupPdfTextChunk($segment);
                                }
                            }
                        }
                    }
                }
            }
        }

        $text = trim(implode(' ', array_filter($text_chunks)));
        if ($text !== '') {
            return $this->normalizeExtractedText($text);
        }

        return '';
    }

    private function cleanupPdfTextChunk($text)
    {
        $text = (string) $text;
        $text = str_replace(array('\\(', '\\)', '\\\\'), array('(', ')', '\\'), $text);
        $text = preg_replace('/\s+/u', ' ', trim($text));
        return $text;
    }

    private function normalizeExtractedText($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        if (stripos($text, '%PDF-') !== false) {
            return '';
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $sample = function_exists('mb_substr') ? mb_substr($text, 0, 1000, 'UTF-8') : substr($text, 0, 1000);
        if ($sample === '') {
            return '';
        }

        $sample_length = function_exists('mb_strlen') ? max(1, mb_strlen($sample, 'UTF-8')) : max(1, strlen($sample));
        $printable_count = preg_match_all('/[^\p{C}]/u', $sample, $matches);
        if ($printable_count === false || (($printable_count / $sample_length) < 0.55)) {
            return '';
        }

        return $text;
    }

    private function failedResult($message)
    {
        return array(
            'success' => false,
            'text' => '',
            'message' => $message,
            'needs_ocr' => false,
        );
    }
}
