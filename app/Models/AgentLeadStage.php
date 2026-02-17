<?php

namespace App\Models;

class AgentLeadStage extends Model
{
    protected $table = 'agent_lead_stages';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
        $this->ensureDefaults();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS agent_lead_stages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            stage_key VARCHAR(50) NOT NULL,
            label VARCHAR(100) NOT NULL,
            color_class VARCHAR(20) NOT NULL DEFAULT 'secondary',
            sort_order INT NOT NULL DEFAULT 0,
            is_won TINYINT(1) NOT NULL DEFAULT 0,
            is_lost TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_stage (user_id, stage_key),
            INDEX idx_user (user_id),
            INDEX idx_sort (user_id, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            $this->db->exec($sql);
        } catch (\Exception $e) {
        }
    }

    private function ensureDefaults()
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) return;

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM {$this->table} WHERE user_id = ?");
            $stmt->execute([(int)$userId]);
            $c = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
            if ($c > 0) return;

            $defaults = [
                ['stage_key' => 'new', 'label' => 'New', 'color_class' => 'primary', 'sort_order' => 10, 'is_won' => 0, 'is_lost' => 0],
                ['stage_key' => 'contacted', 'label' => 'Contacted', 'color_class' => 'warning', 'sort_order' => 20, 'is_won' => 0, 'is_lost' => 0],
                ['stage_key' => 'qualified', 'label' => 'Qualified', 'color_class' => 'info', 'sort_order' => 30, 'is_won' => 0, 'is_lost' => 0],
                ['stage_key' => 'won', 'label' => 'Won', 'color_class' => 'success', 'sort_order' => 40, 'is_won' => 1, 'is_lost' => 0],
                ['stage_key' => 'lost', 'label' => 'Lost', 'color_class' => 'danger', 'sort_order' => 50, 'is_won' => 0, 'is_lost' => 1],
            ];

            foreach ($defaults as $d) {
                $this->insert([
                    'user_id' => (int)$userId,
                    'stage_key' => $d['stage_key'],
                    'label' => $d['label'],
                    'color_class' => $d['color_class'],
                    'sort_order' => (int)$d['sort_order'],
                    'is_won' => (int)$d['is_won'],
                    'is_lost' => (int)$d['is_lost'],
                ]);
            }
        } catch (\Exception $e) {
        }
    }

    public function getAll($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByKey($userId, $stageKey)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? AND stage_key = ? LIMIT 1");
        $stmt->execute([(int)$userId, (string)$stageKey]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getByIdWithAccess($id, $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([(int)$id, (int)$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
