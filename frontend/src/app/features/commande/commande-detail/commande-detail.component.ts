import { DatePipe } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormArray, FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Commande, StatutCommande } from '../../../core/models/commande.model';
import { CommandeService } from '../commande.service';

@Component({
  selector: 'sp-commande-detail',
  standalone: true,
  imports: [DatePipe, RouterLink, ReactiveFormsModule, MatButtonModule, MatInputModule, MatProgressSpinnerModule],
  templateUrl: './commande-detail.component.html',
  styleUrl: './commande-detail.component.scss',
})
export class CommandeDetailComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly commandeService = inject(CommandeService);
  private readonly fb = inject(FormBuilder);

  readonly commande = signal<Commande | null>(null);
  readonly loading = signal(true);
  readonly submitting = signal(false);
  readonly errorMessage = signal<string | null>(null);
  readonly peutReceptionner = computed(() => this.commande()?.statut !== 'RECUE');

  readonly form = this.fb.group({
    lignes: this.fb.array<FormGroup>([]),
  });

  get lignes(): FormArray<FormGroup> {
    return this.form.get('lignes') as FormArray<FormGroup>;
  }

  ngOnInit(): void {
    const idCommande = Number(this.route.snapshot.paramMap.get('id'));
    this.charger(idCommande);
  }

  private charger(idCommande: number): void {
    this.loading.set(true);
    this.commandeService.get(idCommande).subscribe({
      next: (commande) => {
        this.commande.set(commande);
        this.lignes.clear();

        for (const ligne of commande.lignes) {
          const restant = ligne.quantiteCommandee - ligne.quantiteRecue;
          this.lignes.push(
            this.fb.group({
              idLigneCommande: [ligne.idLigneCommande],
              quantiteARecevoir: [0, [Validators.required, Validators.min(0), Validators.max(restant)]],
            }),
          );
        }

        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  statutClasse(statut: StatutCommande): string {
    return (
      {
        BROUILLON: 'sp-status-badge--critique',
        ENVOYEE: 'sp-status-badge--critique',
        RECUE_PARTIELLE: 'sp-status-badge--critique',
        RECUE: 'sp-status-badge--ok',
      } satisfies Record<StatutCommande, string>
    )[statut];
  }

  statutLabel(statut: StatutCommande): string {
    return (
      {
        BROUILLON: 'brouillon',
        ENVOYEE: 'envoyée',
        RECUE_PARTIELLE: 'reçue partiellement',
        RECUE: 'reçue',
      } satisfies Record<StatutCommande, string>
    )[statut];
  }

  enregistrerReception(): void {
    const commande = this.commande();

    if (!commande || this.form.invalid) {
      this.form.markAllAsTouched();

      return;
    }

    const lignes = (this.lignes.value as { idLigneCommande: number; quantiteARecevoir: number }[])
      .filter((l) => Number(l.quantiteARecevoir) > 0)
      .map((l) => ({ idLigneCommande: l.idLigneCommande, quantiteRecue: Number(l.quantiteARecevoir) }));

    if (lignes.length === 0) {
      this.errorMessage.set('Renseignez au moins une quantité reçue.');

      return;
    }

    this.submitting.set(true);
    this.errorMessage.set(null);

    this.commandeService.receptionner(commande.idCommande, { lignes }).subscribe({
      next: () => {
        this.submitting.set(false);
        this.charger(commande.idCommande);
      },
      error: () => {
        this.submitting.set(false);
        this.errorMessage.set('Impossible d’enregistrer cette réception.');
      },
    });
  }
}
