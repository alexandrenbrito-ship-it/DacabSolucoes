<?php
/**
 * Gerenciador de Assinaturas e Limites do Plano
 */
class SubscriptionManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Verificar se usuário pode fazer upload de imagem
     */
    public function canUploadImage($userId) {
        $subscription = $this->getActiveSubscription($userId);
        
        if (!$subscription) {
            return ['allowed' => false, 'reason' => 'Nenhuma assinatura ativa'];
        }

        if ($subscription['images_used'] >= $subscription['images_limit']) {
            return ['allowed' => false, 'reason' => 'Limite de imagens atingido'];
        }

        return ['allowed' => true];
    }

    /**
     * Verificar se usuário pode criar encarte
     */
    public function canCreateFlyer($userId) {
        $subscription = $this->getActiveSubscription($userId);
        
        if (!$subscription) {
            return ['allowed' => false, 'reason' => 'Nenhuma assinatura ativa'];
        }

        if ($subscription['flyers_used'] >= $subscription['flyers_limit']) {
            return ['allowed' => false, 'reason' => 'Limite de encartes atingido'];
        }

        return ['allowed' => true];
    }

    /**
     * Incrementar contador de imagens usadas
     */
    public function incrementImagesUsed($userId) {
        try {
            $subscription = $this->getActiveSubscription($userId);
            
            if (!$subscription) {
                throw new Exception('Nenhuma assinatura ativa');
            }

            if ($subscription['images_used'] >= $subscription['images_limit']) {
                throw new Exception('Limite de imagens atingido');
            }

            $stmt = $this->db->prepare("
                UPDATE user_subscriptions 
                SET images_used = images_used + 1 
                WHERE id = ?
            ");
            $stmt->execute([$subscription['id']]);

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Incrementar contador de encartes usados
     */
    public function incrementFlyersUsed($userId) {
        try {
            $subscription = $this->getActiveSubscription($userId);
            
            if (!$subscription) {
                throw new Exception('Nenhuma assinatura ativa');
            }

            if ($subscription['flyers_used'] >= $subscription['flyers_limit']) {
                throw new Exception('Limite de encartes atingido');
            }

            $stmt = $this->db->prepare("
                UPDATE user_subscriptions 
                SET flyers_used = flyers_used + 1 
                WHERE id = ?
            ");
            $stmt->execute([$subscription['id']]);

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obter assinatura ativa do usuário
     */
    public function getActiveSubscription($userId) {
        $stmt = $this->db->prepare("
            SELECT us.*, p.name as plan_name, p.price, p.images_limit, p.flyers_limit, p.duration_days
            FROM user_subscriptions us
            JOIN plans p ON us.plan_id = p.id
            WHERE us.user_id = ? AND us.status = 'active' AND us.end_date > NOW()
            ORDER BY us.end_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Obter uso atual do usuário (resumo)
     */
    public function getUsageSummary($userId) {
        $subscription = $this->getActiveSubscription($userId);
        
        if (!$subscription) {
            return [
                'has_subscription' => false,
                'plan_name' => 'Nenhum',
                'images_used' => 0,
                'images_limit' => 0,
                'flyers_used' => 0,
                'flyers_limit' => 0,
                'end_date' => null
            ];
        }

        return [
            'has_subscription' => true,
            'plan_name' => $subscription['plan_name'],
            'images_used' => (int)$subscription['images_used'],
            'images_limit' => (int)$subscription['images_limit'],
            'flyers_used' => (int)$subscription['flyers_used'],
            'flyers_limit' => (int)$subscription['flyers_limit'],
            'end_date' => $subscription['end_date']
        ];
    }

    /**
     * Criar/Atualizar assinatura para um usuário
     */
    public function createOrUpdateSubscription($userId, $planId, $durationDays = 30) {
        try {
            // Verificar se existe assinatura ativa
            $current = $this->getActiveSubscription($userId);

            if ($current) {
                // Atualizar assinatura existente
                $stmt = $this->db->prepare("
                    UPDATE user_subscriptions 
                    SET plan_id = ?, end_date = DATE_ADD(NOW(), INTERVAL ? DAY)
                    WHERE id = ?
                ");
                $stmt->execute([$planId, $durationDays, $current['id']]);
                $subscriptionId = $current['id'];
            } else {
                // Criar nova assinatura
                $stmt = $this->db->prepare("
                    INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, images_used, flyers_used, status)
                    VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), 0, 0, 'active')
                ");
                $stmt->execute([$userId, $planId, $durationDays]);
                $subscriptionId = $this->db->lastInsertId();
            }

            return ['success' => true, 'subscription_id' => $subscriptionId];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Listar todos os planos disponíveis
     */
    public function getAvailablePlans() {
        $stmt = $this->db->query("SELECT * FROM plans WHERE status = 'active' ORDER BY price ASC");
        return $stmt->fetchAll();
    }

    /**
     * Obter plano por ID
     */
    public function getPlanById($planId) {
        $stmt = $this->db->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->execute([$planId]);
        return $stmt->fetch();
    }

    /**
     * Renovar assinatura (estender período)
     */
    public function renewSubscription($userId, $additionalDays = 30) {
        try {
            $subscription = $this->getActiveSubscription($userId);
            
            if (!$subscription) {
                throw new Exception('Nenhuma assinatura ativa para renovar');
            }

            $stmt = $this->db->prepare("
                UPDATE user_subscriptions 
                SET end_date = DATE_ADD(end_date, INTERVAL ? DAY)
                WHERE id = ?
            ");
            $stmt->execute([$additionalDays, $subscription['id']]);

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cancelar assinatura
     */
    public function cancelSubscription($userId) {
        try {
            $subscription = $this->getActiveSubscription($userId);
            
            if (!$subscription) {
                throw new Exception('Nenhuma assinatura ativa');
            }

            $stmt = $this->db->prepare("
                UPDATE user_subscriptions 
                SET status = 'cancelled'
                WHERE id = ?
            ");
            $stmt->execute([$subscription['id']]);

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
