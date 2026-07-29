<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudo_versions_model extends Crud_model
{
    protected $table = 'laudo_versions';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id AND deleted=0 ORDER BY version DESC, revision DESC";
        return $this->db->query($sql)->getResult();
    }

    public function get_one($id = 0)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE id=$id AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function get_current($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id AND deleted=0 ORDER BY version DESC LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function get_by_version($laudo_id, $version)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id AND version=$version AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function create_version($laudo_id, $content, $reason = '', $user_id)
    {
        // Buscar última versão
        $last = $this->get_current($laudo_id);
        $new_version = $last ? $last->version + 1 : 1;
        $new_revision = $last ? ($last->version == $new_version ? $last->revision + 1 : 0) : 0;

        // Gerar hash
        $hash = hash('sha256', json_encode($content) . microtime());

        $data = array(
            'laudo_id' => $laudo_id,
            'version' => $new_version,
            'revision' => $new_revision,
            'reason' => $reason,
            'content_json' => json_encode($content),
            'status' => 'draft',
            'document_hash' => $hash,
            'created_by' => $user_id,
            'created_at' => get_my_local_time()
        );

        return parent::ci_save($data, 0);
    }

    public function publish($id)
    {
        $data = array(
            'status' => 'published',
            'published_at' => get_my_local_time()
        );
        return parent::ci_save($data, $id) ? true : false;
    }

    public function compare($laudo_id, $v1, $v2)
    {
        $version1 = $this->get_by_version($laudo_id, $v1);
        $version2 = $this->get_by_version($laudo_id, $v2);

        if (!$version1 || !$version2) {
            return null;
        }

        $c1 = json_decode($version1->content_json, true) ?? array();
        $c2 = json_decode($version2->content_json, true) ?? array();

        $differences = array();
        
        // Comparar chaves
        $all_keys = array_unique(array_merge(array_keys($c1), array_keys($c2)));
        
        foreach ($all_keys as $key) {
            $val1 = $c1[$key] ?? null;
            $val2 = $c2[$key] ?? null;
            
            if ($val1 !== $val2) {
                $differences[] = array(
                    'field' => $key,
                    'old' => $val1,
                    'new' => $val2,
                    'type' => ($val1 === null ? 'added' : ($val2 === null ? 'removed' : 'changed'))
                );
            }
        }

        return $differences;
    }
}

class Laudo_approvals_model extends Crud_model
{
    protected $table = 'laudo_approvals';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');
        
        $sql = "SELECT a.*, u.first_name as approver_name 
            FROM $table a
            LEFT JOIN $users_table u ON u.id = a.approver_id
            WHERE a.laudo_id=$laudo_id AND a.deleted=0
            ORDER BY a.step ASC, a.created_at DESC";
        
        return $this->db->query($sql)->getResult();
    }

    public function get_last_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id AND deleted=0 ORDER BY step DESC, created_at DESC LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function save($data, $id = 0)
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        
        if (!$id) {
            $data['created_at'] = get_my_local_time();
            
            // Gerar hash da versão
            $data['version_hash'] = hash('sha256', microtime() . json_encode($data));
        }
        
        return parent::ci_save($data, $id) ? true : false;
    }

    public function approve($laudo_id, $version, $approver_id, $comment = '')
    {
        $data = array(
            'laudo_id' => $laudo_id,
            'version' => $version,
            'approver_id' => $approver_id,
            'decision' => 'approved',
            'comment' => $comment,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString()
        );

        return $this->save($data, 0);
    }

    public function reject($laudo_id, $version, $approver_id, $comment = '')
    {
        $data = array(
            'laudo_id' => $laudo_id,
            'version' => $version,
            'approver_id' => $approver_id,
            'decision' => 'rejected',
            'comment' => $comment,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString()
        );

        return $this->save($data, 0);
    }
}

class Laudo_signatures_model extends Crud_model
{
    protected $table = 'laudo_signatures';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id, $version = null)
    {
        $table = $this->db->prefixTable($this->table);
        
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id AND deleted=0";
        if ($version) {
            $sql .= " AND version=$version";
        }
        $sql .= " ORDER BY signed_at ASC";
        
        return $this->db->query($sql)->getResult();
    }

    public function save($data, $id = 0)
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        
        if (!$id) {
            $data['created_at'] = get_my_local_time();
            $data['signed_at'] = get_my_local_time();
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        
        return parent::ci_save($data, $id) ? true : false;
    }

    public function add_signature($laudo_id, $version, $signer_name, $signer_document, $signer_role, $signature_data, $user_id = null)
    {
        $data = array(
            'laudo_id' => $laudo_id,
            'version' => $version,
            'signer_name' => $signer_name,
            'signer_document' => $signer_document,
            'signer_role' => $signer_role,
            'signer_type' => 'electronic',
            'signature_data' => $signature_data,
            'user_id' => $user_id,
            'document_hash' => hash('sha256', $laudo_id . $version . $signer_name . microtime())
        );

        return $this->save($data, 0);
    }
}

class Laudo_technical_professionals_model extends Crud_model
{
    protected $table = 'laudo_technical_professionals';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_dropdown()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT id, name FROM $table WHERE status='active' AND deleted=0 ORDER BY name ASC";
        $results = $this->db->query($sql)->getResult();
        
        $dropdown = array();
        foreach ($results as $r) {
            $dropdown[$r->id] = $r->name;
        }
        
        return $dropdown;
    }

    public function get_active()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE status='active' AND deleted=0 ORDER BY name ASC";
        return $this->db->query($sql)->getResult();
    }

    public function is_valid($id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE id=$id AND status='active' AND deleted=0 
            AND (validity_end IS NULL OR validity_end >= CURDATE())";
        return $this->db->query($sql)->getRow() !== null;
    }
}

class Laudo_review_comments_model extends Crud_model
{
    protected $table = 'laudo_review_comments';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');
        
        $sql = "SELECT c.*, u.first_name as author_name 
            FROM $table c
            LEFT JOIN $users_table u ON u.id = c.author_id
            WHERE c.laudo_id=$laudo_id AND c.deleted=0
            ORDER BY c.created_at DESC";
        
        return $this->db->query($sql)->getResult();
    }

    public function get_open_count($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT COUNT(*) as count FROM $table WHERE laudo_id=$laudo_id AND status='open' AND deleted=0";
        return $this->db->query($sql)->getRow()->count;
    }

    public function save($data, $id = 0)
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        
        if (!$id) {
            $data['created_at'] = get_my_local_time();
        }
        
        return parent::ci_save($data, $id) ? true : false;
    }

    public function resolve($id, $user_id, $response = '')
    {
        $data = array(
            'status' => 'resolved',
            'resolved_at' => get_my_local_time(),
            'resolved_by' => $user_id,
            'response' => $response
        );
        
        return parent::ci_save($data, $id) ? true : false;
    }
}

class Laudo_pendencies_model extends Crud_model
{
    protected $table = 'laudo_pendencies';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id AND deleted=0 ORDER BY created_at DESC";
        return $this->db->query($sql)->getResult();
    }

    public function get_open_count($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT COUNT(*) as count FROM $table WHERE laudo_id=$laudo_id AND status='pending' AND deleted=0";
        return $this->db->query($sql)->getRow()->count;
    }

    public function resolve($id)
    {
        $data = array(
            'status' => 'resolved',
            'resolved_at' => get_my_local_time()
        );
        
        return parent::ci_save($data, $id) ? true : false;
    }

    public function save($data, $id = 0)
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        
        if (!$id) {
            $data['created_at'] = get_my_local_time();
        }
        
        return parent::ci_save($data, $id) ? true : false;
    }
}

class Laudo_content_history_model extends Crud_model
{
    protected $table = 'laudo_content_history';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function log_change($laudo_id, $field_name, $old_value, $new_value, $user_id)
    {
        // Não registrar se igual
        if ($old_value === $new_value) return;

        $data = array(
            'laudo_id' => $laudo_id,
            'field_name' => $field_name,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'changed_by' => $user_id,
            'changed_at' => get_my_local_time()
        );

        return parent::ci_save($data, 0);
    }

    public function get_for_laudo($laudo_id, $field_name = null)
    {
        $table = $this->db->prefixTable($this->table);
        
        $sql = "SELECT h.*, u.first_name as user_name 
            FROM $table h
            LEFT JOIN $users_table u ON u.id = h.changed_by
            WHERE h.laudo_id=$laudo_id";
        
        if ($field_name) {
            $sql .= " AND h.field_name='$field_name'";
        }
        
        $sql .= " ORDER BY h.changed_at DESC";
        
        return $this->db->query($sql)->getResult();
    }
}