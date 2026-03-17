<?php

namespace App\Models;

use App\Database\Connection;

class NewsletterSubscriber
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function create($email, $name = null)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO newsletter_subscribers (email, name) VALUES (?, ?)");
            return $stmt->execute([$email, $name]);
        } catch (\Exception $e) {
            // Handle duplicate email
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return false;
            }
            throw $e;
        }
    }

    public function getAll($status = 'active')
    {
        $stmt = $this->db->prepare("SELECT * FROM newsletter_subscribers WHERE status = ? ORDER BY subscribed_at DESC");
        $stmt->execute([$status]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM newsletter_subscribers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function update($id, $email, $name = null)
    {
        $stmt = $this->db->prepare("UPDATE newsletter_subscribers SET email = ?, name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$email, $name, $id]);
    }

    public function unsubscribe($id)
    {
        $stmt = $this->db->prepare("UPDATE newsletter_subscribers SET status = 'unsubscribed', unsubscribed_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function resubscribe($id)
    {
        $stmt = $this->db->prepare("UPDATE newsletter_subscribers SET status = 'active', unsubscribed_at = NULL WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getTotalCount($status = 'active')
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM newsletter_subscribers WHERE status = ?");
        $stmt->execute([$status]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getRecentSubscribers($limit = 10, $status = 'active')
    {
        $stmt = $this->db->prepare("SELECT * FROM newsletter_subscribers WHERE status = ? ORDER BY subscribed_at DESC LIMIT ?");
        $stmt->execute([$status, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
