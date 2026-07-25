<?php

namespace LicitaIA\Models;

class Keyword_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_keywords';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_active_include_keywords()
    {
        return $this->get_active_keywords_by_type('include');
    }

    public function get_active_exclude_keywords()
    {
        return $this->get_active_keywords_by_type('exclude');
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT k.id, k.keyword, k.category, k.keyword_type, k.weight, k.active, k.created_by, k.created_at, k.updated_at, k.deleted
                FROM {$table} k
                WHERE k.deleted = 0
                ";

        $keyword_type = trim((string) get_array_value($options, 'keyword_type'));
        if ($keyword_type !== '') {
            $sql .= ' AND k.keyword_type = ' . $this->db->escape($keyword_type);
        }

        $category = trim((string) get_array_value($options, 'category'));
        if ($category !== '') {
            $sql .= ' AND k.category = ' . $this->db->escape($category);
        }

        $active = get_array_value($options, 'active');
        if ($active !== null && $active !== '') {
            $sql .= ' AND k.active = ' . (int) $active;
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (k.keyword LIKE '%{$search}%' ESCAPE '!' OR k.category LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= ' ORDER BY k.keyword ASC';

        return $this->queryOrEmpty($sql);
    }

    public function keyword_matches($text)
    {
        $text = trim((string) $text);
        if ($text === '' || !$this->hasTable()) {
            return array(
                'include' => array(),
                'exclude' => array(),
                'matched' => false,
                'should_exclude' => false,
            );
        }

        $include = array();
        foreach ($this->get_active_include_keywords() as $keyword) {
            if ($this->keywordExistsInText($text, $keyword)) {
                $include[] = $keyword;
            }
        }

        $exclude = array();
        foreach ($this->get_active_exclude_keywords() as $keyword) {
            if ($this->keywordExistsInText($text, $keyword)) {
                $exclude[] = $keyword;
            }
        }

        return array(
            'include' => $include,
            'exclude' => $exclude,
            'matched' => (bool) ($include || $exclude),
            'should_exclude' => (bool) $exclude,
        );
    }

    private function get_active_keywords_by_type($keyword_type)
    {
        $keyword_type = trim((string) $keyword_type);
        if ($keyword_type === '' || !$this->hasTable()) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        $result = $this->db->table($table)
            ->select('keyword')
            ->where('deleted', 0)
            ->where('active', 1)
            ->where('keyword_type', $keyword_type)
            ->orderBy('keyword', 'ASC')
            ->get()
            ->getResult();

        $keywords = array();
        foreach ($result as $row) {
            $keyword = trim((string) ($row->keyword ?? ''));
            if ($keyword !== '') {
                $keywords[] = $keyword;
            }
        }

        return $keywords;
    }

    public function get_categories_dropdown()
    {
        if (!$this->hasTable()) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        $rows = $this->db->query("SELECT DISTINCT category FROM {$table} WHERE deleted = 0 AND category IS NOT NULL AND category <> '' ORDER BY category ASC")->getResult();

        $dropdown = array();
        foreach ($rows as $row) {
            $category = trim((string) ($row->category ?? ''));
            if ($category !== '') {
                $dropdown[$category] = $category;
            }
        }

        return $dropdown;
    }

    public function set_active($id, $active)
    {
        $id = (int) $id;
        if (!$id || !$this->hasTable()) {
            return false;
        }

        $data = array(
            'active' => (int) $active,
            'updated_at' => get_my_local_time(),
        );

        return $this->ci_save($data, $id);
    }

    private function keywordExistsInText($text, $keyword)
    {
        $text = $this->normalizeSearchText($text);
        $keyword = $this->normalizeSearchText($keyword);
        if ($keyword === '') {
            return false;
        }

        return strpos($text, $keyword) !== false;
    }

    private function normalizeSearchText($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        if (function_exists('transliterator_transliterate')) {
            $normalized = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
            if (is_string($normalized) && $normalized !== '') {
                return trim($normalized);
            }
        }

        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($normalized === false || $normalized === '') {
            $normalized = $text;
        }

        return mb_strtolower(trim((string) $normalized));
    }
}
