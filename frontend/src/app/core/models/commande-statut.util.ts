import { StatutCommande } from './commande.model';

// Single source of truth for commande status → badge class/label, shared by
// commande-list and commande-detail (previously duplicated in both, which
// let BROUILLON/ENVOYEE/RECUE_PARTIELLE drift into the same amber class).
export function commandeStatutClasse(statut: StatutCommande): string {
  return (
    {
      BROUILLON: 'sp-status-badge--neutral',
      ENVOYEE: 'sp-status-badge--critique',
      RECUE_PARTIELLE: 'sp-status-badge--rupture',
      RECUE: 'sp-status-badge--ok',
    } satisfies Record<StatutCommande, string>
  )[statut];
}

export function commandeStatutLabel(statut: StatutCommande): string {
  return (
    {
      BROUILLON: 'brouillon',
      ENVOYEE: 'envoyée',
      RECUE_PARTIELLE: 'reçue partiellement',
      RECUE: 'reçue',
    } satisfies Record<StatutCommande, string>
  )[statut];
}
