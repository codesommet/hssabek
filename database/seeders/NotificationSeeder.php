<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('Aucun utilisateur trouvé. Exécutez DemoTenantSeeder d\'abord.');
            return;
        }

        $notifications = [
            [
                'title'    => 'Nouvelle facture',
                'message'  => 'La facture #INV-0042 a été créée avec succès.',
                'icon'     => 'document-text',
                'color'    => 'success',
            ],
            [
                'title'    => 'Paiement reçu',
                'message'  => 'Un paiement de 1 500,00 MAD a été enregistré pour la facture #INV-0038.',
                'icon'     => 'money-recive',
                'color'    => 'info',
            ],
            [
                'title'    => 'Devis accepté',
                'message'  => 'Le client Entreprise ABC a accepté le devis #QUO-0015.',
                'icon'     => 'tick-circle',
                'color'    => 'success',
            ],
            [
                'title'    => 'Facture en retard',
                'message'  => 'La facture #INV-0031 est en retard de 15 jours.',
                'icon'     => 'danger',
                'color'    => 'danger',
            ],
            [
                'title'    => 'Stock faible',
                'message'  => 'Le produit "Cartouche Encre HP" a atteint le seuil minimum (5 unités restantes).',
                'icon'     => 'box',
                'color'    => 'warning',
            ],
            [
                'title'    => 'Nouveau client',
                'message'  => 'Le client "Société Maroc Digital" a été ajouté au CRM.',
                'icon'     => 'user-add',
                'color'    => 'primary',
            ],
            [
                'title'    => 'Avoir émis',
                'message'  => 'L\'avoir #CN-0008 de 2 300,00 MAD a été émis pour le client Tech Solutions.',
                'icon'     => 'receipt-text',
                'color'    => 'info',
            ],
            [
                'title'    => 'Dépense enregistrée',
                'message'  => 'Une dépense de 800,00 MAD a été enregistrée dans la catégorie "Fournitures".',
                'icon'     => 'wallet-minus',
                'color'    => 'warning',
            ],
            [
                'title'    => 'Bon de commande',
                'message'  => 'Le bon de commande #PO-0022 a été envoyé au fournisseur.',
                'icon'     => 'document-upload',
                'color'    => 'info',
            ],
            [
                'title'    => 'Transfert de stock',
                'message'  => 'Transfert de 50 unités de "Entrepôt A" vers "Entrepôt B" effectué.',
                'icon'     => 'truck',
                'color'    => 'primary',
            ],
            [
                'title'    => 'Rappel facture',
                'message'  => 'Le rappel pour la facture #INV-0029 a été envoyé au client.',
                'icon'     => 'sms-notification',
                'color'    => 'info',
            ],
            [
                'title'    => 'Abonnement',
                'message'  => 'Votre abonnement Pro expire dans 7 jours. Pensez à le renouveler.',
                'icon'     => 'crown',
                'color'    => 'warning',
            ],
        ];

        foreach ($users as $user) {
            foreach ($notifications as $index => $data) {
                DatabaseNotification::create([
                    'id'              => Str::uuid()->toString(),
                    'type'            => 'App\\Notifications\\GeneralNotification',
                    'notifiable_type' => get_class($user),
                    'notifiable_id'   => $user->id,
                    'data'            => json_encode($data),
                    'read_at'         => $index >= 8 ? now()->subMinutes(rand(1, 60)) : null,
                    'created_at'      => now()->subMinutes(($index + 1) * rand(5, 120)),
                    'updated_at'      => now(),
                ]);
            }
        }

        $this->command->info('12 notifications créées pour chaque utilisateur (' . $users->count() . ' utilisateurs).');
    }
}
